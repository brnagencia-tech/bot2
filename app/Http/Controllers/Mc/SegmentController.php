<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function index()
    {
        return response()->json(Segment::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'rule_json' => 'nullable|array',
        ]);
        $data['tenant_id'] = auth()->id();
        return response()->json(Segment::create($data), 201);
    }

    public function update(Request $request, Segment $segment)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'rule_json' => 'nullable|array',
        ]);
        $segment->update($data);
        return response()->json($segment);
    }

    public function destroy(Segment $segment)
    {
        $segment->delete();
        return response()->noContent();
    }
}

