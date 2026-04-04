<?php
session_start();
include 'db.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Canteen</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #ffffff;
    --surface: #f7f7f5;
    --surface2: #efefed;
    --border: #e4e4e0;
    --accent: #c89a00;
    --accent2: #e8533a;
    --text: #1a1a1a;
    --muted: #888;
    --green: #16a34a;
    --radius: 12px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

/* NAV */
nav {
    position: sticky; top: 0; z-index: 100;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    display: flex; align-items: center; justify-content: space-between;
    height: 64px;
}
.nav-brand {
    font-family: 'Syne', sans-serif;
    font-weight: 800; font-size: 1.3rem;
    color: var(--accent);
    letter-spacing: -0.5px;
}
.nav-brand span { color: var(--text); font-weight: 400; }
.nav-links { display: flex; gap: 12px; align-items: center; }
.nav-links a {
    text-decoration: none; font-size: 0.875rem;
    padding: 8px 16px; border-radius: 8px;
    transition: all 0.2s;
}
.nav-user {
    color: var(--text); background: var(--surface);
    border: 1px solid var(--border);
}
.nav-user:hover { border-color: var(--accent); color: var(--accent); }
.nav-admin {
    color: #fff; background: var(--accent);
    font-weight: 600;
}
.nav-admin:hover { background: #a07a00; }
.nav-logout { color: var(--muted); }
.nav-logout:hover { color: var(--accent2); }
.user-chip {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.875rem; color: var(--muted);
}
.user-chip span {
    background: var(--surface); border: 1px solid var(--border);
    padding: 4px 12px; border-radius: 20px;
    color: var(--text);
}

/* HERO */
.hero {
    padding: 60px 40px 40px;
    max-width: 1100px; margin: auto;
}
.hero-tag {
    display: inline-block;
    background: rgba(200,154,0,0.1);
    color: var(--accent); border: 1px solid rgba(200,154,0,0.3);
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    padding: 5px 14px; border-radius: 20px;
    margin-bottom: 16px;
}
.hero h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800; line-height: 1.1;
    margin-bottom: 10px;
}
.hero h1 em { color: var(--accent); font-style: normal; }
.hero p { color: var(--muted); font-size: 1rem; }

/* MAIN LAYOUT */
.layout {
    max-width: 1100px; margin: 0 auto;
    padding: 0 40px 60px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 32px;
    align-items: start;
}

/* CARD */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.card-head {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.card-head h3 {
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 1rem;
}
.card-body { padding: 24px; }

/* STUDENT DETAILS */
.field { margin-bottom: 16px; }
.field label {
    display: block; font-size: 0.75rem;
    text-transform: uppercase; letter-spacing: 1px;
    color: var(--muted); margin-bottom: 6px; font-weight: 500;
}
.field input {
    width: 100%; padding: 11px 14px;
    background: #fff; border: 1px solid var(--border);
    border-radius: 8px; color: var(--text);
    font-family: 'DM Sans', sans-serif; font-size: 0.9rem;
    transition: border-color 0.2s;
}
.field input:focus { outline: none; border-color: var(--accent); }
.field input::placeholder { color: #bbb; }
.field input[readonly] {
    opacity: 0.6; cursor: not-allowed;
    background: var(--surface2);
}

/* MENU GRID */
.section-title {
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 1.1rem;
    margin: 28px 0 16px;
    display: flex; align-items: center; gap: 8px;
}
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}
.item-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    transition: all 0.2s;
    position: relative;
}
.item-card:hover { border-color: rgba(200,154,0,0.5); transform: translateY(-2px); }
.item-card.selected { border-color: var(--accent); background: rgba(200,154,0,0.05); }
.item-name {
    font-family: 'Syne', sans-serif;
    font-weight: 600; font-size: 0.95rem;
    margin-bottom: 4px;
}
.item-price {
    color: var(--accent); font-size: 0.9rem;
    font-weight: 600; margin-bottom: 10px;
}
.stock-badge {
    font-size: 0.72rem; color: var(--green);
    background: rgba(62,207,110,0.1);
    border: 1px solid rgba(62,207,110,0.2);
    padding: 3px 8px; border-radius: 10px;
    margin-bottom: 12px; display: inline-block;
}
.out-badge {
    font-size: 0.72rem; color: var(--accent2);
    background: rgba(232,83,58,0.1);
    border: 1px solid rgba(232,83,58,0.2);
    padding: 3px 8px; border-radius: 10px;
    margin-bottom: 12px; display: inline-block;
}
.qty-wrap {
    display: flex; align-items: center; gap: 8px;
}
.qty-wrap input[type=number] {
    width: 70px; padding: 7px 10px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 7px; color: var(--text);
    font-size: 0.9rem; text-align: center;
}
.qty-wrap input:focus { outline: none; border-color: var(--accent); }
.qty-label { font-size: 0.75rem; color: var(--muted); }

