<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/../../libs/database/courses.php');
require_once(__DIR__ . '/../../libs/database/enrollments.php');

$coursesDB = new Courses();
$enrollmentsDB = new Enrollments();

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
if (!$course_id) redirect(base_url('client/my-courses'));

// Check enrollment using native course_enrollments table
if (!$enrollmentsDB->isEnrolled($getUser['id'], $course_id)) {
    // Not enrolled - try to redirect to course detail page
    $course = $coursesDB->select_by_id($course_id);
    if ($course && $course['product_id']) {
        $product = $SMARTSTUDY->get_row_safe("SELECT slug FROM `products` WHERE `id` = ?", [$course['product_id']]);
        if ($product) redirect(base_url('course/'.$product['slug']));
    }
    redirect(base_url('client/my-courses'));
}

// Get course info from native courses table
$course = $coursesDB->select_by_id($course_id);
if (!$course) redirect(base_url('client/my-courses'));

$initialLessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

// Calculate progress dynamically
$progressPercent = $enrollmentsDB->getCourseProgress($getUser['id'], $course_id);

$body = [
    'title' => __($course['title']).' | Learning',
    'noindex' => true
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$body['title'];?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <link rel="stylesheet" href="<?=BASE_URL('public/client/css/learning.css');?>">
    <script>
        const baseUrl = '<?=base_url();?>';
        const BASE_URL = '<?=BASE_URL('');?>';
    </script>
</head>
<body class="learning-body">

    <div class="learning-topbar">
        <div class="d-flex align-items-center h-100 px-3">
            <button class="sidebar-toggle btn btn-light me-3 d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <a href="<?=base_url('client/my-courses');?>" class="back-btn text-decoration-none text-dark me-4">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
            <h5 class="course-title m-0 text-truncate d-none d-md-block" style="max-width: 400px;"><?=__($course['title']);?></h5>
            
            <div class="progress-indicator ms-auto d-flex align-items-center">
                <div class="progress" style="width: 150px; height: 10px; margin-right: 10px;">
                    <div class="progress-bar bg-success" id="courseProgressBar" role="progressbar" style="width: <?=$progressPercent;?>%"></div>
                </div>
                <span class="progress-text fw-bold" id="courseProgressText"><?=$progressPercent;?>%</span>
            </div>
        </div>
    </div>

    <div class="learning-container">
        <aside class="learning-sidebar" id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="m-0 fw-bold">Nội dung khoá học</h6>
                <button class="sidebar-close btn btn-sm btn-light d-lg-none" id="sidebarClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="curriculum-list accordion accordion-flush" id="curriculumList">
                <!-- Curriculum loaded via JS -->
            </div>
        </aside>

        <main class="learning-content position-relative d-flex flex-column" id="mainContent">
            <div class="lesson-content-wrapper flex-grow-1 p-3 p-md-5" id="lessonContent">
                <!-- Content loaded via JS -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <div class="lesson-attachments px-3 px-md-5 pb-4" id="attachments" style="display: none;">
                <!-- Attachments -->
            </div>

            <div class="lesson-nav d-flex justify-content-between align-items-center p-3 p-md-4 border-top mt-auto bg-white sticky-bottom">
                <button class="btn btn-outline-secondary" id="prevLesson"><i class="fas fa-arrow-left me-2"></i> Bài trước</button>
                <button class="btn btn-success btn-complete" id="markComplete"><i class="fas fa-check me-2"></i> Hoàn thành bài học</button>
                <button class="btn btn-primary" id="nextLesson">Bài tiếp theo <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const COURSE_ID = <?=$course_id;?>;
        const INITIAL_LESSON_ID = <?=$initialLessonId ? $initialLessonId : 'null';?>;
    </script>
    <script src="<?=BASE_URL('public/client/js/learning.js');?>"></script>
</body>
</html>
