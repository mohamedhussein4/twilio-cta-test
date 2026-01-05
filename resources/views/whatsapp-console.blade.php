<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WhatsApp Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="flex items-start justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-semibold">لوحة تحكم WhatsApp (Twilio)</h1>
                <p class="text-sm text-slate-600 mt-1">إرسال دعوة/قبول/رفض/رسالة مخصصة يدويًا عبر Twilio WhatsApp.</p>
            </div>
            <a href="/" class="text-sm text-slate-600 hover:text-slate-900 underline">الصفحة الرئيسية</a>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <div class="font-medium mb-2">تحقق من المدخلات:</div>
                <ul class="list-disc pr-5 space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6">
            <form method="POST" action="{{ route('whatsapp.console.send') }}" class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                @csrf

                <div class="p-6 border-b border-slate-200">
                    <h2 class="text-lg font-semibold">إرسال رسالة</h2>
                    <p class="text-sm text-slate-600 mt-1">تأكد أن الرقم مطابق لـ Sandbox أو رقم موثق في Twilio.</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">رقم الإرسال (From)</label>
                            <input name="from" value="{{ old('from', config('services.twilio.whatsapp_from')) }}" placeholder="whatsapp:+14155238886" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                            <p class="text-xs text-slate-500 mt-1">في Sandbox غالبًا لازم يكون: <span class="font-mono">whatsapp:+14155238886</span></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">رقم العميل (To)</label>
                            <input name="to" value="{{ old('to') }}" placeholder="whatsapp:+2011..." class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                            <p class="text-xs text-slate-500 mt-1">مثال: <span class="font-mono">whatsapp:+201148951078</span></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">نوع الإرسال</label>
                            <select name="mode" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400">
                                <option value="invite_template" @selected(old('mode', 'invite_template') === 'invite_template')>دعوة (Utility Template)</option>
                                <option value="accept" @selected(old('mode') === 'accept')>قبول (تأكيد حضور + كود)</option>
                                <option value="reject" @selected(old('mode') === 'reject')>رفض</option>
                                <option value="custom" @selected(old('mode') === 'custom')>رسالة مخصصة</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">الاسم (name)</label>
                            <input name="name" value="{{ old('name') }}" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">المناسبة (event)</label>
                            <input name="event" value="{{ old('event') }}" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">التاريخ (date)</label>
                            <input name="date" value="{{ old('date') }}" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">نص الرسالة (اختياري)</label>
                        <textarea name="body" rows="6" class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-400" placeholder="اكتب الرسالة هنا...">{{ old('body') }}</textarea>
                        <p class="text-xs text-slate-500 mt-1">
                            - في وضع <span class="font-medium">الدعوة (Template)</span> سيتم تجاهل هذا الحقل.
                            <br>
                            - في وضع <span class="font-medium">القبول</span> إذا تركته فارغًا سيتم استخدام النص الافتراضي مع توليد كود تلقائي.
                        </p>
                    </div>
                </div>

                <div class="p-6 border-t border-slate-200 flex items-center justify-between gap-4">
                    <div class="text-xs text-slate-500">
                        From: <span class="font-mono">{{ config('services.twilio.whatsapp_from') }}</span>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        إرسال
                    </button>
                </div>
            </form>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-2">نصوص افتراضية مقترحة</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                        <div class="font-medium mb-2">قبول</div>
                        <div class="whitespace-pre-wrap text-slate-700">تم تأكيد حضورك 🎉
كود الدخول الخاص بك: @{{code}}
رابط الدخول: https://example.com/event</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                        <div class="font-medium mb-2">رفض</div>
                        <div class="whitespace-pre-wrap text-slate-700">شكرًا على تواصلك معنا، نتمنى لك يومًا سعيدًا!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
