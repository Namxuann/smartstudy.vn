<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/../../libs/lms_bridge.php');

redirect(smartstudy_lms_sso_url($getUser));
