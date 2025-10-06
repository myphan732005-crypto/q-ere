<?php
require_once __DIR__.'/dao.php';

// Router
$action = $_GET['page'] ?? 'home';

// Chặn truy cập login/register khi đã đăng nhập
if (is_logged_in() && in_array($action, ['login', 'register'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => 'Bạn đang đăng nhập. Vui lòng đăng xuất để đăng nhập tài khoản khác.']);
    exit;
}

if ($action === 'logout') { session_destroy(); header('Location: index.php'); exit; }

// Login POST
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $u = user_find_by_email($email);
    header('Content-Type: application/json');
    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'fullname' => $u['fullname'], 'email' => $u['email'], 'role' => $u['role']];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Sai email hoặc mật khẩu']);
    }
    exit;
}

// Register POST
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    header('Content-Type: application/json');
    if (!$name || !$email || !$pass) {
        echo json_encode(['success' => false, 'msg' => 'Vui lòng nhập đủ thông tin']);
    } else if (user_find_by_email($email)) {
        echo json_encode(['success' => false, 'msg' => 'Email đã tồn tại']);
    } else {
        user_create($name, $email, $pass);
        echo json_encode(['success' => true, 'msg' => 'Tạo tài khoản thành công. Đăng nhập để tiếp tục.']);
    }
    exit;
}

// Reset POST
if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $u = user_find_by_email($email);
    header('Content-Type: application/json');
    if ($u) {
        $hash = password_hash('123456', PASSWORD_BCRYPT);
        $st = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $st->execute([$hash, $u['id']]);
        echo json_encode(['success' => true, 'msg' => 'Đặt lại mật khẩu = 123456 thành công!']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Không tìm thấy email']);
    }
    exit;
}

// Header (UI)
function header_html($title = 'E-learning Mini') {
    $u = current_user();
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>" . h($title) . "</title>
    <link rel='stylesheet' href='assets/theme.css'>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js'></script>
    <script src='assets/scripts.js'></script>
    </head><body>
    <header class='header'><div class='container nav'>
      <div class='brand'><span>ELearning<span style='color:#2b59ff'>Mini</span></span></div>
      <nav class='menu'>
        <a href='index.php' class='btn ghost'>Trang chủ</a>
        <a href='index.php?page=courses' class='btn ghost'>Khóa học</a>
        <a href='index.php?page=highlights' class='btn ghost'>Tính năng</a>";
    if ($u) {
        echo "<a class='btn ghost' href='index.php?page=my'>Khóa học của tôi</a>";
        if (is_admin()) echo "<a class='btn ghost' href='admin.php'>Quản trị</a>";
        echo "<a class='btn' href='index.php?page=logout'>Đăng xuất</a>";
    } else {
        echo "<a class='btn' href='index.php?page=login'>Đăng nhập</a>
              <a class='btn primary' href='index.php?page=register'>Đăng ký</a>";
    }
    echo "</nav></div></header>
    <main>";
    if ($m = flash('error')) echo "<div class='container'><div class='flash error'>" . h($m) . "</div></div>";
    if ($m = flash('ok')) echo "<div class='container'><div class='flash ok'>" . h($m) . "</div></div>";
}

// Footer
function footer_html() {
    echo "</main><footer class='footer'><div class='container cols'>
      <div><div style='font-weight:700;font-size:18px;margin-bottom:6px'>ELearningMini</div>
           <div>Nền tảng E-learning mini: học tập tương tác và quiz tự chấm.</div></div>
      <div><div style='font-weight:600;margin-bottom:6px'>Giải pháp</div>
           <div><a href='index.php?page=highlights'>Tính năng</a></div>
           <div><a href='index.php?page=courses'>Khóa học</a></div></div>
      <div><div style='font-weight:600;margin-bottom:6px'>Liên hệ</div>
           <div>email: myphan732005@gmail.com</div></div>
    </div></footer></body></html>";
}

