<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'barangay_name',   'label' => 'Barangay Name',    'value' => 'Barangay Purisima',           'type' => 'text',     'group' => 'general', 'description' => 'Official name of the barangay'],
            ['key' => 'municipality',    'label' => 'Municipality',      'value' => '',                            'type' => 'text',     'group' => 'general', 'description' => 'Municipality where the barangay belongs'],
            ['key' => 'province',        'label' => 'Province',          'value' => '',                            'type' => 'text',     'group' => 'general', 'description' => 'Province where the barangay belongs'],
            ['key' => 'region',          'label' => 'Region',            'value' => '',                            'type' => 'text',     'group' => 'general', 'description' => 'Region of the barangay'],

            // Contact
            ['key' => 'contact_email',   'label' => 'Contact Email',     'value' => '',                            'type' => 'text',     'group' => 'contact', 'description' => 'Official email address'],
            ['key' => 'contact_phone',   'label' => 'Contact Phone',     'value' => '',                            'type' => 'text',     'group' => 'contact', 'description' => 'Official phone number'],
            ['key' => 'office_address',  'label' => 'Office Address',    'value' => '',                            'type' => 'textarea', 'group' => 'contact', 'description' => 'Physical address of the barangay hall'],
            ['key' => 'office_hours',    'label' => 'Office Hours',      'value' => 'Monday–Friday, 8AM–5PM',      'type' => 'text',     'group' => 'contact', 'description' => 'Regular office hours'],

            // Features
            ['key' => 'sms_enabled',     'label' => 'Enable SMS',        'value' => '1',                           'type' => 'boolean',  'group' => 'features', 'description' => 'Toggle SMS notifications globally'],
            ['key' => 'online_payment',  'label' => 'Online Payment',    'value' => '0',                           'type' => 'boolean',  'group' => 'features', 'description' => 'Allow residents to pay online'],
            ['key' => 'self_register',   'label' => 'Self Registration', 'value' => '1',                           'type' => 'boolean',  'group' => 'features', 'description' => 'Allow residents to register themselves'],

            // Certificate
            ['key' => 'captain_name',    'label' => 'Barangay Captain Name',  'value' => '',                       'type' => 'text',     'group' => 'certificate', 'description' => 'Name shown on certificate signatures'],
            ['key' => 'secretary_name',  'label' => 'Barangay Secretary Name','value' => '',                       'type' => 'text',     'group' => 'certificate', 'description' => 'Name of the barangay secretary'],
            ['key' => 'cert_footer',     'label' => 'Certificate Footer Note', 'value' => 'This document is issued upon request of the resident concerned for any legal purpose it may serve.', 'type' => 'textarea', 'group' => 'certificate', 'description' => 'Footer text on all certificates'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
