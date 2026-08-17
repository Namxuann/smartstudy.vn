<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (isset($_GET['slug'])) {
    $slug = validate_slug($_GET['slug'], 255);
    if ($slug === false) {
        redirect(base_url());
    }
    if (!$product = $SMARTSTUDY->get_row_safe("SELECT * FROM `products` WHERE `slug` = ? AND `status` = 1 AND `product_type` = 'course'", [$slug])) {
        redirect(base_url());
    }
} else {
    redirect(base_url());
}

// Load course data from native courses table via product_id
require_once(__DIR__ . '/../../libs/database/courses.php');
require_once(__DIR__ . '/../../libs/database/enrollments.php');
$coursesDB = new Courses();
$enrollmentsDB = new Enrollments();

$courseData = $coursesDB->getCourseByProductId($product['id']);

$body = [
    'title' => __($product['name']).' | '.$SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '<link rel="stylesheet" href="'.BASE_URL('public/client/css/course-detail.css').'">';
$body['footer'] = '';

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');

$isEnrolled = false;
if(isset($getUser) && $courseData) {
    $isEnrolled = $enrollmentsDB->isEnrolled($getUser['id'], $courseData['id']);
}
?>

<section class="course-hero section py-5" style="background:#f8f9fa;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-3"><?=__($product['name']);?></h1>
                <p class="lead text-muted mb-4"><?=str_replace(PHP_EOL, '<br>', $product['short_desc']);?></p>
                <div class="d-flex align-items-center mb-4">
                    <span class="badge bg-primary me-3">Online</span>
                    <span class="text-muted"><i class="fa fa-users me-1"></i> <?=$product['sold'];?> Học viên</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <?php $image = explode(PHP_EOL, $product['images'])[0] ?? ''; ?>
                    <img src="<?=base_url(dirImageProduct($image));?>" class="card-img-top" alt="Course Image">
                    <div class="card-body p-4 text-center">
                        <h3 class="mb-4">
                            <?=$product['discount'] > 0 ? '<del class="text-muted fs-5">'.format_currency($product['price']).'</del> ' : '';?>
                            <span class="text-primary fw-bold"><?=format_currency($product['price'] - ($product['price'] * $product['discount'] / 100));?></span>
                        </h3>
                        
                        <?php if($isEnrolled && $courseData): ?>
                            <a href="<?=base_url('client/learning?course_id='.$courseData['id']);?>" class="btn btn-success btn-lg w-100 rounded-pill">Vào học ngay</a>
                        <?php else: ?>
                            <button id="openModal_<?=$product['id'];?>" onclick="openModal(`<?=isset($getUser) ? $getUser['token'] : NULL;?>`, `<?=$product['id'];?>`, `<?=$product['preview_uid'] ?? 0;?>`)" class="btn btn-primary btn-lg w-100 rounded-pill"><i class="fa-solid fa-cart-shopping"></i> Mua ngay</button>
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
                    <?=base64_decode($product['description']);?>
                </div>

                <h3 class="mb-4">Chương trình học</h3>
                <div class="accordion" id="curriculumAccordion">
                    <?php
                    if ($courseData) {
                        $sections = $coursesDB->getSections($courseData['id']);
                        foreach($sections as $index => $section) {
                            $lessons = $coursesDB->getLessons($section['id']);
                    ?>
                    <div class="accordion-item mb-3 border">
                        <h2 class="accordion-header" id="heading<?=$section['id'];?>">
                            <button class="accordion-button <?= $index == 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?=$section['id'];?>" aria-expanded="<?= $index == 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?=$section['id'];?>">
                                <strong class="me-2">Chương <?=$index+1;?>:</strong> <?=__($section['title']);?>
                                <span class="ms-auto badge bg-light text-dark rounded-pill"><?=count($lessons);?> bài học</span>
                            </button>
                        </h2>
                        <div id="collapse<?=$section['id'];?>" class="accordion-collapse collapse <?= $index == 0 ? 'show' : ''; ?>" aria-labelledby="heading<?=$section['id'];?>">
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
                    <?php 
                        }
                    } else {
                    ?>
                    <div class="text-center text-muted py-4">
                        <p>Chương trình học đang được cập nhật...</p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once(__DIR__.'/footer.php'); ?>
