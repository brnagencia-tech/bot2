<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = WorkspaceSetting::where('user_id', auth()->id())->get()->pluck('value', 'key');
        return view('mc.settings', ['settings' => $settings]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'workspace_name' => 'nullable|string',
            'timezone' => 'nullable|string',
            'language' => 'nullable|string',
            'openai_model' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'whatsapp_service_url' => 'nullable|url',
        ]);
        foreach ($data as $key => $value) {
            WorkspaceSetting::updateOrCreate(
                ['user_id' => auth()->id(), 'key' => $key],
                ['value' => $value]
            );
        }
        return back()->with('status', 'saved');
    }
}

