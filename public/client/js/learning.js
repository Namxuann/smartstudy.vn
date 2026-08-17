class LearningApp {
    constructor(courseId, initialLessonId) {
        this.courseId = courseId;
        this.currentLessonId = initialLessonId;
        this.curriculum = [];
        this.lessonsFlat = [];
        this.player = null;
        this.progressUpdateInterval = null;
        
        this.init();
    }

    async init() {
        this.bindEvents();
        await this.fetchCurriculum();
        
        if (this.currentLessonId) {
            this.loadLesson(this.currentLessonId);
        } else if (this.lessonsFlat.length > 0) {
            // Find first incomplete
            const incomplete = this.lessonsFlat.find(l => !l.completed);
            this.loadLesson(incomplete ? incomplete.id : this.lessonsFlat[0].id);
        }
    }

    async fetchCurriculum() {
        try {
            const response = await $.ajax({
                url: baseUrl + 'ajaxs/client/learning.php',
                type: 'POST',
                data: { action: 'getCurriculum', course_id: this.courseId },
                dataType: 'json'
            });

            if (response.status === 'success') {
                this.curriculum = response.data.modules;
                this.updateProgress(response.data.progress);
                this.renderCurriculum();
                this.buildFlatList();
            } else {
                Swal.fire('Error', response.message || 'Lỗi tải dữ liệu', 'error');
            }
        } catch (error) {
            console.error('Error fetching curriculum:', error);
        }
    }

    buildFlatList() {
        this.lessonsFlat = [];
        this.curriculum.forEach(module => {
            if (module.lessons) {
                module.lessons.forEach(lesson => {
                    this.lessonsFlat.push(lesson);
                });
            }
        });
    }

    renderCurriculum() {
        const container = $('#curriculumList');
        container.empty();

        this.curriculum.forEach((module, index) => {
            let lessonsHtml = '';
            
            if (module.lessons) {
                module.lessons.forEach(lesson => {
                    let icon = 'fa-file-alt';
                    if (lesson.type === 'video') icon = 'fa-play-circle';
                    else if (lesson.type === 'quiz') icon = 'fa-question-circle';
                    
                    if (lesson.completed) icon = 'fa-check-circle';

                    lessonsHtml += `
                        <a href="javascript:void(0)" class="lesson-item ${lesson.completed ? 'completed' : ''}" 
                           data-id="${lesson.id}" onclick="app.loadLesson(${lesson.id})">
                            <div class="lesson-icon">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div class="lesson-details">
                                <div class="lesson-title">${lesson.title}</div>
                                <div class="lesson-meta">${lesson.duration || ''}</div>
                            </div>
                        </a>
                    `;
                });
            }

            const html = `
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="heading-${module.id}">
                        <button class="accordion-button ${index === 0 ? '' : 'collapsed'} py-3" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse-${module.id}">
                            <div class="fw-bold">
                                <div>Chương ${index + 1}: ${module.title}</div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-${module.id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" 
                         data-bs-parent="#curriculumList">
                        <div class="accordion-body p-0">
                            ${lessonsHtml}
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        });
    }

    async loadLesson(lessonId) {
        if (!lessonId) return;
        this.currentLessonId = lessonId;
        
        // Update UI
        $('.lesson-item').removeClass('active');
        $(`.lesson-item[data-id="${lessonId}"]`).addClass('active');
        
        // Expand module containing lesson
        const moduleCollapse = $(`.lesson-item[data-id="${lessonId}"]`).closest('.accordion-collapse');
        if (moduleCollapse.length && !moduleCollapse.hasClass('show')) {
            moduleCollapse.collapse('show');
        }

        // Close sidebar on mobile
        if (window.innerWidth < 992) {
            this.toggleSidebar(false);
        }

        // Update URL
        const newUrl = window.location.pathname + '?course_id=' + this.courseId + '&lesson_id=' + lessonId;
        window.history.pushState({path: newUrl}, '', newUrl);

        $('#lessonContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        // Clean up previous player
        if (this.player) {
            this.player.destroy();
            this.player = null;
        }
        clearInterval(this.progressUpdateInterval);

        try {
            const response = await $.ajax({
                url: baseUrl + 'ajaxs/client/learning.php',
                type: 'POST',
                data: { action: 'getLesson', lesson_id: lessonId, course_id: this.courseId },
                dataType: 'json'
            });

            if (response.status === 'success') {
                const lesson = response.data.lesson;
                this.renderLessonContent(lesson);
                this.updateNavButtons();
                
                if (lesson.completed) {
                    this.setCompleteButtonState(true);
                } else {
                    this.setCompleteButtonState(false);
                }
            } else {
                $('#lessonContent').html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        } catch (error) {
            console.error('Error loading lesson:', error);
            $('#lessonContent').html(`<div class="alert alert-danger">Lỗi kết nối máy chủ.</div>`);
        }
    }

    renderLessonContent(lesson) {
        let html = `<h2 class="mb-4 fw-bold">${lesson.title}</h2>`;

        if (lesson.type === 'video') {
            html += `
                <div class="video-container mb-4">
                    <video id="player" playsinline controls data-poster="${lesson.thumbnail || ''}">
                        <source src="${lesson.video_url}" type="video/mp4" />
                    </video>
                </div>
            `;
            html += `<div class="lesson-html-content">${lesson.content || ''}</div>`;
            $('#lessonContent').html(html);

            this.player = new Plyr('#player');
            if (lesson.last_position) {
                this.player.once('canplay', () => {
                    this.player.currentTime = parseFloat(lesson.last_position);
                });
            }

            this.player.on('timeupdate', () => {
                if (this.player.currentTime > 0 && Math.floor(this.player.currentTime) % 10 === 0) {
                    this.saveVideoProgress(this.player.currentTime);
                }
            });

            this.player.on('ended', () => {
                this.markComplete(false);
            });

        } else if (lesson.type === 'embed') {
            html += `<div class="ratio ratio-16x9 mb-4">${lesson.embed_code}</div>`;
            html += `<div class="lesson-html-content">${lesson.content || ''}</div>`;
            $('#lessonContent').html(html);
        } else {
            html += `<div class="lesson-html-content">${lesson.content || ''}</div>`;
            $('#lessonContent').html(html);
        }
    }

    async markComplete(navNext = true) {
        if (!this.currentLessonId) return;

        try {
            const response = await $.ajax({
                url: baseUrl + 'ajaxs/client/learning.php',
                type: 'POST',
                data: { action: 'markComplete', lesson_id: this.currentLessonId, course_id: this.courseId },
                dataType: 'json'
            });

            if (response.status === 'success') {
                this.setCompleteButtonState(true);
                
                // Update flat list
                const lessonObj = this.lessonsFlat.find(l => l.id == this.currentLessonId);
                if (lessonObj) lessonObj.completed = true;

                // Update UI checkmark
                const icon = $(`.lesson-item[data-id="${this.currentLessonId}"] .lesson-icon i`);
                icon.removeClass('fa-play-circle fa-file-alt fa-question-circle').addClass('fa-check-circle text-success');
                $(`.lesson-item[data-id="${this.currentLessonId}"]`).addClass('completed');

                this.updateProgress(response.data.progress);

                if (navNext) {
                    this.nextLesson();
                }
            }
        } catch (error) {
            console.error('Error marking complete:', error);
        }
    }

    saveVideoProgress(time) {
        $.ajax({
            url: baseUrl + 'ajaxs/client/learning.php',
            type: 'POST',
            data: { action: 'saveVideoProgress', lesson_id: this.currentLessonId, time: time },
            dataType: 'json'
        });
    }

    updateProgress(percentage) {
        $('#courseProgressBar').css('width', percentage + '%');
        $('#courseProgressText').text(percentage + '%');
    }

    setCompleteButtonState(isCompleted) {
        const btn = $('#markComplete');
        if (isCompleted) {
            btn.addClass('completed').html('<i class="fas fa-check-circle me-2"></i> Đã hoàn thành');
        } else {
            btn.removeClass('completed').html('<i class="fas fa-check me-2"></i> Hoàn thành bài học');
        }
    }

    updateNavButtons() {
        const currentIndex = this.lessonsFlat.findIndex(l => l.id == this.currentLessonId);
        $('#prevLesson').prop('disabled', currentIndex <= 0);
        $('#nextLesson').prop('disabled', currentIndex === -1 || currentIndex >= this.lessonsFlat.length - 1);
    }

    nextLesson() {
        const currentIndex = this.lessonsFlat.findIndex(l => l.id == this.currentLessonId);
        if (currentIndex !== -1 && currentIndex < this.lessonsFlat.length - 1) {
            this.loadLesson(this.lessonsFlat[currentIndex + 1].id);
        }
    }

    prevLesson() {
        const currentIndex = this.lessonsFlat.findIndex(l => l.id == this.currentLessonId);
        if (currentIndex > 0) {
            this.loadLesson(this.lessonsFlat[currentIndex - 1].id);
        }
    }

    toggleSidebar(forceState = null) {
        const sidebar = $('#sidebar');
        if (forceState === true) {
            sidebar.addClass('show');
        } else if (forceState === false) {
            sidebar.removeClass('show');
        } else {
            sidebar.toggleClass('show');
        }
    }

    bindEvents() {
        $('#sidebarToggle').on('click', () => this.toggleSidebar());
        $('#sidebarClose').on('click', () => this.toggleSidebar(false));
        
        $('#prevLesson').on('click', () => this.prevLesson());
        $('#nextLesson').on('click', () => this.nextLesson());
        $('#markComplete').on('click', () => this.markComplete(true));

        $(document).on('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            if (e.key === 'ArrowLeft') {
                this.prevLesson();
            } else if (e.key === 'ArrowRight') {
                this.nextLesson();
            } else if (e.key === 'c' || e.key === 'C') {
                this.markComplete();
            }
        });
    }
}

// Init App
let app;
$(document).ready(function() {
    if (typeof COURSE_ID !== 'undefined') {
        app = new LearningApp(COURSE_ID, INITIAL_LESSON_ID);
    }
});
