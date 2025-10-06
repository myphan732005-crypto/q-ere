$(document).ready(function() {
    // Hiệu ứng fade-in cho sections và cards khi load trang
    $('.section, .card').css('opacity', 0).each(function(index) {
        $(this).delay(index * 100).animate({ opacity: 1 }, 600);
    });

    // Smooth scroll cho các link nội bộ
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 600);
        }
    });

    // Hàm hiển thị/ẩn loading spinner
    function showLoading(form) {
        var btn = form.find('button[type="submit"]');
        btn.data('original-text', btn.html()).html('<span class="spinner"></span> Đang xử lý...').prop('disabled', true);
    }
    function hideLoading(form) {
        var btn = form.find('button[type="submit"]');
        btn.html(btn.data('original-text')).prop('disabled', false);
    }

    // Validation cho login form
    $('#login-form').validate({
        rules: {
            email: { required: true, email: true },
            password: { required: true, minlength: 6 }
        },
        messages: {
            email: "Vui lòng nhập email hợp lệ",
            password: "Mật khẩu phải có ít nhất 6 ký tự"
        },
        submitHandler: function(form) {
            showLoading($(form));
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'index.php?page=courses';
                    } else {
                        alert(response.msg);
                        hideLoading($(form));
                    }
                },
                error: function() {
                    alert('Lỗi kết nối server');
                    hideLoading($(form));
                }
            });
            return false;
        }
    });

    // Validation cho register form
    $('#register-form').validate({
        rules: {
            fullname: { required: true, minlength: 2 },
            email: { required: true, email: true },
            password: { required: true, minlength: 6 }
        },
        messages: {
            fullname: "Vui lòng nhập họ tên (ít nhất 2 ký tự)",
            email: "Vui lòng nhập email hợp lệ",
            password: "Mật khẩu phải có ít nhất 6 ký tự"
        },
        submitHandler: function(form) {
            showLoading($(form));
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.msg);
                        window.location.href = 'index.php?page=login';
                    } else {
                        alert(response.msg);
                        hideLoading($(form));
                    }
                },
                error: function() {
                    alert('Lỗi kết nối server');
                    hideLoading($(form));
                }
            });
            return false;
        }
    });

    // Validation cho reset form
    $('#reset-form').validate({
        rules: { email: { required: true, email: true } },
        messages: { email: "Vui lòng nhập email hợp lệ" },
        submitHandler: function(form) {
            showLoading($(form));
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    alert(response.msg);
                    if (response.success) {
                        window.location.href = 'index.php?page=login';
                    } else {
                        hideLoading($(form));
                    }
                },
                error: function() {
                    alert('Lỗi kết nối server');
                    hideLoading($(form));
                }
            });
            return false;
        }
    });

    // Validation cho admin forms (courses, lessons, quizzes, questions)
    $('.admin-form').each(function() {
        $(this).validate({
            rules: {
                title: { required: true, minlength: 3 },
                price: { required: true, number: true, min: 0 },
                description: { required: false },
                course_id: { required: true },
                qtext: { required: true },
                opt_a: { required: true },
                opt_b: { required: true },
                opt_c: { required: true },
                opt_d: { required: true },
                correct_opt: { required: true }
            },
            messages: {
                title: "Vui lòng nhập tiêu đề (ít nhất 3 ký tự)",
                price: "Giá phải là số dương",
                course_id: "Vui lòng chọn khóa học",
                qtext: "Vui lòng nhập nội dung câu hỏi",
                opt_a: "Vui lòng nhập phương án A",
                opt_b: "Vui lòng nhập phương án B",
                opt_c: "Vui lòng nhập phương án C",
                opt_d: "Vui lòng nhập phương án D",
                correct_opt: "Vui lòng chọn đáp án đúng"
            }
        });
    });

    // Hiệu ứng hover cho buttons
    $('.btn').hover(function() {
        $(this).css('transform', 'translateY(-2px)');
    }, function() {
        $(this).css('transform', 'translateY(0)');
    });

    // CSS cho spinner
    $('<style>.spinner{border:2px solid #fff;border-top:2px solid transparent;border-radius:50%;width:16px;height:16px;animation:spin 1s linear infinite;display:inline-block;margin-right:6px;}</style>').appendTo('head');
    $('<style>@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}</style>').appendTo('head');

    // CSS cho thông báo lỗi validation
    $('<style>.error{color:#b91c1c;font-size:13px;margin-top:4px;display:block;}</style>').appendTo('head');
});