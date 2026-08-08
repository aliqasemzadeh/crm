<?php

namespace App\Services\Sepidar;

use Illuminate\Support\Facades\DB;

class ItemStockSummaryUpdater
{
    /**
     * Recalculate INV.ItemStockSummary for the given keys without calling
     * INV.spUpdateItemStockSummary (avoids EXECUTE on INV.SummaryRecordTable TVP).
     *
     * Logic mirrors INV.spUpdateItemStockSummary with:
     * @UpdateSaleWithReserve = 1, @IncludeRegisteredOrders = 0.
     *
     * @param  list<array{stock_ref: int, item_ref: int, tracing_ref?: int|null, fiscal_year_ref?: int|null}>  $keys
     */
    public function refresh(array $keys): void
    {
        $unique = [];
        $fiscalYearDefault = (int) config('sepidar.FiscalYearRef');

        foreach ($keys as $key) {
            $stockRef = (int) ($key['stock_ref'] ?? 0);
            $itemRef = (int) ($key['item_ref'] ?? 0);
            $fiscalYearRef = (int) ($key['fiscal_year_ref'] ?? $fiscalYearDefault);
            $tracingRef = array_key_exists('tracing_ref', $key) && $key['tracing_ref'] !== null
                ? (int) $key['tracing_ref']
                : null;

            if ($stockRef <= 0 || $itemRef <= 0 || $fiscalYearRef <= 0) {
                continue;
            }

            $unique["{$stockRef}:{$itemRef}:".($tracingRef ?? 'null').":{$fiscalYearRef}"] = [
                'stock_ref' => $stockRef,
                'item_ref' => $itemRef,
                'tracing_ref' => $tracingRef,
                'fiscal_year_ref' => $fiscalYearRef,
            ];
        }

        if ($unique === []) {
            return;
        }

        $valueSql = [];
        $bindings = [];

        foreach ($unique as $row) {
            $valueSql[] = '(?, ?, ?, ?, 0)';
            $bindings[] = $row['stock_ref'];
            $bindings[] = $row['item_ref'];
            $bindings[] = $row['tracing_ref'];
            $bindings[] = $row['fiscal_year_ref'];
        }

        $values = implode(",\n", $valueSql);

        $sql = <<<SQL
SET NOCOUNT ON;

CREATE TABLE #tmpSummaryTable
(
    [StockID] [int],
    [ItemID] [int],
    [TracingID] [int] NULL,
    [FiscalYearID] [int],
    [FeedFromClosingOperation] BIT NOT NULL DEFAULT 0
);

INSERT INTO #tmpSummaryTable ([StockID], [ItemID], [TracingID], [FiscalYearID], [FeedFromClosingOperation])
VALUES {$values};

;WITH Inputs AS
(
    SELECT
        SUM(InputQuantity) InputQuantity,
        SUM(SecondaryInputQuantity) SecondaryInputQuantity,
        StockRef, ItemRef, ISNULL(TracingRef, 0) TracingRef, FiscalYearRef
    FROM
    (
        SELECT
            Quantity AS InputQuantity,
            SecondaryQuantity AS SecondaryInputQuantity,
            StockRef, ItemRef, TracingRef, I.FiscalYearRef
        FROM INV.InventoryReceiptItem AS RI
        INNER JOIN INV.InventoryReceipt AS I ON RI.InventoryReceiptRef = I.InventoryReceiptID
        WHERE RI.IsReturn = 0
        UNION ALL
        SELECT
            Quantity,
            SecondaryQuantity,
            StockRef, ItemRef, TracingRef, FiscalYearRef
        FROM INV.InventoryDeliveryItem AS II
        INNER JOIN INV.InventoryDelivery AS I ON II.InventoryDeliveryRef = I.InventoryDeliveryID
        WHERE II.IsReturn = 1
    ) TEMP
    GROUP BY StockRef, ItemRef, ISNULL(TracingRef, 0), FiscalYearRef
),
Outputs AS
(
    SELECT
        SUM(OutputQuantity) OutputQuantity,
        SUM(SecondaryOutputQuantity) SecondaryOutputQuantity,
        StockRef, ItemRef, ISNULL(TracingRef, 0) TracingRef, FiscalYearRef
    FROM
    (
        SELECT
            Quantity OutputQuantity,
            SecondaryQuantity SecondaryOutputQuantity,
            StockRef, ItemRef, TracingRef, I.FiscalYearRef
        FROM INV.InventoryReceiptItem AS RI
        INNER JOIN INV.InventoryReceipt AS I ON RI.InventoryReceiptRef = I.InventoryReceiptID
        WHERE RI.IsReturn = 1
        UNION ALL
        SELECT
            Quantity,
            SecondaryQuantity,
            StockRef, ItemRef, TracingRef, FiscalYearRef
        FROM INV.InventoryDeliveryItem AS II
        INNER JOIN INV.InventoryDelivery AS I ON II.InventoryDeliveryRef = I.InventoryDeliveryID
        WHERE II.IsReturn = 0
    ) TEMP
    GROUP BY StockRef, ItemRef, ISNULL(TracingRef, 0), FiscalYearRef
),
Sales AS
(
    SELECT
        SUM(ii.Quantity) - SUM(ISNULL(DeliveredQuantity, 0)) SaleQuantity,
        SUM(ISNULL(ii.SecondaryQuantity, 0)) - SUM(ISNULL(DeliveredSecondaryQuantity, 0)) SaleSecondaryQuantity,
        ii.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
    FROM SLS.InvoiceItem ii
    JOIN SLS.Invoice iv ON iv.InvoiceID = ii.InvoiceRef
    JOIN INV.Item i ON ii.ItemRef = i.ItemID
    LEFT JOIN
    (
        SELECT BaseInvoiceItem, SUM(Quantity) DeliveredQuantity, SUM(SecondaryQuantity) DeliveredSecondaryQuantity
        FROM INV.InventoryDelivery
        JOIN INV.InventoryDeliveryItem ON InventoryDeliveryID = InventoryDeliveryRef
        WHERE [Type] = 1
        GROUP BY BaseInvoiceItem
    ) idi ON ii.InvoiceItemID = idi.BaseInvoiceItem
    WHERE iv.State <> 2
      AND NOT EXISTS (
          SELECT 1
          FROM [DST].[HotDistributionSaleDocument] AS [HDSD]
          WHERE [HDSD].[InvoiceRef] = iv.[InvoiceId]
      )
      AND ii.StockRef IS NOT NULL
    GROUP BY ii.StockRef, ii.ItemRef, ii.TracingRef, i.UnitRef, i.SecondaryUnitRef, iv.FiscalYearRef
),
ReturnedSale AS
(
    SELECT
        SUM(ii.Quantity) - SUM(ISNULL(DeliveredQuantity, 0)) ReturnedSaleQuantity,
        SUM(ISNULL(ii.SecondaryQuantity, 0)) - SUM(ISNULL(DeliveredSecondaryQuantity, 0)) ReturnedSaleSecondaryQuantity,
        ii.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
    FROM SLS.ReturnedInvoiceItem ii
    JOIN SLS.ReturnedInvoice iv ON iv.ReturnedInvoiceID = ii.ReturnedInvoiceRef
    JOIN INV.Item i ON ii.ItemRef = i.ItemID
    LEFT JOIN
    (
        SELECT BaseReturnedInvoiceItem, SUM(Quantity) DeliveredQuantity, SUM(SecondaryQuantity) DeliveredSecondaryQuantity
        FROM INV.InventoryDelivery
        JOIN INV.InventoryDeliveryItem ON InventoryDeliveryID = InventoryDeliveryRef
        WHERE [Type] = 1
        GROUP BY BaseReturnedInvoiceItem
    ) idi ON ii.ReturnedInvoiceItemID = idi.BaseReturnedInvoiceItem
    WHERE ii.StockRef IS NOT NULL
    GROUP BY ii.StockRef, ii.ItemRef, ii.TracingRef, i.UnitRef, i.SecondaryUnitRef, iv.FiscalYearRef
),
PosSale AS
(
    SELECT
        SUM(ii.Quantity) PosSaleQuantity,
        SUM(ii.SecondaryQuantity) PosSaleSecondaryQuantity,
        iv.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
    FROM POS.InvoiceItem ii
    JOIN POS.Invoice iv ON iv.InvoiceID = ii.InvoiceRef
    WHERE iv.State = 1
      AND iv.StockRef IS NOT NULL
    GROUP BY iv.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
),
PosReturnedSale AS
(
    SELECT
        SUM(ii.Quantity) PosReturnedSaleQuantity,
        SUM(ii.SecondaryQuantity) PosReturnedSaleSecondaryQuantity,
        iv.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
    FROM POS.ReturnedInvoiceItem ii
    JOIN POS.ReturnedInvoice iv ON iv.ReturnedInvoiceID = ii.ReturnedInvoiceRef
    WHERE iv.State = 1
      AND iv.StockRef IS NOT NULL
    GROUP BY iv.StockRef, ii.ItemRef, ii.TracingRef, iv.FiscalYearRef
),
SaleWithReserves AS
(
    SELECT
        SUM(
            CASE
                WHEN QI.Quantity - ISNULL(II.InvoiceUsedQuantity, 0) - ISNULL(IDI.InventoryUsedQuantity, 0) < 0 THEN 0
                ELSE QI.Quantity - ISNULL(II.InvoiceUsedQuantity, 0) - ISNULL(IDI.InventoryUsedQuantity, 0)
            END
        ) AS SaleWithReserveQuantity,
        SUM(
            CASE
                WHEN QI.SecondaryQuantity - ISNULL(II.InvoiceUsedSecondaryQuantity, 0) - ISNULL(InventoryUsedSecondaryQuantity, 0) < 0 THEN 0
                ELSE QI.SecondaryQuantity - ISNULL(II.InvoiceUsedSecondaryQuantity, 0) - ISNULL(InventoryUsedSecondaryQuantity, 0)
            END
        ) AS SaleWithReserveSecondaryQuantity,
        QI.StockRef, QI.ItemRef, QI.TracingRef, Q.FiscalYearRef
    FROM SLS.QuotationItem QI
    INNER JOIN SLS.Quotation Q ON QI.QuotationRef = Q.QuotationID
    INNER JOIN INV.Item I ON QI.ItemRef = I.ItemID
    LEFT JOIN
    (
        SELECT QuotationItemRef, SUM(Quantity) InvoiceUsedQuantity, SUM(SecondaryQuantity) InvoiceUsedSecondaryQuantity
        FROM SLS.InvoiceItem
        WHERE QuotationItemRef IS NOT NULL
        GROUP BY QuotationItemRef
    ) II ON II.QuotationItemRef = QI.QuotationItemID
    LEFT JOIN
    (
        SELECT QuotationItemRef, SUM(Quantity) InventoryUsedQuantity, SUM(SecondaryQuantity) InventoryUsedSecondaryQuantity
        FROM INV.InventoryDeliveryItem
        WHERE BaseInvoiceItem IS NULL AND QuotationItemRef IS NOT NULL
        GROUP BY QuotationItemRef
    ) IDI ON IDI.QuotationItemRef = QI.QuotationItemID
    WHERE Q.Closed = 0
    GROUP BY QI.StockRef, QI.ItemRef, QI.TracingRef, I.UnitRef, I.SecondaryUnitRef, Q.FiscalYearRef
),
OrderWithReserves AS
(
    SELECT
        SUM(
            CASE
                WHEN OI.Quantity - ISNULL(II.InvoiceUsedQuantity, 0) < 0 THEN 0
                ELSE OI.Quantity - ISNULL(II.InvoiceUsedQuantity, 0)
            END
        ) AS OrderWithReserveQuantity,
        SUM(
            CASE
                WHEN OI.SecondaryQuantity - ISNULL(II.InvoiceUsedSecondaryQuantity, 0) < 0 THEN 0
                ELSE OI.SecondaryQuantity - ISNULL(II.InvoiceUsedSecondaryQuantity, 0)
            END
        ) AS OrderWithReserveSecondaryQuantity,
        OI.StockRef, OI.ItemRef, OI.TracingRef, O.FiscalYearRef
    FROM DST.OrderItem OI
    INNER JOIN DST.[Order] O ON OI.OrderRef = O.OrderID
    INNER JOIN INV.Item I ON OI.ItemRef = I.ItemID
    LEFT JOIN
    (
        SELECT OrderItemRef, SUM(Quantity) InvoiceUsedQuantity, SUM(SecondaryQuantity) InvoiceUsedSecondaryQuantity
        FROM SLS.InvoiceItem
        WHERE OrderItemRef IS NOT NULL
        GROUP BY OrderItemRef
    ) II ON II.OrderItemRef = OI.OrderItemID
    WHERE O.State = 2
    GROUP BY OI.StockRef, OI.ItemRef, OI.TracingRef, I.UnitRef, I.SecondaryUnitRef, O.FiscalYearRef
),
SummaryTotals AS
(
    SELECT
        ISNULL(SUM(InputQuantity), 0) InputQuantity,
        ISNULL(SUM(SecondaryInputQuantity), 0) SecondaryInputQuantity,
        ISNULL(SUM(OutputQuantity), 0) OutputQuantity,
        ISNULL(SUM(SecondaryOutputQuantity), 0) SecondaryOutputQuantity,
        ISNULL(SUM(ISNULL(SaleQuantity, 0) - ISNULL(ReturnedSaleQuantity, 0) + CAST(ISNULL(PosSaleQuantity, 0) - ISNULL(PosReturnedSaleQuantity, 0) AS DECIMAL(19, 4))), 0) SaleQuantity,
        ISNULL(SUM(ISNULL(SaleSecondaryQuantity, 0) - ISNULL(ReturnedSaleSecondaryQuantity, 0) + CAST(ISNULL(PosSaleSecondaryQuantity, 0) - ISNULL(PosReturnedSaleSecondaryQuantity, 0) AS DECIMAL(19, 4))), 0) SaleSecondaryQuantity,
        ISNULL(SUM(SaleWithReserveQuantity), 0) SaleWithReserveQuantity,
        ISNULL(SUM(SaleWithReserveSecondaryQuantity), 0) SaleWithReserveSecondaryQuantity,
        ISNULL(SUM(OrderWithReserveQuantity), 0) OrderWithReserveQuantity,
        ISNULL(SUM(OrderWithReserveSecondaryQuantity), 0) OrderWithReserveSecondaryQuantity,
        T.StockID, T.ItemID, T.FiscalYearID, ISNULL(T.TracingID, 0) TracingID,
        T.FeedFromClosingOperation
    FROM #tmpSummaryTable T
    LEFT JOIN Inputs ON Inputs.StockRef = T.StockID AND Inputs.ItemRef = T.ItemID AND Inputs.FiscalYearRef = T.FiscalYearID AND Inputs.TracingRef = ISNULL(T.TracingID, 0)
    LEFT JOIN Outputs ON Outputs.StockRef = T.StockID AND Outputs.ItemRef = T.ItemID AND Outputs.FiscalYearRef = T.FiscalYearID AND Outputs.TracingRef = ISNULL(T.TracingID, 0)
    LEFT JOIN Sales S ON T.StockID = S.StockRef AND T.ItemID = S.ItemRef AND T.FiscalYearID = S.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(S.TracingRef, 0)
    LEFT JOIN ReturnedSale R ON T.StockID = R.StockRef AND T.ItemID = R.ItemRef AND T.FiscalYearID = R.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(R.TracingRef, 0)
    LEFT JOIN PosSale PS ON T.StockID = PS.StockRef AND T.ItemID = PS.ItemRef AND T.FiscalYearID = PS.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(PS.TracingRef, 0)
    LEFT JOIN PosReturnedSale PR ON T.StockID = PR.StockRef AND T.ItemID = PR.ItemRef AND T.FiscalYearID = PR.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(PR.TracingRef, 0)
    LEFT JOIN SaleWithReserves Q ON T.StockID = Q.StockRef AND T.ItemID = Q.ItemRef AND T.FiscalYearID = Q.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(Q.TracingRef, 0)
    LEFT JOIN OrderWithReserves O ON T.StockID = O.StockRef AND T.ItemID = O.ItemRef AND T.FiscalYearID = O.FiscalYearRef AND ISNULL(T.TracingID, 0) = ISNULL(O.TracingRef, 0)
    GROUP BY T.StockID, T.ItemID, T.FiscalYearID, ISNULL(T.TracingID, 0), T.FeedFromClosingOperation
)
UPDATE ISS SET
    InputQuantity = CASE WHEN ISS.UnitRef = I.UnitRef THEN T.InputQuantity WHEN ISS.UnitRef = I.SecondaryUnitRef THEN T.SecondaryInputQuantity ELSE ISS.InputQuantity END,
    OutputQuantity = CASE WHEN ISS.UnitRef = I.UnitRef THEN T.OutputQuantity WHEN ISS.UnitRef = I.SecondaryUnitRef THEN T.SecondaryOutputQuantity ELSE ISS.OutputQuantity END,
    SaleQuantity = CASE WHEN ISS.UnitRef = I.UnitRef THEN T.SaleQuantity WHEN ISS.UnitRef = I.SecondaryUnitRef THEN T.SaleSecondaryQuantity ELSE ISS.SaleQuantity END,
    SaleWithReserveQuantity =
        CASE
            WHEN ISS.UnitRef = I.UnitRef THEN T.SaleWithReserveQuantity + T.OrderWithReserveQuantity
            WHEN ISS.UnitRef = I.SecondaryUnitRef THEN T.SaleWithReserveSecondaryQuantity + T.OrderWithReserveSecondaryQuantity
            ELSE ISS.SaleWithReserveQuantity
        END,
    ISS.FeedFromClosingOperation =
        CASE T.FeedFromClosingOperation
            WHEN 0 THEN ISS.FeedFromClosingOperation
            ELSE T.FeedFromClosingOperation
        END
FROM INV.ItemStockSummary ISS
JOIN SummaryTotals T ON ISS.StockRef = T.StockID AND ISS.ItemRef = T.ItemID AND ISS.FiscalYearRef = T.FiscalYearID AND ISNULL(ISS.TracingRef, 0) = T.TracingID
JOIN INV.Item I ON T.ItemID = I.ItemID;

DELETE ISS
FROM INV.ItemStockSummary ISS
JOIN INV.Item I ON I.ItemID = ItemRef
JOIN #tmpSummaryTable T ON StockRef = T.StockID AND ItemRef = T.ItemID AND ISNULL(TracingRef, 0) = ISNULL(T.TracingID, 0) AND FiscalYearRef = T.FiscalYearID
WHERE (ISS.UnitRef = I.UnitRef OR ISS.UnitRef = I.SecondaryUnitRef)
  AND ISS.InputQuantity = 0
  AND ISS.OutputQuantity = 0
  AND ISS.SaleQuantity = 0
  AND SaleWithReserveQuantity = 0
  AND ISS.FeedFromClosingOperation = 0;

DROP TABLE #tmpSummaryTable;
SQL;

        DB::connection('sqlsrv')->statement($sql, $bindings);
    }
}
