<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<div class="tab-pane text-muted show active" id="addons" role="tabpanel">
    <h4 class="mb-4"><i class="fa-solid fa-puzzle-piece text-info me-2"></i>Tính năng mở rộng Smartstudy.vn</h4>


    <!-- BẮT ĐẦU ACCORDION (danh sách Addon) -->
    <div class="accordion" id="accordionAddons">

        <!-- Các tính năng đang được Smartstudy.vn quản lý nội bộ -->

        <!-- ADDON PREVIEW UID -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingPreviewUid">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapsePreviewUid" aria-expanded="false"
                    aria-controls="collapsePreviewUid">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-eye fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Xem Trước UID</h5>
                            <small class="text-muted">Cho phép khách hàng xem trước UID trước khi mua hàng</small>
                        </div>
                        <span class="badge bg-success ms-2">Sẵn sàng</span>
                    </div>
                </button>
            </h2>
            <div id="collapsePreviewUid"
                class="accordion-collapse collapse border-top"
                aria-labelledby="headingPreviewUid"
                data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <div class="row g-4">
                        <!-- Cột mô tả -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn bật tính năng
                                        <strong>xem trước UID</strong>
                                        cho từng sản phẩm. Khi bật, khách hàng có thể
                                        xem trước UID trước khi quyết định mua hàng.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Cấu hình ON/OFF cho từng sản phẩm riêng biệt.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Tùy chọn xuất hiện tại trang Thêm/Sửa sản phẩm.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Mặc định OFF, an toàn cho mọi sản phẩm.</span>
                                        </li>
                                    </ul>

                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3 mb-3" style="padding-top: 56.25%;">
                                        <img src="https://i.postimg.cc/SNYYKPPs/A-nh-ma-n-hi-nh-2026-03-23-lu-c-19-44-33.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon Xem Trước UID" loading="lazy">
                                    </div>

                                    <!-- Trạng thái tính năng -->
                                    <div class="mt-4 pt-3 border-top">
                                        <h6 class="fw-semibold mb-1">Đã tích hợp trong Smartstudy.vn</h6>
                                        <small class="text-muted">Tính năng được quản lý nội bộ và không cần mã kích hoạt.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cột hướng dẫn -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-sliders-h text-warning me-2"></i>
                                        Hướng Dẫn Sử Dụng
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Tính năng đang sẵn sàng trên hệ thống Smartstudy.vn.
                                    </div>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <strong>Hướng dẫn:</strong> Vào trang Thêm/Sửa sản phẩm để bật tùy chọn "Xem trước UID" cho từng sản phẩm.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADDON BOT QUẢN LÝ TELEGRAM -->
        <?php
        // URL webhook cố định trỏ về endpoint api/webhook_bot_telegram.php (đường dẫn do core quản lý)
        $botQuanLyWebhookUrl = base_url('api/webhook_bot_telegram.php');
        ?>
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingBotQuanLy">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseBotQuanLy" aria-expanded="false"
                    aria-controls="collapseBotQuanLy">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fa-brands fa-telegram fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Bot Quản Lý Telegram</h5>
                            <small class="text-muted">Ra lệnh quản trị website qua Telegram: cộng/trừ tiền, khoá user, xem doanh thu, đơn hàng...</small>
                        </div>
                        <span class="badge bg-success ms-2">Sẵn sàng</span>
                    </div>
                </button>
            </h2>
            <div id="collapseBotQuanLy" class="accordion-collapse collapse border-top"
                aria-labelledby="headingBotQuanLy" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <div class="row g-4">

                        <!-- Cột mô tả + demo + giá -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon cho phép bạn <strong>quản trị toàn bộ website qua Telegram</strong>.
                                        Chỉ cần tạo 1 Bot, cấp quyền cho username Telegram của bạn, bạn có thể
                                        cộng/trừ tiền user, khoá/mở user, xem đơn hàng, thống kê doanh thu, top users...
                                        ngay trên điện thoại mà không cần đăng nhập Admin Panel.
                                    </p>
                                    <ul class="list-unstyled mb-3">
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Quản lý user: cộng/trừ tiền, khoá, đổi mật khẩu, xem log.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Quản lý đơn hàng: xem đơn gần đây, tra chi tiết 1 đơn.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Thống kê nhanh: doanh thu ngày/tuần/tháng, top nạp tiền, top sản phẩm.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Bảo mật với Secret Token, chỉ username trong whitelist mới có quyền gọi lệnh.</span>
                                        </li>
                                    </ul>

                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-terminal me-2"></i>
                                        <strong>Một số lệnh chính:</strong>
                                        <code>/help</code>, <code>/addfund</code>, <code>/removefund</code>,
                                        <code>/balance</code>, <code>/orders</code>, <code>/revenuetoday</code>,
                                        <code>/topusers</code>, <code>/siteinfo</code>...
                                    </div>

                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Đã tích hợp!</strong> Cấu hình Bot Token + danh sách username bên phải, sau đó nhấn
                                        <em>"Cập nhật Webhook"</em> để Bot bắt đầu nhận lệnh.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cột cấu hình / kích hoạt -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-cog text-warning me-2"></i>
                                        Cấu Hình Bot
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Trạng thái bật/tắt Bot - quyết định webhook có xử lý request hay không -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_status">Trạng thái</label>
                                            <select class="form-control" id="telegram_assistant_status">
                                                <option value="1" <?= $SMARTSTUDY->site('telegram_assistant_status') == 1 ? 'selected' : ''; ?>>ON - Bật</option>
                                                <option value="0" <?= $SMARTSTUDY->site('telegram_assistant_status') == 0 ? 'selected' : ''; ?>>OFF - Tắt</option>
                                            </select>
                                        </div>

                                        <!-- Bot Token lấy từ @BotFather trên Telegram -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_token">Bot Token</label>
                                            <input type="text" class="form-control"
                                                id="telegram_assistant_token"
                                                placeholder="VD: 123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"
                                                value="<?= $SMARTSTUDY->site('telegram_assistant_token'); ?>">
                                            <small class="text-muted">Tạo Bot mới từ <a href="https://t.me/BotFather" target="_blank">@BotFather</a> để lấy Token.</small>
                                        </div>

                                        <!-- Danh sách username Telegram được phép ra lệnh, phân tách bằng dấu phẩy -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_list_username">Username Telegram được phép</label>
                                            <input type="text" class="form-control"
                                                id="telegram_assistant_list_username"
                                                placeholder="VD: username1,username2,username3"
                                                value="<?= $SMARTSTUDY->site('telegram_assistant_list_username'); ?>">
                                            <small class="text-muted">Nhập username Telegram (KHÔNG có @), phân tách bằng dấu phẩy. Chỉ các username này mới được quyền ra lệnh cho Bot.</small>
                                        </div>

                                        <!-- Secret Token: Telegram sẽ gửi kèm header X-Telegram-Bot-Api-Secret-Token để webhook xác thực -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_secret_token">Secret Token (chống giả mạo request)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    id="telegram_assistant_secret_token"
                                                    value="<?= $SMARTSTUDY->site('telegram_assistant_secret_token'); ?>"
                                                    readonly>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copyBotQuanLyField('telegram_assistant_secret_token')" title="Sao chép">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" type="button"
                                                    id="btnRegenTelegramSecret" title="Tạo lại Secret Token">
                                                    <i class="fa fa-rotate"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Sau khi "Tạo lại", phải nhấn "Cập nhật Webhook" để đồng bộ lên Telegram.</small>
                                        </div>

                                        <!-- URL Webhook cố định - admin copy và nhấn cập nhật để đăng ký lên Telegram -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">URL Webhook</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    id="telegram_assistant_webhook_url"
                                                    value="<?= $botQuanLyWebhookUrl; ?>" readonly>
                                                <button class="btn btn-outline-primary" type="button"
                                                    onclick="copyBotQuanLyField('telegram_assistant_webhook_url')" title="Sao chép">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Nhấn "Cập nhật Webhook" bên dưới để hệ thống tự đăng ký URL này cho Bot.</small>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-primary flex-grow-1" id="btnSaveBotQuanLySettings">
                                                <i class="fas fa-save me-2"></i>Lưu cấu hình
                                            </button>
                                            <button type="button" class="btn btn-warning" id="btnSetBotQuanLyWebhook">
                                                <i class="fa fa-sync me-2"></i>Cập nhật Webhook
                                            </button>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Hàm copy dùng riêng cho addon Bot Quản Lý (không phụ thuộc ClipboardJS vì lib này chỉ load ở tab telegram-shop)
            function copyBotQuanLyField(inputId) {
                var el = document.getElementById(inputId);
                if (!el) return;
                el.select();
                el.setSelectionRange(0, 99999); // fallback cho mobile khi navigator.clipboard không khả dụng
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(el.value);
                    } else {
                        document.execCommand('copy');
                    }
                    showMessage('Đã sao chép vào bộ nhớ tạm', 'success');
                } catch (e) {
                    showMessage('Không thể sao chép, vui lòng copy thủ công', 'error');
                }
            }

            // Khối JS quản lý toàn bộ thao tác của Addon Bot Quản Lý Telegram
            document.addEventListener('DOMContentLoaded', function() {
                // Nút lưu cấu hình Bot Quản Lý Telegram
                var btnSaveAll = document.getElementById('btnSaveBotQuanLySettings');
                if (btnSaveAll) {
                    btnSaveAll.addEventListener('click', function() {
                        btnSaveAll.disabled = true;
                        btnSaveAll.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang lưu...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: {
                                action: 'update_telegram_assistant_settings',
                                telegram_assistant_status: document.getElementById('telegram_assistant_status').value,
                                telegram_assistant_token: document.getElementById('telegram_assistant_token').value,
                                telegram_assistant_list_username: document.getElementById('telegram_assistant_list_username').value
                            },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Đã xảy ra lỗi khi lưu', 'error'); },
                            complete: function() {
                                btnSaveAll.disabled = false;
                                btnSaveAll.innerHTML = '<i class="fas fa-save me-2"></i>Lưu cấu hình';
                            }
                        });
                    });
                }

                // Nút tạo lại Secret Token - phải confirm vì sau khi đổi cần set lại webhook mới dùng được
                var btnRegen = document.getElementById('btnRegenTelegramSecret');
                if (btnRegen) {
                    btnRegen.addEventListener('click', function() {
                        if (!confirm('Tạo lại Secret Token? Sau khi tạo lại, bạn BẮT BUỘC phải nhấn "Cập nhật Webhook" để Bot hoạt động trở lại.')) return;
                        btnRegen.disabled = true;
                        btnRegen.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: { action: 'regenerate_telegram_assistant_secret' },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                    setTimeout(function() { location.reload(); }, 800);
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Đã xảy ra lỗi', 'error'); },
                            complete: function() {
                                btnRegen.disabled = false;
                                btnRegen.innerHTML = '<i class="fa fa-rotate"></i>';
                            }
                        });
                    });
                }

                // Nút đăng ký webhook lên Telegram - gọi API setWebhook + kèm secret_token
                var btnSetWh = document.getElementById('btnSetBotQuanLyWebhook');
                if (btnSetWh) {
                    btnSetWh.addEventListener('click', function() {
                        btnSetWh.disabled = true;
                        btnSetWh.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang xử lý...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: { action: 'set_telegram_assistant_webhook' },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Lỗi kết nối máy chủ', 'error'); },
                            complete: function() {
                                btnSetWh.disabled = false;
                                btnSetWh.innerHTML = '<i class="fa fa-sync me-2"></i>Cập nhật Webhook';
                            }
                        });
                    });
                }
            });
        </script>

    </div><!-- End accordion -->
</div>
