<?php

    define("IN_SITE", true);
    require_once(__DIR__.'/../../libs/db.php');
    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/../../libs/lang.php');
    require_once(__DIR__.'/../../libs/helper.php');
    $SMARTSTUDY = new DB();

    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($SMARTSTUDY->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $SMARTSTUDY->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }

   