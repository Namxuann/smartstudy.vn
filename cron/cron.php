<?php

define("IN_SITE", true);


require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
$SMARTSTUDY = new DB();

// Nếu có đặt key cron job thì kiểm tra key hợp lệ
if (!empty($SMARTSTUDY->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $SMARTSTUDY->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* START CHỐNG SPAM */
$elapsed = time() - (int)$SMARTSTUDY->site('check_time_cron_cron');
    if ($elapsed >= 0 && $elapsed < 3) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$SMARTSTUDY->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_cron' ");


// Task chỉ xử lý mỗi 24 giờ
if (time() > $SMARTSTUDY->site('task_24h')) {
    if (time() - $SMARTSTUDY->site('task_24h') > 86400) {
        $SMARTSTUDY->update("settings", [
            'value' => time()
        ], " `name` = 'task_24h' ");

        // Dọn dẹp failed_attempts
        $isRemove = $SMARTSTUDY->remove('failed_attempts', " `create_gettime` <= NOW() - INTERVAL 1 DAY ");
        if ($isRemove) {
            $SMARTSTUDY->insert("logs", [
                'user_id'     => 0, // 0 = log hệ thống
                'action'      => "Hệ thống thực hiện dọn dẹp failed_attempts sau mỗi 24 giờ",
                'createdate'  => gettime(),
                'ip'          => myip(),
                'device'      => getUserAgent()
            ]);
        }

    }
}


if ($SMARTSTUDY->site('status_tao_gd_ao') == 1) {
    /** NẠP TIỀN ẢO */
    $int_rand = rand(0, $SMARTSTUDY->site('toc_do_gd_nap_ao'));
    if ($int_rand == $SMARTSTUDY->site('toc_do_gd_nap_ao')) {
        $array_amount = explode(PHP_EOL, $SMARTSTUDY->site('menh_gia_nap_ao_ngau_nhien'));
        $array_method = explode(PHP_EOL, $SMARTSTUDY->site('method_nap_ao'));
        $amount = $array_amount[rand(0, count($array_amount) - 1)];
        $amount = $amount != 0 ? $amount : 10000;
        $method = $array_method[rand(0, count($array_method) - 1)];
        $SMARTSTUDY->insert("deposit_log", [
            'user_id'           => $SMARTSTUDY->get_row_safe("SELECT * FROM `users` ORDER BY RAND() LIMIT 1", [])['id'],
            'method'            => $method,
            'amount'            => (float)$amount,
            'received'          => (float)$amount,
            'create_time'       => time(),
            'is_virtual'        => 1
        ]);
    }
    /** MUA HÀNG ẢO */
    $int_rand = rand(0, $SMARTSTUDY->site('toc_do_gd_mua_ao'));
    if ($int_rand == $SMARTSTUDY->site('toc_do_gd_mua_ao')) {
        $amount = rand($SMARTSTUDY->site('sl_mua_toi_thieu_gd_ao'), $SMARTSTUDY->site('sl_mua_toi_da_gd_ao'));
        $trans_id = random("QWERTYUPASDFGHJKZXCVBNM123456789", 4);
        foreach ($SMARTSTUDY->get_list_safe("SELECT * FROM `products` WHERE `status` = ? ORDER BY RAND() ", [1]) as $product) {
            if ($SMARTSTUDY->site('tao_gd_ao_sp_het_hang') == 1) {
                $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
                if ($stock == 0) {
                    continue;
                }
            }
            // TẠO LOG GIAO DỊCH GẦN ĐÂY
            $SMARTSTUDY->insert('order_log', [
                'buyer'         => $SMARTSTUDY->get_row_safe("SELECT * FROM `users` ORDER BY RAND() LIMIT 1", [])['id'],
                'product_name'  => $product['name'],
                'pay'           => $amount * $product['price'],
                'amount'        => $amount,
                'create_time'   => time(),
                'is_virtual'    => 1
            ]);
            break;
        }
    }
}

$SMARTSTUDY->remove('deposit_log', " " . time() . " - `create_time` >= 604800 ");
$SMARTSTUDY->remove('order_log', " " . time() . " - `create_time` >= 604800 ");



// Thêm các URL của trang web của bạn vào mảng này
$urls = array();
$urls[] = base_url(); // Thêm liên kết trang chủ của website vào sitemap
$urls[] = base_url('tool/check-live-facebook');
$urls[] = base_url('tool/get-2fa');
$urls[] = base_url('tool/icon-facebook');
$urls[] = base_url('tool/random-face');

foreach ($SMARTSTUDY->get_list_safe(" SELECT * FROM categories WHERE `status` = ? ", [1]) as $category) {
    $urls[] = base_url('category/' . $category['slug']);
}
foreach ($SMARTSTUDY->get_list_safe(" SELECT * FROM products WHERE `status` = ? ", [1]) as $product) {
    $urls[] = base_url('product/' . $product['slug']);
}
foreach ($SMARTSTUDY->get_list_safe(" SELECT * FROM posts WHERE `status` = ? ", [1]) as $blog) {
    $urls[] = base_url('blog/' . $blog['slug']);
}
// Tạo tệp XML mới
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;
// Tạo phần tử gốc <urlset> cho sitemap
$urlset = $xml->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
// Thêm các URL vào phần tử gốc <urlset>
foreach ($urls as $url) {
    // Đảm bảo URL trong sitemap luôn sử dụng giao thức bảo mật HTTPS
    if (strpos($url, 'http://') === 0) {
        $url = str_replace('http://', 'https://', $url);
    }
    $urlElement = $xml->createElement('url');
    $locElement = $xml->createElement('loc', htmlspecialchars($url));
    $urlElement->appendChild($locElement);
    $urlset->appendChild($urlElement);
}
$xml->appendChild($urlset);
// Lưu sitemap vào một tệp
$xml->save('../sitemap.xml');
