<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Jobs\BroadcastSendJob;
use App\Models\BroadcastJob;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function createJob(Request $request)
    {
        $data = $request->validate([
            'segment_id' => 'required|integer',
            'text' => 'required|string',
        ]);
        $job = BroadcastJob::create([
            'segment_id' => $data['segment_id'],
            'text' => $data['text'],
            'status' => 'queued',
            'progress' => 0,
            'tenant_id' => auth()->id(),
        ]);
        BroadcastSendJob::dispatch($job->id);
        return response()->json(['queued' => true, 'id' => $job->id]);
    }
}

