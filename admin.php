<?php
session_start();
include 'db.php';

// Auto-detect old (student_id/students) vs new (user_id/users) schema
$cols = $conn->query("SHOW COLUMNS FROM orders")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');

if (in_array('user_id', $colNames)) {
    $sql = "SELECT o.order_group, u.name, u.department, u.admission_no,
                   m.item_name, o.quantity, o.total_amount, o.status
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN menu m ON o.item_id = m.item_id
            ORDER BY o.order_group DESC, o.order_id ASC";
} else {
    $sql = "SELECT o.order_group, s.name, s.department, s.admission_no,
                   m.item_name, o.quantity, o.total_amount, o.status
            FROM orders o
            JOIN students s ON o.student_id = s.student_id
            JOIN menu m ON o.item_id = m.item_id
            ORDER BY o.order_group DESC, o.order_id ASC";
}
$result = $conn->query($sql);

// Group by order_group
$groups = [];
while ($row = $result->fetch_assoc()) {
    $og = $row['order_group'];
    if (!isset($groups[$og])) {
        $groups[$og] = [
            'order_group' => $og,
            'name'        => $row['name'],
            'department'  => $row['department'],
            'admission_no'=> $row['admission_no'],
            'status'      => $row['status'],
            'total'       => 0,
            'items'       => []
        ];
    }
    $groups[$og]['items'][] = [
        'name' => $row['item_name'],
        'qty'  => $row['quantity'],
        'amt'  => $row['total_amount']
    ];
    $groups[$og]['total'] += $row['total_amount'];
    // If any item is pending, whole order is pending
    if ($row['status'] === 'Pending') {
        $groups[$og]['status'] = 'Pending';
    }
}

// Counts
$total = count($groups);
$pending = count(array_filter($groups, fn($g) => $g['status'] === 'Pending'));
$ready   = $total - $pending;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#ffffff;--surface:#f7f7f5;--surface2:#efefed;--border:#e4e4e0;--accent:#c89a00;--text:#1a1a1a;--muted:#888;--green:#16a34a;--orange:#d97706;--red:#e8533a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;}
.nav-brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:var(--accent);}
.admin-chip{background:rgba(232,83,58,0.1);border:1px solid rgba(232,83,58,0.25);color:var(--red);padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.nav-right{display:flex;align-items:center;gap:12px;}
.nav-user{font-size:0.8rem;color:var(--muted);}
.nav-logout{text-decoration:none;color:var(--muted);font-size:0.8rem;padding:6px 12px;border-radius:6px;border:1px solid var(--border);}
.nav-logout:hover{color:var(--red);border-color:rgba(232,83,58,0.3);}

.page{max-width:1100px;margin:0 auto;padding:32px 24px 60px;}
.page-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;}
.page-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;}
.page-sub{color:var(--muted);font-size:0.8rem;margin-top:4px;}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px;}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px 20px;}
.stat-val{font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;line-height:1;}
.stat-lbl{font-size:0.75rem;color:var(--muted);margin-top:6px;text-transform:uppercase;letter-spacing:1px;}
.stat.total .stat-val{color:var(--text);}
.stat.pend .stat-val{color:var(--orange);}
.stat.done .stat-val{color:var(--green);}

/* TABLE */
.table-wrap{overflow-x:auto;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--surface2);}
th{padding:12px 16px;text-align:left;font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);}
td{padding:14px 16px;border-bottom:1px solid var(--border);vertical-align:top;font-size:0.875rem;background:#fff;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafaf8;}
.order-id{font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);}
.items-cell div{color:var(--muted);font-size:0.8rem;line-height:1.6;}
.total-cell{font-family:'Syne',sans-serif;font-weight:700;color:var(--green);}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.badge-pending{background:rgba(217,119,6,0.1);color:var(--orange);border:1px solid rgba(217,119,6,0.25);}
.badge-ready{background:rgba(22,163,74,0.1);color:var(--green);border:1px solid rgba(22,163,74,0.25);}

