<?php
session_start();
if (isset($_SESSION['admin'])) {
    header("Location: admin.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0a0a;--surface:#111;--surface2:#1a1a1a;--border:#222;--accent:#f0c040;--text:#f0ece4;--muted:#666;--accent2:#e8533a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{width:100%;max-width:380px;background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.card-head{padding:28px 28px 22px;border-bottom:1px solid var(--border);}
.brand{font-family:'Syne',sans-serif;font-weight:800;color:var(--accent);font-size:1rem;margin-bottom:14px;}
h1{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:4px;}
p{color:var(--muted);font-size:0.8rem;}
.card-body{padding:24px 28px 28px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:5px;font-weight:500;}
.field input{width:100%;padding:10px 13px;background:var(--surface2);border:1px solid var(--border);border-radius:7px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.875rem;transition:border-color 0.2s;}
.field input:focus{outline:none;border-color:var(--accent);}
.field input::placeholder{color:var(--muted);}
.btn{width:100%;padding:12px;background:var(--accent);color:#0a0a0a;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;cursor:pointer;transition:background 0.2s;margin-top:4px;}
.btn:hover{background:#e0b030;}
.err{background:rgba(232,83,58,0.1);border:1px solid rgba(232,83,58,0.25);color:#ff7a64;padding:10px 13px;border-radius:7px;font-size:0.82rem;margin-bottom:14px;}
.back{display:block;text-align:center;margin-top:16px;font-size:0.8rem;color:var(--muted);text-decoration:none;}
.back:hover{color:var(--accent);}
.admin-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(232,83,58,0.1);border:1px solid rgba(232,83,58,0.2);color:#ff7a64;padding:3px 10px;border-radius:10px;font-size:0.7rem;font-weight:600;margin-bottom:14px;}
</style>
</head>
<body>
<div class="card">
    <div class="card-head">
        <div class="brand">🍽 CanteenOS</div>
        <div class="admin-chip">⚙ Admin Access</div>
        <h1>Admin Login</h1>
        <p>Restricted area — authorized personnel only.</p>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['err'])): ?>
        <div class="err">❌ Invalid username or password.</div>
        <?php endif; ?>

        <form action="check_login.php" method="post">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Authenticate →</button>
        </form>

        <a href="index.php" class="back">← Back to Canteen</a>
    </div>
</div>
</body>
</html>