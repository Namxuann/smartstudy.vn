<?php
define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/../../libs/database/courses.php');
require_once(__DIR__ . '/../../libs/database/enrollments.php');
require_once(__DIR__ . '/../../libs/media_manager.php');

$coursesDB = new Courses();
$enrollmentsDB = new Enrollments();
$mediaManager = new MediaManager($SMARTSTUDY);

$action = isset($_POST['action']) ? check_string($_POST['action']) : (isset($_GET['action']) ? check_string($_GET['action']) : '');

header('Content-Type: application/json; charset=utf-8');

$canViewCourses = checkPermission($getUser['admin'], 'view_course')
    || checkPermission($getUser['admin'], 'edit_course')
    || checkPermission($getUser['admin'], 'manage_students_course');
$canEditCourses = checkPermission($getUser['admin'], 'edit_course');
$canManageStudents = checkPermission($getUser['admin'], 'manage_students_course');

$courseReadActions = ['listCourses', 'getCourse'];
$courseEditActions = [
    'createCourse', 'updateCourse', 'deleteCourse', 'toggleStatus', 'publishCourse', 'unpublishCourse',
    'createSection', 'updateSection', 'deleteSection', 'reorderSections',
    'createLesson', 'updateLesson', 'getLesson', 'getCurriculum', 'deleteLesson', 'reorderLessons',
    'searchProducts', 'uploadMedia', 'deleteMedia',
    'addQuizQuestion', 'updateQuizQuestion', 'deleteQuizQuestion'
];
$studentReadActions = ['listStudents', 'listAllStudents', 'enrollmentStats'];
$studentWriteActions = ['manualEnroll', 'removeEnrollment'];

if (in_array($action, $courseReadActions, true) && !$canViewCourses) {
    echo json_encode(['status' => 'error', 'message' => __('You do not have permission to do this')]);
    exit();
}

if (in_array($action, $courseEditActions, true) && !$canEditCourses) {
    echo json_encode(['status' => 'error', 'message' => __('You do not have permission to do this')]);
    exit();
}

if (in_array($action, $studentReadActions, true) && !$canManageStudents) {
    echo json_encode(['status' => 'error', 'message' => __('You do not have permission to do this')]);
    exit();
}

if (in_array($action, $studentWriteActions, true) && !$canManageStudents) {
    echo json_encode(['status' => 'error', 'message' => __('You do not have permission to do this')]);
    exit();
}

if (!in_array($action, array_merge($courseReadActions, $courseEditActions, $studentReadActions, $studentWriteActions), true)) {
    echo json_encode(['status' => 'error', 'message' => __('Invalid action')]);
    exit();
}

function lms_escape($value)
{
    return htmlspecialchars(htmlspecialchars_decode((string) $value, ENT_QUOTES), ENT_QUOTES, 'UTF-8');
}

function lms_course_exists($db, $courseId)
{
    return $courseId > 0 && (bool) $db->get_row_safe('SELECT id FROM courses WHERE id = ?', [(int) $courseId]);
}

function lms_product_is_linked_to_another_course($db, $productId, $courseId = 0)
{
    $sql = 'SELECT id FROM courses WHERE product_id = ?';
    $params = [(int) $productId];

    if ($courseId > 0) {
        $sql .= ' AND id != ?';
        $params[] = (int) $courseId;
    }

    return (bool) $db->get_row_safe($sql, $params);
}

function lms_values_match($record, $expected)
{
    if (!$record) {
        return false;
    }

    foreach ($expected as $field => $value) {
        if (!array_key_exists($field, $record) || (string) $record[$field] !== (string) $value) {
            return false;
        }
    }

    return true;
}

