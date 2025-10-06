<?php
// dao.php
require_once __DIR__.'/db.php';

/* =============== USERS =============== */
function user_find_by_email($email){
    global $pdo; $st=$pdo->prepare("SELECT * FROM users WHERE email=?");
    $st->execute([$email]); return $st->fetch();
}
function user_find($id){ global $pdo; $st=$pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); return $st->fetch(); }
function user_create($fullname,$email,$password,$role='user',$phone=null,$age=null){
    global $pdo; $hash=password_hash($password,PASSWORD_BCRYPT);
    $st=$pdo->prepare("INSERT INTO users(fullname,email,password_hash,role,phone,age) VALUES(?,?,?,?,?,?)");
    $st->execute([$fullname,$email,$hash,$role,$phone,$age]); return $pdo->lastInsertId();
}
function user_all(){ global $pdo; return $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(); }
function user_update_role($id,$role){ global $pdo; $st=$pdo->prepare("UPDATE users SET role=? WHERE id=?"); return $st->execute([$role,$id]); }

/* =============== COURSES & LESSONS =============== */
function course_all_active(){ global $pdo; return $pdo->query("SELECT * FROM courses WHERE is_active=1 ORDER BY id DESC")->fetchAll(); }
function course_all(){ global $pdo; return $pdo->query("SELECT * FROM courses ORDER BY id DESC")->fetchAll(); }
function course_find($id){ global $pdo; $st=$pdo->prepare("SELECT * FROM courses WHERE id=?"); $st->execute([$id]); return $st->fetch(); }
function course_create($title,$desc,$price,$active=1){ global $pdo; $st=$pdo->prepare("INSERT INTO courses(title,description,price,is_active) VALUES(?,?,?,?)"); $st->execute([$title,$desc,$price,$active]); return $pdo->lastInsertId(); }
function course_update($id,$title,$desc,$price,$active){ global $pdo; $st=$pdo->prepare("UPDATE courses SET title=?,description=?,price=?,is_active=? WHERE id=?"); return $st->execute([$title,$desc,$price,$active,$id]); }
function course_delete($id){ global $pdo; $st=$pdo->prepare("DELETE FROM courses WHERE id=?"); return $st->execute([$id]); }

function lesson_by_course($course_id){ global $pdo; $st=$pdo->prepare("SELECT * FROM lessons WHERE course_id=? ORDER BY id ASC"); $st->execute([$course_id]); return $st->fetchAll(); }
function lesson_create($course_id,$title,$content,$video_url){ global $pdo; $st=$pdo->prepare("INSERT INTO lessons(course_id,title,content,video_url) VALUES(?,?,?,?)"); $st->execute([$course_id,$title,$content,$video_url]); return $pdo->lastInsertId(); }
function lesson_delete($id){ global $pdo; $st=$pdo->prepare("DELETE FROM lessons WHERE id=?"); return $st->execute([$id]); }

/* =============== ENROLLMENTS / REVENUE =============== */
function enroll($user_id,$course_id,$price){ global $pdo; $st=$pdo->prepare("INSERT INTO enrollments(user_id,course_id,price_paid) VALUES(?,?,?) ON DUPLICATE KEY UPDATE price_paid=VALUES(price_paid), enrolled_at=NOW()"); return $st->execute([$user_id,$course_id,$price]); }
function is_enrolled($user_id,$course_id){ global $pdo; $st=$pdo->prepare("SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?"); $st->execute([$user_id,$course_id]); return (bool)$st->fetchColumn(); }
function enrollments_by_user($user_id){ global $pdo; $st=$pdo->prepare("SELECT e.*, c.title FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.enrolled_at DESC"); $st->execute([$user_id]); return $st->fetchAll(); }
function revenue_summary(){ global $pdo; return $pdo->query("SELECT DATE(enrolled_at) d, COUNT(*) enrolls, SUM(price_paid) revenue FROM enrollments GROUP BY DATE(enrolled_at) ORDER BY d DESC")->fetchAll(); }

/* =============== QUIZ & QUESTIONS =============== */
function quizzes_by_course($course_id){ global $pdo; $st=$pdo->prepare("SELECT * FROM quizzes WHERE course_id=? ORDER BY id ASC"); $st->execute([$course_id]); return $st->fetchAll(); }
function quiz_by_course($course_id){ return quizzes_by_course($course_id); }
function quiz_find($id){ global $pdo; $st=$pdo->prepare("SELECT * FROM quizzes WHERE id=?"); $st->execute([$id]); return $st->fetch(); }
function quiz_create($course_id,$title){ global $pdo; $st=$pdo->prepare("INSERT INTO quizzes(course_id,title) VALUES(?,?)"); $st->execute([$course_id,$title]); return $pdo->lastInsertId(); }
function quiz_update($id,$course_id,$title){ global $pdo; $st=$pdo->prepare("UPDATE quizzes SET course_id=?, title=? WHERE id=?"); return $st->execute([$course_id,$title,$id]); }
function quiz_delete($id){ global $pdo; $st=$pdo->prepare("DELETE FROM quizzes WHERE id=?"); return $st->execute([$id]); }

