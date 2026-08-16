<?php
/**
 * Plugin Name: Smartstudy LMS Bridge
 * Description: Đăng nhập một lần từ Smartstudy, đồng bộ học viên và tự động cấp quyền LearnDash sau khi thanh toán.
 * Version: 1.1.0
 * Author: Smartstudy
 */

defined('ABSPATH') || exit;
require_once __DIR__ . '/config.php';

function smartstudy_bridge_base64url_decode($value)
{
    $padding = strlen($value) % 4;
    if ($padding) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function smartstudy_bridge_get_or_create_user(array $data)
{
    $email = sanitize_email($data['email'] ?? '');
    $externalId = absint($data['user_id'] ?? $data['uid'] ?? 0);
    if (!$email || !$externalId) {
        return new WP_Error('invalid_student', 'Thiếu email hoặc mã học viên.', ['status' => 400]);
    }

    $existing = get_users([
        'meta_key'   => 'smartstudy_user_id',
        'meta_value' => (string)$externalId,
        'number'     => 1,
        'fields'     => 'ID'
    ]);
    $userId = $existing ? (int)$existing[0] : (int)email_exists($email);

    if (!$userId) {
        $requestedLogin = sanitize_user($data['username'] ?? '', true);
        $login = $requestedLogin ?: 'smartstudy_' . $externalId;
        if (username_exists($login)) {
            $login = 'smartstudy_' . $externalId;
        }
        if (username_exists($login)) {
            $login .= '_' . wp_generate_password(5, false, false);
        }
        $userId = wp_create_user($login, wp_generate_password(32, true, true), $email);
        if (is_wp_error($userId)) {
            return $userId;
        }
        wp_update_user([
            'ID'           => $userId,
            'display_name' => sanitize_text_field($data['full_name'] ?? $data['name'] ?? $login),
            'role'         => 'subscriber'
        ]);
    }

    update_user_meta($userId, 'smartstudy_user_id', $externalId);
    return get_user_by('id', $userId);
}

function smartstudy_bridge_handle_sso()
{
    if (empty($_GET['smartstudy_sso'])) {
        return;
    }
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $signature = isset($_GET['sig']) ? sanitize_text_field(wp_unslash($_GET['sig'])) : '';
    $expected = hash_hmac('sha256', $token, SMARTSTUDY_BRIDGE_SECRET);
    if (!$token || !$signature || !hash_equals($expected, $signature)) {
        wp_die('Liên kết đăng nhập không hợp lệ.', 'Smartstudy SSO', ['response' => 403]);
    }

    $decoded = smartstudy_bridge_base64url_decode($token);
    $payload = $decoded !== false ? json_decode($decoded, true) : null;
    if (!is_array($payload) || empty($payload['exp']) || (int)$payload['exp'] < time() || (int)$payload['exp'] > time() + 300) {
        wp_die('Liên kết đăng nhập đã hết hạn.', 'Smartstudy SSO', ['response' => 403]);
    }
    $jti = sanitize_key($payload['jti'] ?? '');
    if (!$jti || get_transient('smartstudy_sso_' . $jti)) {
        wp_die('Liên kết đăng nhập đã được sử dụng.', 'Smartstudy SSO', ['response' => 403]);
    }
    set_transient('smartstudy_sso_' . $jti, 1, 5 * MINUTE_IN_SECONDS);

    $user = smartstudy_bridge_get_or_create_user($payload);
    if (is_wp_error($user)) {
        wp_die(esc_html($user->get_error_message()), 'Smartstudy SSO', ['response' => 400]);
    }
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());
    do_action('wp_login', $user->user_login, $user);

    $redirect = (string)($payload['redirect'] ?? '/courses/');
    if (!$redirect || $redirect[0] !== '/' || strpos($redirect, '//') === 0) {
        $redirect = '/courses/';
    }
    wp_safe_redirect(home_url($redirect));
    exit;
}
add_action('template_redirect', 'smartstudy_bridge_handle_sso', 1);

function smartstudy_bridge_rest_permission(WP_REST_Request $request)
{
    $timestamp = $request->get_header('x-smartstudy-timestamp');
    $signature = $request->get_header('x-smartstudy-signature');
    if (!$timestamp || !$signature || abs(time() - (int)$timestamp) > 300) {
        return new WP_Error('invalid_timestamp', 'Yêu cầu đã hết hạn.', ['status' => 403]);
    }
    $expected = hash_hmac('sha256', $timestamp . "\n" . $request->get_body(), SMARTSTUDY_BRIDGE_SECRET);
    if (!hash_equals($expected, $signature)) {
        return new WP_Error('invalid_signature', 'Chữ ký không hợp lệ.', ['status' => 403]);
    }
    return true;
}

