<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/whatsapp-console', function () {
    return view('whatsapp-console');
})->name('whatsapp.console');

Route::post('/whatsapp-console/send', function (Request $request) {
    $validated = $request->validate([
        'from' => ['required', 'string'],
        'to' => ['required', 'string'],
        'mode' => ['required', 'string', 'in:invite_template,accept,reject,custom'],
        'name' => ['nullable', 'string'],
        'event' => ['nullable', 'string'],
        'date' => ['nullable', 'string'],
        'body' => ['nullable', 'string'],
    ]);

    $normalizeWhatsapp = function (string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/^whatsapp\s*:\s*/i', 'whatsapp:', $value) ?? $value;

        if (str_starts_with($value, '+')) {
            return 'whatsapp:'.$value;
        }

        if (str_starts_with($value, 'whatsapp:')) {
            return $value;
        }

        return $value;
    };

    $from = $normalizeWhatsapp((string) $validated['from']);
    $to = $normalizeWhatsapp((string) $validated['to']);
    $mode = (string) $validated['mode'];

    if (!str_starts_with($from, 'whatsapp:') || !str_starts_with($to, 'whatsapp:')) {
        return back()->withInput()->with('error', 'صيغة الأرقام غير صحيحة. استخدم whatsapp:+2011... أو +2011...');
    }

    $sid = (string) config('services.twilio.account_sid');
    $token = (string) config('services.twilio.auth_token');
    $contentSid = (string) config('services.twilio.invite_content_sid');

    if ($sid === '' || $token === '') {
        return back()->withInput()->with('error', 'Twilio غير مُعد. تأكد من TWILIO_ACCOUNT_SID و TWILIO_AUTH_TOKEN');
    }

    $twilio = new Client($sid, $token);

    try {
        if ($mode === 'invite_template') {
            if ($contentSid === '') {
                return back()->withInput()->with('error', 'TWILIO_WHATSAPP_INVITE_CONTENT_SID غير موجود.');
            }

            $name = (string) ($validated['name'] ?? '');
            $event = (string) ($validated['event'] ?? '');
            $date = (string) ($validated['date'] ?? '');

            $twilio->messages->create($to, [
                'from' => $from,
                'contentSid' => $contentSid,
                'contentVariables' => json_encode([
                    '1' => $name,
                    '2' => $event,
                    '3' => $date,
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return back()->with('success', 'تم إرسال الدعوة (Template) بنجاح.');
        }

        if ($mode === 'accept') {
            $code = strtoupper(bin2hex(random_bytes(4)));

            $body = trim((string) ($validated['body'] ?? ''));
            if ($body === '') {
                $body = "تم تأكيد حضورك 🎉\nكود الدخول الخاص بك: {{code}}\nرابط الدخول: https://example.com/event";
            }

            $body = str_replace('{{code}}', $code, $body);

            $twilio->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);

            return back()->with('success', 'تم إرسال رسالة القبول بنجاح. الكود: '.$code);
        }

        if ($mode === 'reject') {
            $body = trim((string) ($validated['body'] ?? ''));
            if ($body === '') {
                $body = 'شكرًا على تواصلك معنا، نتمنى لك يومًا سعيدًا!';
            }

            $twilio->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);

            return back()->with('success', 'تم إرسال رسالة الرفض بنجاح.');
        }

        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '') {
            return back()->withInput()->with('error', 'اكتب نص الرسالة في وضع الرسالة المخصصة.');
        }

        $twilio->messages->create($to, [
            'from' => $from,
            'body' => $body,
        ]);

        return back()->with('success', 'تم إرسال الرسالة المخصصة بنجاح.');
    } catch (Throwable $e) {
        return back()->withInput()->with('error', 'فشل الإرسال: '.$e->getMessage());
    }
})->name('whatsapp.console.send');

Route::post('/twilio/whatsapp', function (Request $request) {
    $from = (string) $request->input('From', '');
    $body = (string) $request->input('Body', '');

    $buttonText = (string) $request->input('ButtonText', '');
    $buttonPayload = (string) $request->input('ButtonPayload', '');

    $incoming = trim($buttonText !== '' ? $buttonText : $body);
    if ($incoming === '' && $buttonPayload !== '') {
        $incoming = trim($buttonPayload);
    }

    $normalized = trim(mb_strtolower($incoming, 'UTF-8'));

    $sid = (string) config('services.twilio.account_sid');
    $token = (string) config('services.twilio.auth_token');
    $whatsappFrom = (string) config('services.twilio.whatsapp_from');

    if ($sid === '' || $token === '' || $whatsappFrom === '') {
        return response('Twilio is not configured', 500);
    }

    $twilio = new Client($sid, $token);

    if ($from === '') {
        return response('Missing From', 400);
    }

    if ($normalized !== '' && (
        str_starts_with($normalized, 'نعم') ||
        str_starts_with($normalized, 'yes') ||
        str_starts_with($normalized, 'confirm') ||
        str_starts_with($normalized, 'confirmed')
    )) {
        $code = strtoupper(bin2hex(random_bytes(4)));

        $twilio->messages->create($from, [
            'from' => $whatsappFrom,
            'body' => "تم تأكيد حضورك 🎉\nكود الدخول الخاص بك: {$code}\nرابط الدخول: https://example.com/event",
        ]);

        return response('OK', 200);
    }

    if ($normalized !== '' && (
        str_starts_with($normalized, 'لا') ||
        str_starts_with($normalized, 'no') ||
        str_starts_with($normalized, 'decline') ||
        str_starts_with($normalized, 'cancel')
    )) {
        $twilio->messages->create($from, [
            'from' => $whatsappFrom,
            'body' => 'شكرًا على تواصلك معنا، نتمنى لك يومًا سعيدًا!',
        ]);

        return response('OK', 200);
    }

    return response('OK', 200);
});
