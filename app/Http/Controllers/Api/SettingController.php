<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Get all system settings.
     */
    public function index()
    {
        $settings = SystemSetting::all();
        
        // Format as key-value pairs for easier frontend usage
        $formatted = [];
        foreach ($settings as $setting) {
            $value = $setting->setting_value;
            
            // Cast based on type
            if ($setting->setting_type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($setting->setting_type === 'json' && $value) {
                $value = json_decode($value, true);
            }
            
            $formatted[$setting->setting_key] = $value;
        }

        return response()->json($formatted);
    }

    /**
     * Update multiple settings at once.
     */
    public function update(Request $request)
    {
        $settings = $request->except(['user_id', 'token']); // Basic cleanup

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('setting_key', $key)->first();
            
            if ($setting) {
                // If it's a file upload for the logo
                // If it's a file upload for the logo
                if ($key === 'store_logo') {
                    if ($request->hasFile('store_logo')) {
                        // Delete old logo if exists
                        if ($setting->setting_value) {
                            Storage::disk('public')->delete($setting->setting_value);
                        }
                        
                        $path = $request->file('store_logo')->store('branding', 'public');
                        $value = $path;
                    } elseif ($value === null || $value === '' || $value === 'null') {
                        // Explicitly removing the logo
                        if ($setting->setting_value) {
                            Storage::disk('public')->delete($setting->setting_value);
                        }
                        $value = null;
                    } else {
                        // Keep existing value if not a file and not removing
                        $value = $setting->setting_value;
                    }
                }

                // Handle boolean conversion if needed
                if ($setting->setting_type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                }

                $setting->update([
                    'setting_value' => is_array($value) ? json_encode($value) : $value,
                    'updated_by' => auth()->id() ?? $request->user_id
                ]);
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Get a specific setting by key.
     */
    public function show($key)
    {
        $setting = SystemSetting::where('setting_key', $key)->first();
        
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json($setting);
    }
}
