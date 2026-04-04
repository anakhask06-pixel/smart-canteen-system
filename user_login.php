<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit();
}
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission = trim($_POST['admission_no'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$admission || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE admission_no=?");
        $stmt->bind_param("s", $admission);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['user_name'] = $row['name'];
            header("Location: index.php"); exit();
        } else {
            $error = 'Invalid admission number or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Login — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f0f;--surface:#1a1a1a;--surface2:#222;--border:#2a2a2a;--accent:#f0c040;--text:#f0ece4;--muted:#888;--accent2:#e8533a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.auth-card{width:100%;max-width:400px;background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
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
.btn{width:100%;padding:13px;background:var(--accent);color:#0f0f0f;border:none;border-radius:9px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;transition:background 0.2s;margin-top:6px;}
.btn:hover{background:#e0b030;}
.alert-error{background:rgba(232,83,58,0.1);border:1px solid rgba(232,83,58,0.3);color:#ff7a64;padding:11px 14px;border-radius:8px;font-size:0.875rem;margin-bottom:18px;}
.auth-footer{text-align:center;margin-top:20px;font-size:0.875rem;color:var(--muted);}
.auth-footer a{color:var(--accent);text-decoration:none;}
.back-link{display:block;text-align:center;margin-top:12px;font-size:0.8rem;color:var(--muted);text-decoration:none;}
.back-link:hover{color:var(--accent);}
</style>
</head>
<body>
<div class="auth-card">
    <div class="auth-head">
        <div class="auth-brand">🍽 CanteenOS</div>
        <h1>Student Login</h1>
        <p>Sign in to order and track your meals.</p>
    </div>
    <div class="auth-body">
        <?php if ($error): ?>
        <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label>Admission Number</label>
                <input type="text" name="admission_no" placeholder="e.g. CS2024001" value="<?php echo htmlspecialchars($_POST['admission_no'] ?? ''); ?>" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Sign In →</button>
        </form>

        <div class="auth-footer">
            No account? <a href="user_register.php">Register here</a>
        </div>
        <a href="index.php" class="back-link">← Back to menu</a>
    </div>
</div>
</body>
</html>