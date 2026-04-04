<?php
session_start();
include 'db.php';

// Auto-detect schema
$cols = $conn->query("SHOW COLUMNS FROM orders")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');
$useNewSchema = in_array('user_id', $colNames);

$groups = [];
$rows = [];
$admission_no = '';
$searched = false;
$error = '';

// If logged in with new user system
if ($useNewSchema && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT admission_no FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $admission_no = $u['admission_no'] ?? '';

    $stmt2 = $conn->prepare(
        "SELECT o.order_group, o.status, o.total_amount, m.item_name, o.quantity
         FROM orders o JOIN menu m ON o.item_id = m.item_id
         WHERE o.user_id = ?
         ORDER BY o.order_group DESC, o.order_id ASC"
    );
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $searched = true;

} elseif (!empty($_POST['admission_no']) || !empty($_GET['admission_no'])) {
    $admission_no = trim($_POST['admission_no'] ?? $_GET['admission_no'] ?? '');
    $searched = true;

    if (!$admission_no) {
        $error = 'Please enter your admission number.';
    } else {
        if ($useNewSchema) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE admission_no=?");
            $stmt->bind_param("s", $admission_no);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            if (!$userRow) {
                $error = 'No orders found for that admission number.';
            } else {
                $uid = $userRow['user_id'];
                $stmt2 = $conn->prepare(
                    "SELECT o.order_group, o.status, o.total_amount, m.item_name, o.quantity
                     FROM orders o JOIN menu m ON o.item_id = m.item_id
                     WHERE o.user_id = ?
                     ORDER BY o.order_group DESC, o.order_id ASC"
                );
                $stmt2->bind_param("i", $uid);
                $stmt2->execute();
                $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            // Old schema: students table
            $stmt = $conn->prepare("SELECT student_id FROM students WHERE admission_no=?");
            $stmt->bind_param("s", $admission_no);
            $stmt->execute();
            $studentRow = $stmt->get_result()->fetch_assoc();
            if (!$studentRow) {
                $error = 'No orders found for that admission number.';
            } else {
                $sid = $studentRow['student_id'];
                $stmt2 = $conn->prepare(
                    "SELECT o.order_group, o.status, o.total_amount, m.item_name, o.quantity
                     FROM orders o JOIN menu m ON o.item_id = m.item_id
                     WHERE o.student_id = ?
                     ORDER BY o.order_group DESC, o.order_id ASC"
                );
                $stmt2->bind_param("i", $sid);
                $stmt2->execute();
                $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
}

// Group orders
foreach ($rows as $row) {
    $og = $row['order_group'];
    if (!isset($groups[$og])) {
        $groups[$og] = ['order_group'=>$og,'status'=>$row['status'],'total'=>0,'items'=>[]];
    }
    $groups[$og]['items'][] = $row['item_name'] . ' x' . $row['quantity'];
    $groups[$og]['total'] += $row['total_amount'];
    if ($row['status'] === 'Pending') $groups[$og]['status'] = 'Pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track My Order — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#fff;--surface:#f7f7f5;--surface2:#efefed;--border:#e4e4e0;--accent:#c89a00;--text:#1a1a1a;--muted:#888;--green:#16a34a;--orange:#d97706;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;}
.nav-brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--accent);}
.nav-brand span{color:var(--text);font-weight:400;}
.back-btn{text-decoration:none;font-size:0.875rem;padding:8px 16px;border-radius:8px;background:var(--surface);border:1px solid var(--border);color:var(--text);transition:all 0.2s;}
.back-btn:hover{border-color:var(--accent);color:var(--accent);}
.page{max-width:680px;margin:0 auto;padding:40px 24px 60px;}
.page-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;margin-bottom:6px;}
.page-sub{color:var(--muted);font-size:0.875rem;margin-bottom:28px;}
.search-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:24px;}
.search-card h3{font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;margin-bottom:14px;}
.search-row{display:flex;gap:10px;}
.search-input{flex:1;padding:11px 14px;background:#fff;border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.9rem;transition:border-color 0.2s;}
.search-input:focus{outline:none;border-color:var(--accent);}
.search-input::placeholder{color:#bbb;}
.btn-search{padding:11px 20px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;cursor:pointer;transition:background 0.2s;}
.btn-search:hover{background:#a07a00;}
.alert-error{background:rgba(220,38,38,0.07);border:1px solid rgba(220,38,38,0.2);color:#dc2626;padding:11px 14px;border-radius:8px;font-size:0.875rem;margin-bottom:18px;}
.live-note{font-size:0.78rem;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:6px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--green);animation:blink 1.4s ease infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.2;}}
.order-card{background:#fff;border:1px solid var(--border);border-radius:14px;margin-bottom:12px;overflow:hidden;transition:all 0.3s;}
.order-card.ready{border-color:rgba(22,163,74,0.4);box-shadow:0 0 0 3px rgba(22,163,74,0.07);}
.order-head{padding:13px 18px;display:flex;align-items:center;justify-content:space-between;background:var(--surface);border-bottom:1px solid var(--border);}
.order-id{font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;color:var(--accent);}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;}
.badge-pending{background:rgba(217,119,6,0.1);color:var(--orange);border:1px solid rgba(217,119,6,0.25);}
.badge-ready{background:rgba(22,163,74,0.1);color:var(--green);border:1px solid rgba(22,163,74,0.25);}
.order-body{padding:14px 18px;}
.item-line{font-size:0.85rem;color:var(--muted);padding:2px 0;}
.order-total{font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);margin-top:8px;}
.ready-banner{background:rgba(22,163,74,0.07);border:1px solid rgba(22,163,74,0.2);border-radius:8px;padding:10px 14px;font-size:0.85rem;color:var(--green);margin-top:10px;}
.empty{text-align:center;padding:48px 20px;color:var(--muted);}
.empty .icon{font-size:2.5rem;margin-bottom:10px;}
@media(max-width:600px){nav{padding:0 16px;}.page{padding:24px 14px 40px;}.search-row{flex-direction:column;}}
</style>
</head>
<body>
<nav>
    <div class="nav-brand">🍽 Canteen<span>OS</span></div>
    <a href="index.php" class="back-btn">← Back to Menu</a>
</nav>

<div class="page">
    <div class="page-title">📋 Track My Order</div>
    <div class="page-sub">Enter your admission number to see live order status.</div>

    <div class="search-card">
        <h3>🔍 Look Up Your Orders</h3>
        <form method="post">
            <div class="search-row">
                <input type="text" name="admission_no" class="search-input"
                       placeholder="e.g. CS2024080"
                       value="<?php echo htmlspecialchars($admission_no); ?>" required>
                <button type="submit" class="btn-search">Track →</button>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($searched && !$error): ?>
        <?php if (empty($groups)): ?>
        <div class="empty">
            <div class="icon">🍽</div>
            <p>No orders found for <b><?php echo htmlspecialchars($admission_no); ?></b>.</p>
        </div>
        <?php else: ?>
        <div class="live-note">
            <span class="live-dot"></span> Auto-refreshing every 15 seconds
        </div>
        <div id="orders-container">
        <?php foreach ($groups as $og => $g):
            $isReady = ($g['status'] === 'Ready');
        ?>
        <div class="order-card <?php echo $isReady ? 'ready' : ''; ?>" data-group="<?php echo htmlspecialchars($og); ?>">
            <div class="order-head">
                <div class="order-id">#<?php echo htmlspecialchars($og); ?></div>
                <span class="badge <?php echo $isReady ? 'badge-ready':'badge-pending'; ?>">
                    <?php echo $isReady ? '✅ Ready for Pickup' : '⏳ Preparing'; ?>
                </span>
            </div>
            <div class="order-body">
                <?php foreach ($g['items'] as $item): ?>
                <div class="item-line">• <?php echo htmlspecialchars($item); ?></div>
                <?php endforeach; ?>
                <div class="order-total">₹<?php echo number_format($g['total'],2); ?></div>
                <?php if ($isReady): ?>
                <div class="ready-banner">🎉 Your order is ready! Please collect it from the counter.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const admNo = <?php echo json_encode($admission_no); ?>;
if (admNo) {
    const lastStatuses = {};
    document.querySelectorAll('.order-card').forEach(c => {
        lastStatuses[c.dataset.group] = c.classList.contains('ready') ? 'Ready' : 'Pending';
    });

    async function poll() {
        try {
            const res = await fetch('order_status_api.php?admission_no=' + encodeURIComponent(admNo));
            const data = await res.json();
            data.forEach(order => {
                const og = String(order.order_group);
                const card = document.querySelector(`.order-card[data-group="${og}"]`);
                if (!card) return;
                if (order.status === 'Ready' && lastStatuses[og] !== 'Ready') {
                    card.classList.add('ready');
                    const badge = card.querySelector('.badge');
                    badge.className = 'badge badge-ready';
                    badge.textContent = '✅ Ready for Pickup';
                    lastStatuses[og] = 'Ready';
                    const body = card.querySelector('.order-body');
                    if (!body.querySelector('.ready-banner')) {
                        const b = document.createElement('div');
                        b.className = 'ready-banner';
                        b.textContent = '🎉 Your order is ready! Please collect it from the counter.';
                        body.appendChild(b);
                    }
                    if (Notification.permission === 'granted') {
                        new Notification('🍽 CanteenOS — Order Ready!', { body: `Order #${og} is ready for pickup!` });
                    }
                    try {
                        const ctx = new (window.AudioContext||window.webkitAudioContext)();
                        const osc = ctx.createOscillator(), g = ctx.createGain();
                        osc.connect(g); g.connect(ctx.destination);
                        osc.frequency.value = 880;
                        g.gain.setValueAtTime(0.3, ctx.currentTime);
                        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime+0.6);
                        osc.start(); osc.stop(ctx.currentTime+0.6);
                    } catch(e){}
                }
            });
        } catch(e){}
    }

    if (Notification.permission === 'default') Notification.requestPermission();
    setInterval(poll, 15000);
}
</script>
</body>
</html>