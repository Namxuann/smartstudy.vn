<?php
if (!defined('IN_SITE')) { die('The Request Not Found'); }

class MediaManager {
    private $db;
    private $upload_dir;
    private $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
    private $allowed_audio_types = ['audio/mpeg', 'audio/ogg', 'audio/wav'];
    private $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $allowed_doc_types = ['application/pdf', 'application/zip', 'application/x-zip-compressed'];
    private $max_file_size; // bytes
    
    public function __construct($db) {
        $this->db = $db;
        $this->upload_dir = __DIR__ . '/../uploads/courses/';
        $this->max_file_size = 500 * 1024 * 1024; // 500MB
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function uploadFile($file, $uploaded_by, $category = 'image') {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['status' => false, 'message' => 'Invalid parameters.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => false, 'message' => 'Upload error code: ' . $file['error']];
        }
        
        $validate = $this->validateFile($file, $category);
        if (!$validate['status']) {
            return $validate;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_') . '.' . $ext;
        $filepath = $this->upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['status' => false, 'message' => 'Failed to move uploaded file.'];
        }

        $thumbnail_path = null;
        if ($category === 'image') {
            $thumbnail_path = $this->generateThumbnail($filepath, $file['type']);
        }

        $media_data = [
            'uploaded_by' => (int)$uploaded_by,
            'filename' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $file['type'],
            'file_size' => $file['size'],
            'file_path' => 'uploads/courses/' . $filename,
            'storage_type' => 'local',
            'thumbnail_path' => $thumbnail_path,
            'duration' => 0, // Should be calculated for audio/video if possible
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $insert_id = $this->db->insert('course_media', $media_data);
        if ($insert_id) {
            return ['status' => true, 'media_id' => $insert_id, 'file_path' => $media_data['file_path']];
        } else {
            return ['status' => false, 'message' => 'Database error.'];
        }
    }
    
    public function deleteFile($media_id) {
        $media = $this->getFile($media_id);
        if ($media) {
            $path = __DIR__ . '/../' . $media['file_path'];
            if (file_exists($path)) {
                unlink($path);
            }
            if ($media['thumbnail_path']) {
                $thumb_path = __DIR__ . '/../' . $media['thumbnail_path'];
                if (file_exists($thumb_path)) {
                    unlink($thumb_path);
                }
            }
            return $this->db->remove('course_media', "id = ?", [(int)$media_id]);
        }
        return false;
    }
    
    public function getFile($media_id) {
        return $this->db->get_row_safe("SELECT * FROM course_media WHERE id = ?", [(int)$media_id]);
    }
    
    public function getFileUrl($media_id) {
        $media = $this->getFile($media_id);
        if ($media) {
            return BASE_URL($media['file_path']);
        }
        return '';
    }
    
    public function getFilesByUser($user_id) {
        return $this->db->get_list_safe("SELECT * FROM course_media WHERE uploaded_by = ? ORDER BY created_at DESC", [(int)$user_id]);
    }
    
    public function validateFile($file, $category) {
        if ($file['size'] > $this->max_file_size) {
            return ['status' => false, 'message' => 'File size exceeds limit.'];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        $allowed_types = [];
        switch ($category) {
            case 'video':
                $allowed_types = $this->allowed_video_types;
                break;
            case 'audio':
                $allowed_types = $this->allowed_audio_types;
                break;
            case 'image':
                $allowed_types = $this->allowed_image_types;
                break;
            case 'document':
                $allowed_types = $this->allowed_doc_types;
                break;
            default:
                return ['status' => false, 'message' => 'Invalid category.'];
        }
        
        if (!in_array($mime_type, $allowed_types)) {
            return ['status' => false, 'message' => 'Invalid file type for category ' . $category];
        }
        
        return ['status' => true];
    }
    
    public function generateThumbnail($filepath, $mime_type) {
        // Basic implementation or placeholder
        // Can be expanded with GD library
        return null; 
    }
}
