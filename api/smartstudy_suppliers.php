<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');

echo json_encode([
    'suppliers' => [],
    'notication' => 'Bạn có thể kết nối trực tiếp nhà cung cấp API trong biểu mẫu phía trên.'
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