function smartstudy_bridge_enroll(WP_REST_Request $request)
{
    if (!function_exists('ld_update_course_access')) {
        return new WP_Error('lms_unavailable', 'LearnDash chưa được kích hoạt.', ['status' => 503]);
    }
    $data = $request->get_json_params();
    $courseId = absint($data['course_id'] ?? 0);
    if (!$courseId || get_post_type($courseId) !== 'sfwd-courses') {
        return new WP_Error('invalid_course', 'Mã khóa học LearnDash không hợp lệ.', ['status' => 400]);
    }

    $user = smartstudy_bridge_get_or_create_user($data);
    if (is_wp_error($user)) {
        return $user;
    }
    ld_update_course_access($user->ID, $courseId, false);
    update_user_meta($user->ID, 'smartstudy_last_order', sanitize_text_field($data['trans_id'] ?? ''));

    return new WP_REST_Response([
        'status'    => 'success',
        'user_id'   => $user->ID,
        'course_id' => $courseId
    ], 200);
}

function smartstudy_bridge_register_routes()
{
    register_rest_route('smartstudy/v1', '/enroll', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'smartstudy_bridge_enroll',
        'permission_callback' => 'smartstudy_bridge_rest_permission'
    ]);
}
add_action('rest_api_init', 'smartstudy_bridge_register_routes');

function smartstudy_bridge_learning_header($courseCount = 0)
{
    return '<header class="smartstudy-learning-header">'
        . '<div><span class="smartstudy-eyebrow">SMARTSTUDY LEARNING</span><h1>Khóa học</h1>'
        . '<p>Tiếp tục bài học và theo dõi tiến độ của bạn tại một nơi.</p></div>'
        . '<label class="smartstudy-search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Tìm kiếm khóa học..." aria-label="Tìm kiếm khóa học"></label>'
        . '</header>'
        . '<nav class="smartstudy-learning-tabs" aria-label="Thư viện khóa học">'
        . '<span class="is-active">Khóa học của tôi <b>' . absint($courseCount) . '</b></span>'
        . '</nav>';
}

function smartstudy_bridge_empty_library($title, $message, $buttonUrl = '', $buttonLabel = '')
{
    $button = '';
    if ($buttonUrl && $buttonLabel) {
        $button = '<a class="smartstudy-button" href="' . esc_url($buttonUrl) . '">' . esc_html($buttonLabel) . '</a>';
    }

    return '<section class="smartstudy-learning-shell">'
        . smartstudy_bridge_learning_header(0)
        . '<div class="smartstudy-empty"><span class="smartstudy-empty-icon" aria-hidden="true">▶</span>'
        . '<h2>' . esc_html($title) . '</h2><p>' . esc_html($message) . '</p>' . $button . '</div></section>';
}

function smartstudy_bridge_course_progress($courseId, $userId)
{
    $percentage = 0;
    if (function_exists('learndash_course_progress')) {
        $progress = learndash_course_progress([
            'user_id'   => $userId,
            'course_id' => $courseId,
            'array'     => true
        ]);
        if (is_array($progress) && isset($progress['percentage'])) {
            $percentage = (int)$progress['percentage'];
        }
    }
    return max(0, min(100, $percentage));
}

function smartstudy_bridge_course_steps($courseId)
{
    if (function_exists('learndash_get_course_steps_count')) {
        return (int)learndash_get_course_steps_count($courseId);
    }
    return 0;
}

