<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        return response()->json(Tag::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'color' => 'nullable|string',
        ]);
        $data['tenant_id'] = auth()->id();
        return response()->json(Tag::create($data), 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'color' => 'nullable|string',
        ]);
        $tag->update($data);
        return response()->json($tag);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->noContent();
    }
}

