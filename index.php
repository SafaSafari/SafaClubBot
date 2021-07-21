<?php
$update = json_decode(file_get_contents('php://input'));
if (isset($_GET['cron'])) {
    if ($_GET['cron'] === 'collect')
        exit(collect());
    exit(refresh());
}
@$chat_id = $update->message->chat->id ?? $update->callback_query->message->chat->id;
if (empty($chat_id) || $chat_id === -1001014833787) exit();
@$text = $update->message->text ?? $update->callback_query->message->text;
@$text = str_replace(' ', '', $text);
@$text = tofa((string)$text);
@$from_id = $update->message->from->id ?? $update->callback_query->from->id;
@$message_id = $update->message->message_id ?? $update->callback_query->message->message_id;
if (in_array(send('getChatMember', ['chat_id' => -1001014833787, 'user_id' => $from_id])['result']['status'], ['left'])) exit(json_encode(send('sendMessage', ['chat_id' => $from_id, 'text' => 'برای استفاده از این ربات باید در کانال وب آموز عضو شوید' . PHP_EOL . 'کانال وب آموز: @webamoozir' . PHP_EOL . 'بعد از عضو شدن در کانال، روی /start کلیک کنید'])));
$db = new PDO("sqlite:invite.db");
$db->exec('PRAGMA journal_mode=OFF');
$db->exec('PRAGMA synchronous=OFF');
$db->query('CREATE TABLE IF NOT EXISTS invite (id INTEGER,time INTEGER,tedad INTEGER)');
$que = $db->query('SELECT * FROM invite WHERE id=' . $from_id);
$query = ($que !== false) ? $que->fetch() : [];
if (isset($query['time']) && time() - $query['time'] < 10) exit(json_encode(send('sendMessage', ['chat_id' => $chat_id, 'text' => 'لطفا بعد از ' . (10 - (time() - $query['time'])) . ' ثانیه دوباره تلاش کنید'])));
if (!isset($query['time']))
    $db->exec('INSERT INTO invite (id,time,tedad) VALUES ("' . $from_id . '","' . time() . '","0")');
