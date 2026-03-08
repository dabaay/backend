<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['setting_key' => 'store_name', 'setting_value' => "Somali Official POS", 'setting_type' => 'string', 'description' => 'The official name of the store'],
            ['setting_key' => 'store_address', 'setting_value' => 'Main Street, Mogadishu, Somalia', 'setting_type' => 'string', 'description' => 'Physical address of the store'],
            ['setting_key' => 'store_number', 'setting_value' => '+252 683632095', 'setting_type' => 'string', 'description' => 'Primary contact number'],
            ['setting_key' => 'store_logo', 'setting_value' => null, 'setting_type' => 'image', 'description' => 'Store logo image path'],
            ['setting_key' => 'tiktok_handle', 'setting_value' => '@somali_store', 'setting_type' => 'string', 'description' => 'TikTok username'],
            ['setting_key' => 'tiktok_show', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Whether to show TikTok in printed documents'],
            ['setting_key' => 'facebook_handle', 'setting_value' => 'somali.store', 'setting_type' => 'string', 'description' => 'Facebook page name/handle'],
            ['setting_key' => 'facebook_show', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Whether to show Facebook in printed documents'],
            ['setting_key' => 'instagram_handle', 'setting_value' => 'somali_store', 'setting_type' => 'string', 'description' => 'Instagram handle'],
            ['setting_key' => 'instagram_show', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Whether to show Instagram in printed documents'],
            ['setting_key' => 'whatsapp_number', 'setting_value' => '+252 683632095', 'setting_type' => 'string', 'description' => 'WhatsApp contact number'],
            ['setting_key' => 'whatsapp_show', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Whether to show WhatsApp in printed documents'],
            ['setting_key' => 'show_social_labels', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Whether to show the name/label of social media handles next to icons'],
        ];

        foreach ($settings as $setting) {
            \App\Models\SystemSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                [
                    'setting_value' => $setting['setting_value'],
                    'setting_type' => $setting['setting_type'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
