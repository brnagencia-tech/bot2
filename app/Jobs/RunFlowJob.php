<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Flow;
use App\Services\OpenAiChatService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $flowId, public ?int $contactId = null, public ?int $segmentId = null) {}

    public function handle(WhatsAppService $wa, OpenAiChatService $ai): void
    {
        $flow = Flow::find($this->flowId);
        if (!$flow) return;
        $json = $flow->json ?? [];

        $targets = collect();
        if ($this->contactId) {
            $c = Contact::find($this->contactId);
            if ($c) $targets = collect([$c]);
        } elseif ($this->segmentId) {
            // minimal: broadcast to all contacts (MVP)
            $targets = Contact::orderBy('id')->get();
        }

        foreach ($targets as $contact) {
            $this->runForContact($json, $contact, $wa, $ai);
        }
    }

    protected function runForContact(array $flowJson, Contact $contact, WhatsAppService $wa, OpenAiChatService $ai): void
    {
        $nodes = $flowJson['nodes'] ?? [];
        $map = [];
        foreach ($nodes as $n) { $map[$n['id']] = $n; }
        $edges = $flowJson['edges'] ?? [];
        $next = fn($id, $branch = null) => collect($edges)->first(function($e) use($id,$branch){
            // edge e.g. [from,to] or ["cond1.true","msg_ai"]
            return ($e[0] === $id) || ($branch && $e[0] === $id.'.'.$branch);
        })[1] ?? null;

        $cur = 'start';
        $safePhone = preg_replace('/\D+/', '', (string)$contact->phone);
        $lastUserMsg = '';

        while ($cur && isset($map[$cur])) {
            $node = $map[$cur];
            switch ($node['type'] ?? 'noop') {
                case 'send_text':
                    $wa->send($safePhone, (string)($node['text'] ?? ''));
                    $cur = $next($cur);
                    break;
                case 'wait':
                    $seconds = (int)($node['seconds'] ?? 1);
                    sleep(max(0, min($seconds, 30)));
                    $cur = $next($cur);
                    break;
                case 'condition':
                    $cond = (string)($node['if'] ?? '');
                    $branch = 'false';
                    if (str_contains($cond, 'contains(')) {
                        // contains(body,'x')
                        if (preg_match("/contains\(body,'(.+)'\)/", $cond, $m)) {
                            $branch = str_contains(mb_strtolower($lastUserMsg), mb_strtolower($m[1])) ? 'true' : 'false';
                        }
                    }
                    $cur = $next('cond1', $branch) ?? $next($cur, $branch) ?? $next($cur);
                    break;
                case 'ai_reply':
                    $prompt = (string)($node['prompt'] ?? '');
                    $content = $ai->reply([
                        ['role'=>'system','content'=>$prompt],
                        ['role'=>'user','content'=>$lastUserMsg],
                    ]);
                    $wa->send($safePhone, (string)$content);
                    $cur = $next($cur);
                    break;
                case 'start':
                case 'end':
                default:
                    $cur = $next($cur);
                    break;
            }
        }
    }
}

