<?php

namespace App\Helpers;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsHelpre
{
    public static function sendMessage(string $to, string $message)
    {
        try {
            $account_sid = env('TWILIO_SID');
            $auth_token = env('TWILIO_TOKEN');
            $messaging_service_sid = env('TWILIO_MESSAGING_SID');

            // Validate Twilio credentials
            if (empty($account_sid) || empty($auth_token) || empty($messaging_service_sid)) {
                Log::error('Twilio credentials not configured');
                return false;
            }

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($to, [
                'messagingServiceSid' => $messaging_service_sid,
                'body' => $message
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Twilio SMS Error: ' . $e->getMessage());
            return false;
        }
    }
}
