<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'setting_type'  => 1,
                'setting_name'  => 'favicon',
                'filed_label'   => 'Favicon (25*25)',
                'filed_type'    => 'file',
                'filed_value'   => 'application/favicon.png',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'logo',
                'filed_label'   => 'Logo',
                'filed_type'    => 'file',
                'filed_value'   => 'application/logo.png',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'application_name',
                'filed_label'   => 'Application Name',
                'filed_type'    => 'text',
                'filed_value'   => 'eTally',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'copyright',
                'filed_label'   => 'Copyright',
                'filed_type'    => 'text',
                'filed_value'   => 'Copyright © 2023. All rights reserved.',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'address',
                'filed_label'   => 'Address',
                'filed_type'    => 'text',
                'filed_value'   => 'Paota, Jodhpur, Rajasthan - 342001',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'email',
                'filed_label'   => 'Email',
                'filed_type'    => 'text',
                'filed_value'   => 'lakshminarayan@adiyogitechnosoft.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'phone',
                'filed_label'   => 'Phone',
                'filed_type'    => 'text',
                'filed_value'   => '+91-7568457070',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 1,
                'setting_name'  => 'helpline_numbers',
                'filed_label'   => 'Helpline Numbers',
                'filed_type'    => 'text',
                'filed_value'   => '+91 0000000000',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            //==================================================================================================
            [
                'setting_type'  => 2,
                'setting_name'  => 'facebook',
                'filed_label'   => 'Facebook',
                'filed_type'    => 'text',
                'filed_value'   => 'https://www.facebook.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 2,
                'setting_name'  => 'twitter',
                'filed_label'   => 'Twitter',
                'filed_type'    => 'text',
                'filed_value'   => 'https://www.twitter.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 2,
                'setting_name'  => 'linkdin',
                'filed_label'   => 'Linkdin',
                'filed_type'    => 'text',
                'filed_value'   => 'https://www.linkdin.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 2,
                'setting_name'  => 'instagram',
                'filed_label'   => 'Instagram',
                'filed_type'    => 'text',
                'filed_value'   => 'https://www.instagram.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            //==================================================================================================
            [
                'setting_type'  => 3,
                'setting_name'  => 'email_from',
                'filed_label'   => 'Email From',
                'filed_type'    => 'text',
                'filed_value'   => 'info@test.com',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 3,
                'setting_name'  => 'smtp_host',
                'filed_label'   => 'SMTP Host',
                'filed_type'    => 'text',
                'filed_value'   => 'sandbox.smtp.mailtrap.io',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 3,
                'setting_name'  => 'smtp_port',
                'filed_label'   => 'SMTP Port',
                'filed_type'    => 'text',
                'filed_value'   => '2525',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 3,
                'setting_name'  => 'smtp_user',
                'filed_label'   => 'SMTP User',
                'filed_type'    => 'text',
                'filed_value'   => 'a39b56215c4b62',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'setting_type'  => 3,
                'setting_name'  => 'smtp_pass',
                'filed_label'   => 'SMTP Password',
                'filed_type'    => 'text',
                'filed_value'   => 'b859477cd68576',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            //==================================================================================================
        ];

        Setting::truncate();
        Setting::insert($settings);
    }
}