// HOME
if ($action === 'home') {
    $courses = course_all_active();
    header_html();
    echo "<section class='hero'><div class='container wrap'>
      <div>
        <h1 class='h-title'>Nền tảng học trực tuyến <br>tương tác gọn nhẹ cho mọi người</h1>
        <p class='h-sub'>Tạo khóa học, học theo lộ trình, làm bài trắc nghiệm chấm điểm tự động. Lưu toàn bộ lịch sử điểm, số lần làm và câu sai.</p>
        <div class='cta'>" .
        (is_logged_in()
            ? "<a class='btn primary' href='index.php?page=courses'>Bắt đầu học</a><a class='btn' href='index.php?page=my'>Khóa học của tôi</a>"
            : "<a class='btn primary' href='index.php?page=register'>Đăng ký miễn phí</a><a class='btn' href='index.php?page=login'>Đăng nhập</a>")
        . "</div>
        <div class='kpis'><span><b>" . count($courses) . "</b>+ khóa học</span><span>•</span><span>Quiz chấm điểm tức thì</span><span>•</span><span>Lưu lịch sử học tập</span></div>
      </div>";
    if (!is_logged_in()) {
        echo "<div>
          <div class='card'>
            <h3>Đăng nhập nhanh</h3>
            <form id='login-form' method='post' action='index.php?page=login' style='display:grid;gap:10px'>
              <input name='email' placeholder='Email' value='myphan732005@gmail.com'>
              <input type='password' name='password' placeholder='Mật khẩu' value='123456'>
              <button class='btn primary' type='submit'>Đăng nhập</button>
              <div style='font-size:13px;color:#6b7080'>Quên mật khẩu? <a href='index.php?page=reset'>Đặt lại</a></div>
            </form>
          </div>
        </div>";
    }
    echo "</div></section>";
    echo "<section class='section'><div class='container'>
      <h2>Tính năng nổi bật</h2>
      <div class='features'>
        <div class='feature'><b>Phòng học & bài học</b>Tạo/chỉnh sửa bài học, nhúng video, nội dung trực quan.</div>
        <div class='feature'><b>Quiz tự chấm</b>Câu hỏi A/B/C/D, chấm điểm tự động ngay sau khi nộp.</div>
        <div class='feature'><b>Lịch sử học</b>Lưu điểm, số lần làm và các câu sai của từng lần làm bài.</div>
      </div>
    </div></section>";
    echo "<section class='section'><div class='container'>
      <h2>Khóa học nổi bật</h2>
      <div class='cards'>";
    foreach (array_slice($courses, 0, 6) as $c) {
        echo "<div class='card'>
          <h3>" . h($c['title']) . "</h3>
          <div style='color:#6b7080;margin:6px 0 10px'>" . nl2br(h($c['description'])) . "</div>
          <span class='price'>" . number_format($c['price']) . "đ</span>
          <div style='margin-top:10px'><a class='btn' href='index.php?page=course&id=" . $c['id'] . "'>Xem chi tiết</a></div>
        </div>";
    }
    echo "</div></div></section>";
    footer_html();
    exit;
}

// Login
if ($action === 'login') {
    header_html('Đăng nhập');
    echo "<section class='section'><div class='container'><div class='card'>
      <h2>Đăng nhập</h2>
      <form id='login-form' method='post' action='index.php?page=login' style='display:grid;gap:10px'>
        <input name='email' placeholder='Email'>
        <input type='password' name='password' placeholder='Mật khẩu'>
        <button class='btn primary' type='submit'>Đăng nhập</button>
        <div style='font-size:13px;color:#6b7080'>Chưa có tài khoản? <a href='index.php?page=register'>Đăng ký</a> | <a href='index.php?page=reset'>Quên mật khẩu</a></div>
      </form>
    </div></div></section>";
    footer_html();
    exit;
}

// Register
if ($action === 'register') {
    header_html('Đăng ký');
    echo "<section class='section'><div class='container'><div class='card'>
      <h2>Đăng ký</h2>
      <form id='register-form' method='post' action='index.php?page=register' style='display:grid;gap:10px'>
        <input name='fullname' placeholder='Họ và tên'>
        <input name='email' placeholder='Email'>
        <input type='password' name='password' placeholder='Mật khẩu'>
        <button class='btn primary' type='submit'>Đăng ký</button>
        <div style='font-size:13px;color:#6b7080'>Đã có tài khoản? <a href='index.php?page=login'>Đăng nhập</a></div>
      </form>
    </div></div></section>";
    footer_html();
    exit;
}

// Reset
if ($action === 'reset') {
    header_html('Đặt lại mật khẩu');
    echo "<section class='section'><div class='container'><div class='card'>
      <h2>Đặt lại mật khẩu</h2>
      <form id='reset-form' method='post' action='index.php?page=reset' style='display:grid;gap:10px'>
        <input name='email' placeholder='Nhập email của bạn'>
        <button class='btn primary' type='submit'>Đặt lại</button>
      </form>
    </div></div></section>";
    footer_html();
    exit;
}

