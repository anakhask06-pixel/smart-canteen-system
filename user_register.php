<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit();
}
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $admission   = trim($_POST['admission_no'] ?? '');
    $dept        = trim($_POST['department'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    if (!$name || !$admission || !$dept || !$password) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if admission_no already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE admission_no=?");
        $stmt->bind_param("s", $admission);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with this admission number already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name, admission_no, department, password) VALUES (?,?,?,?)");
            $ins->bind_param("ssss", $name, $admission, $dept, $hashed);
            if ($ins->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
    --bg:#0f0f0f; --surface:#1a1a1a; --surface2:#222;
    --border:#2a2a2a; --accent:#f0c040; --text:#f0ece4;
    --muted:#888; --accent2:#e8533a; --green:#3ecf6e;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.auth-card{width:100%;max-width:440px;background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.auth-head{padding:32px 32px 24px;border-bottom:1px solid var(--border);}
.auth-brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:var(--accent);margin-bottom:16px;}
.auth-head h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:6px;}
.auth-head p{color:var(--muted);font-size:0.875rem;}
.auth-body{padding:28px 32px 32px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:6px;font-weight:500;}
.field input{width:100%;padding:11px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.9rem;transition:border-color 0.2s;}
.field input:focus{outline:none;border-color:var(--accent);}
.field input::placeholder{color:var(--muted);}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.btn{width:100%;padding:13px;background:var(--accent);color:#0f0f0f;border:none;border-radius:9px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;transition:background 0.2s;margin-top:6px;}
.btn:hover{background:#e0b030;}
.alert{padding:11px 14px;border-radius:8px;font-size:0.875rem;margin-bottom:18px;}
.alert-error{background:rgba(232,83,58,0.1);border:1px solid rgba(232,83,58,0.3);color:#ff7a64;}
.alert-success{background:rgba(62,207,110,0.1);border:1px solid rgba(62,207,110,0.3);color:var(--green);}
.auth-footer{text-align:center;margin-top:20px;font-size:0.875rem;color:var(--muted);}
.auth-footer a{color:var(--accent);text-decoration:none;}
</style>
</head>
<body>
<div class="auth-card">
    <div class="auth-head">
        <div class="auth-brand">🍽 CanteenOS</div>
        <h1>Create Account</h1>
        <p>Register to order and track your meals.</p>
    </div>
    <div class="auth-body">
        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="row2">
                <div class="field">
                    <label>Admission No.</label>
                    <input type="text" name="admission_no" placeholder="CS2024001" value="<?php echo htmlspecialchars($_POST['admission_no'] ?? ''); ?>" required>
                </div>
                <div class="field">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. CSE" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="row2">
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 chars" required>
                </div>
                <div class="field">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat" required>
                </div>
            </div>
            <button type="submit" class="btn">Create Account →</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="user_login.php">Sign in</a>
        </div>
    </div>
</div>
</body>
</html>