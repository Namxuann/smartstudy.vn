<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Liên hệ').' | '.$SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/contact.css">
';
$body['footer'] = '

';

if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');

?>
<section class="inner-section single-banner"
    style="background: url('<?=base_url($SMARTSTUDY->site('banner_singer'));?>') no-repeat center;">
    <div class="container">
        <h2><?=__('Liên Hệ');?></h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?=base_url();?>"><?=__('Trang chủ');?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?=__('Liên hệ');?></li>
        </ol>
    </div>
</section>
<section class="inner-section contact-part">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="contact-card"><i class="icofont-location-pin"></i>
                    <h4><?=__('Address');?></h4>
                    <p><?=$SMARTSTUDY->site('address');?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="contact-card active"><i class="icofont-phone"></i>
                    <h4><?=__('Hotline');?></h4>
                    <p><?=$SMARTSTUDY->site('hotline');?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="contact-card"><i class="icofont-email"></i>
                    <h4><?=__('Support Mail');?></h4>
                    <p><?=$SMARTSTUDY->site('email');?></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="account-card pt-4">
                    <?=$SMARTSTUDY->site('page_contact');?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once(__DIR__.'/footer.php');
?>