<?php

namespace App\Library;

use Illuminate\Support\Facades\Http;

class Firebase
{
    public static function sendMessage(array $fcm_ids = [], string $title = '', string $message = '', string $image = '')
    {
        try {
            $SERVER_API_URL = config('constant.fcm_url');
            $SERVER_API_KEY = config('constant.firebase_key');

            $data = [
                "registration_ids"  => $fcm_ids,
                "notification"  => [
                    "title"     => $title,
                    "body"      => $message,
                    "subtitle"  => "subtitle",
                    "color"     => "#4361ee",
                    "image"     => $image
                ],
            ];

            $http = Http::withHeaders([
                'Authorization'     => 'key=' . $SERVER_API_KEY,
                'Content-Type'      => 'application/json',
            ])->post($SERVER_API_URL, $data);

            return  $http->json();
        } catch (\Exception  $e) {
            return false;
        }
    }


    public static function sendNotification($fcm_id = null, $template = 1)
    {
        if ($fcm_id) {
            switch ($template) {
                case 1:
                    $title      = "Payment Request :: Approved.";
                    $message    = "Your Payment Request has been Approved.";
                    break;
                case 2:
                    $title      = "Payment Request :: Rejected.";
                    $message    = "Your Payment Request has been Rejected.";
                    break;
                default:
                    $title      = "";
                    $message    = "";
                    break;
            }

            self::sendMessage([$fcm_id], $title, $message);
        }
    }
}
