<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý Học viên Khoá học') . ' | ' . $SMARTSTUDY->site('title'),
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

// Load courses for filter dropdown
require_once(__DIR__ . '/../../libs/database/courses.php');
$coursesDB = new Courses();
$allCourses = $SMARTSTUDY->get_list_safe("SELECT id, title FROM courses ORDER BY title ASC", []);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page header breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-users"></i> Quản lý Học viên Khoá học</h1>
            <div class="d-flex">
                <a href="<?= base_url_admin('courses') ?>" class="btn btn-sm btn-secondary btn-wave me-2"><i class="fa-solid fa-arrow-left"></i> Quản lý khoá học</a>
                <button type="button" class="btn btn-sm btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#modalManualEnroll"><i class="fa-solid fa-user-plus"></i> Thêm học viên</button>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="row mb-3" id="stats-cards">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent rounded-circle">
                                    <i class="fa-solid fa-users fs-18 text-primary"></i>
                                </span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted fs-12">Tổng học viên</p>
                                <h5 class="fw-semibold mb-0" id="stat-total">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent rounded-circle">
                                    <i class="fa-solid fa-circle-check fs-18 text-success"></i>
                                </span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted fs-12">Đang hoạt động</p>
                                <h5 class="fw-semibold mb-0" id="stat-active">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-warning-transparent rounded-circle">
                                    <i class="fa-solid fa-trophy fs-18 text-warning"></i>
                                </span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted fs-12">Hoàn thành</p>
                                <h5 class="fw-semibold mb-0" id="stat-completed">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-danger-transparent rounded-circle">
                                    <i class="fa-solid fa-circle-xmark fs-18 text-danger"></i>
                                </span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted fs-12">Đã huỷ</p>
                                <h5 class="fw-semibold mb-0" id="stat-cancelled">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter card -->
        <div class="card custom-card">
            <div class="card-body">
                <form id="filter-form">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm username, email, họ tên..." value="">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="course_id" class="form-select">
                                <option value="">Tất cả khoá học</option>
                                <?php if (!empty($allCourses)): ?>
                                    <?php foreach ($allCourses as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= check_string($c['title']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active">Đang hoạt động</option>
                                <option value="completed">Hoàn thành</option>
                                <option value="expired">Hết hạn</option>
                                <option value="cancelled">Đã huỷ</option>
                                <option value="suspended">Tạm dừng</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="sort" class="form-select">
                                <option value="newest">Mới nhất</option>
                                <option value="oldest">Cũ nhất</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Content table -->
        <div class="card custom-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover border text-nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Học viên</th>
                                <th>Email</th>
                                <th>Khoá học</th>
                                <th>Trạng thái</th>
                                <th>Tiến độ</th>
                                <th>Ngày đăng ký</th>
                                <th>Hoàn thành</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="students-list">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted fs-13" id="showing-info"></div>
                    <div id="pagination-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manual Enroll -->
<div class="modal fade" id="modalManualEnroll" tabindex="-1" aria-labelledby="modalManualEnrollLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalManualEnrollLabel"><i class="fa-solid fa-user-plus"></i> Thêm học viên vào khoá học</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">User ID <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="enroll-user-id" placeholder="Nhập User ID...">
                    <small class="text-muted">Nhập ID của người dùng muốn thêm vào khoá học</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Khoá học <span class="text-danger">*</span></label>
                    <select class="form-select" id="enroll-course-id">
                        <option value="">-- Chọn khoá học --</option>
                        <?php if (!empty($allCourses)): ?>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= check_string($c['title']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btn-manual-enroll"><i class="fa-solid fa-check"></i> Xác nhận thêm</button>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= base_url() ?>';
let currentPage = 1;
const perPage = 20;

$(document).ready(function() {
    loadStudents(1);
    loadStats();

    // Filter form submit
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadStudents(1);
        loadStats();
    });

    // Pagination click
    $(document).on('click', '.pagination a[data-page]', function(e) {
        e.preventDefault();
        let page = $(this).attr('data-page');
        currentPage = parseInt(page);
        loadStudents(currentPage);
    });

    // Manual enroll
    $('#btn-manual-enroll').on('click', function() {
        let userId = $('#enroll-user-id').val();
        let courseId = $('#enroll-course-id').val();
        if (!userId || !courseId) {
            showMessage('Vui lòng nhập đầy đủ User ID và chọn khoá học', 'error');
            return;
        }
        $.ajax({
            url: baseUrl + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: { action: 'manualEnroll', user_id: userId, course_id: courseId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    $('#modalManualEnroll').modal('hide');
                    $('#enroll-user-id').val('');
                    $('#enroll-course-id').val('');
                    loadStudents(currentPage);
                    loadStats();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function() {
                showMessage('Có lỗi xảy ra', 'error');
            }
        });
    });

    // Remove enrollment
    $(document).on('click', '.remove-enrollment', function(e) {
        e.preventDefault();
        let userId = $(this).data('user-id');
        let courseId = $(this).data('course-id');
        let studentName = $(this).data('name');

        Swal.fire({
            title: 'Huỷ ghi danh?',
            text: 'Bạn có chắc muốn huỷ ghi danh của "' + studentName + '" khỏi khoá học này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý huỷ',
            cancelButtonText: 'Không'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + 'ajaxs/admin/courses.php',
                    type: 'POST',
                    data: { action: 'removeEnrollment', user_id: userId, course_id: courseId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Đã huỷ!', response.message, 'success');
                            loadStudents(currentPage);
                            loadStats();
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
});

function loadStudents(page) {
    let search = $('input[name="search"]').val();
    let courseId = $('select[name="course_id"]').val();
    let status = $('select[name="status"]').val();
    let sort = $('select[name="sort"]').val();
    let offset = (page - 1) * perPage;

    $('#students-list').html('<tr><td colspan="9" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Đang tải dữ liệu...</td></tr>');

    $.ajax({
        url: baseUrl + 'ajaxs/admin/courses.php',
        type: 'GET',
        data: {
            action: 'listAllStudents',
            search: search,
            course_id: courseId,
            status: status,
            sort: sort,
            limit: perPage,
            offset: offset
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                renderStudents(response.data, response.total, page);
            } else {
                $('#students-list').html('<tr><td colspan="9" class="text-center text-muted py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> ' + (response.message || 'Không thể tải dữ liệu') + '</td></tr>');
            }
        },
        error: function() {
            $('#students-list').html('<tr><td colspan="9" class="text-center text-danger py-4"><i class="fa-solid fa-triangle-exclamation me-1"></i> Có lỗi xảy ra khi tải dữ liệu</td></tr>');
        }
    });
}

function renderStudents(data, total, page) {
    if (!data || data.length === 0) {
        $('#students-list').html('<tr><td colspan="9" class="text-center text-muted py-4"><i class="fa-solid fa-inbox me-1"></i> Không tìm thấy học viên nào</td></tr>');
        $('#pagination-container').html('');
        $('#showing-info').html('');
        return;
    }

    let html = '';
    let offset = (page - 1) * perPage;

    data.forEach(function(item, index) {
        let statusBadge = getStatusBadge(item.status, item.completed_at);
        let progressPercent = item.progress || 0;
        let progressColor = progressPercent >= 100 ? 'bg-success' : (progressPercent >= 50 ? 'bg-primary' : 'bg-warning');

        html += '<tr>';
        html += '<td>' + (offset + index + 1) + '</td>';
        html += '<td>';
        html += '<div class="d-flex align-items-center">';
        html += '<div>';
        html += '<span class="fw-semibold">' + escapeHtml(item.fullname || item.username) + '</span>';
        if (item.fullname && item.username) {
            html += '<br><small class="text-muted">@' + escapeHtml(item.username) + '</small>';
        }
        html += '</div>';
        html += '</div>';
        html += '</td>';
        html += '<td><span class="text-muted">' + escapeHtml(item.email || '') + '</span></td>';
        html += '<td><span class="fw-medium">' + escapeHtml(item.course_title || 'N/A') + '</span></td>';
        html += '<td>' + statusBadge + '</td>';
        html += '<td style="min-width:120px">';
        html += '<div class="progress progress-xs mb-1"><div class="progress-bar ' + progressColor + '" style="width:' + progressPercent + '%"></div></div>';
        html += '<small class="text-muted">' + progressPercent + '%</small>';
        html += '</td>';
        html += '<td><small class="text-muted">' + formatDate(item.enrolled_at) + '</small></td>';
        html += '<td>' + (item.completed_at ? '<small class="text-success"><i class="fa-solid fa-check me-1"></i>' + formatDate(item.completed_at) + '</small>' : '<small class="text-muted">—</small>') + '</td>';
        html += '<td>';
        html += '<div class="btn-group btn-group-sm">';
        if (item.status === 'active') {
            html += '<button class="btn btn-sm btn-danger-light remove-enrollment" data-user-id="' + item.user_id + '" data-course-id="' + item.course_id + '" data-name="' + escapeHtml(item.fullname || item.username) + '" title="Huỷ ghi danh"><i class="fa-solid fa-user-xmark"></i></button>';
        }
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    });

    $('#students-list').html(html);

    // Show info
    let start = offset + 1;
    let end = Math.min(offset + perPage, total);
    $('#showing-info').html('Hiển thị ' + start + ' - ' + end + ' / ' + total + ' kết quả');

    // Pagination
    renderPagination(total, page, perPage);
}

function getStatusBadge(status, completedAt) {
    if (completedAt && status === 'active') {
        return '<span class="badge bg-warning-transparent">Hoàn thành</span>';
    }
    switch (status) {
        case 'active':
            return '<span class="badge bg-success-transparent">Đang học</span>';
        case 'expired':
            return '<span class="badge bg-secondary-transparent">Hết hạn</span>';
        case 'cancelled':
            return '<span class="badge bg-danger-transparent">Đã huỷ</span>';
        case 'suspended':
            return '<span class="badge bg-info-transparent">Tạm dừng</span>';
        default:
            return '<span class="badge bg-secondary-transparent">' + status + '</span>';
    }
}

function renderPagination(total, currentPage, perPage) {
    let totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) {
        $('#pagination-container').html('');
        return;
    }

    let html = '<nav><ul class="pagination pagination-sm mb-0">';

    // Previous
    if (currentPage > 1) {
        html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '"><i class="fa-solid fa-chevron-left"></i></a></li>';
    } else {
        html += '<li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>';
    }

    // Pages
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
        if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
        } else {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        html += '<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
    }

    // Next
    if (currentPage < totalPages) {
        html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '"><i class="fa-solid fa-chevron-right"></i></a></li>';
    } else {
        html += '<li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>';
    }

    html += '</ul></nav>';
    $('#pagination-container').html(html);
}

function loadStats() {
    $.ajax({
        url: baseUrl + 'ajaxs/admin/courses.php',
        type: 'GET',
        data: { action: 'enrollmentStats' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#stat-total').text(response.data.total || 0);
                $('#stat-active').text(response.data.active || 0);
                $('#stat-completed').text(response.data.completed || 0);
                $('#stat-cancelled').text(response.data.cancelled || 0);
            }
        }
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    let d = new Date(dateStr);
    let day = ('0' + d.getDate()).slice(-2);
    let month = ('0' + (d.getMonth() + 1)).slice(-2);
    let year = d.getFullYear();
    let hours = ('0' + d.getHours()).slice(-2);
    let minutes = ('0' + d.getMinutes()).slice(-2);
    return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
}

function escapeHtml(text) {
    if (!text) return '';
    let div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>

<?php require_once(__DIR__ . '/footer.php'); ?>
