<?php
require_once "province_data.php";
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ولایت‌های افغانستان | Travel Planner</title>
<link rel="stylesheet" href="src/output.css">
<style>
* {
box-sizing: border-box;
}
body {
margin: 0;
font-family: Tahoma, Arial, sans-serif;
background:
radial-gradient(circle at 10% 10%, rgba(99,102,241,.18), transparent 30%),
radial-gradient(circle at 90% 20%, rgba(168,85,247,.15), transparent 30%),
linear-gradient(135deg, #0f172a, #111827, #1e1b4b);
min-height: 100vh;
color: white;
}
.page {
min-height: 100vh;
padding: 30px;
}
.container {
max-width: 1400px;
margin: auto;
}
.topbar {
display: flex;
align-items: center;
justify-content: space-between;
gap: 20px;
margin-bottom: 35px;
animation: slideDown .7s ease;
}
.title-box h1 {
margin: 0 0 10px;
font-size: 36px;
font-weight: 800;
}
.title-box p {
margin: 0;
color: #cbd5e1;
font-size: 15px;
}
.back-btn {
text-decoration: none;
color: white;
padding: 12px 20px;
border-radius: 14px;
background: rgba(255,255,255,.1);
border: 1px solid rgba(255,255,255,.15);
backdrop-filter: blur(12px);
transition: .3s;
}
.back-btn:hover {
transform: translateY(-3px);
background: rgba(255,255,255,.18);
}
.grid {
display: grid;
grid-template-columns: repeat(4, 1fr);
gap: 22px;
}
.province-card {
position: relative;
overflow: hidden;
min-height: 270px;
border-radius: 24px;
background: rgba(255,255,255,.08);
border: 1px solid rgba(255,255,255,.13);
backdrop-filter: blur(16px);
box-shadow: 0 20px 50px rgba(0,0,0,.25);
text-decoration: none;
color: white;
transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
animation: fadeUp .7s ease both;
}
.province-card:hover {
transform: translateY(-10px) scale(1.015);
box-shadow: 0 30px 70px rgba(0,0,0,.45);
border-color: rgba(255,255,255,.35);
}
.province-image {
height: 155px;
overflow: hidden;
background: linear-gradient(135deg, #334155, #1e293b);
}
.province-image img {
width: 100%;
height: 100%;
object-fit: cover;
transition: transform .6s ease;
}
.province-card:hover .province-image img {
transform: scale(1.12);
}
.image-overlay {
position: absolute;
inset: 0 0 auto 0;
height: 155px;
background: linear-gradient(to bottom, transparent 40%, rgba(15,23,42,.75));
}
.province-info {
padding: 18px;
}
.province-name {
font-size: 22px;
font-weight: 800;
margin-bottom: 8px;
}
.province-region {
color: #cbd5e1;
font-size: 14px;
}
.arrow {
position: absolute;
left: 18px;
bottom: 18px;
width: 36px;
height: 36px;
border-radius: 50%;
display: flex;
align-items: center;
justify-content: center;
background: rgba(255,255,255,.12);
transition: .3s;
}
.province-card:hover .arrow {
transform: translateX(-5px);
background: rgba(255,255,255,.22);
}
@keyframes fadeUp {
from {
opacity: 0;
transform: translateY(25px);
}
to {
opacity: 1;
transform: translateY(0);
}
}
@keyframes slideDown {
from {
opacity: 0;
transform: translateY(-25px);
}
to {
opacity: 1;
transform: translateY(0);
}
}
@media(max-width:1100px) {
.grid {
grid-template-columns: repeat(3, 1fr);
}
}
@media(max-width:800px) {
.page {
padding: 20px;
}
.grid {
grid-template-columns: repeat(2, 1fr);
}
.title-box h1 {
font-size: 28px;
}
}
@media(max-width:520px) {
.topbar {
flex-direction: column;
align-items: stretch;
}
.back-btn {
text-align: center;
}
.grid {
grid-template-columns: 1fr;
}
.province-card {
min-height: 280px;
}
}
</style>
</head>
<body>
<div class="page">
<div class="container">
<div class="topbar">
<div class="title-box">
<h1>🇦🇫 ولایت‌های افغانستان</h1>
<p>ولایت مورد نظر خود را انتخاب کنید و اطلاعات آن را مشاهده نمایید.</p>
</div>
<a href="dashboard.php" class="back-btn">
← برگشت به داشبورد
</a>
</div>
<div class="grid">
<?php foreach ($provinces as $name => $province): ?>
<a
href="province.php?province=<?php echo rawurlencode($name); ?>"
class="province-card"
>
<div class="province-image">
<img
src="<?php echo htmlspecialchars($province['image']); ?>"
alt="<?php echo htmlspecialchars($name); ?>"
onerror="this.style.display='none';"
>
</div>
<div class="image-overlay"></div>
<div class="province-info">
<div class="province-name">
<?php echo htmlspecialchars($name); ?>
</div>
<div class="province-region">
📍 <?php echo htmlspecialchars($province['region']); ?>
</div>
</div>
<div class="arrow">
←
</div>
</a>
<?php endforeach; ?>
</div>
</div>
</div>
</body>
</html>