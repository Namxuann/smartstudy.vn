<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function smartstudy_lms_base_url()
{
    return rtrim($_ENV['LMS_URL'] ?? 'https://learn.smartstudy.vn', '/');
}

function smartstudy_lms_secret()
{
    return (string)($_ENV['LMS_BRIDGE_SECRET'] ?? '');
}

function smartstudy_base64url_encode($value)
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function smartstudy_lms_sso_url(array $user, $redirect = '/courses/')
{
    $secret = smartstudy_lms_secret();
    if ($secret === '') {
        return smartstudy_lms_base_url();
    }

    $payload = smartstudy_base64url_encode(json_encode([
        'uid'      => (int)$user['id'],
        'email'    => (string)$user['email'],
        'username' => (string)$user['username'],
        'name'     => (string)($user['fullname'] ?? $user['username']),
        'redirect' => (string)$redirect,
        'iat'      => time(),
        'exp'      => time() + 120,
        'jti'      => bin2hex(random_bytes(16))
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $signature = hash_hmac('sha256', $payload, $secret);

    return smartstudy_lms_base_url() . '/?smartstudy_sso=1&token=' . rawurlencode($payload) . '&sig=' . rawurlencode($signature);
}

function smartstudy_enqueue_enrollment($SMARTSTUDY, $orderId, $transId, array $product, array $user)
{
    $now = gettime();
    $jobId = $SMARTSTUDY->insert('lms_enrollment_jobs', [
        'order_id'        => (int)$orderId,
        'trans_id'        => (string)$transId,
        'product_id'      => (int)$product['id'],
        'user_id'         => (int)$user['id'],
        'email'           => (string)$user['email'],
        'username'        => (string)$user['username'],
        'full_name'       => (string)($user['fullname'] ?? $user['username']),
        'course_id'       => (int)$product['lms_course_id'],
        'status'          => 'pending',
        'attempts'        => 0,
        'last_error'      => null,
        'next_attempt_at' => $now,
        'created_at'      => $now,
        'updated_at'      => $now
    ]);

    if (!$jobId) {
        $existing = $SMARTSTUDY->get_row_safe(
            'SELECT `id`, `order_id` FROM `lms_enrollment_jobs` WHERE (`order_id` = ? OR `user_id` = ?) AND `course_id` = ?',
            [(int)$orderId, (int)$user['id'], (int)$product['lms_course_id']]
        );
        if (!$existing) {
            return 0;
        }
        return (int)$existing['order_id'] === (int)$orderId
            ? (int)$existing['id']
            : -(int)$existing['id'];
    }
    return (int)$jobId;
}

function smartstudy_deliver_enrollment($SMARTSTUDY, $jobId)
{
    $job = $SMARTSTUDY->get_row_safe('SELECT * FROM `lms_enrollment_jobs` WHERE `id` = ?', [(int)$jobId]);
    if (!$job || $job['status'] === 'delivered') {
        return $job && $job['status'] === 'delivered';
    }

    $secret = smartstudy_lms_secret();
    if ($secret === '') {
        smartstudy_reschedule_enrollment($SMARTSTUDY, $job, 'LMS_BRIDGE_SECRET chưa được cấu hình');
        return false;
    }

    $payload = json_encode([
        'order_id'   => (int)$job['order_id'],
        'trans_id'   => (string)$job['trans_id'],
        'product_id' => (int)$job['product_id'],
        'user_id'    => (int)$job['user_id'],
        'email'      => (string)$job['email'],
        'username'   => (string)$job['username'],
        'full_name'  => (string)$job['full_name'],
        'course_id'  => (int)$job['course_id']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $timestamp = (string)time();
    $signature = hash_hmac('sha256', $timestamp . "\n" . $payload, $secret);

    $SMARTSTUDY->update('lms_enrollment_jobs', [
        'status'     => 'processing',
        'updated_at' => gettime()
    ], ' `id` = ?', [(int)$jobId]);

    $ch = curl_init(smartstudy_lms_base_url() . '/wp-json/smartstudy/v1/enroll');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Smartstudy-Timestamp: ' . $timestamp,
            'X-Smartstudy-Signature: ' . $signature
        ],
        CURLOPT_POSTFIELDS     => $payload
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $SMARTSTUDY->update('lms_enrollment_jobs', [
            'status'          => 'delivered',
            'attempts'        => (int)$job['attempts'] + 1,
            'last_error'      => null,
            'next_attempt_at' => null,
            'updated_at'      => gettime()
        ], ' `id` = ?', [(int)$jobId]);
        return true;
    }

    $message = $curlError !== '' ? $curlError : 'HTTP ' . $httpCode . ': ' . mb_substr((string)$response, 0, 500);
    smartstudy_reschedule_enrollment($SMARTSTUDY, $job, $message);
    return false;
}

function smartstudy_reschedule_enrollment($SMARTSTUDY, array $job, $error)
{
    $attempts = (int)$job['attempts'] + 1;
    $delayMinutes = min(60, max(2, (int)pow(2, min(5, $attempts))));
    $status = $attempts >= 20 ? 'failed' : 'retry';
    $SMARTSTUDY->update('lms_enrollment_jobs', [
        'status'          => $status,
        'attempts'        => $attempts,
        'last_error'      => mb_substr((string)$error, 0, 2000),
        'next_attempt_at' => date('Y-m-d H:i:s', time() + ($delayMinutes * 60)),
        'updated_at'      => gettime()
    ], ' `id` = ?', [(int)$job['id']]);
}

function smartstudy_process_enrollment_queue($SMARTSTUDY, $limit = 20)
{
    $jobs = $SMARTSTUDY->get_list_safe(
        "SELECT `id` FROM `lms_enrollment_jobs` WHERE `status` IN ('pending', 'retry') AND (`next_attempt_at` IS NULL OR `next_attempt_at` <= ?) ORDER BY `id` ASC LIMIT ?",
        [gettime(), (int)$limit]
    );
    $processed = 0;
    foreach ($jobs as $job) {
        smartstudy_deliver_enrollment($SMARTSTUDY, (int)$job['id']);
        $processed++;
    }
    return $processed;
}