/* STATUS FORM */
.status-form{display:flex;align-items:center;gap:8px;}
.status-select{padding:7px 10px;background:#fff;border:1px solid var(--border);border-radius:7px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.8rem;cursor:pointer;width:auto;}
.status-select:focus{outline:none;border-color:var(--accent);}
.btn-update{padding:7px 14px;background:var(--accent);color:#fff;border:none;border-radius:7px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.78rem;cursor:pointer;white-space:nowrap;transition:background 0.2s;}
.btn-update:hover{background:#a07a00;}

.name-cell{font-weight:500;}
.dept-cell{font-size:0.78rem;color:var(--muted);}
.adm-cell{font-size:0.78rem;color:var(--muted);}

.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--green);margin-right:5px;animation:blink 1.4s ease infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.2;}}

.filter-bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.filter-btn{padding:7px 16px;border-radius:7px;border:1px solid var(--border);background:#fff;color:var(--muted);font-size:0.8rem;cursor:pointer;transition:all 0.2s;}
.filter-btn.active,.filter-btn:hover{border-color:var(--accent);color:var(--accent);background:rgba(200,154,0,0.05);}

@media(max-width:700px){.stats{grid-template-columns:1fr 1fr;}.page{padding:20px 14px 40px;}nav{padding:0 16px;}}
</style>
</head>
<body>

<nav>
    <div style="display:flex;align-items:center;gap:12px;">
        <div class="nav-brand">🍽 CanteenOS</div>
        <div class="admin-chip">⚙ Admin</div>
    </div>
    <div class="nav-right">
        <span class="nav-user">Logged in as <b><?php echo htmlspecialchars($_SESSION['admin']); ?></b></span>
        <a href="logout.php" class="nav-logout">Sign Out</a>
    </div>
</nav>

<div class="page">
    <div class="page-head">
        <div>
            <div class="page-title">Live Orders</div>
            <div class="page-sub"><span class="live-dot"></span>Auto-refreshes every 20 seconds</div>
        </div>
    </div>

    <div class="stats">
        <div class="stat total">
            <div class="stat-val"><?php echo $total; ?></div>
            <div class="stat-lbl">Total Orders</div>
        </div>
        <div class="stat pend">
            <div class="stat-val"><?php echo $pending; ?></div>
            <div class="stat-lbl">Pending</div>
        </div>
        <div class="stat done">
            <div class="stat-val"><?php echo $ready; ?></div>
            <div class="stat-lbl">Ready</div>
        </div>
    </div>

    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterOrders('all',this)">All Orders</button>
        <button class="filter-btn" onclick="filterOrders('Pending',this)">⏳ Pending</button>
        <button class="filter-btn" onclick="filterOrders('Ready',this)">✅ Ready</button>
    </div>

    <div class="table-wrap">
    <table id="orders-table">
        <thead>
        <tr>
            <th>Order ID</th>
            <th>Student</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $og => $g):
            $isReady = ($g['status'] === 'Ready');
        ?>
        <tr data-status="<?php echo $g['status']; ?>">
            <td><div class="order-id">#<?php echo htmlspecialchars($og); ?></div></td>
            <td>
                <div class="name-cell"><?php echo htmlspecialchars($g['name']); ?></div>
                <div class="dept-cell"><?php echo htmlspecialchars($g['department']); ?></div>
                <div class="adm-cell"><?php echo htmlspecialchars($g['admission_no']); ?></div>
            </td>
            <td class="items-cell">
                <?php foreach ($g['items'] as $item): ?>
                <div><?php echo htmlspecialchars($item['name']); ?> ×<?php echo $item['qty']; ?> — ₹<?php echo number_format($item['amt'],2); ?></div>
                <?php endforeach; ?>
            </td>
            <td class="total-cell">₹<?php echo number_format($g['total'],2); ?></td>
            <td>
                <span class="badge <?php echo $isReady ? 'badge-ready' : 'badge-pending'; ?>">
                    <?php echo $isReady ? '✅ Ready' : '⏳ Pending'; ?>
                </span>
            </td>
            <td>
                <form action="update.php" method="post" class="status-form">
                    <input type="hidden" name="order_group" value="<?php echo htmlspecialchars($og); ?>">
                    <select name="status" class="status-select">
                        <option value="Pending" <?php if($g['status']==='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Ready" <?php if($g['status']==='Ready') echo 'selected'; ?>>Ready</option>
                    </select>
                    <button type="submit" class="btn-update">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
function filterOrders(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#orders-table tbody tr').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

// Auto-refresh every 20 seconds
setTimeout(() => location.reload(), 20000);
</script>
</body>
</html>