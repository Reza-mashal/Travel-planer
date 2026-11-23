<?php
require_once "province_data.php";
if (!isset($_GET["province"])) {
header("Location: provinces.php");
exit();
}
$name = urldecode($_GET["province"]);
if (!isset($provinces[$name])) {
header("Location: provinces.php");
exit();
}
$province = $provinces[$name];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($name); ?> | Travel Planner</title>
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
max-width: 1200px;
margin: auto;
}
.back {
display: inline-flex;
text-decoration: none;
color: white;
padding: 12px 20px;
margin-bottom: 25px;
border-radius: 14px;
background: rgba(255,255,255,.1);
border: 1px solid rgba(255,255,255,.15);
backdrop-filter: blur(12px);
transition: .3s;
animation: slideDown .7s ease;
}
.back:hover {
transform: translateY(-3px);
background: rgba(255,255,255,.18);
}
.hero {
position: relative;
overflow: hidden;
border-radius: 30px;
background: rgba(255,255,255,.08);
border: 1px solid rgba(255,255,255,.15);
backdrop-filter: blur(18px);
box-shadow: 0 30px 80px rgba(0,0,0,.35);
animation: fadeUp .8s ease;
}
.hero-image {
height: 400px;
position: relative;
background:
linear-gradient(135deg, #334155, #1e293b);
}
.hero-image img {
width: 100%;
height: 100%;
object-fit: cover;
}
.hero-overlay {
position: absolute;
inset: 0;
background:
linear-gradient(
to top,
rgba(15,23,42,.95),
rgba(15,23,42,.1)
);
}
.hero-title {
position: absolute;
right: 35px;
bottom: 30px;
left: 35px;
}
.hero-title h1 {
margin: 0 0 10px;
font-size: 48px;
font-weight: 900;
text-shadow: 0 5px 20px rgba(0,0,0,.5);
}
.hero-title p {
margin: 0;
color: #cbd5e1;
font-size: 17px;
}
.content {
padding: 30px;
}
.description {
font-size: 17px;
line-height: 2;
color: #e2e8f0;
margin-bottom: 30px;
background: rgba(255,255,255,.05);
border-radius: 18px;
padding: 20px;
border: 1px solid rgba(255,255,255,.08);
}
.info-grid {
display: grid;
grid-template-columns: repeat(3, 1fr);
gap: 18px;
}
.info-card {
padding: 22px;
border-radius: 20px;
background: rgba(255,255,255,.06);
border: 1px solid rgba(255,255,255,.1);
transition: .35s;
animation: fadeUp .8s ease both;
}
.info-card:hover {
transform: translateY(-7px);
background: rgba(255,255,255,.1);
border-color: rgba(255,255,255,.25);
box-shadow: 0 15px 40px rgba(0,0,0,.25);
}
.info-icon {
font-size: 28px;
margin-bottom: 12px;
}
.info-title {
color: #94a3b8;
font-size: 14px;
margin-bottom: 7px;
}
.info-value {
font-size: 18px;
font-weight: 700;
line-height: 1.8;
}
.section {
margin-top: 25px;
padding: 25px;
border-radius: 22px;
background: rgba(255,255,255,.06);
border: 1px solid rgba(255,255,255,.1);
animation: fadeUp 1s ease;
}
.section h2 {
margin-top: 0;
margin-bottom: 18px;
font-size: 24px;
}
.section p {
line-height: 2;
color: #cbd5e1;
}
.tags {
display: flex;
flex-wrap: wrap;
gap: 10px;
}
.tag {
padding: 9px 15px;
border-radius: 30px;
background: rgba(255,255,255,.08);
border: 1px solid rgba(255,255,255,.1);
color: #e2e8f0;
transition: .3s;
}
.tag:hover {
transform: translateY(-3px);
background: rgba(255,255,255,.15);
}
@keyframes fadeUp {
from {
opacity: 0;
transform: translateY(30px);
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
@media(max-width:900px) {
.info-grid {
grid-template-columns: repeat(2, 1fr);
}
.hero-image {
height: 330px;
}
.hero-title h1 {
font-size: 38px;
}
}
@media(max-width:600px) {
.page {
padding: 15px;
}
.hero-image {
height: 280px;
}
.hero-title {
right: 20px;
left: 20px;
bottom: 20px;
}
.hero-title h1 {
font-size: 30px;
}
.hero-title p {
font-size: 14px;
}
.content {
padding: 18px;
}
.info-grid {
grid-template-columns: 1fr;
}
.description {
font-size: 15px;
}
}
</style>
</head>
<body>
<div class="page">
<div class="container">
<a href="provinces.php" class="back">
← برگشت به ولایت‌ها
</a>
<div class="hero">
<div class="hero-image">
<?php if (!empty($province["image"])): ?>
<img
src="<?php echo htmlspecialchars($province["image"]); ?>"
alt="<?php echo htmlspecialchars($name); ?>"
onerror="this.style.display='none';"
>
<?php endif; ?>
<div class="hero-overlay"></div>
<div class="hero-title">
<h1>
<?php echo htmlspecialchars($name); ?>
</h1>
<p>
اطلاعات کامل ولایت <?php echo htmlspecialchars($name); ?>
</p>
</div>
</div>
<div class="content">
<div class="description">
<?php
echo htmlspecialchars(
$province["description"] ?? "اطلاعاتی برای این ولایت ثبت نشده است."
);
?>
</div>
<div class="info-grid">
<div class="info-card">
<div class="info-icon">🏛️</div>
<div class="info-title">
مرکز ولایت
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["capital"] ?? "-"); ?>
</div>
</div>
<div class="info-card">
<div class="info-icon">📍</div>
<div class="info-title">
منطقه
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["region"] ?? "-"); ?>
</div>
</div>
<div class="info-card">
<div class="info-icon">📐</div>
<div class="info-title">
مساحت
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["area"] ?? "-"); ?>
</div>
</div>
<div class="info-card">
<div class="info-icon">👥</div>
<div class="info-title">
نفوس
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["population"] ?? "-"); ?>
</div>
</div>
<div class="info-card">
<div class="info-icon">🏘️</div>
<div class="info-title">
ولسوالی‌ها
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["districts"] ?? "-"); ?>
</div>
</div>
<div class="info-card">
<div class="info-icon">🌤️</div>
<div class="info-title">
آب‌وهوا
</div>
<div class="info-value">
<?php echo htmlspecialchars($province["weather"] ?? "-"); ?>
</div>
</div>
</div>
<div class="section">
<h2>🗣️ زبان‌ها</h2>
<div class="tags">
<?php
$languages = $province["languages"] ?? "";
if (is_array($languages)):
foreach ($languages as $language):
?>
<span class="tag">
<?php echo htmlspecialchars($language); ?>
</span>
<?php
endforeach;
else:
?>
<span class="tag">
<?php echo htmlspecialchars($languages); ?>
</span>
<?php endif; ?>
</div>
</div>
<div class="section">
<h2>⭐ معروف به</h2>
<p>
<?php echo htmlspecialchars($province["famous"] ?? "-"); ?>
</p>
</div>
<div class="section">
<h2>🍲 غذاهای معروف</h2>
<p>
<?php echo htmlspecialchars($province["food"] ?? "-"); ?>
</p>
</div>
<div class="section">
<h2>🛍️ محصولات</h2>
<p>
<?php echo htmlspecialchars($province["products"] ?? "-"); ?>
</p>
</div>
<div class="section">
<h2>✈️ اطلاعات سفر</h2>
<p>
<?php echo htmlspecialchars($province["travel"] ?? "-"); ?>
</p>
</div>
</div>
</div>
</div>
</div>
</body>
</html>