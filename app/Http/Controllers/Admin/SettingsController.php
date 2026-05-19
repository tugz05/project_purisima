<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->map(fn ($s) => [
                'id'          => $s->id,
                'key'         => $s->key,
                'label'       => $s->label,
                'value'       => $s->value,
                'type'        => $s->type,
                'group'       => $s->group,
                'description' => $s->description,
            ])->values());

        return Inertia::render('admin/Settings/Index', ['settings' => $settings]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings'       => 'required|array',
            'settings.*.key'   => 'required|string|exists:system_settings,key',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($data['settings'] as $item) {
            SystemSetting::where('key', $item['key'])->update(['value' => $item['value'] ?? null]);
            Cache::forget("setting:{$item['key']}");
        }

        return back()->with('success', 'Settings saved successfully.');
    }
}
