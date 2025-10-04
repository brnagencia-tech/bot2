<?php

namespace Tests\Feature;

use App\Services\OpenAiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McModuleTest extends TestCase
{
    public function test_openai_service_without_key_returns_string(): void
    {
        $svc = new OpenAiChatService();
        $resp = $svc->reply([
            ['role' => 'user', 'content' => 'Olá']
        ]);
        $this->assertIsString($resp);
    }
}

