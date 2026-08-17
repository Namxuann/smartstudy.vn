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

if (!checkPermission($getUser['admin'], 'edit_course')) {
    echo json_encode(['status' => 'error', 'message' => __('You do not have permission to do this')]);
    exit();
}

switch ($action) {
    case 'createCourse':
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        $product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id']) : 0;
        
        if (empty($title) || $product_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => __('Title and Product ID are required')]);
            break;
        }
        
        $slug = $coursesDB->generateSlug($title);
        $data = [
            'title' => $title,
            'product_id' => $product_id,
            'slug' => $slug,
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
        
        $data = [];
        if (isset($_POST['title'])) $data['title'] = check_string($_POST['title']);
        if (isset($_POST['subtitle'])) $data['subtitle'] = check_string($_POST['subtitle']);
        if (isset($_POST['description'])) $data['description'] = $_POST['description']; // Keep HTML
        if (isset($_POST['featured_image'])) $data['featured_image'] = check_string($_POST['featured_image']);
        if (isset($_POST['intro_video'])) $data['intro_video'] = check_string($_POST['intro_video']);
        if (isset($_POST['level'])) $data['level'] = check_string($_POST['level']);
        if (isset($_POST['language'])) $data['language'] = check_string($_POST['language']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($coursesDB->update_by_id($data, $id)) {
            echo json_encode(['status' => 'success', 'message' => __('Course updated successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update course')]);
        }
        break;

    case 'deleteCourse':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $coursesDB->delete_by_id($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Course deleted successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to delete course')]);
        }
        break;

    case 'getCourse':
        $id = isset($_GET['id']) ? validate_int($_GET['id']) : 0;
        $course = $coursesDB->select_by_id($id);
        if ($course) {
            echo json_encode(['status' => 'success', 'data' => $course]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Course not found')]);
        }
        break;

    case 'listCourses':
        $limit = isset($_GET['limit']) ? validate_int($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? validate_int($_GET['offset']) : 0;
        $list = $coursesDB->getPublishedCourses($limit, $offset); // Wait, this gets only published.
        // For admin, we should list all. Let's do raw query
        $list = $SMARTSTUDY->get_list_safe("SELECT * FROM courses ORDER BY created_at DESC LIMIT ? OFFSET ?", [(int)$limit, (int)$offset]);
        echo json_encode(['status' => 'success', 'data' => $list]);
        break;

    case 'publishCourse':
    case 'unpublishCourse':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        $status = ($action === 'publishCourse') ? 1 : 0;
        $data = ['is_published' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status == 1) $data['published_at'] = date('Y-m-d H:i:s');
        
        if ($coursesDB->update_by_id($data, $id)) {
            echo json_encode(['status' => 'success', 'message' => __('Status updated')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Failed to update status')]);
        }
        break;

    case 'createSection':
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        if ($course_id > 0 && !empty($title)) {
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
        if ($id > 0) {
            if ($coursesDB->updateSection($id, ['title' => $title, 'description' => $description])) {
                echo json_encode(['status' => 'success', 'message' => __('Section updated')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to update')]);
            }
        }
        break;

    case 'deleteSection':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $coursesDB->deleteSection($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Section deleted')]);
        }
        break;

    case 'reorderSections':
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];
        if ($course_id > 0 && is_array($order)) {
            if ($coursesDB->reorderSections($course_id, $order)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
        break;

    case 'createLesson':
        $section_id = isset($_POST['section_id']) ? validate_int($_POST['section_id']) : 0;
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        $title = isset($_POST['title']) ? check_string($_POST['title']) : '';
        $type = isset($_POST['lesson_type']) ? check_string($_POST['lesson_type']) : 'text';
        
        if ($section_id > 0 && $course_id > 0 && !empty($title)) {
            $slug = vn2en($title) . '-' . time();
            $data = [
                'section_id' => $section_id,
                'course_id' => $course_id,
                'title' => $title,
                'slug' => check_string($slug),
                'lesson_type' => $type,
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
        if ($id > 0) {
            $data = [
                'title' => check_string($_POST['title'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'lesson_type' => check_string($_POST['lesson_type'] ?? ''),
                'media_url' => check_string($_POST['media_url'] ?? ''),
                'embed_code' => $_POST['embed_code'] ?? '',
                'is_free_preview' => isset($_POST['is_free_preview']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($coursesDB->updateLesson($id, $data)) {
                echo json_encode(['status' => 'success', 'message' => __('Lesson updated')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed to update')]);
            }
        }
        break;

    case 'deleteLesson':
        $id = isset($_POST['id']) ? validate_int($_POST['id']) : 0;
        if ($id > 0 && $coursesDB->deleteLesson($id)) {
            echo json_encode(['status' => 'success', 'message' => __('Lesson deleted')]);
        }
        break;

    case 'reorderLessons':
        $section_id = isset($_POST['section_id']) ? validate_int($_POST['section_id']) : 0;
        $order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];
        if ($section_id > 0 && is_array($order)) {
            if ($coursesDB->reorderLessons($section_id, $order)) {
                echo json_encode(['status' => 'success']);
            }
        }
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
        if ($course_id > 0) {
            $students = $enrollmentsDB->getCourseStudents($course_id, $limit, $offset);
            echo json_encode(['status' => 'success', 'data' => $students]);
        }
        break;

    case 'manualEnroll':
        $user_id = isset($_POST['user_id']) ? validate_int($_POST['user_id']) : 0;
        $course_id = isset($_POST['course_id']) ? validate_int($_POST['course_id']) : 0;
        if ($user_id > 0 && $course_id > 0) {
            if ($enrollmentsDB->enrollUser($user_id, $course_id)) {
                echo json_encode(['status' => 'success', 'message' => __('Enrolled successfully')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Failed or already enrolled')]);
            }
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
        }
        break;

    case 'listAllStudents':
        $search = isset($_GET['search']) ? check_string($_GET['search']) : '';
        $course_id = isset($_GET['course_id']) ? validate_int($_GET['course_id']) : 0;
        $status = isset($_GET['status']) ? check_string($_GET['status']) : '';
        $sort = isset($_GET['sort']) ? check_string($_GET['sort']) : 'newest';
        $limit = isset($_GET['limit']) ? validate_int($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? validate_int($_GET['offset']) : 0;

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
        if (!empty($status)) {
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
        $total = $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments", []);
        $active = $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE status = 'active' AND completed_at IS NULL", []);
        $completed = $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE completed_at IS NOT NULL", []);
        $cancelled = $SMARTSTUDY->num_rows_safe("SELECT id FROM course_enrollments WHERE status = 'cancelled'", []);
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
