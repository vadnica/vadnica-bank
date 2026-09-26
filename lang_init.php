<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_langs = ['sl', 'en', 'de', 'it'];
$lang = 'sl';

// 1. Preveri parameter v URL-ju (?lang=en)
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('user_lang', $lang, time() + (30 * 24 * 60 * 60), '/');
}
// 2. Preveri sejo ali piškotek
elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['user_lang']) && in_array($_COOKIE['user_lang'], $allowed_langs)) {
    $lang = $_COOKIE['user_lang'];
}

$lang_file = __DIR__ . "/lang/{$lang}.php";
$txt = file_exists($lang_file) ? require $lang_file : require __DIR__ . "/lang/sl.php";
