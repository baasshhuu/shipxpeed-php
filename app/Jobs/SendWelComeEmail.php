<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Mail\WelcomeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;


class SendWelComeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $data;
    public $settings;

    public function __construct($data, $site_settings)
    {
        $this->data     = $data;
        $this->settings = $site_settings;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $settings = $this->settings;
            if ($settings) {
                Config::set("mail.mailers.smtp", [
                    'transport'     => 'smtp',
                    'host'          => $settings['smtp_host'],
                    'port'          => $settings['smtp_port'],
                    'encryption'    => in_array((int) $settings['smtp_port'], [587, 2525]) ? 'tls' : 'ssl',
                    'username'      => $settings['smtp_user'],
                    'password'      => $settings['smtp_pass'],
                    'timeout'       =>  null,
                    'auth_mode'     =>  null,
                ]);

                Config::set("mail.from", [
                    'address'       =>  $settings['email_from'],
                    'name'          =>  $settings['application_name'],
                ]);

                Mail::to($this->data['email'])->queue(new WelcomeMail($this->data, $this->settings));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