/* CART / SIDEBAR */
.sidebar { position: sticky; top: 80px; }
.cart-items { margin-bottom: 16px; }
.cart-empty { color: var(--muted); font-size: 0.875rem; text-align: center; padding: 20px 0; }
.cart-row {
    display: flex; justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.875rem;
}
.cart-row:last-child { border-bottom: none; }
.cart-item-name { font-weight: 500; }
.cart-item-meta { color: var(--muted); font-size: 0.75rem; margin-top: 2px; }
.cart-item-total { color: var(--accent); font-weight: 600; white-space: nowrap; }
.cart-total-row {
    display: flex; justify-content: space-between;
    padding: 14px 0 0; font-size: 1rem;
    border-top: 1px solid var(--border);
}
.cart-total-row strong { font-family: 'Syne', sans-serif; font-size: 1.1rem; }
.total-amt { color: var(--accent); font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.1rem; }

/* ERROR */
.form-error {
    background: rgba(232,83,58,0.1);
    border: 1px solid rgba(232,83,58,0.3);
    color: #ff7a64; border-radius: 8px;
    padding: 10px 14px; font-size: 0.875rem;
    margin-top: 12px; display: none;
}
.form-error.show { display: block; }

/* BUTTON */
.btn-place {
    width: 100%; padding: 14px;
    background: var(--accent); color: #fff;
    border: none; border-radius: 10px;
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 1rem;
    cursor: pointer; margin-top: 16px;
    transition: all 0.2s; letter-spacing: 0.3px;
}
.btn-place:hover { background: #a07a00; transform: translateY(-1px); }
.btn-place:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* LOGIN PROMPT */
.login-prompt {
    background: rgba(200,154,0,0.06);
    border: 1px solid rgba(200,154,0,0.2);
    border-radius: var(--radius); padding: 20px;
    text-align: center; margin-top: 0;
}
.login-prompt p { color: var(--muted); font-size: 0.875rem; margin-bottom: 12px; }
.login-prompt .btn-login-prompt {
    display: inline-block; padding: 10px 24px;
    background: var(--accent); color: #fff;
    border-radius: 8px; text-decoration: none;
    font-family: 'Syne', sans-serif; font-weight: 700;
    font-size: 0.875rem; transition: background 0.2s;
}
.login-prompt .btn-login-prompt:hover { background: #a07a00; }

/* MY ORDERS SECTION */
.my-orders-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text); text-decoration: none;
    padding: 9px 18px; border-radius: 8px;
    font-size: 0.875rem; transition: all 0.2s;
}
.my-orders-btn:hover { border-color: var(--accent); color: var(--accent); }

@media(max-width: 768px) {
    .layout { grid-template-columns: 1fr; padding: 0 20px 40px; }
    .hero { padding: 40px 20px 24px; }
    nav { padding: 0 20px; }
    .sidebar { position: static; }
}
</style>
</head>
<body>

<nav>
    <div class="nav-brand">🍽 Canteen<span>OS</span></div>
    <div class="nav-links">
        <?php if ($user): ?>
            <div class="user-chip">Hi, <span><?php echo htmlspecialchars($user['name']); ?></span></div>
            <a href="my_orders.php" class="nav-user">📋 My Orders</a>
            <a href="user_logout.php" class="nav-logout">Sign Out</a>
        <?php else: ?>
            <a href="user_login.php" class="nav-user">👤 Student Login</a>
            <a href="user_register.php" class="nav-user">Register</a>
        <?php endif; ?>
        <a href="login.php" class="nav-admin">⚙ Admin</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-tag">🟢 Live Today</div>
    <h1>Order your <em>meal,</em><br>skip the queue.</h1>
    <p>Fresh daily menu — place your order &amp; get notified when it's ready.</p>
</div>

<div class="layout">

<!-- LEFT: FORM -->
<div>
<form action="order.php" method="post" id="order-form">

<div class="card">
    <div class="card-head"><span>🎓</span><h3>Student Details</h3></div>
    <div class="card-body">
        <?php if ($user): ?>
        <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
        </div>
        <div class="field">
            <label>Admission Number</label>
            <input type="text" name="admission_no" value="<?php echo htmlspecialchars($user['admission_no']); ?>" readonly>
        </div>
        <div class="field">
            <label>Department</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" readonly>
        </div>
        <?php else: ?>
        <div class="field">
            <label>Full Name</label>
            <input type="text" id="student-name" name="name" placeholder="Your full name">
        </div>
        <div class="field">
            <label>Admission Number</label>
            <input type="text" id="admission-no" name="admission_no" placeholder="e.g. CS2024001">
        </div>
        <div class="field">
            <label>Department</label>
            <input type="text" id="department" name="department" placeholder="e.g. Computer Science">
        </div>
        <?php endif; ?>
        <div class="form-error" id="form-error"></div>
    </div>
</div>

<div class="section-title">🍱 Today's Menu</div>
<div class="menu-grid">
<?php
$result = $conn->query("SELECT * FROM menu ORDER BY item_name");
while ($row = $result->fetch_assoc()):
    $eid = htmlspecialchars($row['item_id']);
    $ename = htmlspecialchars($row['item_name']);
    $eprice = htmlspecialchars($row['price']);
    $stock = (int)$row['stock'];