function lms_render_course_rows($courses, $page, $perPage, $canEditCourses, $canManageStudents)
{
    if (empty($courses)) {
        return '<tr><td colspan="8" class="text-center text-muted py-4">' . __('No courses found') . '</td></tr>';
    }

    $html = '';
    foreach ($courses as $index => $course) {
        $courseId = (int) $course['id'];
        $title = lms_escape($course['title'] ?? '');
        $productName = lms_escape($course['product_name'] ?? '');
        $studentCount = (int) ($course['student_count'] ?? 0);
        $lessonCount = (int) ($course['lesson_count'] ?? 0);
        $isPublished = (int) ($course['is_published'] ?? 0) === 1;
        $featuredImage = trim((string) ($course['featured_image'] ?? ''));

        if ($featuredImage !== '') {
            $imageUrl = preg_match('#^https?://#i', $featuredImage) ? $featuredImage : base_url(ltrim($featuredImage, '/'));
            $thumbnail = '<img src="' . lms_escape($imageUrl) . '" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;">';
        } else {
            $thumbnail = '<span class="avatar avatar-md bg-light text-muted"><i class="fa-solid fa-graduation-cap"></i></span>';
        }

        $price = isset($course['product_price']) && $course['product_price'] !== null
            ? format_currency((float) $course['product_price'])
            : '-';

        $html .= '<tr>';
        $html .= '<td>' . (($page - 1) * $perPage + $index + 1) . '</td>';
        $html .= '<td>' . $thumbnail . '</td>';
        $html .= '<td><div class="fw-semibold">' . $title . '</div>';
        $html .= $productName !== '' ? '<small class="text-muted">' . $productName . '</small>' : '<small class="text-muted">' . __('No linked product') . '</small>';
        $html .= '</td>';

        if ($canEditCourses) {
            $html .= '<td><div class="form-check form-switch"><input class="form-check-input course-status-toggle" type="checkbox" data-id="' . $courseId . '"' . ($isPublished ? ' checked' : '') . '></div></td>';
        } else {
            $html .= '<td><span class="badge ' . ($isPublished ? 'bg-success-transparent' : 'bg-secondary-transparent') . '">' . ($isPublished ? __('Published') : __('Draft')) . '</span></td>';
        }

        $html .= '<td>' . $studentCount . '</td>';
        $html .= '<td>' . $lessonCount . '</td>';
        $html .= '<td>' . lms_escape($price) . '</td>';
        $html .= '<td><div class="btn-list">';
        if ($canEditCourses) {
            $html .= '<a href="' . lms_escape(base_url_admin('course-builder&id=' . $courseId)) . '" class="btn btn-sm btn-primary-light" title="' . __('Edit') . '"><i class="fa-solid fa-pen"></i></a>';
        }
        if ($canManageStudents) {
            $html .= '<a href="' . lms_escape(base_url_admin('course-students&course_id=' . $courseId)) . '" class="btn btn-sm btn-info-light" title="' . __('Students') . '"><i class="fa-solid fa-users"></i></a>';
        }
        if ($canEditCourses) {
            $html .= '<button type="button" class="btn btn-sm btn-danger-light delete-course" data-id="' . $courseId . '" title="' . __('Delete') . '"><i class="fa-solid fa-trash"></i></button>';
        }
        $html .= '</div></td>';
        $html .= '</tr>';
    }

    return $html;
}

