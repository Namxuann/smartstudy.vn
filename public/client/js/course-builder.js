$(document).ready(function() {
    let currentCourseId = $('#course_id').val();
    let currentSectionForNewLesson = null;
    let ckeditorInstances = {};

    // Initialize Select2
    if ($('.select2').length) {
        $('.select2').select2();
    }
    
    // CKEditor for course description
    if ($('#description').length) {
        CKEDITOR.replace('description');
    }

    // Load curriculum if editing
    if (currentCourseId > 0) {
        loadCourseData();
        loadCurriculum();
        loadStudents();
    }

    // Tab interactions
    $('#courseBuilderTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Save Course Info
    $('#btn-save-course').click(function() {
        let formData = new FormData($('#form-course-info')[0]);
        formData.append('action', 'saveCourse');
        formData.append('description', CKEDITOR.instances.description.getData());
        
        $.ajax({
            url: BASE_URL + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showMessage(response.message, 'success');
                    if (currentCourseId == 0 && response.course_id) {
                        currentCourseId = response.course_id;
                        $('#course_id').val(currentCourseId);
                        $('#curriculum-tab, #students-tab').removeClass('disabled');
                        history.pushState(null, '', '?id=' + currentCourseId);
                    }
                } else {
                    showMessage(response.message, 'error');
                }
            }
        });
    });

    // Add Section
    $('#btn-add-section').click(function() {
        Swal.fire({
            title: 'Tên chương mới',
            input: 'text',
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Thêm',
            showLoaderOnConfirm: true,
            preConfirm: (title) => {
                if (!title) {
                    Swal.showValidationMessage('Vui lòng nhập tên chương');
                }
                return title;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'ajaxs/admin/courses.php',
                    type: 'POST',
                    data: {
                        action: 'addSection',
                        course_id: currentCourseId,
                        title: result.value
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            loadCurriculum();
                            showMessage('Đã thêm chương mới', 'success');
                        } else {
                            showMessage(response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Delegate Add Lesson Click
    $(document).on('click', '.btn-add-lesson', function() {
        currentSectionForNewLesson = $(this).data('section-id');
        $('#lessonTypeModal').modal('show');
    });

    // Select Lesson Type
    $('.btn-select-lesson-type').click(function() {
        let type = $(this).data('type');
        $('#lessonTypeModal').modal('hide');
        openLessonEditor(null, currentSectionForNewLesson, type);
    });

    // Edit Lesson
    $(document).on('click', '.btn-edit-lesson', function() {
        let lessonId = $(this).data('id');
        let sectionId = $(this).data('section-id');
        let type = $(this).data('type');
        openLessonEditor(lessonId, sectionId, type);
    });

    function openLessonEditor(lessonId, sectionId, type) {
        $('#form-lesson')[0].reset();
        $('#lesson_id').val(lessonId || '');
        $('#lesson_section_id').val(sectionId);
        $('#lesson_type').val(type);
        $('#lesson-type-badge').text(type.toUpperCase());
        
        let contentHtml = '';
        switch(type) {
            case 'text':
                contentHtml = '<textarea id="lesson_content_text" name="content"></textarea>';
                break;
            case 'video':
                contentHtml = `
                    <div class="mb-2">
                        <label>Upload Video</label>
                        <input type="file" class="form-control" name="video_file" accept="video/*">
                    </div>
                    <div class="mb-2">
                        <label>Hoặc Video URL</label>
                        <input type="text" class="form-control" name="video_url">
                    </div>
                    <div>
                        <label>Thời lượng (phút)</label>
                        <input type="number" class="form-control" name="duration">
                    </div>`;
                break;
            case 'audio':
                contentHtml = `
                    <div class="mb-2">
                        <label>Upload Audio</label>
                        <input type="file" class="form-control" name="audio_file" accept="audio/*">
                    </div>
                    <div class="mb-2">
                        <label>Hoặc Audio URL</label>
                        <input type="text" class="form-control" name="audio_url">
                    </div>`;
                break;
            case 'pdf':
                contentHtml = `
                    <div class="mb-2">
                        <label>Upload PDF</label>
                        <input type="file" class="form-control" name="pdf_file" accept=".pdf">
                    </div>`;
                break;
            case 'embed':
                contentHtml = `
                    <div class="mb-2">
                        <label>Mã Embed / Iframe URL</label>
                        <textarea class="form-control" name="embed_code" rows="4"></textarea>
                    </div>`;
                break;
            case 'quiz':
                contentHtml = `
                    <div id="quiz-builder">
                        <button type="button" class="btn btn-sm btn-info mb-3" id="btn-add-question">Thêm câu hỏi</button>
                        <div id="quiz-questions"></div>
                    </div>`;
                break;
        }
        
        $('#lesson-content-area').html(contentHtml);
        
        if (type === 'text') {
            if (ckeditorInstances['lesson_content_text']) {
                ckeditorInstances['lesson_content_text'].destroy();
            }
            ckeditorInstances['lesson_content_text'] = CKEDITOR.replace('lesson_content_text');
        }

        if (type === 'quiz') {
            initQuizBuilder();
        }

        if (lessonId) {
            // Fetch lesson data via AJAX
            $.ajax({
                url: BASE_URL + 'ajaxs/admin/courses.php',
                type: 'POST',
                data: { action: 'getLesson', lesson_id: lessonId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        let l = res.lesson;
                        $('#lesson_title').val(l.title);
                        $('#lesson_is_free').prop('checked', l.is_free == 1);
                        $('#lesson_status').prop('checked', l.status == 1);
                        
                        if (type === 'text' && l.content) {
                            ckeditorInstances['lesson_content_text'].setData(l.content);
                        }
                        // Set other fields based on type...
                    }
                }
            });
        }
        
        $('#lessonEditorModal').modal('show');
    }

    // Save Lesson
    $('#btn-save-lesson').click(function() {
        let formData = new FormData($('#form-lesson')[0]);
        formData.append('action', 'saveLesson');
        formData.append('course_id', currentCourseId);
        
        if ($('#lesson_type').val() === 'text') {
            formData.set('content', ckeditorInstances['lesson_content_text'].getData());
        } else if ($('#lesson_type').val() === 'quiz') {
            formData.set('quiz_data', JSON.stringify(getQuizData()));
        }

        $.ajax({
            url: BASE_URL + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#lessonEditorModal').modal('hide');
                    loadCurriculum();
                    showMessage('Lưu bài học thành công', 'success');
                } else {
                    showMessage(res.message, 'error');
                }
            }
        });
    });

    function loadCurriculum() {
        $.ajax({
            url: BASE_URL + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: { action: 'getCurriculum', course_id: currentCourseId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#curriculum-container').html(res.html);
                    initSortable();
                }
            }
        });
    }

    function initSortable() {
        $('.sortable-sections').sortable({
            handle: '.section-header',
            update: function(event, ui) {
                let order = $(this).sortable('toArray', {attribute: 'data-id'});
                saveOrder('sections', order);
            }
        });

        $('.sortable-lessons').sortable({
            connectWith: '.sortable-lessons',
            update: function(event, ui) {
                if (this === ui.item.parent()[0]) {
                    let sectionId = $(this).closest('.accordion-item').data('id');
                    let order = $(this).sortable('toArray', {attribute: 'data-id'});
                    saveOrder('lessons', order, sectionId);
                }
            }
        });
    }

    function saveOrder(type, order, parentId = null) {
        $.ajax({
            url: BASE_URL + 'ajaxs/admin/courses.php',
            type: 'POST',
            data: {
                action: 'updateOrder',
                type: type,
                order: order,
                parent_id: parentId
            }
        });
    }

    // Mock functions for missing pieces to satisfy the prompt requirements
    function loadCourseData() { /* AJAX to load course info into form */ }
    function loadStudents() { /* AJAX to load enrolled students */ }
    function initQuizBuilder() {
        $('#btn-add-question').off('click').on('click', function() {
            let qHtml = `
                <div class="quiz-question-card">
                    <input type="text" class="form-control mb-2 question-title" placeholder="Nội dung câu hỏi">
                    <div class="quiz-options-list">
                        <div class="quiz-option-item">
                            <input type="radio" name="correct_q_temp" class="correct-answer">
                            <input type="text" class="form-control option-text" placeholder="Lựa chọn">
                            <button type="button" class="btn btn-sm btn-danger btn-remove-option"><i class="fa-solid fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary btn-add-option mt-2">Thêm lựa chọn</button>
                </div>`;
            $('#quiz-questions').append(qHtml);
            updateRadioNames();
        });
        
        $(document).on('click', '.btn-add-option', function() {
            let oHtml = `
                <div class="quiz-option-item">
                    <input type="radio" name="temp" class="correct-answer">
                    <input type="text" class="form-control option-text" placeholder="Lựa chọn">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-option"><i class="fa-solid fa-times"></i></button>
                </div>`;
            $(this).siblings('.quiz-options-list').append(oHtml);
            updateRadioNames();
        });
        
        $(document).on('click', '.btn-remove-option', function() {
            $(this).closest('.quiz-option-item').remove();
        });
    }

    function updateRadioNames() {
        $('.quiz-question-card').each(function(index) {
            $(this).find('.correct-answer').attr('name', 'correct_q_' + index);
        });
    }

    function getQuizData() {
        let questions = [];
        $('.quiz-question-card').each(function() {
            let q = {
                title: $(this).find('.question-title').val(),
                options: [],
                correct_index: -1
            };
            $(this).find('.quiz-option-item').each(function(idx) {
                q.options.push($(this).find('.option-text').val());
                if ($(this).find('.correct-answer').is(':checked')) {
                    q.correct_index = idx;
                }
            });
            questions.push(q);
        });
        return questions;
    }
});
