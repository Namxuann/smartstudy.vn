<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (isset($_GET['slug'])) {
    $slug = validate_slug($_GET['slug'], 255);
    if ($slug === false) {
        redirect(base_url());
    }
    if (!$course = $SMARTSTUDY->get_row_safe("SELECT * FROM `products` WHERE `slug` = ? AND `status` = 1 AND `product_type` = 'course'", [$slug])) {
        redirect(base_url());
    }
} else {
    redirect(base_url());
}

$body = [
    'title' => __($course['name']).' | '.$SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '<link rel="stylesheet" href="'.BASE_URL('public/client/css/course-detail.css').'">';
$body['footer'] = '';

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');

$isEnrolled = false;
if(isset($getUser)) {
    $enroll = $SMARTSTUDY->get_row_safe("SELECT * FROM `enrollments` WHERE `user_id` = ? AND `course_id` = ?", [$getUser['id'], $course['id']]);
    if($enroll) $isEnrolled = true;
}
?>

<section class="course-hero section py-5" style="background:#f8f9fa;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-3"><?=__($course['name']);?></h1>
                <p class="lead text-muted mb-4"><?=str_replace(PHP_EOL, '<br>', $course['short_desc']);?></p>
                <div class="d-flex align-items-center mb-4">
                    <span class="badge bg-primary me-3">Online</span>
                    <span class="text-muted"><i class="fa fa-users me-1"></i> <?=$course['sold'];?> Học viên</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <?php $image = explode(PHP_EOL, $course['images'])[0] ?? ''; ?>
                    <img src="<?=base_url(dirImageProduct($image));?>" class="card-img-top" alt="Course Image">
                    <div class="card-body p-4 text-center">
                        <h3 class="mb-4">
                            <?=$course['discount'] > 0 ? '<del class="text-muted fs-5">'.format_currency($course['price']).'</del> ' : '';?>
                            <span class="text-primary fw-bold"><?=format_currency($course['price'] - ($course['price'] * $course['discount'] / 100));?></span>
                        </h3>
                        
                        <?php if($isEnrolled): ?>
                            <a href="<?=base_url('client/learning?course_id='.$course['id']);?>" class="btn btn-success btn-lg w-100 rounded-pill">Vào học ngay</a>
                        <?php else: ?>
                            <button id="openModal_<?=$course['id'];?>" onclick="openModal(`<?=isset($getUser) ? $getUser['token'] : NULL;?>`, `<?=$course['id'];?>`, `<?=$course['preview_uid'] ?? 0;?>`)" class="btn btn-primary btn-lg w-100 rounded-pill"><i class="fa-solid fa-cart-shopping"></i> Mua ngay</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="course-content section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h3 class="mb-4">Mô tả khoá học</h3>
                <div class="content-html mb-5">
                    <?=base64_decode($course['description']);?>
                </div>

                <h3 class="mb-4">Chương trình học</h3>
                <div class="accordion" id="curriculumAccordion">
                    <?php
                    $modules = $SMARTSTUDY->get_list("SELECT * FROM `course_modules` WHERE `course_id` = ? ORDER BY `sort_order` ASC", [$course['id']]);
                    foreach($modules as $index => $module) {
                        $lessons = $SMARTSTUDY->get_list("SELECT * FROM `course_lessons` WHERE `module_id` = ? ORDER BY `sort_order` ASC", [$module['id']]);
                    ?>
                    <div class="accordion-item mb-3 border">
                        <h2 class="accordion-header" id="heading<?=$module['id'];?>">
                            <button class="accordion-button <?= $index == 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?=$module['id'];?>" aria-expanded="<?= $index == 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?=$module['id'];?>">
                                <strong class="me-2">Chương <?=$index+1;?>:</strong> <?=__($module['title']);?>
                                <span class="ms-auto badge bg-light text-dark rounded-pill"><?=count($lessons);?> bài học</span>
                            </button>
                        </h2>
                        <div id="collapse<?=$module['id'];?>" class="accordion-collapse collapse <?= $index == 0 ? 'show' : ''; ?>" aria-labelledby="heading<?=$module['id'];?>">
                            <div class="accordion-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php foreach($lessons as $lesson): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <i class="fa fa-play-circle text-primary me-2"></i>
                                            <?=__($lesson['title']);?>
                                        </div>
                                        <div>
                                            <?php if($lesson['is_free_preview']): ?>
                                            <span class="badge bg-success me-2">Xem miễn phí</span>
                                            <?php endif; ?>
                                            <i class="fa fa-lock text-muted"></i>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once(__DIR__.'/footer.php'); ?>
