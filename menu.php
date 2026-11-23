<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="main-menu">
<div class="menu-logo">
✈️ Travel Planner
</div>
<div class="menu-links">
<a href="dashboard.php"
class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
🏠 داشبورد
</a>
<a href="trips.php"
class="<?= $current_page == 'trips.php' ? 'active' : '' ?>">
🧳 سفرهای من
</a>
<a href="add_trip.php"
class="<?= $current_page == 'add_trip.php' ? 'active' : '' ?>">
➕ ثبت سفر جدید
</a>
<a href="destinations.php"
class="<?= $current_page == 'destinations.php' ? 'active' : '' ?>">
📍 مقصدها
</a>
<a href="expenses.php"
class="<?= $current_page == 'expenses.php' ? 'active' : '' ?>">
💰 مصارف
</a>
<a href="itinerary.php"
class="<?= $current_page == 'itinerary.php' ? 'active' : '' ?>">
📅 برنامه سفر
</a>
<a href="logout.php" class="logout">
🚪 خروج
</a>
</div>
</nav>
<style>
* {
box-sizing: border-box;
}
.main-menu {
width: 100%;
background: #111827;
color: white;
padding: 15px 25px;
display: flex;
align-items: center;
justify-content: space-between;
gap: 20px;
box-shadow: 0 4px 15px rgba(0,0,0,0.15);
direction: rtl;
}
.menu-logo {
font-size: 22px;
font-weight: bold;
white-space: nowrap;
}
.menu-links {
display: flex;
align-items: center;
justify-content: center;
gap: 8px;
flex-wrap: wrap;
}
.menu-links a {
color: #e5e7eb;
text-decoration: none;
padding: 10px 14px;
border-radius: 10px;
font-size: 14px;
transition: 0.3s;
}
.menu-links a:hover {
background: #2563eb;
color: white;
transform: translateY(-2px);
}
.menu-links a.active {
background: #2563eb;
color: white;
}
.menu-links a.logout {
background: #dc2626;
color: white;
}
.menu-links a.logout:hover {
background: #b91c1c;
}
@media (max-width: 800px) {
.main-menu {
flex-direction: column;
padding: 15px;
}
.menu-links {
width: 100%;
}
.menu-links a {
font-size: 13px;
padding: 9px 11px;
}
}
</style>