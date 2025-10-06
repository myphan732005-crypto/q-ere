<?php
// db.php (v2.1 - seed admin + quiz + 10 câu hỏi/khóa)
session_start();

$dsn = 'mysql:host=localhost;dbname=elearning_mini;charset=utf8mb4';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die('DB connect error: ' . $e->getMessage());
}

/** Seed tài khoản & mật khẩu */
function seedPasswords(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT id,email,password_hash,role FROM users");
        $foundAdmin = false;
        foreach ($stmt as $row) {
            $id = $row['id'];
            $email = strtolower($row['email']);
            $hash = $row['password_hash'] ?? '';
            $role = $row['role'] ?? 'user';

            $ok = (!empty($hash) && @password_verify('123456', $hash));
            if (!$ok) {
                $newHash = password_hash('123456', PASSWORD_BCRYPT);
                $up = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
                $up->execute([$newHash, $id]);
            }
            if ($email === 'admin@demo.com' || $role === 'admin') $foundAdmin = true;
        }
        if (!$foundAdmin) {
            $hash = password_hash('123456', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users(fullname,email,password_hash,role,phone,age) VALUES(?,?,?,?,?,?)")
                ->execute(['Admin Demo','admin@demo.com',$hash,'admin','0900000000',30]);
        }
    } catch (Exception $e) { /* ignore nếu bảng chưa có */ }
}

/** Seed quiz + tự thêm đủ 10 câu hỏi cho mỗi khóa */
function seedQuizData(PDO $pdo) {
    try {
        $courses = $pdo->query("SELECT id,title FROM courses")->fetchAll();
        if (!$courses) return;

        // mẫu 10 câu hỏi chung (A/B/C/D)
        $samples = [
            ['Trong Python, kiểu dữ liệu danh sách là gì?', 'list', 'dict', 'set', 'tuple', 'A'],
            ['Hàm in ra màn hình trong Python?', 'echo', 'print', 'puts', 'out', 'B'],
            ['Toán tử so sánh bằng trong Python?', '=', '==', '===', 'eq', 'B'],
            ['Câu lệnh SQL truy vấn dữ liệu?', 'INSERT', 'UPDATE', 'SELECT', 'DELETE', 'C'],
            ['Từ khóa tạo bảng trong SQL?', 'CREATE TABLE', 'MAKE TABLE', 'NEW TABLE', 'TABLE CREATE', 'A'],
            ['Trong SQL, mệnh đề lọc dòng?', 'WHERE', 'ORDER BY', 'GROUP BY', 'HAVING', 'A'],
            ['Kiểu dữ liệu lưu số nguyên?', 'VARCHAR', 'INT', 'DATE', 'TEXT', 'B'],
            ['Trong web, HTTP là viết tắt của?', 'HyperText Transfer Protocol', 'High Transfer Program', 'Host Transfer Protocol', 'Hyper Tool Protocol', 'A'],
            ['HTML dùng để?', 'Lập trình hệ điều hành', 'Định nghĩa cấu trúc trang web', 'Quản trị CSDL', 'Dịch ngôn ngữ', 'B'],
            ['CSS dùng để?', 'Xử lý yêu cầu HTTP', 'Tạo API', 'Trình bày giao diện trang web', 'Chạy truy vấn SQL', 'C'],
        ];

        foreach ($courses as $c) {
            // đảm bảo có ít nhất 1 quiz cho khoá
            $st = $pdo->prepare("SELECT id FROM quizzes WHERE course_id=? LIMIT 1");
            $st->execute([$c['id']]);
            $quiz_id = $st->fetchColumn();

            if (!$quiz_id) {
                $ins = $pdo->prepare("INSERT INTO quizzes(course_id,title) VALUES(?,?)");
                $ins->execute([$c['id'], 'Quiz: '.$c['title']]);
                $quiz_id = $pdo->lastInsertId();
            }

            // đếm số câu hiện có
            $stc = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id=?");
            $stc->execute([$quiz_id]);
            $count = (int)$stc->fetchColumn();

            // nếu < 10 thì thêm cho đủ
            if ($count < 10) {
                $need = 10 - $count;
                $insQ = $pdo->prepare("INSERT INTO questions(quiz_id,qtext,opt_a,opt_b,opt_c,opt_d,correct_opt) VALUES(?,?,?,?,?,?,?)");
                for ($i=0; $i<$need; $i++) {
                    $q = $samples[$i % count($samples)];
                    $insQ->execute([$quiz_id, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]]);
                }
            }
        }
    } catch (Exception $e) { /* ignore nếu bảng chưa có */ }
}

seedPasswords($pdo);
seedQuizData($pdo);

function is_logged_in() { return isset($_SESSION['user']); }
function current_user() { return $_SESSION['user'] ?? null; }
function is_admin() { return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'; }
function require_login() { if (!is_logged_in()) { header('Location: index.php?page=login'); exit; } }
function require_admin() { if (!is_admin()) { header('Location: index.php?page=login'); exit; } }

function flash($key,$val=null){
    if($val!==null){ $_SESSION['flash'][$key]=$val; return; }
    $v = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $v;
}
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
