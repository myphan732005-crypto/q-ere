<?php
require_once __DIR__.'/dao.php';
require_admin();

$action = $_GET['page'] ?? 'dashboard';

function admin_header($title = 'Admin') {
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>" . h($title) . "</title>
    <link rel='stylesheet' href='assets/theme.css'>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js'></script>
    <script src='assets/scripts.js'></script>
    </head><body><div class='container'><nav>
      <a class='btn' href='admin.php'>Tổng quan</a>
      <a class='btn' href='admin.php?page=courses'>Khóa học</a>
      <a class='btn' href='admin.php?page=lessons'>Bài học</a>
      <a class='btn' href='admin.php?page=quizzes'>Quiz</a>
      <a class='btn' href='admin.php?page=users'>Người dùng</a>
      <a class='btn' href='admin.php?page=revenue'>Doanh thu</a>
      <a class='btn' href='index.php'>Về trang người dùng</a>
    </nav>";
}
function admin_footer() { echo "</div></body></html>"; }

/* ===== Dashboard ===== */
if ($action === 'dashboard') {
    admin_header('Tổng quan');
    echo "<div class='card'><h2>Chào mừng quản trị</h2><p>Quản lý khóa học, bài học, quiz & câu hỏi, người dùng, doanh thu.</p></div>";
    admin_footer();
    exit;
}

/* ===== Courses CRUD ===== */
if ($action === 'courses') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create'])) {
            course_create($_POST['title'], $_POST['description'], (float)$_POST['price'], isset($_POST['is_active']) ? 1 : 0);
        }
        if (isset($_POST['update'])) {
            course_update((int)$_POST['id'], $_POST['title'], $_POST['description'], (float)$_POST['price'], isset($_POST['is_active']) ? 1 : 0);
        }
    }
    if (isset($_GET['del'])) { course_delete((int)$_GET['del']); }
    $list = course_all();
    admin_header('Quản lý khóa học');
    echo "<div class='card'><h2>Thêm khóa học</h2>
      <form class='admin-form' method='post' class='row'>
        <input name='title' placeholder='Tiêu đề'>
        <input name='price' type='number' step='0.01' placeholder='Giá'>
        <textarea name='description' placeholder='Mô tả' style='grid-column:1/-1'></textarea>
        <label><input type='checkbox' name='is_active' checked> Kích hoạt</label>
        <button class='btn primary' name='create'>Thêm</button>
      </form>
    </div>";
    echo "<div class='card'><h2>Danh sách</h2><table><tr><th>ID</th><th>Tiêu đề</th><th>Giá</th><th>Active</th><th>Hành động</th></tr>";
    foreach ($list as $c) {
        echo "<tr><td>" . $c['id'] . "</td><td>" . h($c['title']) . "</td><td>" . number_format($c['price']) . "</td><td>" . ($c['is_active'] ? "Yes" : "No") . "</td><td>";
        echo "<form class='admin-form' method='post' style='display:inline'>
        <input type='hidden' name='id' value='" . $c['id'] . "'>
        <input name='title' value=\"" . h($c['title']) . "\" style='width:160px'>
        <input name='description' value=\"" . h($c['description']) . "\" style='width:220px'>
        <input name='price' type='number' step='0.01' value='" . $c['price'] . "' style='width:100px'>
        <label class='tag'><input type='checkbox' name='is_active' " . ($c['is_active'] ? 'checked' : '') . "> Active</label>
        <button class='btn' name='update'>Lưu</button></form>
        <a class='btn' href='admin.php?page=courses&del=" . $c['id'] . "'>Xóa</a>
        <a class='btn' href='admin.php?page=quizzes&course_id=" . $c['id'] . "'>Quiz của khóa</a>
        </td></tr>";
    }
    echo "</table></div>";
    admin_footer();
    exit;
}

