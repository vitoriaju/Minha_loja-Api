<?php
function flash_set($key, $msg) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
    if (!isset($_SESSION)) session_start();
    if (!empty($_SESSION['flash'][$key])) {
        $v = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    return null;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
