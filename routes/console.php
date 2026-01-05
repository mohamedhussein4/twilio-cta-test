<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Twilio\Rest\Client;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('twilio:whatsapp-invite', function () {
    $to = (string) $this->ask('Customer WhatsApp number (format: whatsapp:+2011...)');
    $name = (string) $this->ask('Customer name');
    $event = (string) $this->ask('Event name');
    $date = (string) $this->ask('Event date');

    $sid = (string) config('services.twilio.account_sid');
    $token = (string) config('services.twilio.auth_token');
    $whatsappFrom = (string) config('services.twilio.whatsapp_from');
    $contentSid = (string) config('services.twilio.invite_content_sid');

    if ($sid === '' || $token === '' || $whatsappFrom === '' || $contentSid === '') {
        $this->error('Twilio is not configured. Check TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_WHATSAPP_FROM / TWILIO_WHATSAPP_INVITE_CONTENT_SID');
        return 1;
    }

    if ($to === '' || !str_starts_with($to, 'whatsapp:')) {
        $this->error('Invalid customer number. It must start with "whatsapp:"');
        return 1;
    }

    $twilio = new Client($sid, $token);

    $message = $twilio->messages->create($to, [
        'from' => $whatsappFrom,
        'contentSid' => $contentSid,
        'contentVariables' => json_encode([
            '1' => $name,
            '2' => $event,
            '3' => $date,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    $this->info('Invite sent. Message SID: '.$message->sid);
    return 0;
})->purpose('Send a WhatsApp invite using Twilio Utility Template');
