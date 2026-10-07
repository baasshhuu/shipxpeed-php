<?php

namespace App\Library;

use Illuminate\Support\Facades\Http;

class TextLocal
{
    public static function sendSms(array $numbers, string $message): bool
    {
        try {
            $apiKey     = urlencode(request()->site_settings['textlocal_key']);
            $sender     = urlencode(request()->site_settings['textlocal_sender']);

            // Message details
            $numbers    = urlencode(implode(',', $numbers));
            $message    = urlencode($message);

            // Send the GET request with cURL
            $response = Http::get(request()->site_settings['textlocal_url'], [
                'apikey'    => $apiKey,
                'sender'    => $sender,
                'numbers'   => $numbers,
                'message'   => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] == 'success') return true;
                else return false;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            return false;
        }
    }
}
