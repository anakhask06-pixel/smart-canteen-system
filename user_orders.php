<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php"); exit();
}
include 'db.php';

$user_id = (int)$_SESSION['user_id'];

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch orders grouped
$sql = "SELECT o.order_group, o.status, o.total_amount, o.created_at,
               m.item_name, o.quantity
        FROM orders o
        JOIN menu m ON o.item_id = m.item_id
        WHERE o.user_id = ?
        ORDER BY o.order_group DESC, o.order_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by order_group
$groups = [];
foreach ($rows as $row) {
    $og = $row['order_group'];
    if (!isset($groups[$og])) {
        $groups[$og] = [
            'order_group' => $og,
            'status'      => $row['status'],
            'created_at'  => $row['created_at'],
            'total'       => 0,
            'items'       => []
        ];
    }
    $groups[$og]['items'][] = $row['item_name'] . ' ×' . $row['quantity'];
    $groups[$og]['total']  += $row['total_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f0f;--surface:#1a1a1a;--surface2:#222;--border:#2a2a2a;--accent:#f0c040;--text:#f0ece4;--muted:#888;--green:#3ecf6e;--orange:#f59e0b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{position:sticky;top:0;z-index:100;background:rgba(15,15,15,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;}
.nav-brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--accent);}
.nav-brand span{color:var(--text);font-weight:400;}
.nav-links{display:flex;gap:10px;align-items:center;}
.nav-links a{text-decoration:none;font-size:0.875rem;padding:8px 14px;border-radius:7px;transition:all 0.2s;}
.nav-home{color:var(--text);background:var(--surface);border:1px solid var(--border);}
.nav-home:hover{border-color:var(--accent);color:var(--accent);}
.nav-logout{color:var(--muted);}
.nav-logout:hover{color:#e8533a;}

.page{max-width:700px;margin:0 auto;padding:40px 24px 60px;}
.page-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;margin-bottom:6px;}
.page-sub{color:var(--muted);font-size:0.875rem;margin-bottom:32px;}

.live-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--green);margin-right:6px;animation:blink 1.4s ease infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.2;}}

.order-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;margin-bottom:16px;overflow:hidden;transition:border-color 0.3s;}
.order-card.ready{border-color:rgba(62,207,110,0.4);}
.order-head{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);}
.order-id{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--accent);}
.order-date{font-size:0.75rem;color:var(--muted);margin-top:2px;}
.badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;}
.badge-pending{background:rgba(245,158,11,0.15);color:var(--orange);border:1px solid rgba(245,158,11,0.3);}
.badge-ready{background:rgba(62,207,110,0.15);color:var(--green);border:1px solid rgba(62,207,110,0.3);}
.order-body{padding:16px 20px;}
.items-list{margin-bottom:12px;}
.item-line{font-size:0.875rem;color:var(--muted);padding:3px 0;}
.order-total{font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);font-size:1rem;}

.ready-banner{background:rgba(62,207,110,0.1);border:1px solid rgba(62,207,110,0.2);border-radius:10px;padding:12px 16px;font-size:0.875rem;color:var(--green);margin-top:10px;display:none;}
.order-card.ready .ready-banner{display:block;}

.empty-state{text-align:center;padding:60px 20px;}
.empty-state .icon{font-size:3rem;margin-bottom:16px;}
.empty-state h3{font-family:'Syne',sans-serif;font-size:1.2rem;margin-bottom:8px;}
.empty-state p{color:var(--muted);margin-bottom:20px;font-size:0.9rem;}
.btn-order{display:inline-block;padding:11px 24px;background:var(--accent);color:#0f0f0f;border-radius:9px;text-decoration:none;font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;}

@media(max-width:600px){nav{padding:0 16px;}.page{padding:24px 16px 40px;}}
</style>
</head>
<body>

<nav>
    <div class="nav-brand">🍽 Canteen<span>OS</span></div>
    <div class="nav-links">
        <a href="index.php" class="nav-home">← Menu</a>
        <a href="user_logout.php" class="nav-logout">Sign Out</a>
    </div>
</nav>

<div class="page">
    <div class="page-title">📋 My Orders</div>
    <div class="page-sub">
        <span class="live-dot"></span>
        Auto-refreshing every 15 seconds — you'll be notified when your order is ready.
    </div>

    <?php if (empty($groups)): ?>
    <div class="empty-state">
        <div class="icon">🍽</div>
        <h3>No orders yet</h3>
        <p>You haven't placed any orders. Head to the menu to get started!</p>
        <a href="index.php" class="btn-order">Browse Menu →</a>
    </div>
    <?php else: ?>

    <div id="orders-container">
    <?php foreach ($groups as $og => $g):
        $isReady = ($g['status'] === 'Ready');
        $cardClass = $isReady ? 'order-card ready' : 'order-card';
        $badgeClass = $isReady ? 'badge badge-ready' : 'badge badge-pending';
        $badgeText = $isReady ? '✅ Ready for Pickup' : '⏳ Preparing';
    ?>
    <div class="<?php echo $cardClass; ?>" data-group="<?php echo $og; ?>">
        <div class="order-head">
            <div>
                <div class="order-id">#<?php echo htmlspecialchars($og); ?></div>
                <div class="order-date"><?php echo date('d M Y, h:i A', strtotime($g['created_at'])); ?></div>
            </div>
            <span class="<?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
        </div>
        <div class="order-body">
            <div class="items-list">
                <?php foreach ($g['items'] as $item): ?>
                <div class="item-line">• <?php echo htmlspecialchars($item); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="order-total">₹<?php echo number_format($g['total'], 2); ?></div>
            <div class="ready-banner">
                🎉 Your order is ready! Please collect it from the counter.
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<script>
// Poll for status updates every 15 seconds
let lastStatuses = {};

// Record initial statuses
document.querySelectorAll('.order-card').forEach(card => {
    const og = card.dataset.group;
    lastStatuses[og] = card.classList.contains('ready') ? 'Ready' : 'Pending';
});

async function pollStatuses() {
    try {
        const res = await fetch('order_status_api.php');
        const data = await res.json();

        data.forEach(order => {
            const og = order.order_group;
            const card = document.querySelector(`.order-card[data-group="${og}"]`);
            if (!card) return;

            const badge = card.querySelector('.badge');
            const prevStatus = lastStatuses[og];

            if (order.status === 'Ready' && prevStatus !== 'Ready') {
                // Status just changed to Ready!
                card.classList.add('ready');
                badge.className = 'badge badge-ready';
                badge.textContent = '✅ Ready for Pickup';
                lastStatuses[og] = 'Ready';

                // Show browser notification if permitted
                if (Notification.permission === 'granted') {
                    new Notification('🍽 CanteenOS — Order Ready!', {
                        body: `Order #${og} is ready for pickup!`,
                        icon: '🍴'
                    });
                }

                // Play a soft beep
                playBeep();
            }
        });
    } catch(e) {
        console.log('Poll error:', e);
    }
}

function playBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
        osc.start(); osc.stop(ctx.currentTime + 0.6);
    } catch(e) {}
}

// Request notification permission
if (Notification.permission === 'default') {
    Notification.requestPermission();
}

// Start polling
setInterval(pollStatuses, 15000);
</script>
</body>
</html>