?>
<div class="item-card" id="card-<?php echo $eid; ?>">
    <div class="item-name"><?php echo $ename; ?></div>
    <div class="item-price">₹<?php echo number_format($eprice,2); ?></div>
    <?php if ($stock > 0): ?>
    <div class="stock-badge">✓ <?php echo $stock; ?> left</div>
    <div class="qty-wrap">
        <input type="number"
               name="qty[<?php echo $eid; ?>]"
               min="0" max="<?php echo $stock; ?>" value="0"
               data-id="<?php echo $eid; ?>"
               data-name="<?php echo $ename; ?>"
               data-price="<?php echo $eprice; ?>"
               class="qty-input">
        <span class="qty-label">qty</span>
    </div>
    <?php else: ?>
    <div class="out-badge">✗ Out of Stock</div>
    <?php endif; ?>
</div>
<?php endwhile; ?>
</div>

</form>
</div>

<!-- RIGHT: CART -->
<div class="sidebar">
    <?php if ($user): ?>
    <div class="card">
        <div class="card-head"><span>🛒</span><h3>Your Order</h3></div>
        <div class="card-body">
            <div class="cart-items" id="cart-items">
                <div class="cart-empty" id="cart-empty">No items selected yet.</div>
            </div>
            <div class="cart-total-row">
                <span>Total</span>
                <span class="total-amt" id="grand-total">₹0.00</span>
            </div>
            <button type="submit" form="order-form" class="btn-place" id="place-btn" disabled>
                Place Order →
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-head"><span>🛒</span><h3>Your Order</h3></div>
        <div class="card-body">
            <div class="cart-items" id="cart-items">
                <div class="cart-empty" id="cart-empty">No items selected yet.</div>
            </div>
            <div class="cart-total-row">
                <span>Total</span>
                <span class="total-amt" id="grand-total">₹0.00</span>
            </div>
        </div>
    </div>
    <div class="login-prompt" style="margin-top:16px;">
        <p>Login to place your order and get notified when it's ready!</p>
        <a href="user_login.php" class="btn-login-prompt">Login / Register →</a>
    </div>
    <?php endif; ?>
</div>

</div>

<script>
const inputs = document.querySelectorAll('.qty-input');
const cartItems = document.getElementById('cart-items');
const cartEmpty = document.getElementById('cart-empty');
const grandTotal = document.getElementById('grand-total');
const placeBtn = document.getElementById('place-btn');
const cartData = {};

inputs.forEach(input => {
    input.addEventListener('input', () => {
        const id = input.dataset.id;
        const name = input.dataset.name;
        const price = parseFloat(input.dataset.price);
        const qty = parseInt(input.value) || 0;
        const card = document.getElementById('card-' + id);

        if (qty > 0) {
            cartData[id] = { name, price, qty };
            card.classList.add('selected');
        } else {
            delete cartData[id];
            card.classList.remove('selected');
        }
        renderCart();
    });
});

function renderCart() {
    const keys = Object.keys(cartData);
    if (keys.length === 0) {
        cartItems.innerHTML = '<div class="cart-empty">No items selected yet.</div>';
        grandTotal.textContent = '₹0.00';
        if (placeBtn) placeBtn.disabled = true;
        return;
    }
    let html = '';
    let total = 0;
    keys.forEach(id => {
        const { name, price, qty } = cartData[id];
        const sub = price * qty;
        total += sub;
        html += `<div class="cart-row">
            <div>
                <div class="cart-item-name">${name}</div>
                <div class="cart-item-meta">${qty} × ₹${price.toFixed(2)}</div>
            </div>
            <div class="cart-item-total">₹${sub.toFixed(2)}</div>
        </div>`;
    });
    cartItems.innerHTML = html;
    grandTotal.textContent = '₹' + total.toFixed(2);
    if (placeBtn) placeBtn.disabled = false;
}

// Form validation
const form = document.getElementById('order-form');
const errorBox = document.getElementById('form-error');

form && form.addEventListener('submit', function(e) {
    errorBox.classList.remove('show');
    const name = document.getElementById('student-name');
    const adm = document.getElementById('admission-no');
    const dept = document.getElementById('department');

    if (name && !name.value.trim()) {
        e.preventDefault();
        errorBox.textContent = 'Please enter your name.';
        errorBox.classList.add('show'); return;
    }
    if (adm && !adm.value.trim()) {
        e.preventDefault();
        errorBox.textContent = 'Please enter your admission number.';
        errorBox.classList.add('show'); return;
    }
    if (dept && !dept.value.trim()) {
        e.preventDefault();
        errorBox.textContent = 'Please enter your department.';
        errorBox.classList.add('show'); return;
    }
    if (Object.keys(cartData).length === 0) {
        e.preventDefault();
        errorBox.textContent = 'Please select at least one item.';
        errorBox.classList.add('show');
    }
});
</script>
</body>
</html>