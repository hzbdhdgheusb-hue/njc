<?php
// ============================================================
// NABAJYOTI COLLEGE — Full Website with Admin Panel
// Stack: PHP 8+ · MySQL · HTML · Inline CSS · Vanilla JS
// ============================================================

session_start();

// ─── DATABASE CONFIG ────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'nabajyoti_college');

// ─── DB CONNECTION ───────────────────────────────────────────
function getDB(): mysqli {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_errno) {
            die(json_encode(['error' => 'DB connection failed: ' . $db->connect_error]));
        }
        $db->set_charset('utf8mb4');
    }
    return $db;
}

// ─── AUTO-INSTALL DATABASE ON FIRST RUN ──────────────────────
function installDB(): void {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_errno) return;
    $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);

    $tables = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100),
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            roll_no VARCHAR(20) UNIQUE,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(15),
            dob DATE,
            gender ENUM('Male','Female','Other'),
            category ENUM('General','OBC','SC','ST') DEFAULT 'General',
            address TEXT,
            programme VARCHAR(100),
            semester INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id VARCHAR(20) UNIQUE,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(15),
            department VARCHAR(100),
            designation VARCHAR(100),
            qualification VARCHAR(200),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            app_no VARCHAR(20) UNIQUE,
            student_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(15),
            dob DATE,
            gender ENUM('Male','Female','Other'),
            category ENUM('General','OBC','SC','ST') DEFAULT 'General',
            programme VARCHAR(100) NOT NULL,
            prev_school VARCHAR(200),
            marks_pct DECIMAL(5,2),
            address TEXT,
            status ENUM('pending','review','approved','rejected','enrolled') DEFAULT 'pending',
            remarks TEXT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(10),
            icon VARCHAR(10) DEFAULT '📚',
            head_name VARCHAR(100),
            students_count INT DEFAULT 0,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            category VARCHAR(50) DEFAULT 'General',
            priority ENUM('normal','important','urgent') DEFAULT 'normal',
            target ENUM('all','students','teachers') DEFAULT 'all',
            posted_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS fees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT,
            roll_no VARCHAR(20),
            student_name VARCHAR(100),
            programme VARCHAR(100),
            semester INT,
            fee_type VARCHAR(100),
            amount DECIMAL(10,2),
            paid_amount DECIMAL(10,2) DEFAULT 0,
            due_date DATE,
            paid_date DATE,
            status ENUM('pending','partial','paid','overdue') DEFAULT 'pending',
            transaction_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT,
            roll_no VARCHAR(20),
            student_name VARCHAR(100),
            programme VARCHAR(100),
            semester INT,
            subject VARCHAR(100),
            internal_marks DECIMAL(5,2),
            external_marks DECIMAL(5,2),
            total DECIMAL(5,2),
            grade VARCHAR(5),
            status ENUM('pass','fail','absent') DEFAULT 'pass',
            published TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $sql) $conn->query($sql);

    // Seed default admin
    $check = $conn->query("SELECT id FROM admins WHERE username='admin'");
    if ($check->num_rows === 0) {
        $hash = password_hash('admin@123', PASSWORD_BCRYPT);
        $conn->query("INSERT INTO admins (username, password, name, email) VALUES ('admin', '$hash', 'Super Admin', 'admin@nabajyoticollege.ac.in')");
    }

    // Seed default settings
    $settings = [
        ['site_name', 'Nabajyoti College Kalgachia'],
        ['admission_open', '1'],
        ['admission_deadline', '2025-08-31'],
        ['contact_email', 'principal@nabajyoticollege.ac.in'],
        ['contact_phone', '+91 78960 00000'],
        ['established', '1972'],
    ];
    foreach ($settings as [$k, $v]) {
        $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$k','$v')");
    }

    // Seed sample data
    $conn->query("INSERT IGNORE INTO departments (name, code, icon, head_name, students_count, description) VALUES
        ('Arts','BA','🎭','Dr. Rekha Devi',320,'Assamese, History, Political Sc., Education, Philosophy'),
        ('Science','BSC','🔬','Dr. Bipul Kakati',280,'Physics, Chemistry, Mathematics, Botany, Zoology'),
        ('Commerce','BCOM','💼','Prof. Naba Kr. Das',180,'Accountancy, Business Studies, Economics, Finance'),
        ('Computer Science','BCA','💻','Mr. Ranjit Bora',95,'Programming, Database, Web Dev, Networking')
    ");
    $conn->query("INSERT IGNORE INTO notices (title, content, category, priority, target, posted_by) VALUES
        ('Admission Open 2025-26','Applications for the 2025-26 session are now open. Apply before 31 August 2025.','Admission','urgent','all','Admin'),
        ('Semester Exam Schedule Published','The schedule for End-Semester Examinations has been published on the notice board.','Exam','important','students','Admin'),
        ('Faculty Development Programme','A Faculty Development Programme will be held on 15 June 2025 at the college auditorium.','Event','normal','teachers','Admin')
    ");
    $conn->query("INSERT IGNORE INTO applications (app_no, student_name, email, category, programme, marks_pct, status) VALUES
        ('#NJC-2025-0101','Anurag Sharma','anurag@gmail.com','General','B.Sc. Physics',89.5,'pending'),
        ('#NJC-2025-0102','Priya Borah','priya@gmail.com','OBC','B.A. Assamese',84.0,'review'),
        ('#NJC-2025-0103','Rahul Das','rahul@gmail.com','SC','B.Com. Commerce',78.2,'approved'),
        ('#NJC-2025-0104','Sujata Kalita','sujata@gmail.com','General','B.Sc. Mathematics',92.1,'enrolled')
    ");
    $conn->close();
}

installDB();

// ─── HELPERS ─────────────────────────────────────────────────
function jsonOut(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
function sanitize(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES);
}
function rows(string $sql, array $bind = []): array {
    $db = getDB();
    if (empty($bind)) {
        $r = $db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
    $stmt = $db->prepare($sql);
    $stmt->bind_param(...$bind);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function exec_sql(string $sql, array $bind = []): bool|int {
    $db = getDB();
    if (empty($bind)) { $db->query($sql); return $db->affected_rows; }
    $stmt = $db->prepare($sql);
    $stmt->bind_param(...$bind);
    $stmt->execute();
    return $db->insert_id ?: $db->affected_rows;
}

// ─── AJAX API ROUTER ─────────────────────────────────────────
if (isset($_GET['api'])) {
    $action = $_GET['api'];

    // ── Admin Login ──
    if ($action === 'admin_login') {
        $u = sanitize($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $rows = rows("SELECT * FROM admins WHERE username=?", ['s', $u]);
        if ($rows && password_verify($p, $rows[0]['password'])) {
            $_SESSION['admin'] = $rows[0];
            jsonOut(['ok' => true, 'name' => $rows[0]['name']]);
        }
        jsonOut(['ok' => false, 'msg' => 'Invalid credentials']);
    }

    // ── Student Login ──
    if ($action === 'student_login') {
        $e = sanitize($_POST['email'] ?? '');
        $p = $_POST['password'] ?? '';
        $rows = rows("SELECT * FROM students WHERE email=?", ['s', $e]);
        if ($rows && password_verify($p, $rows[0]['password'])) {
            $_SESSION['student'] = $rows[0];
            jsonOut(['ok' => true, 'name' => $rows[0]['name'], 'roll' => $rows[0]['roll_no']]);
        }
        jsonOut(['ok' => false, 'msg' => 'Invalid credentials']);
    }

    // ── Teacher Login ──
    if ($action === 'teacher_login') {
        $e = sanitize($_POST['email'] ?? '');
        $p = $_POST['password'] ?? '';
        $rows = rows("SELECT * FROM teachers WHERE email=?", ['s', $e]);
        if ($rows && password_verify($p, $rows[0]['password'])) {
            $_SESSION['teacher'] = $rows[0];
            jsonOut(['ok' => true, 'name' => $rows[0]['name']]);
        }
        jsonOut(['ok' => false, 'msg' => 'Invalid credentials']);
    }

    // ── Student Register ──
    if ($action === 'student_register') {
        $n = sanitize($_POST['name'] ?? '');
        $e = sanitize($_POST['email'] ?? '');
        $p = password_hash($_POST['password'] ?? 'pass123', PASSWORD_BCRYPT);
        $roll = 'NJC' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $exist = rows("SELECT id FROM students WHERE email=?", ['s', $e]);
        if ($exist) jsonOut(['ok' => false, 'msg' => 'Email already registered']);
        exec_sql("INSERT INTO students (roll_no,name,email,password) VALUES (?,?,?,?)", ['ssss', $roll, $n, $e, $p]);
        $new = rows("SELECT * FROM students WHERE email=?", ['s', $e]);
        $_SESSION['student'] = $new[0];
        jsonOut(['ok' => true, 'name' => $n, 'roll' => $roll]);
    }

    // Protected routes need admin
    if (!isset($_SESSION['admin']) && in_array($action, [
        'get_stats','get_students','save_student','delete_student',
        'get_teachers','save_teacher','delete_teacher',
        'get_applications','update_application',
        'get_departments','save_department','delete_department',
        'get_notices_admin','save_notice','delete_notice',
        'get_fees','save_fee','delete_fee',
        'get_results','save_result','delete_result',
        'get_settings','save_settings','get_admins','save_admin'
    ])) {
        jsonOut(['ok' => false, 'msg' => 'Unauthorized']);
    }

    // ── Dashboard Stats ──
    if ($action === 'get_stats') {
        $db = getDB();
        jsonOut([
            'students'     => $db->query("SELECT COUNT(*) c FROM students")->fetch_row()[0],
            'teachers'     => $db->query("SELECT COUNT(*) c FROM teachers")->fetch_row()[0],
            'applications' => $db->query("SELECT COUNT(*) c FROM applications")->fetch_row()[0],
            'pending'      => $db->query("SELECT COUNT(*) c FROM applications WHERE status='pending'")->fetch_row()[0],
            'approved'     => $db->query("SELECT COUNT(*) c FROM applications WHERE status='approved'")->fetch_row()[0],
            'enrolled'     => $db->query("SELECT COUNT(*) c FROM applications WHERE status='enrolled'")->fetch_row()[0],
            'notices'      => $db->query("SELECT COUNT(*) c FROM notices")->fetch_row()[0],
            'fee_pending'  => $db->query("SELECT COALESCE(SUM(amount-paid_amount),0) c FROM fees WHERE status!='paid'")->fetch_row()[0],
        ]);
    }

    // ── Students CRUD ──
    if ($action === 'get_students') {
        $q = sanitize($_GET['q'] ?? '');
        $sql = "SELECT * FROM students";
        if ($q) $sql .= " WHERE name LIKE '%$q%' OR email LIKE '%$q%' OR roll_no LIKE '%$q%'";
        $sql .= " ORDER BY id DESC LIMIT 100";
        jsonOut(rows($sql));
    }
    if ($action === 'save_student') {
        $d = $_POST;
        if (!empty($d['id'])) {
            exec_sql("UPDATE students SET name=?,email=?,phone=?,programme=?,category=?,semester=?,gender=?,address=? WHERE id=?",
                ['ssssssssi', $d['name'],$d['email'],$d['phone'],$d['programme'],$d['category'],$d['semester'],$d['gender'],$d['address'],$d['id']]);
            jsonOut(['ok' => true, 'msg' => 'Student updated']);
        } else {
            $p = password_hash($d['password'] ?? 'pass123', PASSWORD_BCRYPT);
            $roll = 'NJC' . date('Y') . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
            exec_sql("INSERT INTO students (roll_no,name,email,password,phone,programme,category,semester,gender,address) VALUES (?,?,?,?,?,?,?,?,?,?)",
                ['sssssssisss',$roll,$d['name'],$d['email'],$p,$d['phone'],$d['programme'],$d['category'],$d['semester']??1,$d['gender'],$d['address']]);
            jsonOut(['ok' => true, 'msg' => 'Student added', 'roll' => $roll]);
        }
    }
    if ($action === 'delete_student') {
        exec_sql("DELETE FROM students WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Teachers CRUD ──
    if ($action === 'get_teachers') {
        jsonOut(rows("SELECT * FROM teachers ORDER BY id DESC"));
    }
    if ($action === 'save_teacher') {
        $d = $_POST;
        if (!empty($d['id'])) {
            exec_sql("UPDATE teachers SET name=?,email=?,phone=?,department=?,designation=?,qualification=? WHERE id=?",
                ['ssssssi',$d['name'],$d['email'],$d['phone'],$d['department'],$d['designation'],$d['qualification'],$d['id']]);
            jsonOut(['ok' => true, 'msg' => 'Teacher updated']);
        } else {
            $p = password_hash($d['password'] ?? 'teacher123', PASSWORD_BCRYPT);
            $emp = 'EMP' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
            exec_sql("INSERT INTO teachers (emp_id,name,email,password,phone,department,designation,qualification) VALUES (?,?,?,?,?,?,?,?)",
                ['ssssssss',$emp,$d['name'],$d['email'],$p,$d['phone'],$d['department'],$d['designation'],$d['qualification']]);
            jsonOut(['ok' => true, 'msg' => 'Teacher added']);
        }
    }
    if ($action === 'delete_teacher') {
        exec_sql("DELETE FROM teachers WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Applications ──
    if ($action === 'get_applications') {
        $s = sanitize($_GET['status'] ?? '');
        $sql = "SELECT * FROM applications";
        if ($s) $sql .= " WHERE status='$s'";
        $sql .= " ORDER BY submitted_at DESC LIMIT 200";
        jsonOut(rows($sql));
    }
    if ($action === 'update_application') {
        exec_sql("UPDATE applications SET status=?,remarks=? WHERE id=?",
            ['ssi', $_POST['status'], $_POST['remarks'] ?? '', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Departments CRUD ──
    if ($action === 'get_departments') { jsonOut(rows("SELECT * FROM departments ORDER BY id")); }
    if ($action === 'save_department') {
        $d = $_POST;
        if (!empty($d['id'])) {
            exec_sql("UPDATE departments SET name=?,code=?,icon=?,head_name=?,description=? WHERE id=?",
                ['ssssssi',$d['name'],$d['code'],$d['icon'],$d['head_name'],$d['description'],$d['id']]);
        } else {
            exec_sql("INSERT INTO departments (name,code,icon,head_name,description) VALUES (?,?,?,?,?)",
                ['sssss',$d['name'],$d['code'],$d['icon']??'📚',$d['head_name'],$d['description']]);
        }
        jsonOut(['ok' => true]);
    }
    if ($action === 'delete_department') {
        exec_sql("DELETE FROM departments WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Notices ──
    if ($action === 'get_notices_admin') { jsonOut(rows("SELECT * FROM notices ORDER BY id DESC")); }
    if ($action === 'save_notice') {
        $d = $_POST;
        if (!empty($d['id'])) {
            exec_sql("UPDATE notices SET title=?,content=?,category=?,priority=?,target=? WHERE id=?",
                ['sssssi',$d['title'],$d['content'],$d['category'],$d['priority'],$d['target'],$d['id']]);
        } else {
            exec_sql("INSERT INTO notices (title,content,category,priority,target,posted_by) VALUES (?,?,?,?,?,?)",
                ['ssssss',$d['title'],$d['content'],$d['category'],$d['priority'],$d['target'],'Admin']);
        }
        jsonOut(['ok' => true]);
    }
    if ($action === 'delete_notice') {
        exec_sql("DELETE FROM notices WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Fees ──
    if ($action === 'get_fees') { jsonOut(rows("SELECT * FROM fees ORDER BY id DESC LIMIT 200")); }
    if ($action === 'save_fee') {
        $d = $_POST;
        if (!empty($d['id'])) {
            exec_sql("UPDATE fees SET student_name=?,programme=?,semester=?,fee_type=?,amount=?,paid_amount=?,due_date=?,paid_date=?,status=?,transaction_id=? WHERE id=?",
                ['ssisddssssi',$d['student_name'],$d['programme'],$d['semester'],$d['fee_type'],$d['amount'],$d['paid_amount'],$d['due_date'],$d['paid_date'],$d['status'],$d['transaction_id'],$d['id']]);
        } else {
            exec_sql("INSERT INTO fees (student_name,roll_no,programme,semester,fee_type,amount,paid_amount,due_date,paid_date,status,transaction_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                ['sssisddsss',$d['student_name'],$d['roll_no']??'',$d['programme'],$d['semester']??1,$d['fee_type'],$d['amount'],$d['paid_amount']??0,$d['due_date'],$d['paid_date']??null,$d['status'],$d['transaction_id']??'']);
        }
        jsonOut(['ok' => true]);
    }
    if ($action === 'delete_fee') {
        exec_sql("DELETE FROM fees WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Results ──
    if ($action === 'get_results') { jsonOut(rows("SELECT * FROM results ORDER BY id DESC LIMIT 200")); }
    if ($action === 'save_result') {
        $d = $_POST;
        $total = ($d['internal_marks'] ?? 0) + ($d['external_marks'] ?? 0);
        $grade = $total >= 90 ? 'A+' : ($total >= 80 ? 'A' : ($total >= 70 ? 'B+' : ($total >= 60 ? 'B' : ($total >= 50 ? 'C' : 'F'))));
        $status = $total >= 40 ? 'pass' : 'fail';
        if (!empty($d['id'])) {
            exec_sql("UPDATE results SET student_name=?,roll_no=?,programme=?,semester=?,subject=?,internal_marks=?,external_marks=?,total=?,grade=?,status=?,published=? WHERE id=?",
                ['sssissddssii',$d['student_name'],$d['roll_no'],$d['programme'],$d['semester'],$d['subject'],$d['internal_marks'],$d['external_marks'],$total,$grade,$status,$d['published']??0,$d['id']]);
        } else {
            exec_sql("INSERT INTO results (student_name,roll_no,programme,semester,subject,internal_marks,external_marks,total,grade,status,published) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                ['sssissdddssss',$d['student_name'],$d['roll_no']??'',$d['programme'],$d['semester']??1,$d['subject'],$d['internal_marks'],$d['external_marks'],$total,$grade,$status,$d['published']??0]);
        }
        jsonOut(['ok' => true]);
    }
    if ($action === 'delete_result') {
        exec_sql("DELETE FROM results WHERE id=?", ['i', $_POST['id']]);
        jsonOut(['ok' => true]);
    }

    // ── Settings ──
    if ($action === 'get_settings') { jsonOut(rows("SELECT * FROM site_settings")); }
    if ($action === 'save_settings') {
        foreach ($_POST as $k => $v) {
            exec_sql("INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?",
                ['sss', $k, $v, $v]);
        }
        jsonOut(['ok' => true, 'msg' => 'Settings saved']);
    }

    // ── Admins ──
    if ($action === 'get_admins') { jsonOut(rows("SELECT id,username,name,email,created_at FROM admins ORDER BY id")); }
    if ($action === 'save_admin') {
        $d = $_POST;
        $p = password_hash($d['password'] ?? 'admin123', PASSWORD_BCRYPT);
        exec_sql("INSERT IGNORE INTO admins (username,password,name,email) VALUES (?,?,?,?)",
            ['ssss',$d['username'],$p,$d['name'],$d['email']]);
        jsonOut(['ok' => true]);
    }

    // ── Logout ──
    if ($action === 'logout') {
        $role = $_POST['role'] ?? 'admin';
        unset($_SESSION[$role]);
        jsonOut(['ok' => true]);
    }

    // ── Submit Application (public) ──
    if ($action === 'submit_application') {
        $d = $_POST;
        if (empty($d['student_name']) || empty($d['programme'])) jsonOut(['ok' => false, 'msg' => 'Missing required fields']);
        $appNo = '#NJC-' . date('Y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        exec_sql("INSERT INTO applications (app_no,student_name,email,phone,dob,gender,category,programme,prev_school,marks_pct,address) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            ['sssssssssds',$appNo,$d['student_name'],$d['email']??'',$d['phone']??'',$d['dob']??null,$d['gender']??'Male',$d['category']??'General',$d['programme'],$d['prev_school']??'',$d['marks_pct']??0,$d['address']??'']);
        jsonOut(['ok' => true, 'app_no' => $appNo]);
    }

    // ── Get Notices (public) ──
    if ($action === 'get_notices') {
        jsonOut(rows("SELECT * FROM notices WHERE target IN ('all','students') ORDER BY id DESC LIMIT 10"));
    }

    jsonOut(['error' => 'Unknown API action']);
}

// Check admin session for admin page
$isAdmin = isset($_SESSION['admin']);
$adminName = $isAdmin ? htmlspecialchars($_SESSION['admin']['name']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nabajyoti College Kalgachia</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --navy: #0d1b3e;
  --gold: #c8922a;
  --gold-light: #e8b94f;
  --cream: #f9f5ee;
  --white: #ffffff;
  --text: #1a1a2e;
  --muted: #6b7280;
  --border: #e5e0d8;
  --success: #16a34a;
  --danger: #dc2626;
  --info: #1d4ed8;
  --warning: #d97706;
  --card-bg: #ffffff;
  --sidebar-bg: #0d1b3e;
  --input-bg: #f8f7f4;
  /* Admin accent */
  --admin-accent: #7c3aed;
  --admin-light: #ede9fe;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--text);overflow-x:hidden;}

/* PAGES */
.page{display:none;min-height:100vh;}
.page.active{display:block;}

/* ====== LANDING ====== */
#landing{background:var(--navy);position:relative;overflow:hidden;}
.hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(200,146,42,.12) 0%,transparent 60%),radial-gradient(ellipse at 10% 80%,rgba(200,146,42,.07) 0%,transparent 50%);}
.hero-pattern{position:absolute;inset:0;background-image:repeating-linear-gradient(45deg,transparent,transparent 40px,rgba(200,146,42,.03) 40px,rgba(200,146,42,.03) 41px);}
nav{position:relative;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:1.5rem 4rem;border-bottom:1px solid rgba(200,146,42,.2);}
.nav-logo{display:flex;align-items:center;gap:1rem;}
.nav-logo-icon{width:48px;height:48px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--navy);}
.nav-logo-text h2{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--white);line-height:1.2;}
.nav-logo-text span{font-size:.7rem;color:var(--gold-light);letter-spacing:.15em;text-transform:uppercase;}
.nav-links{display:flex;gap:2rem;align-items:center;}
.nav-links a{color:rgba(255,255,255,.75);text-decoration:none;font-size:.9rem;font-weight:500;transition:color .2s;cursor:pointer;}
.nav-links a:hover{color:var(--gold-light);}
.nav-cta{display:flex;gap:.75rem;}
.btn{padding:.6rem 1.5rem;border-radius:6px;font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:500;cursor:pointer;transition:all .2s;border:none;text-decoration:none;}
.btn-outline{background:transparent;border:1.5px solid rgba(200,146,42,.6);color:var(--gold-light);}
.btn-outline:hover{background:rgba(200,146,42,.1);border-color:var(--gold);}
.btn-primary{background:var(--gold);color:var(--navy);font-weight:600;}
.btn-primary:hover{background:var(--gold-light);transform:translateY(-1px);}
.btn-secondary{background:var(--navy);color:var(--white);border:1.5px solid rgba(255,255,255,.2);}
.btn-secondary:hover{background:#1a2d5a;}
.btn-success{background:var(--success);color:white;}
.btn-success:hover{background:#15803d;}
.btn-danger{background:var(--danger);color:white;}
.btn-danger:hover{background:#b91c1c;}
.btn-admin{background:var(--admin-accent);color:white;}
.btn-admin:hover{background:#6d28d9;}
.btn-warning{background:var(--warning);color:white;}
.btn-warning:hover{background:#b45309;}
.btn-info{background:var(--info);color:white;}
.btn-info:hover{background:#1e40af;}
.btn-lg{padding:.85rem 2rem;font-size:1rem;}
.btn-sm{padding:.4rem 1rem;font-size:.8rem;}
.btn-xs{padding:.25rem .65rem;font-size:.75rem;}
.btn-block{width:100%;padding:.8rem;font-size:1rem;}

/* HERO */
.hero{position:relative;z-index:5;padding:6rem 4rem 4rem;max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;min-height:calc(100vh - 90px);}
.hero-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(200,146,42,.15);border:1px solid rgba(200,146,42,.3);border-radius:100px;padding:.35rem 1rem;font-size:.78rem;color:var(--gold-light);letter-spacing:.08em;text-transform:uppercase;margin-bottom:1.5rem;font-weight:500;}
.hero h1{font-family:'Playfair Display',serif;font-size:3.8rem;font-weight:700;color:var(--white);line-height:1.15;margin-bottom:1.5rem;}
.hero h1 span{color:var(--gold);}
.hero p{color:rgba(255,255,255,.65);font-size:1.05rem;line-height:1.75;margin-bottom:2.5rem;max-width:480px;}
.hero-actions{display:flex;gap:1rem;flex-wrap:wrap;}
.hero-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem;margin-top:3rem;}
.stat-card{background:rgba(255,255,255,.06);border:1px solid rgba(200,146,42,.2);border-radius:12px;padding:1.5rem;transition:all .3s;}
.stat-card:hover{background:rgba(200,146,42,.1);border-color:rgba(200,146,42,.4);transform:translateY(-3px);}
.stat-card .number{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:700;color:var(--gold);}
.stat-card .label{font-size:.8rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.08em;margin-top:.25rem;}
.hero-right{position:relative;}
.feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.feature-card{background:rgba(255,255,255,.07);border:1px solid rgba(200,146,42,.15);border-radius:14px;padding:1.5rem;transition:all .3s;cursor:pointer;}
.feature-card:hover{background:rgba(200,146,42,.12);border-color:rgba(200,146,42,.4);transform:translateY(-4px);}
.feature-card:first-child{grid-column:1/-1;display:flex;gap:1rem;align-items:flex-start;}
.feature-icon{width:44px;height:44px;background:rgba(200,146,42,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.feature-card h3{font-size:1rem;font-weight:600;color:var(--white);margin-bottom:.4rem;}
.feature-card p{font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.5;margin:0;}
.section{padding:5rem 4rem;max-width:1200px;margin:0 auto;}
.section-title{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:var(--navy);margin-bottom:.5rem;}
.section-sub{color:var(--muted);font-size:1rem;margin-bottom:3rem;}
#depts-section{background:var(--white);padding:5rem 0;}
.dept-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;}
.dept-card{border:1px solid var(--border);border-radius:16px;padding:2rem;transition:all .3s;cursor:default;position:relative;overflow:hidden;}
.dept-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--gold);transform:scaleX(0);transition:transform .3s;}
.dept-card:hover::before{transform:scaleX(1);}
.dept-card:hover{box-shadow:0 12px 40px rgba(13,27,62,.1);transform:translateY(-4px);border-color:var(--gold);}
.dept-icon{font-size:2rem;margin-bottom:1rem;}
.dept-card h3{font-size:1.05rem;font-weight:600;color:var(--navy);margin-bottom:.5rem;}
.dept-card p{font-size:.85rem;color:var(--muted);line-height:1.6;}
.announcement-list{display:flex;flex-direction:column;gap:1rem;}
.announcement-item{display:flex;gap:1rem;align-items:flex-start;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:1.25rem;transition:all .25s;cursor:default;}
.announcement-item:hover{border-color:var(--gold);box-shadow:0 4px 16px rgba(200,146,42,.1);transform:translateX(4px);}
.ann-badge{background:rgba(200,146,42,.12);color:var(--gold);border-radius:6px;padding:.3rem .6rem;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;}
.ann-content h4{font-size:.95rem;font-weight:600;color:var(--navy);margin-bottom:.2rem;}
.ann-content p{font-size:.82rem;color:var(--muted);}
footer{background:var(--navy);color:rgba(255,255,255,.6);padding:3rem 4rem;font-size:.85rem;}
.footer-grid{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr;gap:3rem;padding-bottom:2rem;border-bottom:1px solid rgba(255,255,255,.1);}
.footer-bottom{max-width:1200px;margin:1.5rem auto 0;display:flex;justify-content:space-between;font-size:.78rem;}
footer h4{color:var(--white);margin-bottom:1rem;font-size:.95rem;}
footer a{color:rgba(255,255,255,.55);text-decoration:none;display:block;margin-bottom:.5rem;cursor:pointer;}
footer a:hover{color:var(--gold-light);}

/* MODAL */
.modal-overlay{position:fixed;inset:0;z-index:1000;background:rgba(13,27,62,.7);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--white);border-radius:20px;padding:2.5rem;width:100%;max-width:480px;position:relative;box-shadow:0 32px 80px rgba(0,0,0,.3);animation:slideUp .3s ease;}
.modal-lg{max-width:640px;}
@keyframes slideUp{from{transform:translateY(20px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.modal-close{position:absolute;top:1rem;right:1.25rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--muted);transition:color .2s;}
.modal-close:hover{color:var(--text);}
.modal h2{font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:var(--navy);margin-bottom:.4rem;}
.modal-sub{color:var(--muted);font-size:.875rem;margin-bottom:2rem;}
.tab-switcher{display:flex;background:var(--cream);border-radius:10px;padding:4px;margin-bottom:1.75rem;}
.tab-btn{flex:1;padding:.55rem;border:none;border-radius:7px;background:none;cursor:pointer;font-size:.875rem;font-weight:500;color:var(--muted);transition:all .2s;font-family:'DM Sans',sans-serif;}
.tab-btn.active{background:var(--white);color:var(--navy);box-shadow:0 1px 4px rgba(0,0,0,.08);}
.form-group{margin-bottom:1.25rem;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.form-label{display:block;font-size:.82rem;font-weight:500;color:var(--navy);margin-bottom:.4rem;}
.form-control{width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;font-family:'DM Sans',sans-serif;background:var(--input-bg);color:var(--text);transition:border-color .2s,box-shadow .2s;outline:none;}
.form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(200,146,42,.1);}
select.form-control{cursor:pointer;}
.form-hint{font-size:.78rem;color:var(--muted);margin-top:.3rem;}
.divider{text-align:center;font-size:.8rem;color:var(--muted);margin:1.25rem 0;position:relative;}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:calc(50% - 1.5rem);height:1px;background:var(--border);}
.divider::before{left:0;}
.divider::after{right:0;}

/* DASHBOARD LAYOUT */
#student-dashboard,#teacher-dashboard,#admin-dashboard{display:none;min-height:100vh;}
#student-dashboard.active,#teacher-dashboard.active,#admin-dashboard.active{display:flex;}
.dash-layout{display:flex;width:100%;min-height:100vh;}
.sidebar{width:260px;flex-shrink:0;background:var(--sidebar-bg);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar-header{padding:1.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08);}
.sidebar-logo{display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;}
.sidebar-logo-icon{width:38px;height:38px;background:var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:var(--navy);}
.sidebar-logo span{font-size:.85rem;font-weight:600;color:var(--white);line-height:1.3;}
.sidebar-user{display:flex;align-items:center;gap:.75rem;padding:1rem 1.5rem;background:rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.06);}
.user-avatar{width:38px;height:38px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:.95rem;font-weight:700;color:var(--navy);flex-shrink:0;}
.user-avatar.admin-av{background:var(--admin-accent);color:white;}
.user-info{flex:1;min-width:0;}
.user-name{font-size:.875rem;font-weight:600;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-role{font-size:.72rem;color:rgba(200,146,42,.9);text-transform:uppercase;letter-spacing:.08em;}
.user-role.admin-role{color:#a78bfa;}
.sidebar-nav{flex:1;padding:1rem 0;}
.nav-section-label{font-size:.65rem;font-weight:600;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.12em;padding:.75rem 1.5rem .35rem;}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.7rem 1.5rem;color:rgba(255,255,255,.6);cursor:pointer;transition:all .2s;font-size:.875rem;font-weight:400;border-left:3px solid transparent;margin:2px 0;}
.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06);}
.nav-item.active{color:var(--gold-light);background:rgba(200,146,42,.12);border-left-color:var(--gold);font-weight:500;}
.nav-item.active.admin-nav{color:#c4b5fd;background:rgba(124,58,237,.15);border-left-color:#7c3aed;}
.nav-item .nav-icon{font-size:1rem;width:20px;text-align:center;}
.sidebar-footer{padding:1rem 1.5rem;border-top:1px solid rgba(255,255,255,.08);}
.logout-btn{display:flex;align-items:center;gap:.75rem;color:rgba(255,255,255,.5);cursor:pointer;font-size:.85rem;padding:.5rem 0;transition:color .2s;background:none;border:none;font-family:inherit;width:100%;}
.logout-btn:hover{color:#f87171;}
.dash-main{flex:1;background:#f4f2ee;overflow-y:auto;min-width:0;}
.dash-topbar{background:var(--white);border-bottom:1px solid var(--border);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;}
.dash-topbar h1{font-size:1.2rem;font-weight:600;color:var(--navy);}
.dash-topbar p{font-size:.8rem;color:var(--muted);margin-top:.1rem;}
.topbar-actions{display:flex;gap:.75rem;align-items:center;}
.notification-btn{width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;position:relative;transition:all .2s;}
.notification-btn:hover{background:var(--cream);border-color:var(--gold);}
.notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--danger);border-radius:50%;border:2px solid var(--white);}
.dash-content{padding:2rem;}
.panel{display:none;}
.panel.active{display:block;}

/* METRIC CARDS */
.metrics-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
.metric-card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:all .25s;position:relative;overflow:hidden;}
.metric-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:var(--card-accent,var(--gold));transform:scaleX(0);transform-origin:left;transition:transform .3s;}
.metric-card:hover::after{transform:scaleX(1);}
.metric-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07);transform:translateY(-2px);}
.metric-icon{font-size:1.5rem;margin-bottom:.75rem;display:block;}
.metric-label{font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;}
.metric-value{font-size:2rem;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;}
.metric-change{font-size:.78rem;color:var(--success);margin-top:.25rem;}

/* CARDS & TABLES */
.card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
.card-title{font-size:1rem;font-weight:600;color:var(--navy);}
.card-sub{font-size:.8rem;color:var(--muted);margin-top:.1rem;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{text-align:left;padding:.75rem 1rem;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:var(--cream);border-bottom:1px solid var(--border);}
.data-table td{padding:.85rem 1rem;font-size:.875rem;border-bottom:1px solid var(--border);color:var(--text);}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:var(--cream);}
.badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .7rem;border-radius:100px;font-size:.75rem;font-weight:500;}
.badge-pending{background:#fef3c7;color:#92400e;}
.badge-review{background:#dbeafe;color:#1e40af;}
.badge-approved{background:#d1fae5;color:#065f46;}
.badge-rejected{background:#fee2e2;color:#991b1b;}
.badge-enrolled{background:#ede9fe;color:#5b21b6;}
.badge-paid{background:#d1fae5;color:#065f46;}
.badge-overdue{background:#fee2e2;color:#991b1b;}
.badge-partial{background:#fef3c7;color:#92400e;}
.badge-pass{background:#d1fae5;color:#065f46;}
.badge-fail{background:#fee2e2;color:#991b1b;}

/* PROGRESS */
.progress-bar-wrap{background:var(--cream);border-radius:100px;height:8px;overflow:hidden;}
.progress-bar{height:100%;border-radius:100px;background:var(--gold);transition:width .8s ease;}

/* STEPS TRACKER */
.steps-track{display:flex;align-items:flex-start;gap:0;margin:1.5rem 0;position:relative;}
.step-item{flex:1;text-align:center;position:relative;}
.step-item::before{content:'';position:absolute;top:18px;left:50%;right:-50%;height:2px;background:var(--border);z-index:0;}
.step-item:last-child::before{display:none;}
.step-circle{width:36px;height:36px;border-radius:50%;border:2px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;margin:0 auto .5rem;position:relative;z-index:1;color:var(--muted);transition:all .3s;}
.step-item.done .step-circle{background:var(--success);border-color:var(--success);color:white;}
.step-item.active .step-circle{background:var(--gold);border-color:var(--gold);color:var(--navy);}
.step-item.done::before{background:var(--success);}
.step-label{font-size:.72rem;color:var(--muted);}
.step-item.done .step-label,.step-item.active .step-label{color:var(--text);font-weight:500;}

/* ADMIN-SPECIFIC */
.admin-header-bar{background:linear-gradient(135deg,#4c1d95,#7c3aed);padding:1.5rem 2rem;border-radius:14px;margin-bottom:1.5rem;color:white;display:flex;align-items:center;justify-content:space-between;}
.admin-header-bar h2{font-family:'Playfair Display',serif;font-size:1.4rem;}
.admin-header-bar p{font-size:.85rem;opacity:.8;margin-top:.2rem;}
.search-bar{display:flex;gap:.75rem;margin-bottom:1.25rem;}
.search-bar input{flex:1;}
.table-wrap{overflow-x:auto;}
.action-group{display:flex;gap:.4rem;flex-wrap:nowrap;}
.filter-bar{display:flex;gap:.75rem;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;}
.filter-bar select,.filter-bar input{padding:.5rem .85rem;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;font-family:'DM Sans',sans-serif;background:var(--white);outline:none;}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--gold);}

/* TOAST */
#toastContainer{position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.75rem;}
.toast{display:flex;align-items:center;gap:.75rem;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:1rem 1.25rem;box-shadow:0 8px 32px rgba(0,0,0,.12);font-size:.875rem;font-weight:500;animation:toastIn .3s ease;max-width:360px;}
.toast.success{border-left:4px solid var(--success);}
.toast.danger{border-left:4px solid var(--danger);}
.toast.info{border-left:4px solid var(--info);}
@keyframes toastIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}

/* Admin login page */
#admin-login-page{min-height:100vh;background:linear-gradient(135deg,#0d1b3e 0%,#1e1b4b 50%,#312e81 100%);display:none;align-items:center;justify-content:center;}
#admin-login-page.active{display:flex;}
.admin-login-card{background:white;border-radius:24px;padding:3rem;width:100%;max-width:420px;box-shadow:0 40px 100px rgba(0,0,0,.4);}
.admin-login-card .logo{width:60px;height:60px;background:var(--admin-accent);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 1.5rem;box-shadow:0 8px 24px rgba(124,58,237,.4);}
.admin-login-card h2{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--navy);text-align:center;margin-bottom:.4rem;}
.admin-login-card p{color:var(--muted);font-size:.875rem;text-align:center;margin-bottom:2rem;}

/* Confirm dialog */
.confirm-dialog{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;}
.confirm-dialog.open{display:flex;}
.confirm-box{background:white;border-radius:16px;padding:2rem;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.confirm-box h3{font-size:1.1rem;font-weight:600;margin-bottom:.5rem;}
.confirm-box p{color:var(--muted);font-size:.875rem;margin-bottom:1.5rem;}
.confirm-actions{display:flex;gap:1rem;justify-content:center;}

/* Charts */
.chart-bar-wrap{display:flex;flex-direction:column;gap:.75rem;}
.chart-bar-item{display:flex;align-items:center;gap:.75rem;}
.chart-bar-label{width:120px;font-size:.8rem;color:var(--muted);text-align:right;flex-shrink:0;}
.chart-bar-track{flex:1;background:var(--cream);border-radius:100px;height:10px;overflow:hidden;}
.chart-bar-fill{height:100%;border-radius:100px;background:var(--gold);transition:width 1s ease;}
.chart-bar-val{font-size:.8rem;font-weight:600;color:var(--navy);width:40px;}

/* Responsive tweaks */
@media(max-width:768px){
  nav{padding:1rem 1.5rem;}
  .hero{grid-template-columns:1fr;padding:3rem 1.5rem;gap:2rem;}
  .hero h1{font-size:2.2rem;}
  .metrics-grid{grid-template-columns:1fr 1fr;}
  .dept-grid{grid-template-columns:1fr;}
  .sidebar{width:220px;}
}
</style>
</head>
<body>

<!-- ======================== TOAST CONTAINER ======================== -->
<div id="toastContainer"></div>

<!-- ======================== CONFIRM DIALOG ======================== -->
<div class="confirm-dialog" id="confirmDialog">
  <div class="confirm-box">
    <div style="font-size:2.5rem;margin-bottom:1rem;" id="confirmIcon">⚠️</div>
    <h3 id="confirmTitle">Are you sure?</h3>
    <p id="confirmMsg">This action cannot be undone.</p>
    <div class="confirm-actions">
      <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmOkBtn" onclick="">Confirm</button>
    </div>
  </div>
</div>

<!-- ====================== ADMIN LOGIN PAGE ====================== -->
<div class="page" id="admin-login-page">
  <div class="admin-login-card">
    <div class="logo">🛡️</div>
    <h2>Admin Portal</h2>
    <p>Nabajyoti College — Secure Admin Access</p>
    <div class="form-group">
      <label class="form-label">Username</label>
      <input id="adminUser" class="form-control" type="text" placeholder="admin" value="admin">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input id="adminPass" class="form-control" type="password" placeholder="••••••••" value="admin@123">
    </div>
    <button class="btn btn-admin btn-block" onclick="adminLogin()">🔐 Sign In as Admin</button>
    <div style="text-align:center;margin-top:1.25rem;">
      <span onclick="showPage('landing')" style="color:var(--muted);font-size:.82rem;cursor:pointer;">← Back to Website</span>
    </div>
  </div>
</div>

<!-- ====================== LANDING PAGE ====================== -->
<div class="page active" id="landing">
  <div class="hero-bg"></div>
  <div class="hero-pattern"></div>
  <nav>
    <div class="nav-logo">
      <div class="nav-logo-icon">NC</div>
      <div class="nav-logo-text">
        <h2>Nabajyoti College</h2>
        <span>Kalgachia, Assam · Est. 1972</span>
      </div>
    </div>
    <div class="nav-links">
      <a onclick="scrollToId('about-section')">About</a>
      <a onclick="scrollToId('depts-section')">Departments</a>
      <a onclick="scrollToId('notices-section')">Notices</a>
      <a onclick="scrollToId('contact-section')">Contact</a>
    </div>
    <div class="nav-cta">
      <button class="btn btn-outline" onclick="openModal('loginModal')">Login</button>
      <button class="btn btn-primary" onclick="openModal('loginModal')">Apply Now</button>
      <button class="btn btn-admin btn-sm" onclick="showPage('admin-login-page')" title="Admin Access">🛡️ Admin</button>
    </div>
  </nav>

  <div class="hero">
    <div class="hero-left">
      <div class="hero-badge">🏛️ Gauhati University Affiliated</div>
      <h1>Shaping <span>Futures</span> Since 1971</h1>
      <p>Nabajyoti College Kalgachia — a centre of excellence in Arts, Science and Commerce, nurturing the brightest minds of Assam for over five decades.</p>
      <div class="hero-actions">
        <button class="btn btn-primary btn-lg" onclick="openModal('loginModal')">Apply for Admission</button>
        <button class="btn btn-outline btn-lg" onclick="scrollToId('depts-section')">Explore Courses</button>
      </div>
      <div class="hero-stats">
        <div class="stat-card"><div class="number">3200+</div><div class="label">Students Enrolled</div></div>
        <div class="stat-card"><div class="number">120+</div><div class="label">Faculty Members</div></div>
        <div class="stat-card"><div class="number">15+</div><div class="label">Departments</div></div>
        <div class="stat-card"><div class="number">52+</div><div class="label">Years of Excellence</div></div>
      </div>
    </div>
    <div class="hero-right">
      <div class="feature-grid">
        <div class="feature-card" onclick="openModal('loginModal')">
          <div class="feature-icon">🎓</div>
          <div><h3>Online Admission 2025–26</h3><p>Apply now for B.A., B.Sc. programmes. Seats filling fast!</p></div>
        </div>
        <div class="feature-card" onclick="scrollToId('notices-section')">
          <div class="feature-icon">📢</div>
          <h3>Latest Notices</h3>
          <p>Exam schedules, results and circulars</p>
        </div>
        <div class="feature-card" onclick="openModal('loginModal')">
          <div class="feature-icon">📋</div>
          <h3>Track Application</h3>
          <p>Check your admission status</p>
        </div>
        <div class="feature-card" onclick="scrollToId('depts-section')">
          <div class="feature-icon">🔬</div>
          <h3>Programmes</h3>
          <p>BA and BSc,</p>
        </div>
      </div>
    </div>
  </div>

  <!-- DEPARTMENTS SECTION -->
  <div id="depts-section">
    <div class="section">
      <div class="section-title">Our Departments</div>
      <div class="section-sub">Explore our diverse academic programmes</div>
      <div class="dept-grid" id="deptGrid">
        <div class="dept-card"><div class="dept-icon">🎭</div><h3>Arts</h3><p>Assamese · History · Political Science · Education · Philosophy</p></div>
        <div class="dept-card"><div class="dept-icon">🔬</div><h3>Science</h3><p>Physics · Chemistry · Mathematics · Botany · Zoology</p></div>
        <div class="dept-card"><div class="dept-icon">💻</div><h3>Computer Science</h3><p>Programming · Database · Web Dev · Networking</p></div>
        <div class="dept-card"><div class="dept-icon">⚖️</div><h3>Political Science</h3><p>Indian Politics · International Relations · Public Administration</p></div>
        <div class="dept-card"><div class="dept-icon">📖</div><h3>Education</h3><p>Pedagogy · Curriculum Design · Child Psychology</p></div>
      </div>
    </div>
  </div>

  <!-- NOTICES SECTION -->
  <div id="notices-section">
    <div class="section">
      <div class="section-title">Notices & Announcements</div>
      <div class="section-sub">Stay updated with the latest from the college</div>
      <div class="announcement-list" id="publicNotices">
        <div class="announcement-item">
          <span class="ann-badge">Admission</span>
          <div class="ann-content"><h4>Loading notices...</h4><p>Please wait</p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTACT -->
  <div id="contact-section" style="background:white;">
    <div class="section">
      <div class="section-title">Contact Us</div>
      <div class="section-sub">We're here to help</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:2rem;">
        <div style="text-align:center;padding:2rem;border:1px solid var(--border);border-radius:16px;">
          <div style="font-size:2rem;margin-bottom:1rem;">📍</div>
          <h4 style="color:var(--navy);margin-bottom:.5rem;">Address</h4>
          <p style="color:var(--muted);font-size:.9rem;">Nabajyoti College, Kalgachia,<br>Barpeta, Assam — 781319</p>
        </div>
        <div style="text-align:center;padding:2rem;border:1px solid var(--border);border-radius:16px;">
          <div style="font-size:2rem;margin-bottom:1rem;">📞</div>
          <h4 style="color:var(--navy);margin-bottom:.5rem;">Phone</h4>
          <p style="color:var(--muted);font-size:.9rem;">+91 78960 00000<br>Office: 9 AM – 4 PM (Mon–Sat)</p>
        </div>
        <div style="text-align:center;padding:2rem;border:1px solid var(--border);border-radius:16px;">
          <div style="font-size:2rem;margin-bottom:1rem;">✉️</div>
          <h4 style="color:var(--navy);margin-bottom:.5rem;">Email</h4>
          <p style="color:var(--muted);font-size:.9rem;">principal@nabajyoticollege.ac.in<br>admission@nabajyoticollege.ac.in</p>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-grid">
      <div>
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
          <div style="width:36px;height:36px;background:var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;">NC</div>
          <div><h4 style="margin:0;">Nabajyoti College</h4><span style="font-size:.72rem;color:var(--gold-light);">KALGACHIA, ASSAM</span></div>
        </div>
        <p>A leading institution of higher education in Assam, affiliated to Gauhati University. Committed to academic excellence and holistic development.</p>
      </div>
      <div>
        <h4>Quick Links</h4>
        <a onclick="scrollToId('about-section')">About Us</a>
        <a onclick="scrollToId('depts-section')">Departments</a>
        <a onclick="openModal('loginModal')">Admission</a>
        <a onclick="scrollToId('notices-section')">Notices</a>
        <a onclick="scrollToId('contact-section')">Contact</a>
      </div>
      <div>
        <h4>Student Portal</h4>
        <a onclick="openModal('loginModal')">Login / Register</a>
        <a onclick="openModal('loginModal')">Apply Online</a>
        <a onclick="openModal('loginModal')">Track Application</a>
        <a onclick="openModal('loginModal')">Fee Payment</a>
        <a onclick="openModal('loginModal')">Results</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Nabajyoti College Kalgachia. All rights reserved.</span>
      <span style="color:var(--gold-light);">NAAC Accredited Institution</span>
    </div>
  </footer>
</div>

<!-- ====================== LOGIN MODAL ====================== -->
<div class="modal-overlay" id="loginModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('loginModal')">×</button>
    <h2>Welcome</h2>
    <p class="modal-sub">Sign in or create an account to access your portal</p>
    <div class="tab-switcher">
      <button class="tab-btn active" onclick="switchTab('login',this)">Login</button>
      <button class="tab-btn" onclick="switchTab('register',this)">Register</button>
    </div>
    <!-- LOGIN -->
    <div id="loginForm">
      <div class="form-group">
        <label class="form-label">Role</label>
        <select id="loginRole" class="form-control">
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input id="loginEmail" class="form-control" type="email" placeholder="your@email.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input id="loginPassword" class="form-control" type="password" placeholder="••••••••">
      </div>
      <button class="btn btn-primary btn-block" onclick="doLogin()">Sign In →</button>
      <div class="form-hint" style="text-align:center;margin-top:1rem;">Demo: any email + any password works</div>
    </div>
    <!-- REGISTER -->
    <div id="registerForm" style="display:none;">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input id="regName" class="form-control" type="text" placeholder="e.g. Anurag Sharma">
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input id="regEmail" class="form-control" type="email" placeholder="your@email.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input id="regPassword" class="form-control" type="password" placeholder="Minimum 6 characters">
      </div>
      <button class="btn btn-primary btn-block" onclick="doRegister()">Create Account →</button>
    </div>
  </div>
</div>

<!-- ====================== STUDENT DASHBOARD ====================== -->
<div class="page" id="student-dashboard">
  <div class="dash-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <div class="sidebar-logo-icon">NC</div>
          <span>Nabajyoti College</span>
        </div>   
      </div>
      <div class="sidebar-user">
        <div class="user-avatar" id="studentAvatar">ST</div>
        <div class="user-info">
          <div class="user-name" id="studentName">Student</div>
          <div class="user-role">Student Portal</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section-label">My Portal</div>
        <div class="nav-item active" onclick="switchPanel('s-overview',this)"><span class="nav-icon">🏠</span>Overview</div>
        <div class="nav-item" onclick="switchPanel('s-admission',this)"><span class="nav-icon">📝</span>Apply for Admission</div>
        <div class="nav-item" onclick="switchPanel('s-status',this)"><span class="nav-icon">📊</span>Application Status</div>
        <div class="nav-item" onclick="switchPanel('s-results',this)"><span class="nav-icon">📈</span>My Results</div>
        <div class="nav-item" onclick="switchPanel('s-fees',this)"><span class="nav-icon">💳</span>Fee Payment</div>
        <div class="nav-item" onclick="switchPanel('s-notices',this)"><span class="nav-icon">📢</span>Notices</div>
        <div class="nav-item" onclick="switchPanel('s-profile',this)"><span class="nav-icon">👤</span>My Profile</div>
      </nav>
      <div class="sidebar-footer">
        <button class="logout-btn" onclick="doLogout('student')">🚪 Logout</button>
      </div>
    </aside>
    <main class="dash-main">
      <div class="dash-topbar">
        <div>
          <h1 id="panelTitle">Overview</h1>
          <p id="panelSub">Welcome back to your student portal</p>
        </div>
        <div class="topbar-actions">
          <button class="notification-btn">🔔<span class="notif-dot"></span></button>
          <button class="btn btn-primary btn-sm" onclick="switchPanel('s-admission',null)">+ Apply Now</button>
        </div>
      </div>
      <div class="dash-content">
        <!-- OVERVIEW -->
        <div class="panel active" id="s-overview">
          <div class="metrics-grid">
            <div class="metric-card" style="--card-accent:var(--gold)"><span class="metric-icon">📝</span><div class="metric-label">Application</div><div class="metric-value">1</div><div class="metric-change">Under Review</div></div>
            <div class="metric-card" style="--card-accent:var(--info)"><span class="metric-icon">📚</span><div class="metric-label">Programme</div><div class="metric-value">BSc</div><div class="metric-change">Semester 1</div></div>
            <div class="metric-card" style="--card-accent:var(--success)"><span class="metric-icon">💳</span><div class="metric-label">Fees Due</div><div class="metric-value">₹0</div><div class="metric-change">All Clear</div></div>
            <div class="metric-card" style="--card-accent:var(--danger)"><span class="metric-icon">📊</span><div class="metric-label">Results</div><div class="metric-value">—</div><div class="metric-change">Awaited</div></div>
          </div>
          <div class="card">
            <div class="card-header"><div><div class="card-title">Application Progress</div><div class="card-sub">Track your admission journey</div></div></div>
            <div class="steps-track">
              <div class="step-item done"><div class="step-circle">✓</div><div class="step-label">Registered</div></div>
              <div class="step-item done"><div class="step-circle">✓</div><div class="step-label">Form Filled</div></div>
              <div class="step-item active"><div class="step-circle">3</div><div class="step-label">Under Review</div></div>
              <div class="step-item"><div class="step-circle">4</div><div class="step-label">Approved</div></div>
              <div class="step-item"><div class="step-circle">5</div><div class="step-label">Enrolled</div></div>
            </div>
          </div>
        </div>
        <!-- ADMISSION FORM -->
        <div class="panel" id="s-admission">
          <div class="card">
            <div class="card-header"><div class="card-title">Admission Application 2025–26</div></div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Full Name *</label><input id="af-name" class="form-control" placeholder="As in marksheet"></div>
              <div class="form-group"><label class="form-label">Date of Birth *</label><input id="af-dob" class="form-control" type="date"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Gender</label><select id="af-gender" class="form-control"><option>Male</option><option>Female</option><option>Other</option></select></div>
              <div class="form-group"><label class="form-label">Category</label><select id="af-category" class="form-control"><option>General</option><option>OBC</option><option>SC</option><option>ST</option></select></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Email *</label><input id="af-email" class="form-control" type="email" placeholder="your@email.com"></div>
              <div class="form-group"><label class="form-label">Phone *</label><input id="af-phone" class="form-control" placeholder="+91 XXXXXXXXXX"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Programme *</label>
                <select id="af-programme" class="form-control">
                  <option value="">— Select Programme —</option>
                  <option>B.A. Assamese</option><option>B.A. History</option><option>B.A. Political Science</option><option>B.A. Education</option><option>B.A. English</option>
                  <option>B.Sc. Physics</option><option>B.Sc. Chemistry</option><option>B.Sc. Mathematics</option><option>B.Sc. Botany</option><option>B.Sc. Zoology</option>
                  <option>B.Com. Commerce</option><option>BCA</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Previous School *</label><input id="af-school" class="form-control" placeholder="School/College name"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Marks % (Class 12) *</label><input id="af-marks" class="form-control" type="number" min="0" max="100" step="0.01" placeholder="e.g. 85.5"></div>
              <div class="form-group"><label class="form-label">Address</label><input id="af-address" class="form-control" placeholder="Village, District, State"></div>
            </div>
            <button class="btn btn-primary btn-lg" onclick="submitAdmissionForm()">Submit Application →</button>
          </div>
        </div>
        <!-- STATUS -->
        <div class="panel" id="s-status">
          <div class="card">
            <div class="card-header"><div class="card-title">Application Status</div></div>
            <div style="text-align:center;padding:2rem;">
              <div style="font-size:3rem;margin-bottom:1rem;">📋</div>
              <h3 style="color:var(--navy);margin-bottom:.5rem;">Application Under Review</h3>
              <p style="color:var(--muted);">Your application is being processed. You will be notified via email.</p>
              <div style="margin-top:1.5rem;"><span class="badge badge-review" style="font-size:.9rem;padding:.5rem 1.25rem;">Under Review</span></div>
            </div>
          </div>
        </div>
        <!-- RESULTS -->
        <div class="panel" id="s-results">
          <div class="card">
            <div class="card-header"><div class="card-title">Examination Results</div></div>
            <div style="text-align:center;padding:3rem;color:var(--muted);">Results not yet published for your semester.</div>
          </div>
        </div>
        <!-- FEES -->
        <div class="panel" id="s-fees">
          <div class="card">
            <div class="card-header"><div class="card-title">Fee Payment</div></div>
            <div style="text-align:center;padding:3rem;color:var(--muted);">No fee dues at the moment.</div>
          </div>
        </div>
        <!-- NOTICES -->
        <div class="panel" id="s-notices">
          <div class="card">
            <div class="card-header"><div class="card-title">Notices</div></div>
            <div class="announcement-list" id="studentNoticeList"><p style="color:var(--muted);">Loading...</p></div>
          </div>
        </div>
        <!-- PROFILE -->
        <div class="panel" id="s-profile">
          <div class="card">
            <div class="card-header"><div class="card-title">My Profile</div></div>
            <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:2rem;">
              <div style="width:80px;height:80px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:var(--navy);" id="profileAvatar">ST</div>
              <div><h3 id="profileName" style="color:var(--navy);">Student</h3><p style="color:var(--muted);">Student Portal</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  </div>
</div>

<!-- ====================== TEACHER DASHBOARD ====================== -->
<div class="page" id="teacher-dashboard">
  <div class="dash-layout">
    <aside class="sidebar">
      <div class="sidebar-header"><div class="sidebar-logo"><div class="sidebar-logo-icon">NC</div><span>Staff Portal</span></div></div>
      <div class="sidebar-user">
        <div class="user-avatar">👨‍🏫</div>
        <div class="user-info"><div class="user-name" id="teacherName">Teacher</div><div class="user-role">Faculty</div></div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-item active" onclick="switchPanelT('t-overview',this)"><span class="nav-icon">🏠</span>Dashboard</div>
        <div class="nav-item" onclick="switchPanelT('t-applications',this)"><span class="nav-icon">📋</span>Applications</div>
        <div class="nav-item" onclick="switchPanelT('t-results',this)"><span class="nav-icon">📈</span>Results Entry</div>
        <div class="nav-item" onclick="switchPanelT('t-notices',this)"><span class="nav-icon">📢</span>Notice Board</div>
      </nav>
      <div class="sidebar-footer"><button class="logout-btn" onclick="doLogout('teacher')">🚪 Logout</button></div>
    </aside>
    <main class="dash-main">
      <div class="dash-topbar">
        <div><h1 id="tPanelTitle">Dashboard</h1><p id="tPanelSub">Faculty portal</p></div>
        <div class="topbar-actions"><button class="notification-btn">🔔<span class="notif-dot"></span></button></div>
      </div>
      <div class="dash-content">
        <div class="panel active" id="t-overview">
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-icon">📝</span><div class="metric-label">Applications</div><div class="metric-value" id="t-apps-count">—</div></div>
            <div class="metric-card"><span class="metric-icon">✅</span><div class="metric-label">Approved</div><div class="metric-value" id="t-approved-count">—</div></div>
            <div class="metric-card"><span class="metric-icon">⏳</span><div class="metric-label">Pending</div><div class="metric-value" id="t-pending-count">—</div></div>
            <div class="metric-card"><span class="metric-icon">🎓</span><div class="metric-label">Enrolled</div><div class="metric-value" id="t-enrolled-count">—</div></div>
          </div>
        </div>
        <div class="panel" id="t-applications">
          <div class="card">
            <div class="card-header"><div class="card-title">Student Applications</div></div>
            <div class="filter-bar">
              <select id="tAppFilter" onchange="loadTeacherApplications()" class="form-control" style="width:auto;">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="review">Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>App No</th><th>Name</th><th>Programme</th><th>Marks</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                <tbody id="tAppBody"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="panel" id="t-results">
          <div class="card">
            <div class="card-header"><div class="card-title">Results Entry</div></div>
            <p style="color:var(--muted);">Please use the Admin panel for full result management.</p>
          </div>
        </div>
        <div class="panel" id="t-notices">
          <div class="card">
            <div class="card-header"><div class="card-title">Post a Notice</div></div>
            <div class="form-group"><label class="form-label">Title *</label><input id="tNoticeTitle" class="form-control" placeholder="Notice title"></div>
            <div class="form-group"><label class="form-label">Content</label><textarea id="tNoticeContent" class="form-control" rows="4" placeholder="Notice content..."></textarea></div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Category</label><select id="tNoticeCat" class="form-control"><option>General</option><option>Exam</option><option>Admission</option><option>Event</option></select></div>
              <div class="form-group"><label class="form-label">Target</label><select id="tNoticeTarget" class="form-control"><option value="all">All</option><option value="students">Students</option><option value="teachers">Teachers</option></select></div>
            </div>
            <button class="btn btn-primary" onclick="postTeacherNotice()">📢 Post Notice</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ====================== ADMIN DASHBOARD ====================== -->
<div class="page" id="admin-dashboard">
  <div class="dash-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <div class="sidebar-logo-icon" style="background:var(--admin-accent);color:white;">🛡️</div>
          <span>Admin Panel</span>
        </div>
      </div>
      <div class="sidebar-user">
        <div class="user-avatar admin-av">AD</div>
        <div class="user-info">
          <div class="user-name" id="adminDisplayName"><?= $adminName ?></div>
          <div class="user-role admin-role">Super Admin</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item admin-nav active" onclick="switchPanelA('a-overview',this)"><span class="nav-icon">📊</span>Dashboard</div>
        <div class="nav-section-label">Academic</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-students',this)"><span class="nav-icon">👨‍🎓</span>Students</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-teachers',this)"><span class="nav-icon">👨‍🏫</span>Teachers</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-applications',this)"><span class="nav-icon">📋</span>Applications</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-departments',this)"><span class="nav-icon">🏛️</span>Departments</div>
        <div class="nav-section-label">Finance & Results</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-fees',this)"><span class="nav-icon">💰</span>Fee Management</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-results',this)"><span class="nav-icon">📈</span>Results</div>
        <div class="nav-section-label">Communication</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-notices',this)"><span class="nav-icon">📢</span>Notices</div>
        <div class="nav-section-label">System</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-admins',this)"><span class="nav-icon">🛡️</span>Admin Users</div>
        <div class="nav-item admin-nav" onclick="switchPanelA('a-settings',this)"><span class="nav-icon">⚙️</span>Settings</div>
      </nav>
      <div class="sidebar-footer">
        <button class="logout-btn" onclick="doLogout('admin')">🚪 Logout</button>
      </div>
    </aside>
    <main class="dash-main">
      <div class="dash-topbar">
        <div><h1 id="aPanelTitle">Admin Dashboard</h1><p id="aPanelSub">Full system management</p></div>
        <div class="topbar-actions">
          <button class="notification-btn">🔔<span class="notif-dot"></span></button>
          <a href="?export=students" class="btn btn-secondary btn-sm">⬇️ Export</a>
        </div>
      </div>
      <div class="dash-content">

        <!-- ── ADMIN OVERVIEW ── -->
        <div class="panel active" id="a-overview">
          <div class="admin-header-bar">
            <div><h2>Welcome, <?= $adminName ?: 'Admin' ?>!</h2><p>Nabajyoti College Management System — Full Control</p></div>
            <div style="font-size:3rem;">🛡️</div>
          </div>
          <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="metric-card" style="--card-accent:#7c3aed"><span class="metric-icon">👨‍🎓</span><div class="metric-label">Total Students</div><div class="metric-value" id="statStudents">—</div></div>
            <div class="metric-card" style="--card-accent:var(--gold)"><span class="metric-icon">👨‍🏫</span><div class="metric-label">Teachers</div><div class="metric-value" id="statTeachers">—</div></div>
            <div class="metric-card" style="--card-accent:var(--info)"><span class="metric-icon">📋</span><div class="metric-label">Applications</div><div class="metric-value" id="statApplications">—</div></div>
            <div class="metric-card" style="--card-accent:var(--warning)"><span class="metric-icon">⏳</span><div class="metric-label">Pending</div><div class="metric-value" id="statPending">—</div></div>
            <div class="metric-card" style="--card-accent:var(--success)"><span class="metric-icon">✅</span><div class="metric-label">Approved</div><div class="metric-value" id="statApproved">—</div></div>
            <div class="metric-card" style="--card-accent:#0891b2"><span class="metric-icon">🎓</span><div class="metric-label">Enrolled</div><div class="metric-value" id="statEnrolled">—</div></div>
            <div class="metric-card" style="--card-accent:var(--danger)"><span class="metric-icon">💰</span><div class="metric-label">Fee Due (₹)</div><div class="metric-value" id="statFeeDue">—</div></div>
            <div class="metric-card" style="--card-accent:var(--navy)"><span class="metric-icon">📢</span><div class="metric-label">Notices</div><div class="metric-value" id="statNotices">—</div></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div class="card">
              <div class="card-header"><div class="card-title">Quick Actions</div></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <button class="btn btn-primary" onclick="switchPanelA('a-applications',document.querySelector('.nav-item.admin-nav:nth-child(7)'))">📋 Review Applications</button>
                <button class="btn btn-success" onclick="switchPanelA('a-students',null)">➕ Add Student</button>
                <button class="btn btn-info" onclick="switchPanelA('a-notices',null)">📢 Post Notice</button>
                <button class="btn btn-warning" onclick="switchPanelA('a-fees',null)">💰 Manage Fees</button>
              </div>
            </div>
            <div class="card">
              <div class="card-header"><div class="card-title">Application Overview</div></div>
              <div class="chart-bar-wrap" id="appChart"></div>
            </div>
          </div>
        </div>

        <!-- ── STUDENTS ── -->
        <div class="panel" id="a-students">
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Student Management</div><div class="card-sub">Add, edit, delete student records</div></div>
              <button class="btn btn-primary btn-sm" onclick="openStudentModal()">+ Add Student</button>
            </div>
            <div class="search-bar"><input id="studentSearch" class="form-control" placeholder="Search by name, email or roll no..." oninput="loadStudents()"><button class="btn btn-secondary" onclick="loadStudents()">🔍</button></div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Roll No</th><th>Name</th><th>Email</th><th>Programme</th><th>Category</th><th>Semester</th><th>Actions</th></tr></thead>
                <tbody id="studentsBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── TEACHERS ── -->
        <div class="panel" id="a-teachers">
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Teacher / Faculty Management</div></div>
              <button class="btn btn-primary btn-sm" onclick="openTeacherModal()">+ Add Teacher</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Emp ID</th><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Actions</th></tr></thead>
                <tbody id="teachersBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── APPLICATIONS ── -->
        <div class="panel" id="a-applications">
          <div class="card">
            <div class="card-header"><div class="card-title">Admission Applications</div></div>
            <div class="filter-bar">
              <select id="appStatusFilter" onchange="loadApplications()" class="form-control" style="width:auto;">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="review">Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="enrolled">Enrolled</option>
              </select>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>App No</th><th>Name</th><th>Programme</th><th>Marks %</th><th>Category</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="applicationsBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── DEPARTMENTS ── -->
        <div class="panel" id="a-departments">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Department Management</div>
              <button class="btn btn-primary btn-sm" onclick="openDeptModal()">+ Add Department</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Icon</th><th>Name</th><th>Code</th><th>Head</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody id="deptsBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── FEES ── -->
        <div class="panel" id="a-fees">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Fee Management</div>
              <button class="btn btn-primary btn-sm" onclick="openFeeModal()">+ Add Fee Record</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Student</th><th>Programme</th><th>Sem</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="feesBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── RESULTS ── -->
        <div class="panel" id="a-results">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Results Management</div>
              <button class="btn btn-primary btn-sm" onclick="openResultModal()">+ Add Result</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Roll No</th><th>Name</th><th>Programme</th><th>Sem</th><th>Subject</th><th>Internal</th><th>External</th><th>Total</th><th>Grade</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="resultsBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── NOTICES ── -->
        <div class="panel" id="a-notices">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Notice Board Management</div>
              <button class="btn btn-primary btn-sm" onclick="openNoticeModal()">+ Post Notice</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Target</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody id="noticesBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── ADMIN USERS ── -->
        <div class="panel" id="a-admins">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Admin Users</div>
              <button class="btn btn-admin btn-sm" onclick="openAdminUserModal()">+ Add Admin</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Created</th></tr></thead>
                <tbody id="adminUsersBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── SETTINGS ── -->
        <div class="panel" id="a-settings">
          <div class="card">
            <div class="card-header"><div class="card-title">Site Settings</div></div>
            <div id="settingsForm"></div>
            <button class="btn btn-primary btn-lg" onclick="saveSettings()" style="margin-top:1rem;">💾 Save Settings</button>
          </div>
        </div>

      </div><!-- dash-content -->
    </main>
  </div>
</div><!-- admin-dashboard -->

<!-- ============ CRUD MODALS ============ -->

<!-- Student Modal -->
<div class="modal-overlay" id="studentModal">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeModal('studentModal')">×</button>
    <h2 id="studentModalTitle">Add Student</h2>
    <input type="hidden" id="sm-id">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Full Name *</label><input id="sm-name" class="form-control" placeholder="Full name"></div>
      <div class="form-group"><label class="form-label">Email *</label><input id="sm-email" class="form-control" type="email" placeholder="Email"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Password (new only)</label><input id="sm-password" class="form-control" type="password" placeholder="Leave blank to keep current"></div>
      <div class="form-group"><label class="form-label">Phone</label><input id="sm-phone" class="form-control" placeholder="Phone number"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Programme</label>
        <select id="sm-programme" class="form-control">
          <option value="">— Select —</option>
          <option>B.A. Assamese</option><option>B.A. History</option><option>B.A. Political Science</option>
          <option>B.Sc. Physics</option><option>B.Sc. Chemistry</option><option>B.Sc. Mathematics</option>
          <option>B.Com. Commerce</option><option>BCA</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Semester</label><input id="sm-semester" class="form-control" type="number" min="1" max="6" value="1"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Category</label><select id="sm-category" class="form-control"><option>General</option><option>OBC</option><option>SC</option><option>ST</option></select></div>
      <div class="form-group"><label class="form-label">Gender</label><select id="sm-gender" class="form-control"><option>Male</option><option>Female</option><option>Other</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Address</label><input id="sm-address" class="form-control" placeholder="Address"></div>
    <div style="display:flex;gap:1rem;margin-top:1rem;">
      <button class="btn btn-primary btn-lg" onclick="saveStudent()">💾 Save Student</button>
      <button class="btn btn-secondary" onclick="closeModal('studentModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Teacher Modal -->
<div class="modal-overlay" id="teacherModal">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeModal('teacherModal')">×</button>
    <h2 id="teacherModalTitle">Add Teacher</h2>
    <input type="hidden" id="tm-id">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Full Name *</label><input id="tm-name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Email *</label><input id="tm-email" class="form-control" type="email"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Password (new only)</label><input id="tm-password" class="form-control" type="password"></div>
      <div class="form-group"><label class="form-label">Phone</label><input id="tm-phone" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Department</label><select id="tm-department" class="form-control"><option>Arts</option><option>Science</option><option>Commerce</option><option>Computer Science</option><option>Political Science</option><option>Education</option></select></div>
      <div class="form-group"><label class="form-label">Designation</label><input id="tm-designation" class="form-control" placeholder="e.g. Assistant Professor"></div>
    </div>
    <div class="form-group"><label class="form-label">Qualification</label><input id="tm-qualification" class="form-control" placeholder="e.g. M.Sc., Ph.D."></div>
    <div style="display:flex;gap:1rem;margin-top:1rem;">
      <button class="btn btn-primary btn-lg" onclick="saveTeacher()">💾 Save Teacher</button>
      <button class="btn btn-secondary" onclick="closeModal('teacherModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Department Modal -->
<div class="modal-overlay" id="deptModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('deptModal')">×</button>
    <h2 id="deptModalTitle">Add Department</h2>
    <input type="hidden" id="dm-id">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Name *</label><input id="dm-name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Code</label><input id="dm-code" class="form-control" placeholder="e.g. BSC"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Icon (emoji)</label><input id="dm-icon" class="form-control" placeholder="📚" maxlength="4"></div>
      <div class="form-group"><label class="form-label">Head Name</label><input id="dm-head_name" class="form-control" placeholder="HOD name"></div>
    </div>
    <div class="form-group"><label class="form-label">Description</label><textarea id="dm-description" class="form-control" rows="3"></textarea></div>
    <button class="btn btn-primary btn-lg" onclick="saveDept()" style="margin-top:1rem;">💾 Save</button>
  </div>
</div>

<!-- Notice Modal -->
<div class="modal-overlay" id="noticeModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('noticeModal')">×</button>
    <h2 id="noticeModalTitle">Post Notice</h2>
    <input type="hidden" id="nm-id">
    <div class="form-group"><label class="form-label">Title *</label><input id="nm-title" class="form-control"></div>
    <div class="form-group"><label class="form-label">Content</label><textarea id="nm-content" class="form-control" rows="4"></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Category</label><select id="nm-category" class="form-control"><option>General</option><option>Admission</option><option>Exam</option><option>Event</option><option>Holiday</option></select></div>
      <div class="form-group"><label class="form-label">Priority</label><select id="nm-priority" class="form-control"><option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Target Audience</label><select id="nm-target" class="form-control"><option value="all">All</option><option value="students">Students Only</option><option value="teachers">Teachers Only</option></select></div>
    <button class="btn btn-primary btn-lg" onclick="saveNotice()" style="margin-top:1rem;">📢 Post Notice</button>
  </div>
</div>

<!-- Fee Modal -->
<div class="modal-overlay" id="feeModal">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeModal('feeModal')">×</button>
    <h2 id="feeModalTitle">Add Fee Record</h2>
    <input type="hidden" id="fm-id">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Student Name *</label><input id="fm-student_name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Roll No</label><input id="fm-roll_no" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Programme</label><input id="fm-programme" class="form-control" placeholder="e.g. B.Sc. Physics"></div>
      <div class="form-group"><label class="form-label">Semester</label><input id="fm-semester" class="form-control" type="number" min="1" max="6" value="1"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Fee Type</label><select id="fm-fee_type" class="form-control"><option>Tuition Fee</option><option>Exam Fee</option><option>Library Fee</option><option>Sports Fee</option><option>Development Fee</option></select></div>
      <div class="form-group"><label class="form-label">Total Amount (₹)</label><input id="fm-amount" class="form-control" type="number" step="0.01"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Paid Amount (₹)</label><input id="fm-paid_amount" class="form-control" type="number" step="0.01" value="0"></div>
      <div class="form-group"><label class="form-label">Status</label><select id="fm-status" class="form-control"><option value="pending">Pending</option><option value="partial">Partial</option><option value="paid">Paid</option><option value="overdue">Overdue</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Due Date</label><input id="fm-due_date" class="form-control" type="date"></div>
      <div class="form-group"><label class="form-label">Paid Date</label><input id="fm-paid_date" class="form-control" type="date"></div>
    </div>
    <div class="form-group"><label class="form-label">Transaction ID</label><input id="fm-transaction_id" class="form-control" placeholder="Optional"></div>
    <button class="btn btn-primary btn-lg" onclick="saveFee()" style="margin-top:1rem;">💾 Save Fee Record</button>
  </div>
</div>

<!-- Result Modal -->
<div class="modal-overlay" id="resultModal">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeModal('resultModal')">×</button>
    <h2 id="resultModalTitle">Add Result</h2>
    <input type="hidden" id="rm-id">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Student Name *</label><input id="rm-student_name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Roll No</label><input id="rm-roll_no" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Programme</label><input id="rm-programme" class="form-control"></div>
      <div class="form-group"><label class="form-label">Semester</label><input id="rm-semester" class="form-control" type="number" min="1" max="6" value="1"></div>
    </div>
    <div class="form-group"><label class="form-label">Subject *</label><input id="rm-subject" class="form-control" placeholder="e.g. Physics Paper I"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Internal Marks</label><input id="rm-internal_marks" class="form-control" type="number" step="0.01" min="0" max="30" value="0"></div>
      <div class="form-group"><label class="form-label">External Marks</label><input id="rm-external_marks" class="form-control" type="number" step="0.01" min="0" max="70" value="0"></div>
    </div>
    <div class="form-group"><label class="form-label">Publish Result?</label><select id="rm-published" class="form-control"><option value="0">No (Draft)</option><option value="1">Yes (Publish)</option></select></div>
    <button class="btn btn-primary btn-lg" onclick="saveResult()" style="margin-top:1rem;">💾 Save Result</button>
  </div>
</div>

<!-- Application Edit Modal -->
<div class="modal-overlay" id="appEditModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('appEditModal')">×</button>
    <h2>Update Application</h2>
    <input type="hidden" id="aem-id">
    <div class="form-group"><label class="form-label">Status</label>
      <select id="aem-status" class="form-control">
        <option value="pending">Pending</option>
        <option value="review">Under Review</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="enrolled">Enrolled</option>
      </select>
    </div>
    <div class="form-group"><label class="form-label">Remarks</label><textarea id="aem-remarks" class="form-control" rows="3" placeholder="Optional remarks"></textarea></div>
    <button class="btn btn-primary btn-lg" onclick="updateApplication()" style="margin-top:.5rem;">💾 Update</button>
  </div>
</div>

<!-- Admin User Modal -->
<div class="modal-overlay" id="adminUserModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('adminUserModal')">×</button>
    <h2>Add Admin User</h2>
    <div class="form-group"><label class="form-label">Full Name *</label><input id="au-name" class="form-control"></div>
    <div class="form-group"><label class="form-label">Username *</label><input id="au-username" class="form-control"></div>
    <div class="form-group"><label class="form-label">Email</label><input id="au-email" class="form-control" type="email"></div>
    <div class="form-group"><label class="form-label">Password *</label><input id="au-password" class="form-control" type="password"></div>
    <button class="btn btn-admin btn-lg" onclick="saveAdminUser()" style="margin-top:.5rem;">🛡️ Add Admin</button>
  </div>
</div>

<!-- =================== JAVASCRIPT =================== -->
<script>
// ========== API HELPER ==========
async function api(action, data = {}, method = 'POST') {
  const url = `?api=${action}`;
  const opts = { method, headers: {} };
  if (method === 'POST') {
    const fd = new FormData();
    for (const [k,v] of Object.entries(data)) fd.append(k, v ?? '');
    opts.body = fd;
  }
  const res = await fetch(url, opts);
  return res.json();
}
async function apiGet(action, params = {}) {
  const qs = Object.entries(params).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&');
  const res = await fetch(`?api=${action}&${qs}`);
  return res.json();
}

// ========== PAGES ==========
function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  window.scrollTo(0, 0);
}

// ========== MODALS ==========
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

// ========== CONFIRM DIALOG ==========
let confirmCallback = null;
function confirm2(title, msg, cb, icon = '⚠️') {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMsg').textContent = msg;
  document.getElementById('confirmIcon').textContent = icon;
  confirmCallback = cb;
  document.getElementById('confirmDialog').classList.add('open');
}
function closeConfirm() { document.getElementById('confirmDialog').classList.remove('open'); }
document.getElementById('confirmOkBtn').addEventListener('click', () => {
  closeConfirm();
  if (confirmCallback) confirmCallback();
});

// ========== TOAST ==========
function showToast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success: '✅', danger: '❌', info: 'ℹ️', warning: '⚠️' };
  t.innerHTML = `<span>${icons[type]||'ℹ️'}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ========== AUTH ==========
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
  document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
}

async function doLogin() {
  const role = document.getElementById('loginRole').value;
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;
  if (!email || !password) { showToast('Please fill all fields', 'danger'); return; }
  const res = await api(`${role}_login`, { email, password });
  if (res.ok) {
    closeModal('loginModal');
    showPage(`${role}-dashboard`);
    if (role === 'student') {
      document.getElementById('studentName').textContent = res.name;
      document.getElementById('studentAvatar').textContent = res.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
      document.getElementById('profileName').textContent = res.name;
      document.getElementById('profileAvatar').textContent = res.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
      loadStudentNotices();
    }
    if (role === 'teacher') {
      document.getElementById('teacherName').textContent = res.name;
      loadTeacherStats();
    }
    showToast(`Welcome, ${res.name}!`, 'success');
  } else {
    showToast(res.msg || 'Login failed', 'danger');
  }
}

async function doRegister() {
  const name = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const password = document.getElementById('regPassword').value;
  if (!name || !email || !password) { showToast('Please fill all fields', 'danger'); return; }
  const res = await api('student_register', { name, email, password });
  if (res.ok) {
    closeModal('loginModal');
    showPage('student-dashboard');
    document.getElementById('studentName').textContent = res.name;
    document.getElementById('studentAvatar').textContent = res.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
    showToast(`Account created! Roll: ${res.roll}`, 'success');
    setTimeout(() => switchPanel('s-admission', null), 500);
  } else {
    showToast(res.msg || 'Registration failed', 'danger');
  }
}

async function adminLogin() {
  const username = document.getElementById('adminUser').value.trim();
  const password = document.getElementById('adminPass').value;
  const res = await api('admin_login', { username, password });
  if (res.ok) {
    showPage('admin-dashboard');
    document.getElementById('adminDisplayName').textContent = res.name;
    loadAdminStats();
    showToast(`Welcome, ${res.name}!`, 'success');
  } else {
    showToast(res.msg || 'Invalid admin credentials', 'danger');
  }
}

async function doLogout(role) {
  await api('logout', { role });
  showPage('landing');
  showToast('Logged out successfully', 'success');
}

// ========== PANEL SWITCHERS ==========
const sPanelInfo = {
  's-overview': ['Overview', 'Welcome to your portal'],
  's-admission': ['Apply for Admission', 'Fill in details for 2025–26'],
  's-status': ['Application Status', 'Track your admission progress'],
  's-results': ['Examination Results', 'Your academic performance'],
  's-fees': ['Fee Payment', 'View and pay fees'],
  's-notices': ['Notices', 'Important announcements'],
  's-profile': ['My Profile', 'Manage your information'],
};
function switchPanel(id, navItem) {
  document.querySelectorAll('#student-dashboard .panel').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  if (navItem) {
    document.querySelectorAll('#student-dashboard .nav-item').forEach(n => n.classList.remove('active'));
    navItem.classList.add('active');
  }
  const info = sPanelInfo[id] || ['Dashboard', ''];
  document.getElementById('panelTitle').textContent = info[0];
  document.getElementById('panelSub').textContent = info[1];
}

const tPanelInfo = {
  't-overview': ['Dashboard', 'Faculty portal overview'],
  't-applications': ['Student Applications', 'Review and process applications'],
  't-results': ['Results Entry', 'Enter examination results'],
  't-notices': ['Post Notice', 'Send announcements to students'],
};
function switchPanelT(id, navItem) {
  document.querySelectorAll('#teacher-dashboard .panel').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  if (navItem) {
    document.querySelectorAll('#teacher-dashboard .nav-item').forEach(n => n.classList.remove('active'));
    navItem.classList.add('active');
  }
  const info = tPanelInfo[id] || ['Dashboard', ''];
  document.getElementById('tPanelTitle').textContent = info[0];
  document.getElementById('tPanelSub').textContent = info[1];
  if (id === 't-applications') loadTeacherApplications();
}

const aPanelInfo = {
  'a-overview': ['Admin Dashboard', 'System overview and quick stats'],
  'a-students': ['Student Management', 'Add, edit, delete students'],
  'a-teachers': ['Teacher Management', 'Faculty records management'],
  'a-applications': ['Applications', 'Review and process admissions'],
  'a-departments': ['Departments', 'Manage academic departments'],
  'a-fees': ['Fee Management', 'Track and manage fee collection'],
  'a-results': ['Results Management', 'Enter and publish exam results'],
  'a-notices': ['Notice Board', 'Post and manage announcements'],
  'a-admins': ['Admin Users', 'Manage admin accounts'],
  'a-settings': ['Site Settings', 'Configure college website settings'],
};
function switchPanelA(id, navItem) {
  document.querySelectorAll('#admin-dashboard .panel').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('#admin-dashboard .nav-item').forEach(n => n.classList.remove('active'));
  if (navItem) navItem.classList.add('active');
  const info = aPanelInfo[id] || ['Admin', ''];
  document.getElementById('aPanelTitle').textContent = info[0];
  document.getElementById('aPanelSub').textContent = info[1];
  // Load data for panel
  const loaders = {
    'a-overview': loadAdminStats,
    'a-students': loadStudents,
    'a-teachers': loadTeachers,
    'a-applications': loadApplications,
    'a-departments': loadDepts,
    'a-fees': loadFees,
    'a-results': loadResults,
    'a-notices': loadNoticesAdmin,
    'a-admins': loadAdminUsers,
    'a-settings': loadSettings,
  };
  if (loaders[id]) loaders[id]();
}

// ========== ADMIN STATS ==========
async function loadAdminStats() {
  const s = await apiGet('get_stats');
  document.getElementById('statStudents').textContent = s.students;
  document.getElementById('statTeachers').textContent = s.teachers;
  document.getElementById('statApplications').textContent = s.applications;
  document.getElementById('statPending').textContent = s.pending;
  document.getElementById('statApproved').textContent = s.approved;
  document.getElementById('statEnrolled').textContent = s.enrolled;
  document.getElementById('statNotices').textContent = s.notices;
  document.getElementById('statFeeDue').textContent = '₹' + parseFloat(s.fee_pending).toLocaleString('en-IN');
  // Simple chart
  const total = parseInt(s.applications) || 1;
  const bars = [
    { label: 'Pending', val: s.pending, color: '#f59e0b' },
    { label: 'Approved', val: s.approved, color: '#16a34a' },
    { label: 'Enrolled', val: s.enrolled, color: '#7c3aed' },
    { label: 'Rejected', val: s.rejected || 0, color: '#dc2626' },
  ];
  document.getElementById('appChart').innerHTML = bars.map(b => `
    <div class="chart-bar-item">
      <div class="chart-bar-label">${b.label}</div>
      <div class="chart-bar-track"><div class="chart-bar-fill" style="width:${Math.round(b.val/total*100)}%;background:${b.color};"></div></div>
      <div class="chart-bar-val">${b.val}</div>
    </div>`).join('');
}

// ========== TEACHER STATS ==========
async function loadTeacherStats() {
  const s = await apiGet('get_stats');
  document.getElementById('t-apps-count').textContent = s.applications;
  document.getElementById('t-approved-count').textContent = s.approved;
  document.getElementById('t-pending-count').textContent = s.pending;
  document.getElementById('t-enrolled-count').textContent = s.enrolled;
}

// ========== STUDENTS ==========
async function loadStudents() {
  const q = document.getElementById('studentSearch')?.value || '';
  const rows = await apiGet('get_students', { q });
  document.getElementById('studentsBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td style="font-size:.78rem;color:var(--muted);">${r.roll_no||'—'}</td>
      <td><strong>${esc(r.name)}</strong></td>
      <td>${esc(r.email)}</td>
      <td>${esc(r.programme)||'—'}</td>
      <td>${esc(r.category)||'—'}</td>
      <td>Sem ${r.semester||1}</td>
      <td class="action-group">
        <button class="btn btn-sm btn-info" onclick='editStudent(${JSON.stringify(r)})'>✏️ Edit</button>
        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${r.id})">🗑️</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No students found. Add one above.</td></tr>';
}

function openStudentModal(data = null) {
  ['id','name','email','password','phone','programme','semester','category','gender','address'].forEach(f => {
    const el = document.getElementById(`sm-${f}`);
    if (el) el.value = data ? (data[f] || '') : '';
  });
  document.getElementById('sm-semester').value = data?.semester || 1;
  document.getElementById('studentModalTitle').textContent = data ? 'Edit Student' : 'Add Student';
  openModal('studentModal');
}
function editStudent(r) { openStudentModal(r); }

async function saveStudent() {
  const data = {};
  ['id','name','email','password','phone','programme','semester','category','gender','address'].forEach(f => {
    data[f] = document.getElementById(`sm-${f}`)?.value || '';
  });
  const res = await api('save_student', data);
  if (res.ok) { closeModal('studentModal'); loadStudents(); showToast(res.msg, 'success'); }
  else showToast(res.msg || 'Error', 'danger');
}

async function deleteStudent(id) {
  confirm2('Delete Student', 'This will permanently delete the student record.', async () => {
    const res = await api('delete_student', { id });
    if (res.ok) { loadStudents(); showToast('Student deleted', 'success'); }
  }, '🗑️');
}

// ========== TEACHERS ==========
async function loadTeachers() {
  const rows = await apiGet('get_teachers');
  document.getElementById('teachersBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td style="font-size:.78rem;color:var(--muted);">${r.emp_id||'—'}</td>
      <td><strong>${esc(r.name)}</strong></td>
      <td>${esc(r.email)}</td>
      <td>${esc(r.department)||'—'}</td>
      <td>${esc(r.designation)||'—'}</td>
      <td class="action-group">
        <button class="btn btn-sm btn-info" onclick='editTeacher(${JSON.stringify(r)})'>✏️ Edit</button>
        <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${r.id})">🗑️</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem;">No teachers found.</td></tr>';
}

function openTeacherModal(data = null) {
  ['id','name','email','password','phone','department','designation','qualification'].forEach(f => {
    const el = document.getElementById(`tm-${f}`);
    if (el) el.value = data ? (data[f] || '') : '';
  });
  document.getElementById('teacherModalTitle').textContent = data ? 'Edit Teacher' : 'Add Teacher';
  openModal('teacherModal');
}
function editTeacher(r) { openTeacherModal(r); }

async function saveTeacher() {
  const data = {};
  ['id','name','email','password','phone','department','designation','qualification'].forEach(f => {
    data[f] = document.getElementById(`tm-${f}`)?.value || '';
  });
  const res = await api('save_teacher', data);
  if (res.ok) { closeModal('teacherModal'); loadTeachers(); showToast(res.msg, 'success'); }
  else showToast(res.msg || 'Error', 'danger');
}

async function deleteTeacher(id) {
  confirm2('Delete Teacher', 'Remove this teacher from the system?', async () => {
    await api('delete_teacher', { id });
    loadTeachers(); showToast('Teacher removed', 'success');
  }, '🗑️');
}

// ========== APPLICATIONS ==========
async function loadApplications() {
  const status = document.getElementById('appStatusFilter')?.value || '';
  const rows = await apiGet('get_applications', { status });
  document.getElementById('applicationsBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td style="font-size:.78rem;color:var(--muted);">${r.app_no}</td>
      <td><strong>${esc(r.student_name)}</strong></td>
      <td>${esc(r.programme)}</td>
      <td>${r.marks_pct}%</td>
      <td>${r.category}</td>
      <td>${r.submitted_at?.slice(0,10)||'—'}</td>
      <td>${getBadge(r.status)}</td>
      <td class="action-group">
        <button class="btn btn-xs btn-success" onclick="quickUpdateApp(${r.id},'approved')">✅</button>
        <button class="btn btn-xs btn-danger" onclick="quickUpdateApp(${r.id},'rejected')">❌</button>
        <button class="btn btn-xs btn-info" onclick="openAppEdit(${r.id},'${r.status}','${esc(r.remarks||'')}')">✏️</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">No applications found.</td></tr>';
}

async function loadTeacherApplications() {
  const status = document.getElementById('tAppFilter')?.value || '';
  const rows = await apiGet('get_applications', { status });
  document.getElementById('tAppBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td style="font-size:.78rem;color:var(--muted);">${r.app_no}</td>
      <td><strong>${esc(r.student_name)}</strong></td>
      <td>${esc(r.programme)}</td>
      <td>${r.marks_pct}%</td>
      <td>${r.category}</td>
      <td>${getBadge(r.status)}</td>
      <td class="action-group">
        <button class="btn btn-xs btn-success" onclick="quickUpdateApp(${r.id},'approved')">Approve</button>
        <button class="btn btn-xs btn-danger" onclick="quickUpdateApp(${r.id},'rejected')">Reject</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No applications found.</td></tr>';
}

async function quickUpdateApp(id, status) {
  await api('update_application', { id, status, remarks: '' });
  loadApplications();
  loadTeacherApplications();
  showToast(`Application ${status}`, status === 'approved' ? 'success' : 'danger');
}

function openAppEdit(id, status, remarks) {
  document.getElementById('aem-id').value = id;
  document.getElementById('aem-status').value = status;
  document.getElementById('aem-remarks').value = remarks;
  openModal('appEditModal');
}

async function updateApplication() {
  const id = document.getElementById('aem-id').value;
  const status = document.getElementById('aem-status').value;
  const remarks = document.getElementById('aem-remarks').value;
  await api('update_application', { id, status, remarks });
  closeModal('appEditModal');
  loadApplications();
  showToast('Application updated', 'success');
}

// ========== DEPARTMENTS ==========
async function loadDepts() {
  const rows = await apiGet('get_departments');
  document.getElementById('deptsBody').innerHTML = rows.map(r => `
    <tr>
      <td style="font-size:1.5rem;">${r.icon||'📚'}</td>
      <td><strong>${esc(r.name)}</strong></td>
      <td style="color:var(--muted);">${r.code||'—'}</td>
      <td>${esc(r.head_name)||'—'}</td>
      <td style="color:var(--muted);font-size:.82rem;">${esc(r.description||'').slice(0,60)}...</td>
      <td class="action-group">
        <button class="btn btn-xs btn-info" onclick='editDept(${JSON.stringify(r)})'>✏️ Edit</button>
        <button class="btn btn-xs btn-danger" onclick="deleteDept(${r.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

function openDeptModal(data = null) {
  ['id','name','code','icon','head_name','description'].forEach(f => {
    const el = document.getElementById(`dm-${f}`);
    if (el) el.value = data ? (data[f] || '') : '';
  });
  document.getElementById('deptModalTitle').textContent = data ? 'Edit Department' : 'Add Department';
  openModal('deptModal');
}
function editDept(r) { openDeptModal(r); }

async function saveDept() {
  const data = {};
  ['id','name','code','icon','head_name','description'].forEach(f => { data[f] = document.getElementById(`dm-${f}`)?.value || ''; });
  const res = await api('save_department', data);
  if (res.ok) { closeModal('deptModal'); loadDepts(); showToast('Department saved', 'success'); }
}

async function deleteDept(id) {
  confirm2('Delete Department', 'Remove this department?', async () => {
    await api('delete_department', { id }); loadDepts(); showToast('Deleted', 'success');
  });
}

// ========== NOTICES ==========
const priorityColors = { normal: 'badge-review', important: 'badge-pending', urgent: 'badge-rejected' };
async function loadNoticesAdmin() {
  const rows = await apiGet('get_notices_admin');
  document.getElementById('noticesBody').innerHTML = rows.map(r => `
    <tr>
      <td><strong>${esc(r.title)}</strong></td>
      <td><span class="badge badge-review">${r.category}</span></td>
      <td><span class="badge ${priorityColors[r.priority]||'badge-review'}">${r.priority}</span></td>
      <td>${r.target}</td>
      <td style="font-size:.78rem;color:var(--muted);">${r.created_at?.slice(0,10)}</td>
      <td class="action-group">
        <button class="btn btn-xs btn-info" onclick='editNotice(${JSON.stringify(r)})'>✏️ Edit</button>
        <button class="btn btn-xs btn-danger" onclick="deleteNotice(${r.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

function openNoticeModal(data = null) {
  ['id','title','content','category','priority','target'].forEach(f => {
    const el = document.getElementById(`nm-${f}`);
    if (el) el.value = data ? (data[f] || '') : '';
  });
  document.getElementById('noticeModalTitle').textContent = data ? 'Edit Notice' : 'Post Notice';
  openModal('noticeModal');
}
function editNotice(r) { openNoticeModal(r); }

async function saveNotice() {
  const data = {};
  ['id','title','content','category','priority','target'].forEach(f => { data[f] = document.getElementById(`nm-${f}`)?.value || ''; });
  if (!data.title) { showToast('Title is required', 'danger'); return; }
  const res = await api('save_notice', data);
  if (res.ok) { closeModal('noticeModal'); loadNoticesAdmin(); showToast('Notice posted!', 'success'); }
}

async function deleteNotice(id) {
  confirm2('Delete Notice', 'Remove this notice?', async () => {
    await api('delete_notice', { id }); loadNoticesAdmin(); showToast('Deleted', 'success');
  });
}

async function postTeacherNotice() {
  const title = document.getElementById('tNoticeTitle').value.trim();
  const content = document.getElementById('tNoticeContent').value;
  const category = document.getElementById('tNoticeCat').value;
  const target = document.getElementById('tNoticeTarget').value;
  if (!title) { showToast('Please enter a title', 'danger'); return; }
  await api('save_notice', { title, content, category, target, priority: 'normal' });
  document.getElementById('tNoticeTitle').value = '';
  document.getElementById('tNoticeContent').value = '';
  showToast('Notice posted successfully!', 'success');
}

// ========== FEES ==========
async function loadFees() {
  const rows = await apiGet('get_fees');
  document.getElementById('feesBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td><strong>${esc(r.student_name)}</strong><br><span style="font-size:.75rem;color:var(--muted);">${r.roll_no||''}</span></td>
      <td>${esc(r.programme)||'—'}</td>
      <td>Sem ${r.semester}</td>
      <td>${esc(r.fee_type)}</td>
      <td>₹${parseFloat(r.amount).toLocaleString('en-IN')}</td>
      <td>₹${parseFloat(r.paid_amount||0).toLocaleString('en-IN')}</td>
      <td style="font-size:.78rem;">${r.due_date||'—'}</td>
      <td><span class="badge badge-${r.status}">${r.status}</span></td>
      <td class="action-group">
        <button class="btn btn-xs btn-info" onclick='editFee(${JSON.stringify(r)})'>✏️</button>
        <button class="btn btn-xs btn-danger" onclick="deleteFee(${r.id})">🗑️</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem;">No fee records. Add one above.</td></tr>';
}

function openFeeModal(data = null) {
  ['id','student_name','roll_no','programme','semester','fee_type','amount','paid_amount','due_date','paid_date','status','transaction_id'].forEach(f => {
    const el = document.getElementById(`fm-${f}`);
    if (el) el.value = data ? (data[f] ?? '') : '';
  });
  document.getElementById('fm-semester').value = data?.semester || 1;
  document.getElementById('feeModalTitle').textContent = data ? 'Edit Fee Record' : 'Add Fee Record';
  openModal('feeModal');
}
function editFee(r) { openFeeModal(r); }

async function saveFee() {
  const data = {};
  ['id','student_name','roll_no','programme','semester','fee_type','amount','paid_amount','due_date','paid_date','status','transaction_id'].forEach(f => {
    data[f] = document.getElementById(`fm-${f}`)?.value ?? '';
  });
  const res = await api('save_fee', data);
  if (res.ok) { closeModal('feeModal'); loadFees(); showToast('Fee record saved', 'success'); }
}

async function deleteFee(id) {
  confirm2('Delete Fee Record', 'Remove this fee entry?', async () => {
    await api('delete_fee', { id }); loadFees(); showToast('Deleted', 'success');
  });
}

// ========== RESULTS ==========
async function loadResults() {
  const rows = await apiGet('get_results');
  document.getElementById('resultsBody').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td style="font-size:.78rem;color:var(--muted);">${r.roll_no||'—'}</td>
      <td><strong>${esc(r.student_name)}</strong></td>
      <td>${esc(r.programme)||'—'}</td>
      <td>Sem ${r.semester}</td>
      <td>${esc(r.subject)}</td>
      <td>${r.internal_marks}</td>
      <td>${r.external_marks}</td>
      <td><strong>${r.total}</strong></td>
      <td><span class="badge badge-${r.status==='pass'?'approved':'rejected'}">${r.grade}</span></td>
      <td><span class="badge badge-${r.status}">${r.status}</span></td>
      <td class="action-group">
        <button class="btn btn-xs btn-info" onclick='editResult(${JSON.stringify(r)})'>✏️</button>
        <button class="btn btn-xs btn-danger" onclick="deleteResult(${r.id})">🗑️</button>
      </td>
    </tr>`).join('') : '<tr><td colspan="11" style="text-align:center;color:var(--muted);padding:2rem;">No results found. Add one above.</td></tr>';
}

function openResultModal(data = null) {
  ['id','student_name','roll_no','programme','semester','subject','internal_marks','external_marks','published'].forEach(f => {
    const el = document.getElementById(`rm-${f}`);
    if (el) el.value = data ? (data[f] ?? '') : '';
  });
  document.getElementById('resultModalTitle').textContent = data ? 'Edit Result' : 'Add Result';
  openModal('resultModal');
}
function editResult(r) { openResultModal(r); }

async function saveResult() {
  const data = {};
  ['id','student_name','roll_no','programme','semester','subject','internal_marks','external_marks','published'].forEach(f => {
    data[f] = document.getElementById(`rm-${f}`)?.value ?? '';
  });
  const res = await api('save_result', data);
  if (res.ok) { closeModal('resultModal'); loadResults(); showToast('Result saved', 'success'); }
}

async function deleteResult(id) {
  confirm2('Delete Result', 'Remove this result entry?', async () => {
    await api('delete_result', { id }); loadResults(); showToast('Deleted', 'success');
  });
}

// ========== SETTINGS ==========
async function loadSettings() {
  const rows = await apiGet('get_settings');
  const labels = {
    site_name: 'Site Name', admission_open: 'Admission Open (1=Yes, 0=No)',
    admission_deadline: 'Admission Deadline', contact_email: 'Contact Email',
    contact_phone: 'Contact Phone', established: 'Year Established',
  };
  document.getElementById('settingsForm').innerHTML = rows.map(r => `
    <div class="form-group">
      <label class="form-label">${labels[r.setting_key] || r.setting_key}</label>
      <input class="form-control" name="${r.setting_key}" id="setting_${r.setting_key}" value="${esc(r.setting_value||'')}">
    </div>`).join('');
}

async function saveSettings() {
  const inputs = document.querySelectorAll('#settingsForm input');
  const data = {};
  inputs.forEach(inp => { data[inp.name] = inp.value; });
  const res = await api('save_settings', data);
  if (res.ok) showToast(res.msg, 'success');
}

// ========== ADMIN USERS ==========
async function loadAdminUsers() {
  const rows = await apiGet('get_admins');
  document.getElementById('adminUsersBody').innerHTML = rows.map(r => `
    <tr>
      <td>${r.id}</td>
      <td><strong>${esc(r.username)}</strong></td>
      <td>${esc(r.name||'')}</td>
      <td>${esc(r.email||'')}</td>
      <td style="font-size:.78rem;color:var(--muted);">${r.created_at?.slice(0,10)}</td>
    </tr>`).join('');
}

function openAdminUserModal() { openModal('adminUserModal'); }
async function saveAdminUser() {
  const data = {
    name: document.getElementById('au-name').value,
    username: document.getElementById('au-username').value,
    email: document.getElementById('au-email').value,
    password: document.getElementById('au-password').value,
  };
  if (!data.username || !data.password) { showToast('Username and password required', 'danger'); return; }
  const res = await api('save_admin', data);
  if (res.ok) { closeModal('adminUserModal'); loadAdminUsers(); showToast('Admin user added', 'success'); }
}

// ========== PUBLIC NOTICES ==========
async function loadPublicNotices() {
  const rows = await apiGet('get_notices');
  const catColors = { Admission: '#c8922a', Exam: '#1d4ed8', Event: '#16a34a', General: '#6b7280' };
  document.getElementById('publicNotices').innerHTML = rows.map(r => `
    <div class="announcement-item">
      <span class="ann-badge" style="background:${catColors[r.category]||'#6b7280'}22;color:${catColors[r.category]||'#6b7280'};">${r.category}</span>
      <div class="ann-content"><h4>${esc(r.title)}</h4><p>${esc(r.content||'').slice(0,120)}...</p></div>
    </div>`).join('') || '<p style="color:var(--muted);padding:1rem;">No notices at this time.</p>';
}

async function loadStudentNotices() {
  const rows = await apiGet('get_notices');
  document.getElementById('studentNoticeList').innerHTML = rows.map(r => `
    <div class="announcement-item">
      <span class="ann-badge">${r.category}</span>
      <div class="ann-content"><h4>${esc(r.title)}</h4><p>${esc(r.content||'').slice(0,100)}</p></div>
    </div>`).join('') || '<p style="color:var(--muted);">No notices.</p>';
}

// ========== ADMISSION FORM ==========
async function submitAdmissionForm() {
  const data = {
    student_name: document.getElementById('af-name').value.trim(),
    dob: document.getElementById('af-dob').value,
    gender: document.getElementById('af-gender').value,
    category: document.getElementById('af-category').value,
    email: document.getElementById('af-email').value.trim(),
    phone: document.getElementById('af-phone').value.trim(),
    programme: document.getElementById('af-programme').value,
    prev_school: document.getElementById('af-school').value.trim(),
    marks_pct: document.getElementById('af-marks').value,
    address: document.getElementById('af-address').value.trim(),
  };
  if (!data.student_name || !data.programme || !data.marks_pct) {
    showToast('Please fill all required fields (*)', 'danger'); return;
  }
  const res = await api('submit_application', data);
  if (res.ok) {
    showToast(`Application submitted! ID: ${res.app_no}`, 'success');
    setTimeout(() => switchPanel('s-status', null), 1500);
  } else {
    showToast(res.msg || 'Submission failed', 'danger');
  }
}

// ========== HELPERS ==========
function getBadge(status) {
  const map = {
    pending: '<span class="badge badge-pending">Pending</span>',
    review: '<span class="badge badge-review">Under Review</span>',
    approved: '<span class="badge badge-approved">Approved</span>',
    rejected: '<span class="badge badge-rejected">Rejected</span>',
    enrolled: '<span class="badge badge-enrolled">Enrolled</span>',
  };
  return map[status] || status;
}
function esc(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function scrollToId(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

// ========== INIT ==========
window.addEventListener('DOMContentLoaded', () => {
  loadPublicNotices();
  // If admin already logged in (PHP session)
  <?php if ($isAdmin): ?>
  showPage('admin-dashboard');
  loadAdminStats();
  <?php endif; ?>
});
</script>
<?php
// ─── EXPORT CSV (simple) ─────────────────────────────────────
if (isset($_GET['export']) && isset($_SESSION['admin'])) {
    $type = $_GET['export'];
    if ($type === 'students') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=students_' . date('Ymd') . '.csv');
        $rows = rows("SELECT roll_no,name,email,phone,programme,category,semester,created_at FROM students");
        echo "Roll No,Name,Email,Phone,Programme,Category,Semester,Created\n";
        foreach ($rows as $r) echo implode(',', array_map(fn($v) => '"'.str_replace('"','""',$v).'"', $r))."\n";
        exit;
    }
}
?>
</body>
</html>
