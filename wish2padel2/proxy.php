<?php
header("Content-Type: application/json");

$text = $_POST['text'] ?? $_GET['text'] ?? '';
if (!$text) {
    echo json_encode(["translatedText" => ""]);
    exit;
}

// cURL helper
function curlRequest($url, $postFields = null, $isJson = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0"); // Fake browser

    if ($postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $isJson ? json_encode($postFields) : $postFields);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        }
    }

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// API 1: LibreTranslate
function translateLibre($text) {
    $data = ["q" => $text, "source" => "en", "target" => "ar", "format" => "text"];
    $res = curlRequest("https://libretranslate.de/translate", $data, true);
    $json = json_decode($res, true);
    return $json['translatedText'] ?? false;
}

// API 2: Google Translate (tanpa API key)
function translateGoogleFree($text) {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=" . urlencode($text);
    $res = curlRequest($url);
    $json = json_decode($res, true);
    return $json[0][0][0] ?? false;
}

// API 3: MyMemory
function translateMyMemory($text) {
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=en|ar";
    $res = curlRequest($url);
    $json = json_decode($res, true);
    return $json['responseData']['translatedText'] ?? false;
}

// Jalankan berurutan
$translated = translateLibre($text);
if (!$translated) $translated = translateGoogleFree($text);
if (!$translated) $translated = translateMyMemory($text);

// Kalau tetap gagal → balikin teks asli
echo json_encode(["translatedText" => $translated ?: $text]);