// Courses
if ($action === 'courses') {
    header_html('Danh sách khóa học');
    $list = course_all_active();
    echo "<section class='section'><div class='container'><h2>Khóa học</h2><div class='cards'>";
    foreach ($list as $c) {
        echo "<div class='card'><h3>" . h($c['title']) . "</h3><div style='color:#6b7080;margin:6px 0 10px'>" . nl2br(h($c['description'])) . "</div>
        <span class='price'>" . number_format($c['price']) . "đ</span>
        <div style='margin-top:10px'><a class='btn' href='index.php?page=course&id=" . $c['id'] . "'>Xem chi tiết</a></div></div>";
    }
    echo "</div></div></section>";
    footer_html();
    exit;
}

// Course Detail
if ($action === 'course') {
    $id = (int)($_GET['id'] ?? 0);
    $c = course_find($id);
    if (!$c) {
        header('Location:index.php?page=courses');
        exit;
    }
    header_html('Chi tiết khóa học');
    echo "<div class='container'><div class='card'><h2>" . h($c['title']) . "</h2>
          <p>Giá: <b>" . number_format($c['price']) . "đ</b></p>
          <p>" . nl2br(h($c['description'])) . "</p>";
    if (is_logged_in()) {
        $en = is_enrolled(current_user()['id'], $c['id']);
        if (!$en) echo "<a class='btn primary' href='index.php?page=enroll&id=" . $c['id'] . "'>Đăng ký tham gia</a>";
        else echo "<span class='price'>Bạn đã đăng ký</span> <a class='btn' href='index.php?page=learn&id=" . $c['id'] . "'>Vào học</a>";
    } else {
        echo "<a class='btn primary' href='index.php?page=login'>Đăng nhập để đăng ký</a>";
    }
    echo "</div></div>";
    footer_html();
    exit;
}

// Enroll
if ($action === 'enroll') {
    require_login();
    $id = (int)($_GET['id'] ?? 0);
    $c = course_find($id);
    if (!$c) {
        header('Location:index.php?page=courses');
        exit;
    }
    enroll(current_user()['id'], $c['id'], $c['price']);
    flash('ok', 'Đăng ký thành công');
    header('Location:index.php?page=learn&id=' . $c['id']);
    exit;
}

// Learn
if ($action === 'learn') {
    require_login();
    $id = (int)($_GET['id'] ?? 0);
    $c = course_find($id);
    if (!$c) {
        header('Location:index.php?page=courses');
        exit;
    }
    if (!is_enrolled(current_user()['id'], $c['id'])) {
        flash('error', 'Bạn chưa đăng ký khoá này');
        header('Location:index.php?page=course&id=' . $c['id']);
        exit;
    }
    $less = lesson_by_course($c['id']);
    $quizzes = quiz_by_course($c['id']);
    header_html('Học khóa: ' . $c['title']);
    echo "<section class='section'><div class='container'>
      <div class='cards' style='grid-template-columns:2fr 1fr'>
        <div class='card'><h3>Bài học</h3>";
    foreach ($less as $l) {
        echo "<div><b>" . h($l['title']) . "</b><div>" . nl2br(h($l['content'])) . "</div>" .
            ($l['video_url'] ? "<div><a class='btn' target='_blank' href='" . h($l['video_url']) . "'>Xem video</a></div>" : "") . "<hr></div>";
    }
    echo "</div>
        <div class='card'><h3>Quiz</h3>";
    foreach ($quizzes as $q) {
        echo "<div><b>" . h($q['title']) . "</b> <a class='btn primary' href='index.php?page=quiz_start&id=" . $q['id'] . "'>Làm bài</a> <a class='btn' href='index.php?page=quiz_history&id=" . $q['id'] . "'>Lịch sử</a></div><hr>";
    }
    echo "</div></div></div></section>";
    footer_html();
    exit;
}

// My Courses
if ($action === 'my') {
    require_login();
    header_html('Khóa học của tôi');
    $en = enrollments_by_user(current_user()['id']);
    echo "<section class='section'><div class='container'><h2>Đã đăng ký</h2><div class='cards'>";
    foreach ($en as $e) {
        echo "<div class='card'><h3>" . h($e['title']) . "</h3><p>Ngày: " . h($e['enrolled_at']) . "</p><a class='btn' href='index.php?page=learn&id=" . $e['course_id'] . "'>Vào học</a></div>";
    }
    echo "</div></div></section>";
    footer_html();
    exit;
}

