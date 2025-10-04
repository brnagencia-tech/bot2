<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    protected string $model;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->model = env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * Reply using OpenAI Chat Completions-like API.
     * If no API key is available, returns a fallback message.
     * @param array<int,array<string,mixed>> $messages
     * @param array<int,array<string,mixed>> $tools
     */
    public function reply(array $messages, array $tools = []): string
    {
        if (!$this->apiKey) {
            return 'IA indisponível no momento.';
        }

        try {
            // Minimal HTTP call; avoid external deps. Adjust endpoint if needed.
            $body = [
                'model' => $this->model,
                'messages' => $messages,
            ];
            if (!empty($tools)) {
                $body['tools'] = $tools;
            }

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => 20,
            ]);
            $res = curl_exec($ch);
            if ($res === false) {
                Log::warning('OpenAI request failed: ' . curl_error($ch));
                return 'IA temporariamente indisponível.';
            }
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $json = json_decode($res, true);
            if ($code >= 200 && $code < 300 && isset($json['choices'][0]['message']['content'])) {
                return (string)$json['choices'][0]['message']['content'];
            }
            Log::warning('OpenAI non-2xx', ['code' => $code, 'body' => $json]);
            return 'IA temporariamente indisponível.';
        } catch (\Throwable $e) {
            Log::error('OpenAI error: ' . $e->getMessage());
            return 'IA temporariamente indisponível.';
        }
    }
}

