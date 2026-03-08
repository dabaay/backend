<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class StoreInfoController extends Controller
{
    public function index(Request $request)
    {
        $keys     = ['store_name', 'store_phone', 'store_address', 'store_email', 'store_logo', 'store_description'];
        $settings = SystemSetting::whereIn('setting_key', $keys)->get()->keyBy('setting_key');

        $info = [];
        foreach ($keys as $key) {
            $info[$key] = $settings->get($key)?->setting_value ?? null;
        }

        return response()->json([
            'success' => true,
            'data'    => $info,
        ]);
    }
}