// Quiz Start
if ($action === 'quiz_start') {
    require_login();
    $qid = (int)($_GET['id'] ?? 0);
    $q = quiz_find($qid);
    if (!$q) {
        header('Location:index.php');
        exit;
    }
    if (!is_enrolled(current_user()['id'], $q['course_id'])) {
        flash('error', 'Cần đăng ký khoá học để làm quiz');
        header('Location:index.php?page=course&id=' . $q['course_id']);
        exit;
    }
    $qs = question_by_quiz($qid);
    if (!$qs) {
        flash('error', 'Quiz chưa có câu hỏi. Vui lòng quay lại sau.');
        header('Location:index.php?page=learn&id=' . $q['course_id']);
        exit;
    }
    $attempt_id = attempt_create($qid, current_user()['id'], count($qs));
    header_html('Bắt đầu quiz');
    echo "<section class='section'><div class='container'><div class='card'><h2>" . h($q['title']) . "</h2><form method='post' action='index.php?page=quiz_submit&id=" . $qid . "&attempt=" . $attempt_id . "'>";
    foreach ($qs as $i => $qq) {
        echo "<div style='margin-bottom:12px'><b>Câu " . ($i + 1) . ": " . h($qq['qtext']) . "</b><br>";
        foreach (['A', 'B', 'C', 'D'] as $ch) {
            $opt = $qq['opt_' . strtolower($ch)];
            echo "<label><input type='radio' name='q" . $qq['id'] . "' value='" . $ch . "' required> " . h($ch) . ". " . h($opt) . "</label><br>";
        }
        echo "</div>";
    }
    echo "<button class='btn primary' type='submit'>Nộp bài</button></form></div></div></section>";
    footer_html();
    exit;
}

// Quiz Submit
if ($action === 'quiz_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $qid = (int)($_GET['id'] ?? 0);
    $attempt_id = (int)($_GET['attempt'] ?? 0);
    $qs = question_by_quiz($qid);
    $score = 0;
    $answers = [];
    foreach ($qs as $qq) {
        $chosen = $_POST['q' . $qq['id']] ?? 'A';
        $correct = ($chosen === $qq['correct_opt']) ? 1 : 0;
        if ($correct) $score++;
        $answers[] = ['qid' => $qq['id'], 'chosen' => $chosen, 'correct' => $correct];
    }
    attempt_answers_save($attempt_id, $answers);
    attempt_finish($attempt_id, $score);
    flash('ok', 'Điểm: ' . $score . ' / ' . count($qs));
    header('Location:index.php?page=quiz_history&id=' . $qid);
    exit;
}

// Quiz History
if ($action === 'quiz_history') {
    require_login();
    $qid = (int)($_GET['id'] ?? 0);
    $list = attempts_by_user_quiz(current_user()['id'], $qid);
    header_html('Lịch sử làm bài');
    echo "<section class='section'><div class='container'><div class='card'><h2>Lịch sử</h2><table><tr><th>Lần</th><th>Điểm</th><th>Thời gian</th><th>Chi tiết câu sai</th></tr>";
    foreach ($list as $a) {
        echo "<tr><td>" . h($a['attempt_no']) . "</td><td>" . h($a['score']) . "/" . h($a['total_questions']) . "</td><td>" . h($a['finished_at']) . "</td><td><a class='btn' href='index.php?page=wrong_detail&attempt=" . $a['id'] . "'>Xem</a></td></tr>";
    }
    echo "</table></div></div></section>";
    footer_html();
    exit;
}

// Wrong Detail
if ($action === 'wrong_detail') {
    require_login();
    $attempt_id = (int)($_GET['attempt'] ?? 0);
    $rows = wrong_details_by_attempt($attempt_id);
    header_html('Câu sai');
    echo "<section class='section'><div class='container'><div class='card'><h2>Câu sai</h2>";
    if (!$rows) echo "Không có câu sai. Tuyệt vời!";
    else foreach ($rows as $r) {
        echo "<div><b>" . h($r['qtext']) . "</b><div>Đã chọn: " . h($r['chosen_opt']) . " | Đúng: " . h($r['correct_opt']) . "</div></div><hr>";
    }
    echo "</div></div></section>";
    footer_html();
    exit;
}

// Fallback
header('Location: index.php?page=home');
?>