/* ===== Lessons ===== */
if ($action === 'lessons') {
    $courses = course_all();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
        lesson_create((int)$_POST['course_id'], $_POST['title'], $_POST['content'], $_POST['video_url']);
    }
    if (isset($_GET['del'])) { lesson_delete((int)$_GET['del']); }
    admin_header('Quản lý bài học');
    echo "<div class='card'><h2>Thêm bài học</h2><form class='admin-form' method='post'>
    <div class='row'>
      <select name='course_id'>";
    foreach ($courses as $c) { echo "<option value='" . $c['id'] . "'>" . h($c['title']) . "</option>"; }
    echo "</select>
      <input name='title' placeholder='Tiêu đề'>
      <input name='video_url' placeholder='Link video (tuỳ chọn)'>
      <textarea name='content' placeholder='Nội dung' style='grid-column:1/-1'></textarea>
    </div>
    <button class='btn primary' name='create'>Thêm</button></form></div>";
    echo "<div class='card'><h2>Danh sách bài học</h2>";
    foreach ($courses as $c) {
        $less = lesson_by_course($c['id']);
        echo "<h3>" . h($c['title']) . "</h3><table><tr><th>ID</th><th>Tiêu đề</th><th>Hành động</th></tr>";
        foreach ($less as $l) {
            echo "<tr><td>" . $l['id'] . "</td><td>" . h($l['title']) . "</td><td><a class='btn' href='admin.php?page=lessons&del=" . $l['id'] . "'>Xóa</a></td></tr>";
        }
        echo "</table><hr>";
    }
    echo "</div>";
    admin_footer();
    exit;
}

/* ===== Quizzes ===== */
if ($action === 'quizzes') {
    $courses = course_all();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create'])) {
            quiz_create((int)$_POST['course_id'], trim($_POST['title'] ?: 'Quiz mới'));
        }
        if (isset($_POST['update'])) {
            quiz_update((int)$_POST['id'], (int)$_POST['course_id'], trim($_POST['title']));
        }
    }
    if (isset($_GET['del'])) { quiz_delete((int)$_GET['del']); }
    admin_header('Quản lý Quiz');
    echo "<div class='card'><h2>Thêm quiz cho khóa học</h2>
      <form class='admin-form' method='post' class='row'>
        <select name='course_id'>";
    foreach ($courses as $c) { echo "<option value='" . $c['id'] . "'>" . h($c['title']) . "</option>"; }
    echo "</select>
        <input name='title' placeholder='Tên quiz (ví dụ: Quiz chương 1)'>
        <button class='btn primary' name='create'>Thêm</button>
      </form>
      <div class='muted'>Sau khi tạo, bấm <b>Quản lý câu hỏi</b> để thêm/sửa câu hỏi.</div>
    </div>";
    $filter_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
    $show_courses = $filter_course ? array_filter($courses, function($c) use ($filter_course) { return $c['id'] == $filter_course; }) : $courses;
    foreach ($show_courses as $c) {
        $quizzes = quizzes_by_course($c['id']);
        echo "<div class='card'><h2>Quiz của khóa: " . h($c['title']) . "</h2><table><tr><th>ID</th><th>Tiêu đề</th><th>Hành động</th></tr>";
        foreach ($quizzes as $q) {
            echo "<tr><td>" . $q['id'] . "</td><td><form class='admin-form' method='post' style='display:inline'>
              <input type='hidden' name='id' value='" . $q['id'] . "'>
              <select name='course_id'>";
            foreach ($courses as $c2) {
                echo "<option value='" . $c2['id'] . "' " . ($c2['id'] == $q['course_id'] ? 'selected' : '') . ">" . h($c2['title']) . "</option>";
            }
            echo "</select>
              <input name='title' value=\"" . h($q['title']) . "\">
              <button class='btn' name='update'>Lưu</button></form></td>
              <td><a class='btn' href='admin.php?page=questions&quiz_id=" . $q['id'] . "'>Quản lý câu hỏi</a>
              <a class='btn' href='admin.php?page=quizzes&del=" . $q['id'] . "'>Xóa</a></td></tr>";
        }
        echo "</table></div>";
    }
    admin_footer();
    exit;
}

