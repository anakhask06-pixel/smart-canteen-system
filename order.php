<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user details from DB
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: user_login.php"); exit();
}

$name        = $user['name'];
$department  = $user['department'];
$admission_no = $user['admission_no'];

// Validate qty array exists
if (empty($_POST['qty']) || !is_array($_POST['qty'])) {
    die("❌ No items selected.");
}

$order_group = time() . rand(100, 999); // more unique
$total_amount = 0;

$conn->begin_transaction();

try {
    foreach ($_POST['qty'] as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty     = (int)$qty;

        if ($qty <= 0) continue;

        // Get item details with lock
        $res = $conn->query("SELECT price, stock FROM menu WHERE item_id=$item_id FOR UPDATE");
        $row = $res->fetch_assoc();

        if (!$row) throw new Exception("Item #$item_id not found.");
        if ($row['stock'] < $qty) throw new Exception("Not enough stock for that item.");

        $total = round($row['price'] * $qty, 2);
        $total_amount += $total;

        $ins = $conn->prepare("INSERT INTO orders (user_id, item_id, quantity, total_amount, status, order_group) VALUES (?,?,?,?,'Pending',?)");
        $ins->bind_param("iiidi", $user_id, $item_id, $qty, $total, $order_group);
        $ins->execute();

        $upd = $conn->prepare("UPDATE menu SET stock = stock - ? WHERE item_id = ?");
        $upd->bind_param("ii", $qty, $item_id);
        $upd->execute();
    }

    if ($total_amount == 0) {
        throw new Exception("No valid items were ordered.");
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("❌ Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Placed — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f0f;--surface:#1a1a1a;--border:#2a2a2a;--accent:#f0c040;--text:#f0ece4;--muted:#888;--green:#3ecf6e;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.success-card{width:100%;max-width:440px;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:40px 36px;text-align:center;}
.icon{font-size:3rem;margin-bottom:20px;animation:pop 0.4s ease;}
@keyframes pop{0%{transform:scale(0.5);opacity:0;}100%{transform:scale(1);opacity:1;}}
h1{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--green);margin-bottom:8px;}
.sub{color:var(--muted);font-size:0.9rem;margin-bottom:28px;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px;text-align:left;}
.info-cell{background:#1e1e1e;border:1px solid var(--border);border-radius:10px;padding:14px;}
.info-cell .lbl{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;}
.info-cell .val{font-weight:600;font-size:0.95rem;}
.order-id{grid-column:1/-1;background:rgba(240,192,64,0.07);border-color:rgba(240,192,64,0.2);}
.order-id .val{color:var(--accent);font-family:'Syne',sans-serif;font-size:1.2rem;}
.total-cell{grid-column:1/-1;}
.total-cell .val{color:var(--green);font-size:1.1rem;}
.notify-banner{background:rgba(62,207,110,0.08);border:1px solid rgba(62,207,110,0.2);border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:0.875rem;color:var(--green);}
.links{display:flex;gap:12px;justify-content:center;}
.btn{padding:11px 24px;border-radius:9px;text-decoration:none;font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;transition:all 0.2s;}
.btn-primary{background:var(--accent);color:#0f0f0f;}
.btn-primary:hover{background:#e0b030;}
.btn-sec{background:var(--surface);border:1px solid var(--border);color:var(--text);}
.btn-sec:hover{border-color:var(--accent);color:var(--accent);}
</style>
</head>
<body>
<div class="success-card">
    <div class="icon">✅</div>
    <h1>Order Placed!</h1>
    <p class="sub">Your order has been sent to the kitchen.</p>

    <div class="info-grid">
        <div class="info-cell order-id">
            <div class="lbl">Order ID</div>
            <div class="val">#<?php echo htmlspecialchars($order_group); ?></div>
        </div>
        <div class="info-cell">
            <div class="lbl">Name</div>
            <div class="val"><?php echo htmlspecialchars($name); ?></div>
        </div>
        <div class="info-cell">
            <div class="lbl">Dept</div>
            <div class="val"><?php echo htmlspecialchars($department); ?></div>
        </div>
        <div class="info-cell total-cell">
            <div class="lbl">Total Amount</div>
            <div class="val">₹<?php echo number_format($total_amount, 2); ?></div>
        </div>
    </div>

    <div class="notify-banner">
        🔔 Your order status will update automatically. Check <b>My Orders</b> to track it live.
    </div>

    <div class="links">
        <a href="my_orders.php" class="btn btn-primary">Track Order →</a>
        <a href="index.php" class="btn btn-sec">← Menu</a>
    </div>
</div>
</body>
</html>