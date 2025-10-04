<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Jobs\RunFlowJob;
use App\Models\Flow;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    public function index()
    {
        return response()->json(Flow::orderByDesc('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'json' => 'required|array',
        ]);
        $data['tenant_id'] = auth()->id();
        return response()->json(Flow::create($data), 201);
    }

    public function show(Flow $flow)
    {
        return response()->json($flow);
    }

    public function update(Request $request, Flow $flow)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'json' => 'sometimes|array',
        ]);
        $flow->update($data);
        return response()->json($flow);
    }

    public function destroy(Flow $flow)
    {
        $flow->delete();
        return response()->noContent();
    }

    public function run(Request $request, Flow $flow)
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|integer',
            'segment_id' => 'nullable|integer',
        ]);
        RunFlowJob::dispatch($flow->id, $validated['contact_id'] ?? null, $validated['segment_id'] ?? null);
        return response()->json(['queued' => true]);
    }
}

