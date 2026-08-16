<?php

    define("IN_SITE", true);
    require_once(__DIR__.'/../../libs/db.php');
    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/../../libs/lang.php');
    require_once(__DIR__.'/../../libs/helper.php');
    require_once(__DIR__.'/../../libs/suppliers.php');
    $SMARTSTUDY = new DB();

    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($SMARTSTUDY->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $SMARTSTUDY->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }

    /* START CHỐNG SPAM */
    $elapsed = time() - (int)$SMARTSTUDY->site('time_cron_suppliers_api27');
    if ($elapsed >= 0 && $elapsed < 2) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $SMARTSTUDY->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_suppliers_api27' ");



    foreach($SMARTSTUDY->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_27']) as $supplier){

  
        // CURL LẤY SẢN PHẨM
        $result = curl_get($supplier['domain'].'api/get_services');
        $result = json_decode($result, true);
        
        // Kiểm tra nếu kết quả là một mảng
        if(is_array($result)){
            foreach($result as $api){
                // Bỏ qua các sản phẩm có tên chứa từ "rent_"
                if(strpos($api['name_service'], 'rent_') !== false){ 
                    continue;
                }
                
                $api_id = check_string($api['name_service']);
                $api_name = check_string($api['name_service']); // Thay đổi từ 'name' thành 'name_service'
                $api_stock = 999; // Giá trị mặc định vì không có trong JSON mới
                $api_desc = NULL;
                $api_price = intval(check_string($api['price'])); // Thay đổi từ 'price_wmz' thành 'price'
                $ck = $api_price * $supplier['discount'] / 100;
                $price = intval(check_string($api['price']));
                if($supplier['update_price'] == 'ON'){
                    // CẬP NHẬT GIÁ BÁN
                    if($supplier['roundMoney'] == 'ON'){
                        // LÀM TRÒN GIÁ BÁN
                        $price = roundMoney($api_price + $ck);
                    }else{
                        $price = $api_price + $ck;
                    } 
                } 
                if(!$product = $SMARTSTUDY->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])){
                    // THÊM SẢN PHẨM
                    $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                    $SMARTSTUDY->insert('products', [
                        'user_id'           => $supplier['user_id'],
                        'category_id'       => 0,
                        'supplier_id'       => $supplier['id'],
                        'name'              => $api_name,
                        'slug'              => create_slug($api_name.' '.$api_id),
                        'short_desc'        => $api_desc,
                        'price'             => $price,
                        'status'            => $product_status,
                        'cost'              => $api_price,
                        'api_id'            => $api_id,
                        'api_name'          => $api_name,
                        'api_stock'         => $api_stock,
                        'api_time_update'   => time(),
                        'create_gettime'    => gettime(),
                        'update_gettime'   => gettime()
                    ]);
                    if($SMARTSTUDY->site('debug_api_suppliers') == 1){
                        echo '<b style="color:red;">CREATE</b> - Tạo sản phẩm '.$api_name.' thành công !<br>';
                    }
                }else{
                    // CẬP NHẬT SẢN PHẨM
                    $api_name = check_string($api['name_service']); // Thay đổi từ 'name' thành 'name_service'
                    $api_stock = 999; // Giá trị mặc định vì không có trong JSON mới
                    $api_desc = NULL;
                    $api_price = intval(check_string($api['price'])); // Thay đổi từ 'price_wmz' thành 'price'
                    $ck = $api_price * $supplier['discount'] / 100;

                    $price = $product['price'];
                    if($supplier['update_price'] == 'ON'){
                        // CẬP NHẬT GIÁ BÁN
                        if($supplier['roundMoney'] == 'ON'){
                            // LÀM TRÒN GIÁ BÁN
                            $price = roundMoney($api_price + $ck);
                        }else{
                            $price = $api_price + $ck;
                        } 
                    } 
                    $product_name = $api_name;
                    $product_desc = $api_desc;
                    if($supplier['update_name'] == 'OFF'){
                        $product_name = $product['name'];
                        $product_desc = $product['short_desc'];
                    }
                    $SMARTSTUDY->update('products', [
                        'price'         => $price,
                        'name'          => $product_name,
                        'slug'          => create_slug($product_name.' '.$api_id),
                        'short_desc'    => $product_desc,
                        'cost'          => $api_price,
                        'api_name'      => $api_name,
                        'api_time_update'    => time(),
                        'api_stock'     => $api_stock
                    ], " `id` = '".$product['id']."' ");
                    if($SMARTSTUDY->site('debug_api_suppliers') == 1){
                        echo '<b style="color:green;">UPDATE</b> - sản phẩm '.$api_name.' thành công !<br>';
                    }
                }
               
            }
            $SMARTSTUDY->remove('products', " `supplier_id` = '".$supplier['id']."' AND ".time()." - `api_time_update` >= 3600 ");
        }

    }
