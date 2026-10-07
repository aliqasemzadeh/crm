<?php

namespace Tests\Feature\Customer;

use App\Livewire\Panels\Customer\Invoice\History;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\Item;
use App\Services\Sepidar\InvoiceCreator;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\Concerns\UsesSepidarSqlsrvTransaction;
use Tests\TestCase;

class InvoiceHistoryTest extends TestCase
{
    use UsesSepidarSqlsrvTransaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSepidarTransaction();
    }

    protected function tearDown(): void
    {
        $this->tearDownSepidarTransaction();
        parent::tearDown();
    }

    public function test_invoice_view_requires_valid_signed_url(): void
    {
        $party = Party::query()->first();
        if (! $party) {
            $this->markTestSkipped('No party available in database.');
        }

        $item = Item::query()->first();
        if (! $item) {
            $this->markTestSkipped('No item available in database.');
        }

        $invoice = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'Signed route test invoice',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1.0,
                'fee' => 500000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        // 1. Unsigned request should fail with 403
        $unsignedUrl = route('panels.customer.invoice.view', ['invoiceId' => $invoice->InvoiceId]);
        $response = $this->get($unsignedUrl);
        $response->assertStatus(403);

        // 2. Tampered signature should fail with 403
        $signedUrl = URL::signedRoute('panels.customer.invoice.view', ['invoiceId' => $invoice->InvoiceId]);
        $tamperedUrl = $signedUrl.'invalid';
        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);

        // 3. Valid signed request should succeed with 200
        $response = $this->get($signedUrl);
        $response->assertStatus(200);
        $response->assertSee((string) $invoice->Number);

        // 4. The response should contain the signed purchase history link
        $expectedHistoryUrl = URL::signedRoute('panels.customer.invoice.history', ['partyId' => $invoice->CustomerPartyRef]);
        $response->assertSee(html_entity_decode($expectedHistoryUrl), false);
    }

    public function test_customer_invoice_history_requires_valid_signed_url(): void
    {
        $party = Party::query()->first();
        if (! $party) {
            $this->markTestSkipped('No party available in database.');
        }

        // 1. Unsigned request should fail with 403
        $unsignedUrl = route('panels.customer.invoice.history', ['partyId' => $party->PartyId]);
        $response = $this->get($unsignedUrl);
        $response->assertStatus(403);

        // 2. Tampered signature should fail with 403
        $signedUrl = URL::signedRoute('panels.customer.invoice.history', ['partyId' => $party->PartyId]);
        $tamperedUrl = $signedUrl.'tampered';
        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);

        // 3. Valid signed request should succeed with 200
        $response = $this->get($signedUrl);
        $response->assertStatus(200);
    }

    public function test_customer_invoice_history_lists_customer_invoices_and_links(): void
    {
        $party = Party::query()->first();
        if (! $party) {
            $this->markTestSkipped('No party available in database.');
        }

        $item = Item::query()->first();
        if (! $item) {
            $this->markTestSkipped('No item available in database.');
        }

        $invoice1 = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'History test invoice 1',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 1.0,
                'fee' => 100000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $invoice2 = app(InvoiceCreator::class)->create([
            'customer_party_ref' => (int) $party->PartyId,
            'sale_type_ref' => 2,
            'date' => Jalalian::now()->format('Y/m/d'),
            'description' => 'History test invoice 2',
            'items' => [[
                'item_ref' => (int) $item->ItemID,
                'quantity' => 2.0,
                'fee' => 200000,
                'discount' => 0,
                'tax' => 0,
            ]],
        ]);

        $signedHistoryUrl = URL::signedRoute('panels.customer.invoice.history', ['partyId' => $party->PartyId]);
        $response = $this->get($signedHistoryUrl);
        $response->assertStatus(200);
        $response->assertSee((string) $invoice1->Number);
        $response->assertSee((string) $invoice2->Number);

        // Invoices should have signed view links
        $expectedInvoice1Url = URL::signedRoute('panels.customer.invoice.view', ['invoiceId' => $invoice1->InvoiceId]);
        $expectedInvoice2Url = URL::signedRoute('panels.customer.invoice.view', ['invoiceId' => $invoice2->InvoiceId]);

        $response->assertSee(html_entity_decode($expectedInvoice1Url), false);
        $response->assertSee(html_entity_decode($expectedInvoice2Url), false);

        // Test Livewire component directly
        Livewire::test(History::class, ['partyId' => $party->PartyId])
            ->assertSee((string) $invoice1->Number)
            ->assertSee((string) $invoice2->Number)
            ->set('search', (string) $invoice1->Number)
            ->assertSee((string) $invoice1->Number);
    }
}
