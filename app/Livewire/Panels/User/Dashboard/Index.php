<?php

namespace App\Livewire\Panels\User\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Index extends Component
{
  public string $serial = '10006d04';
    public int $integrationId = 1000;

    public string $deviceTitle = '';
    public string $publicKeyXml = '';

    public string $itemsResponse = '';

    /* ===============================
       STEP 1 - Register Device
    =============================== */
public function registerDevice()
{
    $serial = '10006d04';

    // MUST be derived from serial
    $integrationId = (int) substr($serial, 0, 4);

    // EXACT key
    $aesKey = $serial . $serial;

    $iv = random_bytes(16);

    $cipherRaw = openssl_encrypt(
        (string) $integrationId,     // MUST be string
        'AES-128-CBC',
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    $payload = [
        'Cypher'        => base64_encode($cipherRaw),
        'IV'            => base64_encode($iv),
        'IntegrationID' => $integrationId,
    ];

    $response = Http::withoutVerifying()
        ->acceptJson()
        ->post('http://127.0.0.1:7373/api/Devices/Register', $payload);

    if (! $response->successful()) {
        // برای دیباگ
        $this->itemsResponse = $response->body();
        return;
    }

    $data = $response->json();

    $this->publicKeyXml = openssl_decrypt(
        base64_decode($data['Cypher']),
        'AES-128-CBC',
        $aesKey,
        OPENSSL_RAW_DATA,
        base64_decode($data['IV'])
    );

    $this->deviceTitle = $data['DeviceTitle'] ?? '';
}


    /* ===============================
       STEP 2 - Call Items API
    =============================== */
    public function getItems()
    {
        if (!$this->publicKeyXml) {
            return;
        }

        $arbitraryCode = (string) Str::uuid();

        // XML -> PEM
        $pem = $this->rsaXmlToPem($this->publicKeyXml);

        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new \Exception('Invalid RSA public key');
        }

        openssl_public_encrypt(
            $arbitraryCode,
            $encrypted,
            $publicKey,
            OPENSSL_PKCS1_PADDING
        );

        $headers = [
            'IntegrationID'     => $this->integrationId,
            'ArbitraryCode'     => $arbitraryCode,
            'EncArbitraryCode'  => base64_encode($encrypted),
            'GenerationVersion' => 1,
            'Accept'            => 'application/json',
        ];

        $response = Http::withoutVerifying()
            ->withHeaders($headers)
            ->get('http://127.0.0.1:7373/api/Items');

        $this->itemsResponse = $response->body();
    }

    /* ===============================
       Helper: RSA XML -> PEM
    =============================== */
    private function rsaXmlToPem(string $xml): string
    {
        $xml = simplexml_load_string($xml);

        $modulus  = base64_decode((string)$xml->Modulus);
        $exponent = base64_decode((string)$xml->Exponent);

        $encodeLength = function ($length) {
            if ($length <= 0x7F) {
                return chr($length);
            }
            $temp = ltrim(pack('N', $length), "\x00");
            return chr(0x80 | strlen($temp)) . $temp;
        };

        $encodeInteger = function ($data) use ($encodeLength) {
            if (ord($data[0]) > 0x7F) {
                $data = "\x00" . $data;
            }
            return "\x02" . $encodeLength(strlen($data)) . $data;
        };

        $modulusEnc  = $encodeInteger($modulus);
        $exponentEnc = $encodeInteger($exponent);

        $rsaKey = "\x30"
            . $encodeLength(strlen($modulusEnc . $exponentEnc))
            . $modulusEnc . $exponentEnc;

        $rsaOid = "\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01";
        $algId  = "\x30"
            . $encodeLength(strlen($rsaOid . "\x05\x00"))
            . $rsaOid . "\x05\x00";

        $bitString = "\x03"
            . $encodeLength(strlen($rsaKey) + 1)
            . "\x00" . $rsaKey;

        $publicKeyInfo = "\x30"
            . $encodeLength(strlen($algId . $bitString))
            . $algId . $bitString;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.dashboard.index');
    }
}
