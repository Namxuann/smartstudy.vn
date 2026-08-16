<?php

define('IN_SITE', true);
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../libs/lms_bridge.php');

$SMARTSTUDY = new DB();
$isCli = PHP_SAPI === 'cli';
$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
if (!$isCli && !hash_equals((string)$SMARTSTUDY->site('key_cron_job'), $key)) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'    => 'success',
    'processed' => smartstudy_process_enrollment_queue($SMARTSTUDY, 20)
]);
