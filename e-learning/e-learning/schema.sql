-- Database: elearning_minielearning_mini
DROP DATABASE IF EXISTS ;
CREATE DATABASE elearning_mini CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elearning_mini;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  phone VARCHAR(30),
  age INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- COURSES
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- LESSONS
CREATE TABLE lessons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  content TEXT,
  video_url VARCHAR(500),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ENROLLMENTS (ghi nhận doanh thu)
CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  price_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_course (user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- QUIZZES (mỗi khóa có thể có 1+ quiz)
CREATE TABLE quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- QUESTIONS (4 lựa chọn A/B/C/D)
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  qtext TEXT NOT NULL,
  opt_a TEXT NOT NULL,
  opt_b TEXT NOT NULL,
  opt_c TEXT NOT NULL,
  opt_d TEXT NOT NULL,
  correct_opt ENUM('A','B','C','D') NOT NULL,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- QUIZ ATTEMPTS (lịch sử làm bài)
CREATE TABLE attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  user_id INT NOT NULL,
  attempt_no INT NOT NULL,
  total_questions INT NOT NULL,
  score INT NOT NULL,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ANSWERS (câu sai/đúng của từng lần)
CREATE TABLE attempt_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  chosen_opt ENUM('A','B','C','D') NOT NULL,
  is_correct TINYINT(1) NOT NULL,
  FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- DỮ LIỆU MẪU: tạo admin rỗng password, app sẽ đặt hash = 123456 khi chạy
INSERT INTO users (fullname,email,password_hash,role,phone,age) VALUES
('Admin Demo','admin@demo.com', '', 'admin','0900000000',30),
('User Demo','user@demo.com', '', 'user','0900000001',22);

INSERT INTO courses (title,description,price,is_active) VALUES
('Python cơ bản','Học Python từ con số 0', 299000, 1),
('SQL cho người mới','Truy vấn dữ liệu với MySQL', 199000, 1);

INSERT INTO lessons (course_id,title,content,video_url) VALUES
(1,'Giới thiệu Python','Nội dung bài 1','https://www.youtube.com/watch?v=kqtD5dpn9C8'),
(1,'Biến & Kiểu dữ liệu','Nội dung bài 2',NULL),
(2,'Giới thiệu SQL','Nội dung bài 1',NULL);

INSERT INTO quizzes (course_id,title) VALUES
(1,'Quiz Python #1'),
(2,'Quiz SQL #1');

INSERT INTO questions (quiz_id,qtext,opt_a,opt_b,opt_c,opt_d,correct_opt) VALUES
(1,'Kiểu dữ liệu nào là danh sách trong Python?','list','dict','set','tuple','A'),
(1,'Hàm in ra màn hình?','echo','print','puts','write','B'),
(2,'Câu lệnh truy vấn dữ liệu?','INSERT','UPDATE','SELECT','DELETE','C');