/* ===== Questions ===== */
if ($action === 'questions') {
    $quiz_id = (int)($_GET['quiz_id'] ?? 0);
    $quiz = quiz_find($quiz_id);
    if (!$quiz) { header('Location:admin.php?page=quizzes'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create'])) {
            question_create($quiz_id, $_POST['qtext'], $_POST['opt_a'], $_POST['opt_b'], $_POST['opt_c'], $_POST['opt_d'], $_POST['correct_opt']);
        }
        if (isset($_POST['update'])) {
            question_update((int)$_POST['id'], $_POST['qtext'], $_POST['opt_a'], $_POST['opt_b'], $_POST['opt_c'], $_POST['opt_d'], $_POST['correct_opt']);
        }
        if (isset($_POST['seed10'])) {
            $samples = [
                ['Trong Python, kiểu dữ liệu danh sách là gì?', 'list', 'dict', 'set', 'tuple', 'A'],
                ['Hàm in ra màn hình trong Python?', 'echo', 'print', 'puts', 'out', 'B'],
                ['Toán tử so sánh bằng trong Python?', '=', '==', '===', 'eq', 'B'],
                ['Câu lệnh SQL truy vấn dữ liệu?', 'INSERT', 'UPDATE', 'SELECT', 'DELETE', 'C'],
                ['Từ khóa tạo bảng trong SQL?', 'CREATE TABLE', 'MAKE TABLE', 'NEW TABLE', 'TABLE CREATE', 'A'],
                ['Trong SQL, mệnh đề lọc dòng?', 'WHERE', 'ORDER BY', 'GROUP BY', 'HAVING', 'A'],
                ['Kiểu dữ liệu lưu số nguyên?', 'VARCHAR', 'INT', 'DATE', 'TEXT', 'B'],
                ['HTTP là viết tắt của?', 'HyperText Transfer Protocol', 'High Transfer Program', 'Host Transfer Protocol', 'Hyper Tool Protocol', 'A'],
                ['HTML dùng để?', 'Lập trình hệ điều hành', 'Định nghĩa cấu trúc trang web', 'Quản trị CSDL', 'Dịch ngôn ngữ', 'B'],
                ['CSS dùng để?', 'Xử lý yêu cầu HTTP', 'Tạo API', 'Trình bày giao diện trang web', 'Chạy truy vấn SQL', 'C'],
            ];
            foreach ($samples as $s) {
                question_create($quiz_id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5]);
            }
        }
    }
    if (isset($_GET['del'])) { question_delete((int)$_GET['del']); }
    $questions = question_by_quiz($quiz_id);
    $course = course_find($quiz['course_id']);
    admin_header('Câu hỏi: ' . $quiz['title']);
    echo "<div class='card'><a class='btn' href='admin.php?page=quizzes&course_id=" . $quiz['course_id'] . "'>&larr; Quay lại Quiz</a></div>";
    echo "<div class='card'><h2>Thêm câu hỏi cho: " . h($course['title']) . " / " . h($quiz['title']) . "</h2>
      <form class='admin-form' method='post'>
        <textarea name='qtext' placeholder='Nội dung câu hỏi'></textarea>
        <div class='row'>
          <input name='opt_a' placeholder='Phương án A'>
          <input name='opt_b' placeholder='Phương án B'>
          <input name='opt_c' placeholder='Phương án C'>
          <input name='opt_d' placeholder='Phương án D'>
        </div>
        <div class='row' style='grid-template-columns:200px 1fr'>
          <select name='correct_opt'>
            <option value='A'>Đáp án đúng: A</option>
            <option value='B'>Đáp án đúng: B</option>
            <option value='C'>Đáp án đúng: C</option>
            <option value='D'>Đáp án đúng: D</option>
          </select>
          <div class='muted'>Mẹo: nhập ngắn gọn, rõ ý. Có thể dùng nút “Seed 10 câu mẫu” để chèn nhanh.</div>
        </div>
        <button class='btn primary' name='create'>Thêm câu</button>
        <button class='btn' name='seed10' value='1' type='submit'>Seed 10 câu mẫu</button>
      </form>
    </div>";
    echo "<div class='card'><h2>Danh sách câu hỏi (" . count($questions) . ")</h2>";
    if (!$questions) { echo "<div class='muted'>Chưa có câu hỏi.</div>"; }
    else {
        echo "<table><tr><th>ID</th><th>Câu hỏi & phương án</th><th>Đáp án đúng</th><th>Hành động</th></tr>";
        foreach ($questions as $qs) {
            echo "<tr><td>" . $qs['id'] . "</td><td>
                <form class='admin-form' method='post'>
                  <input type='hidden' name='id' value='" . $qs['id'] . "'>
                  <textarea name='qtext'>" . h($qs['qtext']) . "</textarea>
                  <div class='row'>
                    <input name='opt_a' value=\"" . h($qs['opt_a']) . "\" placeholder='A'>
                    <input name='opt_b' value=\"" . h($qs['opt_b']) . "\" placeholder='B'>
                    <input name='opt_c' value=\"" . h($qs['opt_c']) . "\" placeholder='C'>
                    <input name='opt_d' value=\"" . h($qs['opt_d']) . "\" placeholder='D'>
                  </div>
            </td><td style='min-width:130px'>
                  <select name='correct_opt'>
                    <option " . ($qs['correct_opt'] == 'A' ? 'selected' : '') . " value='A'>A</option>
                    <option " . ($qs['correct_opt'] == 'B' ? 'selected' : '') . " value='B'>B</option>
                    <option " . ($qs['correct_opt'] == 'C' ? 'selected' : '') . " value='C'>C</option>
                    <option " . ($qs['correct_opt'] == 'D' ? 'selected' : '') . " value='D'>D</option>
                  </select>
            </td><td style='white-space:nowrap'>
                  <button class='btn' name='update'>Lưu</button>
                  <a class='btn' href='admin.php?page=questions&quiz_id=" . $quiz_id . "&del=" . $qs['id'] . "'>Xóa</a>
                </form>
            </td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    admin_footer();
    exit;
}

/* ===== Users ===== */
if ($action === 'users') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changerole'])) {
        user_update_role((int)$_POST['id'], $_POST['role']);
    }
    $list = user_all();
    admin_header('Người dùng');
    echo "<div class='card'><h2>Danh sách người dùng</h2><table><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Tra cứu</th></tr>";
    foreach ($list as $u) {
        echo "<tr><td>" . $u['id'] . "</td><td>" . h($u['fullname']) . "</td><td>" . h($u['email']) . "</td><td>
        <form class='admin-form' method='post' style='display:inline'>
        <input type='hidden' name='id' value='" . $u['id'] . "'>
        <select name='role'><option value='user' " . ($u['role'] == 'user' ? 'selected' : '') . ">user</option><option value='admin' " . ($u['role'] == 'admin' ? 'selected' : '') . ">admin</option></select>
        <button class='btn' name='changerole'>Lưu</button></form>
        </td><td><a class='btn' href='admin.php?page=user_lookup&id=" . $u['id'] . "'>Xem lịch sử</a></td></tr>";
    }
    echo "</table></div>";
    admin_footer();
    exit;
}