function smartstudy_bridge_course_library()
{
    if (!is_user_logged_in()) {
        return smartstudy_bridge_empty_library(
            'Đăng nhập để tiếp tục học',
            'Vui lòng đăng nhập từ Smartstudy để xem các khóa học đã mua.',
            'https://smartstudy.vn/client/learning',
            'Đăng nhập qua Smartstudy'
        );
    }
    if (!post_type_exists('sfwd-courses')) {
        return smartstudy_bridge_empty_library(
            'Nền tảng học tập đã sẵn sàng',
            'Thư viện khóa học sẽ xuất hiện tại đây sau khi LearnDash được kích hoạt và khóa học đầu tiên được xuất bản.'
        );
    }

    $courses = get_posts([
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC'
    ]);
    $available = [];
    foreach ($courses as $course) {
        if (function_exists('sfwd_lms_has_access') && sfwd_lms_has_access($course->ID, get_current_user_id())) {
            $available[] = $course;
        }
    }
    if (!$available) {
        return smartstudy_bridge_empty_library(
            'Bạn chưa có khóa học nào',
            'Các khóa học đã mua sẽ tự động xuất hiện trong thư viện này.',
            'https://smartstudy.vn/',
            'Xem khóa học'
        );
    }

    $userId = get_current_user_id();
    $html = '<section class="smartstudy-learning-shell">' . smartstudy_bridge_learning_header(count($available));
    $html .= '<div class="smartstudy-library-toolbar"><span>Tiến trình của tôi</span><span class="smartstudy-view-toggle" aria-hidden="true">▦</span></div>';
    $html .= '<div class="smartstudy-grid">';
    foreach ($available as $course) {
        $image = get_the_post_thumbnail_url($course->ID, 'large');
        $permalink = get_permalink($course);
        $title = get_the_title($course);
        $progress = smartstudy_bridge_course_progress($course->ID, $userId);
        $steps = smartstudy_bridge_course_steps($course->ID);
        $author = get_the_author_meta('display_name', (int)$course->post_author);
        $status = $progress >= 100 ? 'Hoàn thành' : ($progress > 0 ? 'Đang học' : 'Bắt đầu học');

        $html .= '<article class="smartstudy-course" data-course-title="' . esc_attr(wp_strip_all_tags($title)) . '">';
        $html .= '<a class="smartstudy-course-media" href="' . esc_url($permalink) . '">';
        if ($image) {
            $html .= '<img src="' . esc_url($image) . '" alt="">';
        } else {
            $html .= '<span class="smartstudy-course-fallback"><small>SMARTSTUDY</small>' . esc_html($title) . '</span>';
        }
        $html .= '<strong class="smartstudy-status">' . esc_html($status) . '</strong>';
        if ($steps > 0) {
            $html .= '<span class="smartstudy-step-count">' . absint($steps) . ' bài học</span>';
        }
        $html .= '</a><div class="smartstudy-course-body">';
        $html .= '<h2><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h2>';
        if ($author) {
            $html .= '<p class="smartstudy-instructor"><span aria-hidden="true">●</span>' . esc_html($author) . '</p>';
        }
        $html .= '<div class="smartstudy-progress" aria-label="Tiến độ ' . absint($progress) . '%"><span style="width:' . absint($progress) . '%"></span></div>';
        $html .= '<div class="smartstudy-course-footer"><span>' . absint($progress) . '% hoàn thành</span><a href="' . esc_url($permalink) . '">' . ($progress > 0 ? 'Tiếp tục' : 'Vào học') . ' →</a></div>';
        $html .= '</div></article>';
    }
    return $html . '</div></section>';
}
add_shortcode('smartstudy_course_library', 'smartstudy_bridge_course_library');