function question_by_quiz($quiz_id){ global $pdo; $st=$pdo->prepare("SELECT * FROM questions WHERE quiz_id=? ORDER BY id ASC"); $st->execute([$quiz_id]); return $st->fetchAll(); }
function question_find($id){ global $pdo; $st=$pdo->prepare("SELECT * FROM questions WHERE id=?"); $st->execute([$id]); return $st->fetch(); }
function question_create($quiz_id,$qtext,$a,$b,$c,$d,$correct){
    global $pdo; $st=$pdo->prepare("INSERT INTO questions(quiz_id,qtext,opt_a,opt_b,opt_c,opt_d,correct_opt) VALUES(?,?,?,?,?,?,?)");
    return $st->execute([$quiz_id,$qtext,$a,$b,$c,$d,$correct]);
}
function question_update($id,$qtext,$a,$b,$c,$d,$correct){
    global $pdo; $st=$pdo->prepare("UPDATE questions SET qtext=?, opt_a=?, opt_b=?, opt_c=?, opt_d=?, correct_opt=? WHERE id=?");
    return $st->execute([$qtext,$a,$b,$c,$d,$correct,$id]);
}
function question_delete($id){ global $pdo; $st=$pdo->prepare("DELETE FROM questions WHERE id=?"); return $st->execute([$id]); }

/* =============== ATTEMPTS (quan trọng) =============== */
function attempt_next_no($quiz_id,$user_id){
    global $pdo; $st=$pdo->prepare("SELECT IFNULL(MAX(attempt_no),0)+1 FROM attempts WHERE quiz_id=? AND user_id=?");
    $st->execute([$quiz_id,$user_id]); return (int)$st->fetchColumn();
}
function attempt_create($quiz_id,$user_id,$total_q){
    global $pdo; $no = attempt_next_no($quiz_id,$user_id);
    $st=$pdo->prepare("INSERT INTO attempts(quiz_id,user_id,attempt_no,total_questions,score) VALUES(?,?,?,?,0)");
    $st->execute([$quiz_id,$user_id,$no,$total_q]); return $pdo->lastInsertId();
}
function attempt_finish($attempt_id,$score){
    global $pdo; $st=$pdo->prepare("UPDATE attempts SET score=?, finished_at=NOW() WHERE id=?");
    return $st->execute([$score,$attempt_id]);
}
function attempt_answers_save($attempt_id,$answers){
    global $pdo; $st=$pdo->prepare("INSERT INTO attempt_answers(attempt_id,question_id,chosen_opt,is_correct) VALUES(?,?,?,?)");
    foreach($answers as $a){ $st->execute([$attempt_id,$a['qid'],$a['chosen'],$a['correct']]); }
}
function attempts_by_user_quiz($user_id,$quiz_id){
    global $pdo; $st=$pdo->prepare("SELECT * FROM attempts WHERE user_id=? AND quiz_id=? ORDER BY id DESC");
    $st->execute([$user_id,$quiz_id]); return $st->fetchAll();
}
function wrong_details_by_attempt($attempt_id){
    global $pdo; $sql="SELECT aa.*, q.qtext, q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.correct_opt
                       FROM attempt_answers aa
                       JOIN questions q ON q.id=aa.question_id
                       WHERE aa.attempt_id=? AND aa.is_correct=0";
    $st=$pdo->prepare($sql); $st->execute([$attempt_id]); return $st->fetchAll();
}

/* =============== ADMIN LOOKUP =============== */
function admin_user_history($user_id){
    return [
        'user' => user_find($user_id),
        'enrollments' => enrollments_by_user($user_id),
        'attempts' => admin_attempts_by_user($user_id)
    ];
}
function admin_attempts_by_user($user_id){
    global $pdo; $sql = "SELECT a.*, q.title quiz_title, c.title course_title
                         FROM attempts a
                         JOIN quizzes q ON q.id=a.quiz_id
                         JOIN courses c ON c.id=q.course_id
                         WHERE a.user_id=? ORDER BY a.started_at DESC";
    $st=$pdo->prepare($sql); $st->execute([$user_id]); return $st->fetchAll();
}