/* ===== User Lookup ===== */
if ($action === 'user_lookup') {
    $uid = (int)($_GET['id'] ?? 0);
    $data = admin_user_history($uid);
    admin_header('Tra cứu tài khoản');
    if (!$data['user']) {
        echo "<div class='card'>Không tìm thấy người dùng</div>";
        admin_footer();
        exit;
    }
    echo "<div class='card'><h2>" . h($data['user']['fullname']) . " (" . h($data['user']['email']) . ")</h2>";
    echo "<h3>Đăng ký khóa học</h3><table><tr><th>Khóa</th><th>Giá</th><th>Ngày</th></tr>";
    foreach ($data['enrollments'] as $e) {
        echo "<tr><td>" . h($e['title']) . "</td><td>" . number_format($e['price_paid']) . "</td><td>" . h($e['enrolled_at']) . "</td></tr>";
    }
    echo "</table><h3>Lịch sử làm quiz</h3><table><tr><th>Khoá</th><th>Quiz</th><th>Lần</th><th>Điểm</th><th>Thời gian</th></tr>";
    foreach ($data['attempts'] as $a) {
        echo "<tr><td>" . h($a['course_title']) . "</td><td>" . h($a['quiz_title']) . "</td><td>" . h($a['attempt_no']) . "</td><td>" . h($a['score']) . "/" . h($a['total_questions']) . "</td><td>" . h($a['finished_at']) . "</td></tr>";
    }
    echo "</table></div>";
    admin_footer();
    exit;
}

/* ===== Revenue ===== */
if ($action === 'revenue') {
    $rows = revenue_summary();
    admin_header('Doanh thu');
    echo "<div class='card'><h2>Tổng hợp theo ngày</h2><table><tr><th>Ngày</th><th>Lượt đăng ký</th><th>Doanh thu</th></tr>";
    foreach ($rows as $r) {
        echo "<tr><td>" . h($r['d']) . "</td><td>" . h($r['enrolls']) . "</td><td>" . number_format($r['revenue']) . "</td></tr>";
    }
    echo "</table></div>";
    admin_footer();
    exit;
}
?>