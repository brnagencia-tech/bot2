<?php

namespace App\Jobs;

use App\Models\BroadcastJob;
use App\Models\Contact;
use App\Models\Segment;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $broadcastJobId) {}

    public function handle(WhatsAppService $wa): void
    {
        $job = BroadcastJob::find($this->broadcastJobId);
        if (!$job) return;
        $job->update(['status' => 'running']);

        $segment = Segment::find($job->segment_id);
        $rule = $segment?->rule_json ?? [];

        $q = Contact::query();
        if (!empty($rule['ddd'])) {
            $q->where('phone', 'like', $rule['ddd'].'%');
        }
        // Additional simple filters can go here (e.g., tag)
        $contacts = $q->orderBy('id')->get();

        $total = max(1, $contacts->count());
        $sent = 0;
        foreach ($contacts as $c) {
            $ok = $wa->send(preg_replace('/\D+/','', (string)$c->phone), $job->text);
            usleep(125000); // ~8 msg/s
            $sent++;
            $job->update(['progress' => (int) round($sent * 100 / $total)]);
        }
        $job->update(['status' => 'done']);
    }
}

