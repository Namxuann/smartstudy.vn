<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Course Builder') . ' | ' . $SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '
    <link rel="stylesheet" href="' . BASE_URL('public/client/css/course-builder.css') . '">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
';
$body['footer'] = '
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>const BASE_URL = "' . base_url() . '";</script>
    <script src="' . BASE_URL('public/client/js/course-builder.js') . '?v=' . filemtime(__DIR__ . '/../../public/client/js/course-builder.js') . '"></script>
';

require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');

if (checkPermission($getUser['admin'], 'edit_course') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

$canManageStudents = checkPermission($getUser['admin'], 'manage_students_course');

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = $course_id > 0 ? 'edit' : 'create';
$course = [];
if ($mode == 'edit') {
    // $course = fetch course from db... (placeholder logic as we use ajax)
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page header breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tools"></i> <?= $mode == 'edit' ? 'Chỉnh sửa khoá học' : 'Tạo khoá học mới' ?></h1>
            <div class="d-flex">
                <a href="<?= base_url_admin('courses') ?>" class="btn btn-sm btn-secondary btn-wave me-2"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                <button type="button" id="btn-save-course" class="btn btn-sm btn-primary btn-wave"><i class="fa-solid fa-save"></i> Lưu khoá học</button>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="courseBuilderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Thông tin khoá học</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $mode == 'create' ? 'disabled' : '' ?>" id="curriculum-tab" data-bs-toggle="tab" data-bs-target="#curriculum" type="button" role="tab" aria-controls="curriculum" aria-selected="false" <?= $mode == 'create' ? 'disabled' : '' ?>>Nội dung khoá học</button>
                    </li>
                    <?php if ($canManageStudents): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $mode == 'create' ? 'disabled' : '' ?>" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="false" <?= $mode == 'create' ? 'disabled' : '' ?>>Học viên</button>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="tab-content" id="courseBuilderTabsContent">
                    <!-- Tab 1: Thông tin khoá học -->
                    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <form id="form-course-info">
                            <input type="hidden" name="course_id" id="course_id" value="<?= $course_id ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tên khoá học <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" id="title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phụ đề</label>
                                        <input type="text" class="form-control" name="subtitle" id="subtitle">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea class="form-control" name="description" id="description" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh đại diện</label>
                                        <input type="file" class="form-control" name="featured_image" id="featured_image" accept="image/*">
                                        <div id="image-preview" class="mt-2"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Video giới thiệu (URL)</label>
                                        <input type="text" class="form-control" name="intro_video_url" id="intro_video_url">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Độ khó (Level)</label>
                                        <select class="form-select" name="level" id="level">
                                            <option value="all">Tất cả</option>
                                            <option value="beginner">Cơ bản</option>
                                            <option value="intermediate">Trung cấp</option>
                                            <option value="advanced">Nâng cao</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngôn ngữ</label>
                                        <select class="form-select" name="language" id="language">
                                            <option value="vi">Tiếng Việt</option>
                                            <option value="en">English</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sản phẩm liên kết</label>
                                        <select class="form-select select2" name="linked_product_id" id="linked_product_id">
                                            <option value="">-- Chọn sản phẩm --</option>
                                            <!-- AJAX load products -->
                                        </select>
                                    </div>
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" checked>
                                        <label class="form-check-label" for="status">Xuất bản</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tab 2: Nội dung khoá học -->
                    <div class="tab-pane fade" id="curriculum" role="tabpanel" aria-labelledby="curriculum-tab">
                        <div class="d-flex justify-content-between mb-3">
                            <h5 class="card-title">Chương trình học</h5>
                            <button class="btn btn-sm btn-success" id="btn-add-section"><i class="fa-solid fa-plus"></i> Thêm chương mới</button>
                        </div>
                        <div id="curriculum-container" class="accordion sortable-sections">
                            <!-- Dynamically loaded -->
                        </div>
                    </div>

                    <?php if ($canManageStudents): ?>
                        <!-- Tab 3: Học viên -->
                        <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="search-student" placeholder="Tìm tên/email/username...">
                                        <button class="btn btn-primary" type="button" id="btn-search-student"><i class="fa-solid fa-search"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enrollModal"><i class="fa-solid fa-user-plus"></i> Thêm học viên</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover border text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Tiến độ</th>
                                            <th>Ngày ghi danh</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list">
                                        <!-- AJAX loaded -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chọn loại bài học -->
<div class="modal fade" id="lessonTypeModal" tabindex="-1" aria-labelledby="lessonTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lessonTypeModalLabel">Chọn loại bài học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center row g-3">
                <div class="col-4">
                    <button class="btn btn-outline-primary w-100 py-3 btn-select-lesson-type" data-type="text">
                        <i class="fa-solid fa-file-alt fs-2 mb-2"></i><br>Văn bản
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-danger w-100 py-3 btn-select-lesson-type" data-type="video">
                        <i class="fa-solid fa-video fs-2 mb-2"></i><br>Video
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-success w-100 py-3 btn-select-lesson-type" data-type="audio">
                        <i class="fa-solid fa-volume-up fs-2 mb-2"></i><br>Audio
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-warning w-100 py-3 btn-select-lesson-type" data-type="pdf">
                        <i class="fa-solid fa-file-pdf fs-2 mb-2"></i><br>PDF
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-info w-100 py-3 btn-select-lesson-type" data-type="embed">
                        <i class="fa-solid fa-code fs-2 mb-2"></i><br>Embed
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-secondary w-100 py-3 btn-select-lesson-type" data-type="quiz">
                        <i class="fa-solid fa-question-circle fs-2 mb-2"></i><br>Bài kiểm tra
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lesson Editor -->
<div class="modal fade" id="lessonEditorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa bài học <span id="lesson-type-badge" class="badge bg-secondary ms-2"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-lesson">
                    <input type="hidden" id="lesson_id" name="lesson_id">
                    <input type="hidden" id="lesson_section_id" name="section_id">
                    <input type="hidden" id="lesson_type" name="type">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên bài học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lesson_title" name="title" required>
                    </div>

                    <div id="lesson-content-area" class="mb-3">
                        <!-- Dynamic content based on type -->
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="lesson_is_free" name="is_free">
                                <label class="form-check-label" for="lesson_is_free">Cho phép xem trước (Miễn phí)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="lesson_status" name="status" checked>
                                <label class="form-check-label" for="lesson_status">Xuất bản</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tài liệu đính kèm</label>
                        <input type="file" class="form-control" id="lesson_attachments" name="attachments[]" multiple>
                        <div id="attachments-list" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btn-save-lesson">Lưu bài học</button>
            </div>
        </div>
    </div>
</div>

<?php if ($canManageStudents): ?>
    <!-- Modal Enroll Student -->
    <div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ghi danh học viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn học viên</label>
                        <select class="form-select select2-user" id="enroll_user_id" style="width: 100%;">
                            <option value="">-- Tìm kiếm user --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success" id="btn-submit-enroll">Ghi danh</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once(__DIR__ . '/footer.php'); ?>
