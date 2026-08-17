<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Khoá học của tôi | ' . $SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
$body['noindex'] = true;

require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/../../libs/database/courses.php');
require_once(__DIR__ . '/../../libs/database/enrollments.php');

$coursesDB = new Courses();
$enrollmentsDB = new Enrollments();

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<div style="margin-bottom:40px;"></div>
<section class="inner-section" style="margin-bottom:40px;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-heading">
                    <h2>Khoá học của tôi</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <?php 
            $enrollments = $enrollmentsDB->getUserEnrollments($getUser['id']);
            if(count($enrollments) > 0) {
                foreach($enrollments as $enroll) {
                    $progress = $enrollmentsDB->getCourseProgress($getUser['id'], $enroll['course_id']);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="product-card">
                    <div class="product-media">
                        <a href="<?=base_url('client/learning?course_id='.$enroll['course_id']);?>">
                            <img src="<?=base_url($enroll['featured_image'] ? $enroll['featured_image'] : 'mod/img/placeholder-course.png');?>" alt="course">
                        </a>
                    </div>
                    <div class="product-content">
                        <h6 class="product-name">
                            <a href="<?=base_url('client/learning?course_id='.$enroll['course_id']);?>"><?=__($enroll['title']);?></a>
                        </h6>
                        <div class="progress" style="height: 10px; margin: 10px 0;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?=$progress;?>%;" aria-valuenow="<?=$progress;?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted"><small>Hoàn thành: <?=$progress;?>%</small></p>
                        <div class="product-action">
                            <a href="<?=base_url('client/learning?course_id='.$enroll['course_id']);?>" class="btn btn-primary w-100">
                                <?=$progress >= 100 ? 'Học lại' : 'Tiếp tục học';?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else { 
            ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-graduation-cap fs-1 text-muted mb-3" style="font-size: 4rem !important;"></i>
                <h4>Bạn chưa đăng ký khoá học nào</h4>
                <a href="<?=base_url();?>" class="btn btn-primary mt-3">Khám phá các khoá học</a>
            </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php require_once(__DIR__ . '/footer.php'); ?>