$db->exec('UPDATE invite SET time="' . time() . '" WHERE id=' . $from_id);
if (strtolower($text) === '/start') {
    $text = 'سلام
در این ربات میتوانید بصورت کاملا رایگان دعوتنامه کلاب هاوس بگیرید
برای استفاده از ربات، شماره تلفن مورد نظر را بفرستید
این ربات توسط کانال وب آموز ساخته شده
کلاب وب آموز در کلاب هاوس:
https://www.joinclubhouse.com/club/Webamooz';
    send('sendMessage', ['chat_id' => $chat_id, 'text' => $text]);
} elseif (is_numeric($text) || (isset($update->message->contact))) {
    //    if (5 - $query['tedad'] <= 0) $text = 'متاسفانه دعوت های شما تموم شده';
    //    else {
    if (isset($update->message->contact)) $text = $update->message->contact->phone_number;
    $status = submit($text);
    if ($status === 7) {
        $tedad = $query['tedad'] + 1;
        $text = 'با موفقیت دعوت شدید' . PHP_EOL . 'پیشنهاد میکنیم در کلاب وب آموز عضو شوید:' . PHP_EOL . 'https://www.joinclubhouse.com/club/Webamooz';
        send('sendMessage', ['chat_id' => $chat_id, 'text' => $text, 'reply_to_message_id' => $message_id]);
        $text = 'پاسخ به پرسش های متداول: ' . PHP_EOL . PHP_EOL . '1. بعد از دریافت اس ام اس امکان ورود به برنامه را ندارم' . PHP_EOL . 'بعد از دریافت اس ام اس یکبار اقدام به لاگ اوت و مجددا وارد اپلیکیشن بشید و لاگین کنید اگر مشکل پابرجا بود، شمارتون رو بصورت انگلیسی و بدون فاصله برای ربات بفرستید' . PHP_EOL . PHP_EOL . '2. پیامک ارسال میشه و داخلش لینکی مربوط به نسخه آی او اس کلاب هاوس هست' . PHP_EOL . 'کافیست به لینک داخل پیامک توجهی نکنید و مستقیم وارد برنامه کلاب هاوس بشید' . PHP_EOL . PHP_EOL . '3. موقع لاگین از من ایمیل میخواد' . PHP_EOL . 'شما برنامه اشتباهی را نصب کردید' . PHP_EOL . 'نسخه رسمی کلاب هاوس، قابل نصب روی اندروید 8 و 8 به بالا: https://t.me/safa_club/3' . PHP_EOL . 'نسخه غیر رسمی کلاب هاوس (کلاب هاوز)(تایید نشده از لحاظ امنیتی): https://t.me/MiladNouriChannel/283';
        $db->exec('UPDATE invite SET tedad="' . $tedad . '" WHERE id=' . $from_id);
    } elseif ($status === 1) $text = 'متاسفانه تمام لینکا منقضی شدن' . PHP_EOL . 'هر روز ساعت 7 لینکا تمدید میشن، سعی کنید در این ساعت مراجعه کنید' . PHP_EOL . 'توجه، این خرابی محسوب نمیشه و دلیلش استقبال بسیار زیاد کاربران هست و بزودی درست میشه';
    elseif ($status === 6) $text = 'مشکلی به وجود آمد، لطفا چند دقیقه دیگر دوباره تلاش کنید';
    if ($chat_id === 43390784) $text .= PHP_EOL . $status;
    //    }
    send('sendMessage', ['chat_id' => $chat_id, 'text' => $text, 'reply_to_message_id' => $message_id]);
} elseif ($text === '/s2a' && is_numeric($reply_to_msg_id) && $from_id === 43390784) {
    $members = $db->query('SELECT id FROM invite');
    foreach ($members->fetchAll() as $user_id) {
        $send[] = ['chat_id' => $user_id['id'], 'from_chat_id' => $chat_id, 'message_id' => $reply_to_msg_id];
    }
    $i = 0;
    $db = null;
    foreach ($send as $key => $sen) {
        if (++$i === 25) {
            $i = 0;
            sleep(1);
        }
        $result = send('copyMessage', $sen);
    }
}
function send($api, array $content = [], $post = true)
{
    $token = ''; // YOUR TELEGRAM BOT TOKEN
    $url = 'https://api.telegram.org/bot' . $token . '/' . $api;
    if (isset($content['chat_id'])) {
        $url = $url . '?chat_id=' . $content['chat_id'];
        unset($content['chat_id']);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
function submit(string $phone): int
{
    if (substr($phone, 0, 2) === '00')
        $phone = '+' . substr($phone, 2);
	if (stripos($phone, '+') === false)
        $phone = fixNumber($phone);
    $file = file_get_contents('urls');
    if (!trim($file)) return 1;
    $urls = explode(PHP_EOL, $file);
    $rand = rand(0, count($urls) - 1);
    $url = $urls[$rand];
    $get = get($url);
    if (stripos($get, 'id="phone_number"') === false) {
        unset($urls[$rand]);
        file_put_contents('urls', implode(PHP_EOL, $urls));
        return submit($phone);
    }
    $webpage = get($url, true);
    preg_match('/xhr\.setRequestHeader\(\"X-CSRFToken\"\, \"(.*)\"\);/', $webpage, $result);
    $csrftoken = $result[1];
    $part = explode('/', $url);
    $code = $part[count($part) - 1];
    if(count($part) === 7) $code = $part[count($part) - 2];
    $res = json_decode(get('https://www.joinclubhouse.com/join_club_from_invite', true, ['phone_number' => $phone, 'invite_code' => $code], ['X-CSRFToken: ' . $csrftoken, 'Referer: ' . $url, 'Host: www.joinclubhouse.com']), true);
    unlink('cookie');
    if ($res['success'] === true) return 7;
    return 6;
}
function tofa(string $str): string
{
    return str_replace(str_split('۰۱۲۳۴۵۶۷۸۹', 2), range(0, 9), $str);
}
function collect(): void
{
    $file = explode(PHP_EOL, file_get_contents('collect'));
    $collect = twitter();
    $collect = array_flip(array_flip($collect));
    foreach ($collect as $url) {
        if (!trim($url)) continue;
        $url = 'https://www.joinclubhouse.com/join/' . str_replace('%2F', '/', urlencode(explode('/join/', trim($url))[1]));
        if (!in_array($url, $file)) $file[] = $url;
    }
    file_put_contents('collect', implode(PHP_EOL, $file));
    foreach ($file as $url) {
        $get = get($url);
        if (stripos($get, 'id="phone_number"') !== false) $ok[] = $url;
    }
    $old = explode(PHP_EOL, file_get_contents('urls'));
    if (count($ok) > 0) {
        $final = array_merge($old, $ok);
        $final = array_flip(array_flip($final));
        file_put_contents('urls', implode(PHP_EOL, $final));
    }
}
function refresh(): void
{
    $collect = explode(PHP_EOL, file_get_contents('collect'));
    $ok = [];
//    if(!file_exists('log')) $log = true;
    foreach ($collect as $url) {
        $get = get($url);
//        if($log)
//            file_put_contents('log', $get . PHP_EOL, FILE_APPEND);
        if (stripos($get, 'id="phone_number"') !== false) $ok[] = $url;
    }
    if (count($ok) > 0) {
        $final = array_flip(array_flip($ok));
        file_put_contents('urls', implode(PHP_EOL, $final));
    }
}
function get(string $url, bool $cookie = false, array $data = null, array $header = null): string
{
    global $csrf;
    $ch = curl_init($url);
    $options[CURLOPT_RETURNTRANSFER] = true;
    $options[CURLOPT_FOLLOWLOCATION] = true;
    $options[CURLOPT_HEADER] = true;
    if ($cookie) {
        $options[CURLOPT_COOKIEJAR] = 'cookie';
        $options[CURLOPT_COOKIEFILE] = 'cookie';
    }
    $options[CURLOPT_SSL_VERIFYHOST] = 0;
    $options[CURLOPT_SSL_VERIFYPEER] = 0;
    $options[CURLOPT_ACCEPT_ENCODING] = 'gzip, deflate';
    if ($data) list($options[CURLOPT_POST], $options[CURLOPT_POSTFIELDS]) = [true, http_build_query($data)];
    if ($header) $options[CURLOPT_HTTPHEADER] = $header;
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    $header_size = $info['header_size'];
    curl_close($ch);
    return substr($result, $header_size);
}

function fixNumber(string $number): string
{
    if (preg_match('/(\+98|0)?(\d{9,12})/', $number, $result)) {
        return '+98' . $result[2];
    }
    return false;
}
function twitter(): array
{
    $token = ''; // YOUR TWITTER BEARER TOKEN
    $url = 'https://twitter.com/i/api/2/search/adaptive.json?include_profile_interstitial_type=1&include_blocking=1&include_blocked_by=1&include_followed_by=1&include_want_retweets=1&include_mute_edge=1&include_can_dm=1&include_can_media_tag=1&skip_status=1&cards_platform=Web-12&include_cards=1&include_ext_alt_text=true&include_quote_count=true&include_reply_count=1&tweet_mode=extended&include_entities=true&include_user_entities=true&include_ext_media_color=true&include_ext_media_availability=true&send_error_codes=true&simple_quoted_tweet=true&q=joinclubhouse.com%2Fjoin&tweet_search_mode=live&count=20&query_source=recent_search_click&pc=1&spelling_corrections=1&ext=mediaStats%2ChighlightedLabel';
    $header[] = 'Host: twitter.com';
    $header[] = 'Connection: close';
    $header[] = 'Pragma: no-cache';
    $header[] = 'Cache-Control: no-cache';
    $header[] = 'authorization: Bearer ' . $token;
    $header[] = 'x-twitter-client-language: en';
    $header[] = 'x-csrf-token: a086bfc5fd5e8b9f4a6186cbaf4df1d8d100e5e8b717778fd5b5051c20046fc4265b5416a0fe7abd422d7fc1e92a2619776f4c91e0e04ae076923af2a21e124106551081cebb50db6755c9b402b85ad8';
    $header[] = 'x-twitter-auth-type: OAuth2Session';
    $header[] = 'x-twitter-active-user: no';
    $header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36';
    $header[] = 'Accept: */*';
    $header[] = 'Sec-Fetch-Site: same-origin';
    $header[] = 'Sec-Fetch-Mode: cors';
    $header[] = 'Sec-Fetch-Dest: empty';
    $header[] = 'Referer: https://twitter.com/search?q=joinclubhouse.com%2Fjoin&src=recent_search_click&f=live';
    $header[] = 'Accept-Language: en-US,en;q=0.9';
    $header[] = 'Cookie: personalization_id="v1_Ynx531aiaTUQKJwGmrlbQQ=="; guest_id=v1%3A161751229591012224; gt=1378572693352615937; _sl=1; _ga=GA1.2.123097062.1617512324; _gid=GA1.2.1395358043.1617512324; dnt=1; ads_prefs="HBISAAA="; kdt=cj4UMSl0z2ycsPnF4Xw39qZbhkvs6G9LhZN3Q3fA; remember_checked_on=1; _twitter_sess=BAh7CiIKZmxhc2hJQzonQWN0aW9uQ29udHJvbGxlcjo6Rmxhc2g6OkZsYXNo%250ASGFzaHsABjoKQHVzZWR7ADoPY3JlYXRlZF9hdGwrCBRLP5t4AToMY3NyZl9p%250AZCIlOGJjM2YzNjQ3ZjhlZTFlMzk2NDQ5NGI2ZDI3YWYyZmI6B2lkIiU5Njdk%250ANTYzZjc1MjY0Zjg1MmMwNzRjM2I0NWExNDE5YzoJdXNlcmwrB0SHyMk%253D--953a05a2604faf9079acc1bb39b928361953cefa; auth_token=06e0f3fd40c80a5506937aa550634b85f9e054e6; ct0=a086bfc5fd5e8b9f4a6186cbaf4df1d8d100e5e8b717778fd5b5051c20046fc4265b5416a0fe7abd422d7fc1e92a2619776f4c91e0e04ae076923af2a21e124106551081cebb50db6755c9b402b85ad8; twid=u%3D3385362244; lang=en; night_mode=1';
    $result = json_decode(get($url, false, null, $header), true);
    foreach ($result['globalObjects']['tweets'] as $tweet)
        if (isset($tweet['entities']['urls'][0]))
            foreach ($tweet['entities']['urls'] as $link)
                if (stripos($link['expanded_url'], 'joinclubhouse') !== false)
                    $collect[] = $link['expanded_url'];
    return $collect;
}