function lms_render_course_pagination($page, $totalPages)
{
    if ($totalPages <= 1) {
        return '';
    }

    $pages = [1];
    for ($item = max(2, $page - 2); $item <= min($totalPages - 1, $page + 2); $item++) {
        $pages[] = $item;
    }
    if ($totalPages > 1) {
        $pages[] = $totalPages;
    }
    $pages = array_values(array_unique($pages));

    $html = '<nav aria-label="' . lms_escape(__('Course pages')) . '"><ul class="pagination pagination-sm mb-0">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="#" data-page="' . max(1, $page - 1) . '"><i class="fa-solid fa-chevron-left"></i></a></li>';

    $previous = 0;
    foreach ($pages as $item) {
        if ($previous > 0 && $item > $previous + 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        if ($item === $page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $item . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="#" data-page="' . $item . '">' . $item . '</a></li>';
        }
        $previous = $item;
    }

    $html .= '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '"><a class="page-link" href="#" data-page="' . min($totalPages, $page + 1) . '"><i class="fa-solid fa-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';
    return $html;
}

switch ($action) {
    case 'createCourse':
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        $product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id']) : 0;
        
        if (empty($title) || $product_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => __('Title and Product ID are required')]);
            break;
        }

        $product = $SMARTSTUDY->get_row_safe('SELECT id FROM products WHERE id = ?', [(int) $product_id]);
        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => __('Linked product was not found')]);
            break;
        }
        if (lms_product_is_linked_to_another_course($SMARTSTUDY, $product_id)) {
            echo json_encode(['status' => 'error', 'message' => __('This product is already linked to another course')]);
            break;
        }

        $level = check_string($_POST['level'] ?? 'all');
        if (!in_array($level, ['beginner', 'intermediate', 'advanced', 'all'], true)) {
            $level = 'all';
        }
        $isPublished = isset($_POST['status']) ? 1 : 0;
        
        $slug = $coursesDB->generateSlug($title);
        $data = [
            'title' => $title,
            'product_id' => $product_id,
            'slug' => $slug,
            'subtitle' => check_string($_POST['subtitle'] ?? ''),
            'description' => $_POST['description'] ?? '',
            'intro_video' => check_string($_POST['intro_video'] ?? ($_POST['intro_video_url'] ?? '')),
            'level' => $level,
            'language' => check_string($_POST['language'] ?? 'vi'),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $coursesDB->add_new($data);
        if ($id) {
            echo json_encode(['status' => 'success', 'message' => __('Course created successfully'), 'id' => $id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to create course')]);
        }
        break;

    case 'updateCourse':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => __('Invalid course ID')]);
            break;
        }

        $existingCourse = $coursesDB->select_by_id($id);
        if (!$existingCourse) {
            echo json_encode(['status' => 'error', 'message' => __('Course not found')]);
            break;
        }
        
        $data = [];
        if (isset($_POST['title'])) {
            $data['title'] = check_string($_POST['title']);
            if ($data['title'] === '') {
                echo json_encode(['status' => 'error', 'message' => __('Course title is required')]);
                break;
            }
        }
        if (isset($_POST['subtitle'])) $data['subtitle'] = check_string($_POST['subtitle']);
        if (isset($_POST['description'])) $data['description'] = $_POST['description']; // Keep HTML
        if (isset($_POST['featured_image'])) $data['featured_image'] = check_string($_POST['featured_image']);
        if (isset($_POST['intro_video'])) $data['intro_video'] = check_string($_POST['intro_video']);
        if (isset($_POST['intro_video_url'])) $data['intro_video'] = check_string($_POST['intro_video_url']);
        if (isset($_POST['level'])) {
            $level = check_string($_POST['level']);
            $data['level'] = in_array($level, ['beginner', 'intermediate', 'advanced', 'all'], true) ? $level : 'all';
        }
        if (isset($_POST['language'])) $data['language'] = check_string($_POST['language']);
        if (isset($_POST['product_id'])) {
            $data['product_id'] = validate_int($_POST['product_id']);
        } elseif (isset($_POST['linked_product_id'])) {
            $data['product_id'] = validate_int($_POST['linked_product_id']);
        }
        if (isset($data['product_id'])) {
            if ($data['product_id'] <= 0 || !$SMARTSTUDY->get_row_safe('SELECT id FROM products WHERE id = ?', [(int) $data['product_id']])) {
                echo json_encode(['status' => 'error', 'message' => __('Linked product was not found')]);
                break;
            }
            if (lms_product_is_linked_to_another_course($SMARTSTUDY, $data['product_id'], $id)) {
                echo json_encode(['status' => 'error', 'message' => __('This product is already linked to another course')]);
                break;
            }
        }
        $data['is_published'] = isset($_POST['status']) ? 1 : 0;
        if ($data['is_published'] === 1 && empty($existingCourse['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($coursesDB->update_by_id($data, $id) || lms_values_match($coursesDB->select_by_id($id), $data)) {
            echo json_encode(['status' => 'success', 'message' => __('Course updated successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update course')]);
        }
        break;

    case 'deleteCourse':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && lms_course_exists($SMARTSTUDY, $id) && $coursesDB->delete_by_id($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Course deleted successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to delete course')]);
        }
        break;

    case 'getCourse':
        $id = isset($_GET['id']) ? validate_int($_GET['id']) : 0;
        $course = $SMARTSTUDY->get_row_safe(
            "SELECT c.*, p.name AS product_name
             FROM courses c
             LEFT JOIN products p ON p.id = c.product_id
             WHERE c.id = ?",
            [$id]
        );
        if ($course) {
            foreach (['title', 'subtitle', 'intro_video', 'language', 'product_name'] as $field) {
                if (isset($course[$field])) {
                    $course[$field] = htmlspecialchars_decode((string) $course[$field], ENT_QUOTES);
                }
            }
            echo json_encode(['status' => 'success', 'data' => $course]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Course not found')]);
        }
        break;

    case 'listCourses':
        $pageInput = $_POST['page'] ?? ($_GET['page'] ?? 1);
        $titleInput = $_POST['title'] ?? ($_GET['title'] ?? '');
        $statusInput = $_POST['status'] ?? ($_GET['status'] ?? '');
        $pageValue = validate_int($pageInput);
        $page = $pageValue === false ? 1 : max(1, (int) $pageValue);
        $perPage = 20;
        $title = is_string($titleInput) ? check_string($titleInput) : '';
        $status = is_scalar($statusInput) ? (string) $statusInput : '';
        $where = [];
        $params = [];

        if ($title !== '') {
            $where[] = 'c.title LIKE ?';
            $params[] = '%' . $title . '%';
        }
        if ($status === '0' || $status === '1') {
            $where[] = 'c.is_published = ?';
            $params[] = (int) $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countRow = $SMARTSTUDY->get_row_safe("SELECT COUNT(*) AS total FROM courses c $whereSql", $params);
        $total = $countRow ? (int) $countRow['total'] : 0;
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $list = $SMARTSTUDY->get_list_safe(
            "SELECT c.*, p.name AS product_name, p.price AS product_price,
                (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id AND ce.status = 'active') AS student_count,
                (SELECT COUNT(*) FROM course_lessons cl WHERE cl.course_id = c.id) AS lesson_count
             FROM courses c
             LEFT JOIN products p ON p.id = c.product_id
             $whereSql
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        echo json_encode([
            'status' => 'success',
            'data' => $list ?: [],
            'html' => lms_render_course_rows($list ?: [], $page, $perPage, $canEditCourses, $canManageStudents),
            'pagination' => lms_render_course_pagination($page, $totalPages),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ]);
        break;

    case 'publishCourse':
    case 'unpublishCourse':
    case 'toggleStatus':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if (!lms_course_exists($SMARTSTUDY, $id)) {
            echo json_encode(['status' => 'error', 'message' => __('Course not found')]);
            break;
        }
        if ($action === 'toggleStatus') {
            $status = isset($_POST['status']) && in_array((string) $_POST['status'], ['0', '1'], true) ? (int) $_POST['status'] : null;
            if ($status === null) {
                echo json_encode(['status' => 'error', 'message' => __('Invalid status')]);
                break;
            }
        } else {
            $status = ($action === 'publishCourse') ? 1 : 0;
        }
        $data = ['is_published' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status == 1) $data['published_at'] = date('Y-m-d H:i:s');
        
        if ($coursesDB->update_by_id($data, $id) || lms_values_match($coursesDB->select_by_id($id), ['is_published' => $status])) {
            echo json_encode(['status' => 'success', 'message' => __('Status updated')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update status')]);
        }
        break;

    case 'createSection':
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        if ($course_id > 0 && !empty($title) && lms_course_exists($SMARTSTUDY, $course_id)) {
            $id = $coursesDB->addSection(['course_id' => $course_id, 'title' => $title, 'created_at' => date('Y-m-d H:i:s')]);
            if ($id) {
                echo json_encode(['status' => 'success', 'message' => __('Section created'), 'id' => $id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to create section')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
        }
        break;

    case 'updateSection':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        $description = isset($_POST['description']) ? check_string($_POST['description']) : '';
        $section = $id > 0 ? $SMARTSTUDY->get_row_safe('SELECT * FROM course_sections WHERE id = ?', [(int) $id]) : false;
        if (!$section || $title === '') {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
            break;
        }

        $data = ['title' => $title, 'description' => $description];
        if ($coursesDB->updateSection($id, $data) || lms_values_match($SMARTSTUDY->get_row_safe('SELECT * FROM course_sections WHERE id = ?', [(int) $id]), $data)) {
            echo json_encode(['status' => 'success', 'message' => __('Section updated')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update')]);
        }
        break;

    case 'deleteSection':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        $section = $id > 0 ? $SMARTSTUDY->get_row_safe('SELECT id FROM course_sections WHERE id = ?', [(int) $id]) : false;
        if ($section && $coursesDB->deleteSection($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Section deleted')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to delete section')]);
        }
        break;

    case 'reorderSections':
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];
        if ($course_id > 0 && is_array($order) && lms_course_exists($SMARTSTUDY, $course_id)) {
            if ($coursesDB->reorderSections($course_id, $order)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to update order')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
        }
        break;

    case 'createLesson':
        $section_id = isset($_POST['section_id']) ? validate_int($_POST['section_id']) : 0;
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        $type = check_string($_POST['lesson_type'] ?? ($_POST['type'] ?? 'text'));
        $allowedLessonTypes = ['text', 'video', 'audio', 'pdf', 'embed', 'quiz'];
        if (!in_array($type, $allowedLessonTypes, true)) {
            $type = 'text';
        }
        $section = $section_id > 0 ? $SMARTSTUDY->get_row_safe('SELECT id FROM course_sections WHERE id = ? AND course_id = ?', [(int) $section_id, (int) $course_id]) : false;
        
        if ($section && !empty($title)) {
            $slug = vn2en($title) . '-' . time();
            $duration = validate_int($_POST['duration'] ?? 0);
            $data = [
                'section_id' => $section_id,
                'course_id' => $course_id,
                'title' => $title,
                'slug' => check_string($slug),
                'lesson_type' => $type,
                'content' => $_POST['content'] ?? '',
                'media_url' => check_string($_POST['media_url'] ?? ($_POST['video_url'] ?? ($_POST['audio_url'] ?? ''))),
                'media_duration' => $duration === false ? 0 : $duration,
                'embed_code' => $_POST['embed_code'] ?? '',
                'is_free_preview' => (int) ($_POST['is_free_preview'] ?? ($_POST['is_free'] ?? 0)) === 1 ? 1 : 0,
                'is_published' => (int) ($_POST['is_published'] ?? ($_POST['status'] ?? 0)) === 1 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $id = $coursesDB->addLesson($data);
            if ($id) {
                echo json_encode(['status' => 'success', 'message' => __('Lesson created'), 'id' => $id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to create')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
        }
        break;

    case 'updateLesson':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        $existingLesson = $id > 0 ? $coursesDB->getLesson($id) : false;
        if (!$existingLesson) {
            echo json_encode(['status' => 'error', 'message' => __('Lesson not found')]);
            break;
        }

        $lessonType = check_string($_POST['lesson_type'] ?? ($_POST['type'] ?? 'text'));
        if (!in_array($lessonType, ['text', 'video', 'audio', 'pdf', 'embed', 'quiz'], true)) {
            $lessonType = 'text';
        }
        $title = check_string($_POST['title'] ?? '');
        if ($title === '') {
            echo json_encode(['status' => 'error', 'message' => __('Lesson title is required')]);
            break;
        }
        $duration = validate_int($_POST['duration'] ?? 0);
        $data = [
            'title' => $title,
            'content' => $_POST['content'] ?? '',
            'lesson_type' => $lessonType,
            'media_url' => check_string($_POST['media_url'] ?? ($_POST['video_url'] ?? ($_POST['audio_url'] ?? ''))),
            'media_duration' => $duration === false ? 0 : $duration,
            'embed_code' => $_POST['embed_code'] ?? '',
            'is_free_preview' => (int) ($_POST['is_free_preview'] ?? ($_POST['is_free'] ?? 0)) === 1 ? 1 : 0,
            'is_published' => (int) ($_POST['is_published'] ?? ($_POST['status'] ?? 0)) === 1 ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($coursesDB->updateLesson($id, $data) || lms_values_match($coursesDB->getLesson($id), $data)) {
            echo json_encode(['status' => 'success', 'message' => __('Lesson updated')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update')]);
        }
        break;

    case 'getLesson':
        $id = validate_int($_REQUEST['lesson_id'] ?? ($_REQUEST['id'] ?? 0));
        $lesson = $id > 0 ? $coursesDB->getLesson($id) : false;
        if ($lesson) {
            echo json_encode(['status' => 'success', 'lesson' => $lesson]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Lesson not found')]);
        }
        break;

    case 'getCurriculum':
        $course_id = validate_int($_REQUEST['course_id'] ?? 0);
        if ($course_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => __('Invalid course ID')]);
            break;
        }

        $html = '';
        foreach ($coursesDB->getFullCurriculum($course_id) as $section) {
            $sectionId = (int) $section['id'];
            $html .= '<div class="accordion-item" data-id="' . $sectionId . '">';
            $html .= '<div class="section-header d-flex align-items-center justify-content-between p-3">';
            $html .= '<h6 class="mb-0">' . lms_escape($section['title']) . '</h6>';
            $html .= '<button type="button" class="btn btn-sm btn-outline-primary btn-add-lesson" data-section-id="' . $sectionId . '"><i class="fa-solid fa-plus"></i> ' . __('Add lesson') . '</button>';
            $html .= '</div><div class="sortable-lessons list-group list-group-flush">';
            foreach ($section['lessons'] as $lesson) {
                $lessonId = (int) $lesson['id'];
                $lessonType = lms_escape($lesson['lesson_type']);
                $html .= '<div class="list-group-item d-flex align-items-center justify-content-between" data-id="' . $lessonId . '">';
                $html .= '<span>' . lms_escape($lesson['title']) . ' <small class="text-muted">(' . $lessonType . ')</small></span>';
                $html .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-lesson" data-id="' . $lessonId . '" data-section-id="' . $sectionId . '" data-type="' . $lessonType . '"><i class="fa-solid fa-pen"></i></button>';
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        echo json_encode(['status' => 'success', 'html' => $html]);
        break;

    case 'deleteLesson':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $coursesDB->getLesson($id) && $coursesDB->deleteLesson($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Lesson deleted')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to delete lesson')]);
        }
        break;

    case 'reorderLessons':
        $section_id = isset($_POST['section_id']) ? validate_int($_POST['section_id']) : 0;
        $order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];
        if ($section_id > 0 && is_array($order)) {
            if ($coursesDB->reorderLessons($section_id, $order)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to update order')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
        }
        break;

    case 'searchProducts':
        $q = isset($_GET['q']) ? check_string($_GET['q']) : '';
        $page = max(1, validate_int($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereSql = '';
        if ($q !== '') {
            $whereSql = 'WHERE (name LIKE ? OR id = ?)';
            $params = ['%' . $q . '%', (int) $q];
        }
        $countRow = $SMARTSTUDY->get_row_safe("SELECT COUNT(*) AS total FROM products $whereSql", $params);
        $products = $SMARTSTUDY->get_list_safe(
            "SELECT id, name FROM products $whereSql ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $total = $countRow ? (int) $countRow['total'] : 0;
        echo json_encode([
            'status' => 'success',
            'data' => $products ?: [],
            'has_more' => ($offset + $perPage) < $total
        ]);
        break;

    case 'uploadMedia':
        if (isset($_FILES['file'])) {
            $category = isset($_POST['category']) ? check_string($_POST['category']) : 'image';
            $result = $mediaManager->uploadFile($_FILES['file'], $getUser['id'], $category);
            echo json_encode($result);
        } else {
            echo json_encode(['status' => false, 'message' => __('No file uploaded')]);
        }
        break;

    case 'deleteMedia':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $mediaManager->deleteFile($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Media deleted')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to delete')]);
        }
        break;

    case 'addQuizQuestion':
        $lesson_id = isset($_POST['lesson_id']) ? validate_int($_POST['lesson_id']) : 0;
        if ($lesson_id > 0) {
            $data = [
                'lesson_id' => $lesson_id,
                'question' => check_string($_POST['question'] ?? ''),
                'question_type' => check_string($_POST['question_type'] ?? 'multiple_choice'),
                'options' => $_POST['options'] ?? '[]', // Should be validated JSON
                'explanation' => check_string($_POST['explanation'] ?? ''),
                'points' => validate_int($_POST['points'] ?? 1)
            ];
            $id = $coursesDB->addQuizQuestion($data);
            if ($id) echo json_encode(['status' => 'success', 'id' => $id]);
            else echo json_encode(['status' => 'error']);
        }
        break;

    case 'updateQuizQuestion':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0) {
            $data = [
                'question' => check_string($_POST['question'] ?? ''),
                'question_type' => check_string($_POST['question_type'] ?? 'multiple_choice'),
                'options' => $_POST['options'] ?? '[]',
                'explanation' => check_string($_POST['explanation'] ?? ''),
                'points' => validate_int($_POST['points'] ?? 1)
            ];
            if ($coursesDB->updateQuizQuestion($id, $data)) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error']);
        }
        break;

    case 'deleteQuizQuestion':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $coursesDB->deleteQuizQuestion($id)) {
            echo json_encode(['status' => 'success']);
        }
        break;

    case 'listStudents':
        $course_id = isset($_GET['course_id']) ? validate_int($_GET['course_id']) : 0;
        $limit = isset($_GET['limit']) ? validate_int($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? validate_int($_GET['offset']) : 0;
        $limit = $limit === false ? 50 : min(max(1, $limit), 100);
        $offset = $offset === false ? 0 : max(0, $offset);
        if ($course_id > 0 && lms_course_exists($SMARTSTUDY, $course_id)) {
            $students = $enrollmentsDB->getCourseStudents($course_id, $limit, $offset);
            foreach ($students as &$student) {
                $student['progress'] = $enrollmentsDB->getCourseProgress($student['user_id'], $course_id);
            }
            unset($student);
            echo json_encode(['status' => 'success', 'data' => $students ?: []]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Course not found')]);
        }
        break;

    case 'manualEnroll':
        $user_id = isset($_POST['user_id']) ? validate_int($_POST['user_id']) : 0;
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $user = $user_id > 0 ? $SMARTSTUDY->get_row_safe('SELECT id FROM users WHERE id = ?', [(int) $user_id]) : false;
        if ($user && $course_id > 0 && lms_course_exists($SMARTSTUDY, $course_id)) {
            if ($enrollmentsDB->enrollUser($user_id, $course_id)) {
                echo json_encode(['status' => 'success', 'message' => __('Enrolled successfully')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed or already enrolled')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid user or course')]);
        }
        break;

    case 'removeEnrollment':
        $user_id = isset($_POST['user_id']) ? validate_int($_POST['user_id']) : 0;
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        if ($user_id > 0 && $course_id > 0) {
            if ($enrollmentsDB->cancelEnrollment($user_id, $course_id)) {
                echo json_encode(['status' => 'success', 'message' => __('Enrollment cancelled')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid data')]);
        }
        break;

    case 'listAllStudents':
        $search = isset($_GET['search']) ? check_string($_GET['search']) : '';
        $course_id = isset($_GET['course_id']) ? validate_int($_GET['course_id']) : 0;
        $status = isset($_GET['status']) ? check_string($_GET['status']) : '';
        $sort = isset($_GET['sort']) ? check_string($_GET['sort']) : 'newest';
        $limit = isset($_GET['limit']) ? validate_int($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? validate_int($_GET['offset']) : 0;
        $limit = $limit === false ? 20 : min(max(1, $limit), 100);
        $offset = $offset === false ? 0 : max(0, $offset);

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.fullname LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($course_id > 0) {
            $where[] = "e.course_id = ?";
            $params[] = (int)$course_id;
        }
        if (!empty($status) && in_array($status, ['active', 'expired', 'cancelled', 'suspended', 'completed'], true)) {
            if ($status === 'completed') {
                $where[] = "e.completed_at IS NOT NULL";
            } else {
                $where[] = "e.status = ?";
                $params[] = $status;
            }
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSql = ($sort === 'oldest') ? 'ORDER BY e.enrolled_at ASC' : 'ORDER BY e.enrolled_at DESC';

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM course_enrollments e JOIN users u ON e.user_id = u.id JOIN courses c ON e.course_id = c.id $whereSql";
        $countRow = $SMARTSTUDY->get_row_safe($countSql, $params);
        $total = $countRow ? (int)$countRow['total'] : 0;

        // Get data
        $dataSql = "SELECT e.*, u.username, u.email, u.fullname, c.title as course_title 
                    FROM course_enrollments e 
                    JOIN users u ON e.user_id = u.id 
                    JOIN courses c ON e.course_id = c.id 
                    $whereSql $orderSql LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [(int)$limit, (int)$offset]);
        $students = $SMARTSTUDY->get_list_safe($dataSql, $dataParams);

        // Calculate progress for each student
        if (!empty($students)) {
            foreach ($students as &$student) {
                $student['progress'] = $enrollmentsDB->getCourseProgress($student['user_id'], $student['course_id']);
            }
            unset($student);
        }

        echo json_encode(['status' => 'success', 'data' => $students, 'total' => $total]);
        break;

    case 'enrollmentStats':
        $total = (int) $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments", []);
        $active = (int) $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE status = 'active' AND completed_at IS NULL", []);
        $completed = (int) $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE completed_at IS NOT NULL", []);
        $cancelled = (int) $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE status = 'cancelled'", []);
        echo json_encode(['status' => 'success', 'data' => [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $cancelled
        ]]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
