<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý Khoá học') . ' | ' . $SMARTSTUDY->site('title'),
    'desc'   => $SMARTSTUDY->site('description'),
    'keyword' => $SMARTSTUDY->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');

// Permission check
if (checkPermission($getUser['admin'], 'view_course') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page header breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-graduation-cap"></i> Quản lý Khoá học</h1>
            <div class="d-flex">
                <a href="<?= base_url_admin('course-builder') ?>" class="btn btn-sm btn-primary btn-wave"><i class="fa-solid fa-plus"></i> Tạo khoá học mới</a>
            </div>
        </div>

        <!-- Filter card -->
        <div class="card custom-card">
            <div class="card-body">
                <form action="" method="GET">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="courses">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" class="form-control" name="title" placeholder="Tìm kiếm tên khoá học..." value="<?= isset($_GET['title']) ? check_string($_GET['title']) : '' ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="1" <?= (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : '' ?>>Đã xuất bản</option>
                                <option value="0" <?= (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : '' ?>>Bản nháp</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Content cards -->
        <div class="card custom-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover border text-nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Thumbnail</th>
                                <th>Tên khoá học</th>
                                <th>Trạng thái</th>
                                <th>Số học viên</th>
                                <th>Số bài học</th>
                                <th>Giá</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="course-list">
                            <!-- Populated by AJAX -->
                        </tbody>
                    </table>
                </div>
                <div id="pagination-container" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= base_url() ?>';
$(document).ready(function() {
    loadCourses(1);
    
    function loadCourses(page) {
        let title = $('input[name="title"]').val();
        let status = $('select[name="status"]').val();
        
        $.ajax({
            url: baseUrl + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: { 
                action: 'listCourses',
                page: page,
                title: title,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#course-list').html(response.html);
                    $('#pagination-container').html(response.pagination);
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function() {
                showMessage('Có lỗi xảy ra', 'error');
            }
        });
    }
    
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let page = $(this).attr('data-page');
        loadCourses(page);
    });

    $(document).on('change', '.course-status-toggle', function() {
        let id = $(this).data('id');
        let status = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            url: baseUrl + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: {
                action: 'toggleStatus',
                id: id,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                } else {
                    showMessage(response.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.delete-course', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Dữ liệu sau khi xoá sẽ không thể khôi phục!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý xoá!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + 'ajaxs/admin/courses.php',
                    type: 'POST',
                    data: {
                        action: 'deleteCourse',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Đã xoá!', response.message, 'success');
                            loadCourses(1);
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    }
                });
            }
        })
    });
});
</script>

<?php require_once(__DIR__ . '/footer.php'); ?>
