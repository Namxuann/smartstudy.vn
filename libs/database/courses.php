<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

class Courses extends DB {
    protected $_table_name = 'courses';
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
    
    public function getCourseBySlug($slug) {
        return $this->get_row_safe("SELECT * FROM " . $this->_table_name . " WHERE slug = ?", [$slug]);
    }
    public function getCourseByProductId($product_id) {
        return $this->get_row_safe("SELECT * FROM " . $this->_table_name . " WHERE product_id = ?", [(int)$product_id]);
    }
    public function getPublishedCourses($limit, $offset, $category_id = 0) {
        $sql = "SELECT * FROM " . $this->_table_name . " WHERE is_published = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->get_list_safe($sql, [(int)$limit, (int)$offset]);
    }
    public function getFullCurriculum($course_id) {
        $sections = $this->getSections($course_id);
        $curriculum = [];
        foreach ($sections as $section) {
            $section['lessons'] = $this->getLessons($section['id']);
            $curriculum[] = $section;
        }
        return $curriculum;
    }
    public function getSections($course_id) {
        return $this->get_list_safe("SELECT * FROM course_sections WHERE course_id = ? ORDER BY sort_order ASC, id ASC", [(int)$course_id]);
    }
    public function addSection($data) {
        return $this->insert('course_sections', $data);
    }
    public function updateSection($id, $data) {
        return $this->update('course_sections', $data, "id = ?", [(int)$id]);
    }
    public function deleteSection($id) {
        return $this->remove('course_sections', "id = ?", [(int)$id]);
    }
    public function reorderSections($course_id, $order_array) {
        foreach ($order_array as $sort_order => $section_id) {
            $section = $this->get_row_safe(
                'SELECT id, sort_order FROM course_sections WHERE id = ? AND course_id = ?',
                [(int) $section_id, (int) $course_id]
            );
            if (!$section) {
                return false;
            }
            if ((int) $section['sort_order'] !== (int) $sort_order
                && !$this->update('course_sections', ['sort_order' => (int) $sort_order], 'id = ? AND course_id = ?', [(int) $section_id, (int) $course_id])) {
                return false;
            }
        }
        return true;
    }
    public function getLessons($section_id) {
        return $this->get_list_safe("SELECT * FROM course_lessons WHERE section_id = ? ORDER BY sort_order ASC, id ASC", [(int)$section_id]);
    }
    public function getLesson($id) {
        return $this->get_row_safe("SELECT * FROM course_lessons WHERE id = ?", [(int)$id]);
    }
    public function addLesson($data) {
        return $this->insert('course_lessons', $data);
    }
    public function updateLesson($id, $data) {
        return $this->update('course_lessons', $data, "id = ?", [(int)$id]);
    }
    public function deleteLesson($id) {
        return $this->remove('course_lessons', "id = ?", [(int)$id]);
    }
    public function reorderLessons($section_id, $order_array) {
        foreach ($order_array as $sort_order => $lesson_id) {
            $lesson = $this->get_row_safe(
                'SELECT id, sort_order FROM course_lessons WHERE id = ? AND section_id = ?',
                [(int) $lesson_id, (int) $section_id]
            );
            if (!$lesson) {
                return false;
            }
            if ((int) $lesson['sort_order'] !== (int) $sort_order
                && !$this->update('course_lessons', ['sort_order' => (int) $sort_order], 'id = ? AND section_id = ?', [(int) $lesson_id, (int) $section_id])) {
                return false;
            }
        }
        return true;
    }
    public function getQuizQuestions($lesson_id) {
        return $this->get_list_safe("SELECT * FROM course_quiz_questions WHERE lesson_id = ? ORDER BY sort_order ASC, id ASC", [(int)$lesson_id]);
    }
    public function addQuizQuestion($data) {
        return $this->insert('course_quiz_questions', $data);
    }
    public function updateQuizQuestion($id, $data) {
        return $this->update('course_quiz_questions', $data, "id = ?", [(int)$id]);
    }
    public function deleteQuizQuestion($id) {
        return $this->remove('course_quiz_questions', "id = ?", [(int)$id]);
    }
    public function countStudents($course_id) {
        return $this->num_rows_safe("SELECT id FROM course_enrollments WHERE course_id = ? AND status = 'active'", [(int)$course_id]);
    }
    public function countLessons($course_id) {
        return $this->num_rows_safe("SELECT id FROM course_lessons WHERE course_id = ?", [(int)$course_id]);
    }
    public function getTotalDuration($course_id) {
        $row = $this->get_row_safe("SELECT SUM(media_duration) as total_duration FROM course_lessons WHERE course_id = ?", [(int)$course_id]);
        return $row && $row['total_duration'] ? (int)$row['total_duration'] : 0;
    }
    public function generateSlug($title) {
        $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', create_slug($title))));
        $base_slug = $slug;
        $counter = 1;
        while ($this->num_rows_safe("SELECT id FROM " . $this->_table_name . " WHERE slug = ?", [$slug]) > 0) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        return $slug;
    }
}
