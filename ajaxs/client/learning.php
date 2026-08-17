<?php
define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/../../libs/database/courses.php');
require_once(__DIR__ . '/../../libs/database/enrollments.php');

$coursesDB = new Courses();
$enrollmentsDB = new Enrollments();

$action = isset($_POST['action']) ? check_string($_POST['action']) : (isset($_GET['action']) ? check_string($_GET['action']) : '');
$user_id = $getUser['id'];

switch ($action) {
    case 'getCurriculum':
        $course_id = isset($_GET['course_id']) ? validate_int($_GET['course_id']) : 0;
        if ($course_id > 0) {
            $curriculum = $coursesDB->getFullCurriculum($course_id);
            if ($enrollmentsDB->isEnrolled($user_id, $course_id)) {
                $completed = $enrollmentsDB->getCompletedLessons($user_id, $course_id);
                // Attach progress to each lesson
                foreach ($curriculum as &$section) {
                    foreach ($section['lessons'] as &$lesson) {
                        $lesson['is_completed'] = in_array($lesson['id'], $completed);
                    }
                }
            }
            echo json_encode(['status' => 'success', 'data' => $curriculum]);
        } else {
            echo json_encode(['status' => 'error', 'message' => __('Invalid course ID')]);
        }
        break;

    case 'getLessonContent':
        $lesson_id = isset($_GET['lesson_id']) ? validate_int($_GET['lesson_id']) : 0;
        if ($lesson_id > 0) {
            $lesson = $coursesDB->getLesson($lesson_id);
            if ($lesson) {
                $course_id = $lesson['course_id'];
                if ($lesson['is_free_preview'] || $enrollmentsDB->isEnrolled($user_id, $course_id)) {
                    $progress = $enrollmentsDB->getLessonProgress($user_id, $lesson_id);
                    $response = [
                        'status' => 'success',
                        'data' => [
                            'id' => $lesson['id'],
                            'title' => $lesson['title'],
                            'content' => $lesson['content'],
                            'type' => $lesson['lesson_type'],
                            'media_url' => $lesson['media_url'],
                            'embed_code' => $lesson['embed_code'],
                            'attachments' => $lesson['attachments'] ? json_decode($lesson['attachments'], true) : [],
                            'progress' => $progress
                        ]
                    ];
                    if ($lesson['lesson_type'] === 'quiz') {
                        $response['data']['questions'] = $coursesDB->getQuizQuestions($lesson_id);
                        // Hide correct answers from client side
                        foreach ($response['data']['questions'] as &$q) {
                            $options = json_decode($q['options'], true);
                            foreach ($options as &$opt) {
                                unset($opt['is_correct']);
                            }
                            $q['options'] = $options;
                        }
                    }
                    echo json_encode($response);
                } else {
                    echo json_encode(['status' => 'error', 'message' => __('You need to enroll to view this lesson')]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Lesson not found')]);
            }
        }
        break;

    case 'markComplete':
        $lesson_id = isset($_POST['lesson_id']) ? validate_int($_POST['lesson_id']) : 0;
        if ($lesson_id > 0) {
            $lesson = $coursesDB->getLesson($lesson_id);
            if ($lesson && $enrollmentsDB->isEnrolled($user_id, $lesson['course_id'])) {
                $enrollmentsDB->markLessonComplete($user_id, $lesson['course_id'], $lesson_id);
                $progress_pct = $enrollmentsDB->getCourseProgress($user_id, $lesson['course_id']);
                
                if ($progress_pct >= 100) {
                    $enrollmentsDB->markCourseComplete($user_id, $lesson['course_id']);
                }
                
                echo json_encode(['status' => 'success', 'progress' => $progress_pct]);
            } else {
                echo json_encode(['status' => 'error', 'message' => __('Not enrolled or invalid lesson')]);
            }
        }
        break;

    case 'saveProgress':
        $lesson_id = isset($_POST['lesson_id']) ? validate_int($_POST['lesson_id']) : 0;
        $position = isset($_POST['position']) ? validate_int($_POST['position']) : 0;
        if ($lesson_id > 0) {
            $lesson = $coursesDB->getLesson($lesson_id);
            if ($lesson && $enrollmentsDB->isEnrolled($user_id, $lesson['course_id'])) {
                $enrollmentsDB->savePlaybackPosition($user_id, $lesson_id, $position);
                echo json_encode(['status' => 'success']);
            }
        }
        break;

    case 'submitQuiz':
        $lesson_id = isset($_POST['lesson_id']) ? validate_int($_POST['lesson_id']) : 0;
        $answers = isset($_POST['answers']) ? json_decode($_POST['answers'], true) : [];
        if ($lesson_id > 0 && is_array($answers)) {
            $lesson = $coursesDB->getLesson($lesson_id);
            if ($lesson && $enrollmentsDB->isEnrolled($user_id, $lesson['course_id'])) {
                $questions = $coursesDB->getQuizQuestions($lesson_id);
                $score = 0;
                $total_points = 0;
                $results = [];
                
                foreach ($questions as $q) {
                    $total_points += $q['points'];
                    $q_id = $q['id'];
                    $user_ans = $answers[$q_id] ?? null;
                    $is_correct = false;
                    
                    if ($q['question_type'] == 'multiple_choice' || $q['question_type'] == 'true_false') {
                        $options = json_decode($q['options'], true);
                        foreach ($options as $index => $opt) {
                            if (!empty($opt['is_correct']) && $user_ans == $index) {
                                $is_correct = true;
                                break;
                            }
                        }
                    }
                    
                    if ($is_correct) {
                        $score += $q['points'];
                    }
                    $results[$q_id] = ['correct' => $is_correct];
                }
                
                $final_score = ($total_points > 0) ? round(($score / $total_points) * 100, 2) : 0;
                $enrollmentsDB->saveQuizAttempt($user_id, $lesson['course_id'], $lesson_id, $final_score);
                
                echo json_encode([
                    'status' => 'success', 
                    'score' => $final_score,
                    'results' => $results
                ]);
            }
        }
        break;

    case 'getMyEnrollments':
        $enrollments = $enrollmentsDB->getUserEnrollments($user_id);
        foreach ($enrollments as &$enrollment) {
            $enrollment['progress_percentage'] = $enrollmentsDB->getCourseProgress($user_id, $enrollment['course_id']);
        }
        echo json_encode(['status' => 'success', 'data' => $enrollments]);
        break;

    case 'getProgress':
        $course_id = isset($_GET['course_id']) ? validate_int($_GET['course_id']) : 0;
        if ($course_id > 0) {
            $progress_pct = $enrollmentsDB->getCourseProgress($user_id, $course_id);
            $completed = $enrollmentsDB->getCompletedLessons($user_id, $course_id);
            echo json_encode(['status' => 'success', 'data' => ['percentage' => $progress_pct, 'completed_lessons' => $completed]]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