function smartstudy_bridge_styles()
{
    wp_register_style('smartstudy-bridge', false, [], '1.1.0');
    wp_enqueue_style('smartstudy-bridge');
    wp_add_inline_style('smartstudy-bridge', '.smartstudy-learning-shell{max-width:1180px;margin:58px auto 90px;padding:0 24px;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#171717}.smartstudy-learning-header{display:flex;align-items:flex-end;justify-content:space-between;gap:32px;margin-bottom:30px}.smartstudy-learning-header h1{font-size:42px;line-height:1.1;margin:7px 0 9px;letter-spacing:-.035em}.smartstudy-learning-header p{margin:0;color:#687080}.smartstudy-eyebrow{color:#143cff;font-size:11px;font-weight:800;letter-spacing:.15em}.smartstudy-search{display:flex;align-items:center;gap:8px;width:270px;padding:11px 14px;border:1px solid #e2e5ea;border-radius:10px;background:#fff;color:#969ca8}.smartstudy-search input{width:100%;border:0!important;outline:0;background:transparent;padding:0!important;font-size:14px}.smartstudy-learning-tabs{display:flex;gap:30px;border-bottom:1px solid #e8e9ed;margin-bottom:25px}.smartstudy-learning-tabs span{position:relative;padding:0 0 14px;color:#687080;font-weight:650}.smartstudy-learning-tabs .is-active{color:#171717}.smartstudy-learning-tabs .is-active:after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#143cff}.smartstudy-learning-tabs b{display:inline-flex;align-items:center;justify-content:center;min-width:23px;height:21px;margin-left:5px;border-radius:6px;background:#f0f1f4;font-size:12px}.smartstudy-library-toolbar{display:flex;justify-content:flex-end;align-items:center;gap:12px;margin:0 0 24px;color:#626a78;font-size:14px}.smartstudy-view-toggle{display:inline-flex;align-items:center;justify-content:center;width:38px;height:34px;border:1px solid #e2e5ea;border-radius:8px;color:#143cff}.smartstudy-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}.smartstudy-course{overflow:hidden;border:1px solid #e2e4e8;border-radius:12px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.04);transition:transform .2s ease,box-shadow .2s ease}.smartstudy-course:hover{transform:translateY(-3px);box-shadow:0 16px 34px rgba(15,23,42,.09)}.smartstudy-course-media{position:relative;display:block;height:188px;overflow:hidden;background:#080808;text-decoration:none}.smartstudy-course-media img{width:100%;height:100%;object-fit:cover}.smartstudy-course-fallback{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:9px;width:100%;height:100%;padding:24px;color:#fff;text-align:center;font-size:21px;font-weight:800;line-height:1.25;letter-spacing:.01em;background:radial-gradient(circle at 75% 20%,#303030 0,transparent 38%),#080808}.smartstudy-course-fallback small{font-size:9px;font-weight:700;letter-spacing:.2em;color:#aab0bd}.smartstudy-status{position:absolute;left:0;top:14px;padding:6px 13px 6px 16px;border-radius:0 16px 16px 0;background:#143cff;color:#fff;font-size:10px;text-transform:uppercase;letter-spacing:.08em}.smartstudy-step-count{position:absolute;right:12px;bottom:10px;padding:5px 9px;border-radius:7px;background:rgba(0,0,0,.68);color:#fff;font-size:11px;font-weight:650}.smartstudy-course-body{padding:21px}.smartstudy-course h2{font-size:21px;line-height:1.3;margin:0 0 14px}.smartstudy-course h2 a{color:#171717;text-decoration:none}.smartstudy-instructor{display:flex;align-items:center;gap:8px;margin:0 0 19px;color:#4f5663;font-size:13px}.smartstudy-instructor span{color:#143cff;font-size:12px}.smartstudy-progress{height:4px;overflow:hidden;border-radius:999px;background:#e5e7eb}.smartstudy-progress span{display:block;height:100%;border-radius:999px;background:#143cff}.smartstudy-course-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:10px;font-size:12px;color:#737a87}.smartstudy-course-footer a{color:#143cff;text-decoration:none;font-weight:750}.smartstudy-empty{text-align:center;padding:80px 24px;border:1px solid #e4e6eb;border-radius:14px;background:#fafbfc}.smartstudy-empty-icon{display:inline-flex;align-items:center;justify-content:center;width:54px;height:54px;margin-bottom:16px;border-radius:50%;background:#111;color:#fff;padding-left:3px}.smartstudy-empty h2{margin:0 0 8px;font-size:26px}.smartstudy-empty p{max-width:590px;margin:0 auto 24px;color:#687080}.smartstudy-button{display:inline-block;padding:12px 20px;border-radius:8px;background:#143cff;color:#fff!important;text-decoration:none;font-weight:750}@media(max-width:900px){.smartstudy-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.smartstudy-learning-shell{margin-top:34px;padding:0 16px}.smartstudy-learning-header{align-items:stretch;flex-direction:column}.smartstudy-learning-header h1{font-size:34px}.smartstudy-search{width:auto}.smartstudy-grid{grid-template-columns:1fr}.smartstudy-course-media{height:205px}.smartstudy-library-toolbar{justify-content:flex-start}}');
}

function smartstudy_bridge_library_script()
{
    if (!is_page('courses')) {
        return;
    }
    echo '<script>(function(){var input=document.querySelector(".smartstudy-search input");if(!input)return;input.addEventListener("input",function(){var query=this.value.trim().toLowerCase();document.querySelectorAll(".smartstudy-course").forEach(function(card){card.hidden=query && card.dataset.courseTitle.toLowerCase().indexOf(query)===-1;});});})();</script>';
}
add_action('wp_footer', 'smartstudy_bridge_library_script', 30);

function smartstudy_bridge_body_class($classes)
{
    if (is_page('courses')) {
        $classes[] = 'smartstudy-learning-page';
    }
    return $classes;
}
add_filter('body_class', 'smartstudy_bridge_body_class');

function smartstudy_bridge_page_head()
{
    if (is_page('courses')) {
        echo '<style>.smartstudy-learning-page .wp-block-post-title{display:none}</style>';
    }
}
add_action('wp_head', 'smartstudy_bridge_page_head', 30);
add_action('wp_enqueue_scripts', 'smartstudy_bridge_styles');

function smartstudy_bridge_activate()
{
    if (!get_page_by_path('courses')) {
        wp_insert_post([
            'post_title'   => 'Khóa học của tôi',
            'post_name'    => 'courses',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[smartstudy_course_library]'
        ]);
    }
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'smartstudy_bridge_activate');

function smartstudy_bridge_admin_notice()
{
    if (current_user_can('activate_plugins') && !function_exists('ld_update_course_access')) {
        echo '<div class="notice notice-warning"><p><strong>Smartstudy LMS Bridge:</strong> SSO đã sẵn sàng. Cài và kích hoạt LearnDash bản quyền để bật thiết kế bài giảng và tự động cấp khóa học.</p></div>';
    }
}
add_action('admin_notices', 'smartstudy_bridge_admin_notice');
