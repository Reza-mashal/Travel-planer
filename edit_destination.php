<?php
session_start();
include "database.php";
if (!isset($_SESSION["user_id"])) {
header("Location: login.php");
exit();
}
$user_id = $_SESSION["user_id"];
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
header("Location: destinations.php");
exit();
}
$destination_id = (int)$_GET["id"];
/*
مقصد را فقط اگر متعلق به سفر کاربر باشد بگیریم
*/
$stmt = $conn->prepare(
"SELECT
destinations.id,
destinations.trip_id,
destinations.name,
destinations.country,
destinations.description
FROM destinations
INNER JOIN trips
ON destinations.trip_id = trips.id
WHERE destinations.id = ?
AND trips.user_id = ?"
);
$stmt->bind_param("ii", $destination_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
header("Location: destinations.php");
exit();
}
$destination = $result->fetch_assoc();
/*
گرفتن سفرهای کاربر
*/
$stmt = $conn->prepare(
"SELECT id, title
FROM trips
WHERE user_id = ?
ORDER BY id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trips = $stmt->get_result();
$message = "";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
$trip_id = (int)($_POST["trip_id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$country = trim($_POST["country"] ?? "");
$description = trim($_POST["description"] ?? "");
/*
بررسی سفر
*/
$check = $conn->prepare(
"SELECT id
FROM trips
WHERE id = ? AND user_id = ?"
);
$check->bind_param("ii", $trip_id, $user_id);
$check->execute();
$check_result = $check->get_result();
if ($trip_id <= 0 || $check_result->num_rows !== 1) {
$error = "سفر انتخاب‌شده معتبر نیست.";
} elseif ($name === "") {
$error = "نام مقصد را وارد کنید.";
} else {
$stmt = $conn->prepare(
"UPDATE destinations
SET trip_id = ?,
name = ?,
country = NULLIF(?, ''),
description = NULLIF(?, '')
WHERE id = ?"
);
$stmt->bind_param(
"isssi",
$trip_id,
$name,
$country,
$description,
$destination_id
);
if ($stmt->execute()) {
$message = "✅ مقصد با موفقیت ویرایش شد.";
$destination["trip_id"] = $trip_id;
$destination["name"] = $name;
$destination["country"] = $country;
$destination["description"] = $description;
} else {
$error = "خطا در ویرایش مقصد: " . $conn->error;
}
}
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">
<title>ویرایش مقصد | Travel Planner</title>
<style>
* {
box-sizing: border-box;
}
body {
margin: 0;
font-family: Tahoma, Arial, sans-serif;
background: #f3f4f6;
}
.container {
width: 92%;
max-width: 750px;
margin: 40px auto;
}
.card {
background: white;
padding: 30px;
border-radius: 20px;
box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
h1 {
margin-top: 0;
}
.subtitle {
color: #6b7280;
margin-bottom: 25px;
}
.message {
background: #dcfce7;
color: #166534;
padding: 14px;
border-radius: 10px;
margin-bottom: 20px;
}
.error {
background: #fee2e2;
color: #991b1b;
padding: 14px;
border-radius: 10px;
margin-bottom: 20px;
}
.form-group {
margin-bottom: 18px;
}
label {
display: block;
margin-bottom: 8px;
font-weight: bold;
}
input,
select,
textarea {
width: 100%;
padding: 13px;
border: 1px solid #d1d5db;
border-radius: 10px;
font-family: inherit;
font-size: 15px;
outline: none;
}
textarea {
min-height: 130px;
resize: vertical;
}
input:focus,
select:focus,
textarea:focus {
border-color: #2563eb;
box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
.buttons {
display: flex;
gap: 12px;
margin-top: 25px;
}
button,
.back {
flex: 1;
padding: 13px;
border: none;
border-radius: 10px;
font-family: inherit;
font-size: 15px;
font-weight: bold;
cursor: pointer;
text-align: center;
text-decoration: none;
}
button {
background: #2563eb;
color: white;
}
button:hover {
background: #1d4ed8;
}
.back {
background: #e5e7eb;
color: #374151;
}
@media (max-width: 600px) {
.container {
width: 95%;
margin: 20px auto;
}
.card {
padding: 20px;
}
.buttons {
flex-direction: column;
}
}
</style>
</head>
<body>
<?php include "menu.php"; ?>
<div class="container">
<div class="card">
<h1>✏️ ویرایش مقصد</h1>
<p class="subtitle">
اطلاعات مقصد را تغییر دهید.
</p>
<?php if ($message): ?>
<div class="message">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="error">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
<form method="POST">
<div class="form-group">
<label>
سفر *
</label>
<select name="trip_id" required>
<?php while ($trip = $trips->fetch_assoc()): ?>
<option
value="<?= $trip["id"] ?>"
<?= $destination["trip_id"] == $trip["id"] ? "selected" : "" ?>
>
<?= htmlspecialchars($trip["title"]) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group">
<label>
نام مقصد *
</label>
<input
type="text"
name="name"
value="<?= htmlspecialchars($destination["name"]) ?>"
required
>
</div>
<div class="form-group">
<label>
کشور
</label>
<input
type="text"
name="country"
value="<?= htmlspecialchars($destination["country"] ?? "") ?>"
>
</div>
<div class="form-group">
<label>
توضیحات
</label>
<textarea
name="description"
><?= htmlspecialchars($destination["description"] ?? "") ?></textarea>
</div>
<div class="buttons">
<button type="submit">
💾 ذخیره تغییرات
</button>
<a href="destinations.php"
class="back">
↩️ برگشت
</a>
</div>
</form>
</div>
</div>
</body>
</html>