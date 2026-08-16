<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/../libs/session.php';
}

SecurityValidator::enforce('__CORE__', 'SMARTSTUDY:bootstrap');

$SMARTSTUDY = new DB();

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

