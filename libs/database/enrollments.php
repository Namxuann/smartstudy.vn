<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

class Enrollments extends DB {
    protected $_table_name = 'course_enrollments';
    protected $_key = 'id';
    
    public function __construct() {
        parent::connect();
    }
    public function __destruct() {
        parent::dis_connect();
    }
    public function add_new($data) {
        return parent::insert($this->_table_name, $data);
    }
    public function delete_by_id($id) {
        return $this->remove($this->_table_name, $this->_key.'='.(int)$id);
    }
    public function update_by_id($data, $id) {
        return $this->update($this->_table_name, $data, $this->_key."=".(int)$id);
    }
    public function select_by_id($id) {
        return $this->get_row_safe("SELECT * FROM " . $this->_table_name . " WHERE " . $this->_key . " = ?", [(int)$id]);
    }
    public function get_row_by_id($id) {
        return $this->select_by_id($id);
    }
    public function get_list_by_id($id) {
        return $this->get_list_safe("SELECT * FROM " . $this->_table_name . " WHERE " . $this->_key . " = ?", [(int)$id]);
    }
    
    public function enrollUser($user_id, $course_id, $order_id = null) {
        $existingEnrollment = $this->getEnrollment($user_id, $course_id);
        if ($existingEnrollment && $existingEnrollment['status'] === 'active') {
            return false;
        }

        if ($existingEnrollment) {
            return $this->update($this->_table_name, [
                'order_id' => $order_id,
                'status' => 'active',
                'enrolled_at' => date('Y-m-d H:i:s'),
                'expires_at' => null,
                'completed_at' => null
            ], 'id = ?', [(int) $existingEnrollment['id']]);
        }

        return $this->insert($this->_table_name, [
            'user_id' => (int)$user_id,
            'course_id' => (int)$course_id,
            'order_id' => $order_id,
            'status' => 'active',
            'enrolled_at' => date('Y-m-d H:i:s')
        ]);
    }
    public function isEnrolled($user_id, $course_id) {
        return $this->num_rows_safe("SELECT id FROM " . $this->_table_name . " WHERE user_id = ? AND course_id = ? AND status = 'active'", [(int)$user_id, (int)$course_id]) > 0;
    }
    public function getEnrollment($user_id, $course_id) {
        return $this->get_row_safe("SELECT * FROM " . $this->_table_name . " WHERE user_id = ? AND course_id = ?", [(int)$user_id, (int)$course_id]);
    }
    public function getUserEnrollments($user_id) {
        return $this->get_list_safe("SELECT e.*, c.title, c.slug, c.featured_image FROM " . $this->_table_name . " e JOIN courses c ON e.course_id = c.id WHERE e.user_id = ? ORDER BY e.enrolled_at DESC", [(int)$user_id]);
    }
    public function getCourseStudents($course_id, $limit = 50, $offset = 0) {
        return $this->get_list_safe("SELECT e.*, u.username, u.email, u.fullname FROM " . $this->_table_name . " e JOIN users u ON e.user_id = u.id WHERE e.course_id = ? ORDER BY e.enrolled_at DESC LIMIT ? OFFSET ?", [(int)$course_id, (int)$limit, (int)$offset]);
    }
    public function cancelEnrollment($user_id, $course_id) {
        return $this->update($this->_table_name, ['status' => 'cancelled'], "user_id = ? AND course_id = ?", [(int)$user_id, (int)$course_id]);
    }
    public function markLessonComplete($user_id, $course_id, $lesson_id) {
        $time = date('Y-m-d H:i:s');
        $exists = $this->get_row_safe("SELECT id FROM course_progress WHERE user_id = ? AND lesson_id = ?", [(int)$user_id, (int)$lesson_id]);
        if ($exists) {
            return $this->update('course_progress', ['status' => 'completed', 'completed_at' => $time, 'updated_at' => $time], "id = ?", [(int)$exists['id']]);
        } else {
            return $this->insert('course_progress', [
                'user_id' => (int)$user_id,
                'course_id' => (int)$course_id,
                'lesson_id' => (int)$lesson_id,
                'status' => 'completed',
                'completed_at' => $time,
                'updated_at' => $time
            ]);
        }
    }
    public function markLessonInProgress($user_id, $course_id, $lesson_id) {
        $time = date('Y-m-d H:i:s');
        $exists = $this->get_row_safe("SELECT id, status FROM course_progress WHERE user_id = ? AND lesson_id = ?", [(int)$user_id, (int)$lesson_id]);
        if ($exists) {
            if ($exists['status'] !== 'completed') {
                return $this->update('course_progress', ['status' => 'in_progress', 'updated_at' => $time], "id = ?", [(int)$exists['id']]);
            }
            return true;
        } else {
            return $this->insert('course_progress', [
                'user_id' => (int)$user_id,
                'course_id' => (int)$course_id,
                'lesson_id' => (int)$lesson_id,
                'status' => 'in_progress',
                'updated_at' => $time
            ]);
        }
    }
    public function savePlaybackPosition($user_id, $lesson_id, $position) {
        $time = date('Y-m-d H:i:s');
        $lesson = $this->get_row_safe("SELECT course_id FROM course_lessons WHERE id = ?", [(int)$lesson_id]);
        if (!$lesson) return false;
        $course_id = $lesson['course_id'];
        
        $exists = $this->get_row_safe("SELECT id FROM course_progress WHERE user_id = ? AND lesson_id = ?", [(int)$user_id, (int)$lesson_id]);
        if ($exists) {
            return $this->update('course_progress', ['last_position' => (int)$position, 'updated_at' => $time], "id = ?", [(int)$exists['id']]);
        } else {
            return $this->insert('course_progress', [
                'user_id' => (int)$user_id,
                'course_id' => (int)$course_id,
                'lesson_id' => (int)$lesson_id,
                'status' => 'in_progress',
                'last_position' => (int)$position,
                'updated_at' => $time
            ]);
        }
    }
    public function getCourseProgress($user_id, $course_id) {
        $total_lessons = $this->num_rows_safe("SELECT id FROM course_lessons WHERE course_id = ?", [(int)$course_id]);
        if ($total_lessons == 0) return 0;
        $completed_lessons = $this->num_rows_safe("SELECT id FROM course_progress WHERE user_id = ? AND course_id = ? AND status = 'completed'", [(int)$user_id, (int)$course_id]);
        return round(($completed_lessons / $total_lessons) * 100);
    }
    public function getLessonProgress($user_id, $lesson_id) {
        return $this->get_row_safe("SELECT * FROM course_progress WHERE user_id = ? AND lesson_id = ?", [(int)$user_id, (int)$lesson_id]);
    }
    public function getCompletedLessons($user_id, $course_id) {
        $list = $this->get_list_safe("SELECT lesson_id FROM course_progress WHERE user_id = ? AND course_id = ? AND status = 'completed'", [(int)$user_id, (int)$course_id]);
        return array_column($list, 'lesson_id');
    }
    public function saveQuizAttempt($user_id, $course_id, $lesson_id, $score) {
        $time = date('Y-m-d H:i:s');
        $exists = $this->get_row_safe("SELECT id, quiz_score, quiz_attempts FROM course_progress WHERE user_id = ? AND lesson_id = ?", [(int)$user_id, (int)$lesson_id]);
        if ($exists) {
            $new_score = max((float)$exists['quiz_score'], (float)$score);
            return $this->update('course_progress', [
                'quiz_score' => $new_score,
                'quiz_attempts' => (int)$exists['quiz_attempts'] + 1,
                'updated_at' => $time
            ], "id = ?", [(int)$exists['id']]);
        } else {
            return $this->insert('course_progress', [
                'user_id' => (int)$user_id,
                'course_id' => (int)$course_id,
                'lesson_id' => (int)$lesson_id,
                'status' => 'in_progress',
                'quiz_score' => (float)$score,
                'quiz_attempts' => 1,
                'updated_at' => $time
            ]);
        }
    }
    public function markCourseComplete($user_id, $course_id) {
        return $this->update($this->_table_name, ['completed_at' => date('Y-m-d H:i:s')], "user_id = ? AND course_id = ?", [(int)$user_id, (int)$course_id]);
    }
}
