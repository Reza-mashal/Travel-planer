<?php
session_start();
include "database.php";
if (!isset($_SESSION["user_id"])) {
header("Location: login.php");
exit();
}
$user_id = $_SESSION["user_id"];
$sql = "
SELECT
destinations.id,
destinations.trip_id,
destinations.name,
destinations.country,
destinations.description,
trips.title AS trip_title
FROM destinations
INNER JOIN trips ON destinations.trip_id = trips.id
WHERE trips.user_id = ?
ORDER BY destinations.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">
<title>مقصدها | Travel Planner</title>
<style>
* {
box-sizing: border-box;
}
body {
margin: 0;
font-family: Tahoma, Arial, sans-serif;
background: #f3f4f6;
color: #1f2937;
}
.container {
width: 92%;
max-width: 1200px;
margin: 35px auto;
}
.header {
background: white;
padding: 25px;
border-radius: 18px;
margin-bottom: 25px;
display: flex;
justify-content: space-between;
align-items: center;
gap: 15px;
box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}
.header h1 {
margin: 0 0 8px;
}
.header p {
margin: 0;
color: #6b7280;
}
.add-button {
background: #2563eb;
color: white;
text-decoration: none;
padding: 12px 18px;
border-radius: 10px;
font-weight: bold;
white-space: nowrap;
}
.add-button:hover {
background: #1d4ed8;
}
.destinations-grid {
display: grid;
grid-template-columns:
repeat(auto-fit, minmax(280px, 1fr));
gap: 20px;
}
.destination-card {
background: white;
border-radius: 18px;
overflow: hidden;
box-shadow: 0 5px 20px rgba(0,0,0,0.08);
transition: 0.3s;
}
.destination-card:hover {
transform: translateY(-5px);
}
.destination-header {
background: linear-gradient(
135deg,
#0ea5e9,
#2563eb
);
color: white;
padding: 23px;
}
.destination-header h2 {
margin: 0 0 8px;
font-size: 21px;
}
.country {
opacity: 0.9;
}
.destination-body {
padding: 20px;
}
.trip-name {
background: #eff6ff;
color: #1d4ed8;
padding: 10px;
border-radius: 9px;
margin-bottom: 15px;
font-size: 13px;
}
.description {
color: #6b7280;
line-height: 1.9;
min-height: 55px;
}
.actions {
display: flex;
gap: 8px;
margin-top: 18px;
}
.actions a {
flex: 1;
text-align: center;
text-decoration: none;
padding: 10px 5px;
border-radius: 9px;
font-size: 13px;
font-weight: bold;
}
.edit {
background: #f59e0b;
color: white;
}
.delete {
background: #dc2626;
color: white;
}
.actions a:hover {
opacity: 0.85;
}
.empty {
background: white;
padding: 50px 20px;
text-align: center;
border-radius: 18px;
box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}
.empty-icon {
font-size: 55px;
margin-bottom: 15px;
}
.empty p {
color: #6b7280;
}
@media (max-width: 600px) {
.container {
width: 95%;
margin: 20px auto;
}
.header {
flex-direction: column;
align-items: stretch;
}
.add-button {
text-align: center;
}
}
</style>
</head>
<body>
<?php include "menu.php"; ?>
<div class="container">
<div class="header">
<div>
<h1>📍 مقصدهای سفر</h1>
<p>
مقصدهای ثبت‌شده برای سفرهای شما
</p>
</div>
<a href="add_destination.php"
class="add-button">
➕ افزودن مقصد
</a>
</div>
<?php if ($result->num_rows === 0): ?>
<div class="empty">
<div class="empty-icon">📍</div>
<h2>
هنوز مقصدی ثبت نشده است
</h2>
<p>
برای سفر خود یک مقصد اضافه کنید.
</p>
<br>
<a href="add_destination.php"
class="add-button">
افزودن مقصد
</a>
</div>
<?php else: ?>
<div class="destinations-grid">
<?php while ($destination = $result->fetch_assoc()): ?>
<div class="destination-card">
<div class="destination-header">
<h2>
📍
<?= htmlspecialchars(
$destination["name"]
) ?>
</h2>
<div class="country">
🌎
<?= htmlspecialchars(
$destination["country"] ?? "نامشخص"
) ?>
</div>
</div>
<div class="destination-body">
<div class="trip-name">
🧳 سفر:
<?= htmlspecialchars(
$destination["trip_title"]
) ?>
</div>
<div class="description">
<?php
if (
!empty(
$destination["description"]
)
) {
echo nl2br(
htmlspecialchars(
$destination["description"]
)
);
} else {
echo "توضیحی برای این مقصد ثبت نشده است.";
}
?>
</div>
<div class="actions">
<a
href="edit_destination.php?id=<;?= $destination["id"] ?>"
class="edit"
>
✏️ ویرایش
</a>
<a
href="delete_destination.php?id=<;?= $destination["id"] ?>"
class="delete"
onclick="return confirm('آیا مطمئن هستید که می‌خواهید این مقصد را حذف کنید؟');"
>
🗑 حذف
</a>
</div>
</div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>