<?php

define('IN_SITE', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'status' => 'managed',
    'project' => $config['project'],
    'version' => $config['version'],
    'message' => 'Phiên bản Smartstudy.vn được quản lý nội bộ. Không có kết nối cập nhật từ xa.'
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

