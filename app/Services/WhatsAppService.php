<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3001'), '/');
    }

    public function status(): array
    {
        return $this->getJson('/status') ?? [
            'connected' => false,
            'state' => 'unknown',
            'hasCreds' => false,
            'me' => null,
        ];
    }

    /** Return raw binary PNG data or null */
    public function qrPngBinary(): ?string
    {
        try {
            $url = $this->baseUrl . '/qr';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Accept: image/png'],
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 204) {
                return null; // connected
            }
            if ($code >= 200 && $code < 300 && is_string($data)) {
                return $data;
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('QR fetch failed: '.$e->getMessage());
            return null;
        }
    }

    public function send(string $to, string $text): bool
    {
        $payload = [
            'to' => $to,
            'text' => $text,
        ];
        $res = $this->postJson('/send', $payload);
        return (bool)($res['ok'] ?? ($res['success'] ?? false));
    }

    private function getJson(string $path): ?array
    {
        try {
            $ch = curl_init($this->baseUrl . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res === false) return null;
            if ($code >= 200 && $code < 300) {
                return json_decode($res, true);
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('WA getJson error: ' . $e->getMessage());
            return null;
        }
    }

    private function postJson(string $path, array $data): ?array
    {
        try {
            $ch = curl_init($this->baseUrl . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res === false) return null;
            if ($code >= 200 && $code < 300) {
                return json_decode($res, true);
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('WA postJson error: ' . $e->getMessage());
            return null;
        }
    }
}

