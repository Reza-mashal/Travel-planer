<?php
session_start();
include "database.php";
if (!isset($_SESSION["user_id"])) {
header("Location: login.php");
exit();
}
$user_id = $_SESSION["user_id"];
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
header("Location: trips.php");
exit();
}
$trip_id = (int)$_GET["id"];
$stmt = $conn->prepare(
"SELECT id, title, destination, start_date, end_date, budget
FROM trips
WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
header("Location: trips.php");
exit();
}
$trip = $result->fetch_assoc();
$message = "";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
$title = trim($_POST["title"] ?? "");
$destination = trim($_POST["destination"] ?? "");
$start_date = $_POST["start_date"] ?? "";
$end_date = $_POST["end_date"] ?? "";
$budget = $_POST["budget"] ?? "0";
if ($title === "") {
$error = "عنوان سفر را وارد کنید.";
} elseif (
$start_date !== "" &&
$end_date !== "" &&
$end_date < $start_date
) {
$error = "تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.";
} elseif (!is_numeric($budget) || (float)$budget < 0) {
$error = "بودجه واردشده صحیح نیست.";
} else {
$budget = (float)$budget;
$stmt = $conn->prepare(
"UPDATE trips
SET title = ?,
destination = ?,
start_date = NULLIF(?, ''),
end_date = NULLIF(?, ''),
budget = ?
WHERE id = ? AND user_id = ?"
);
$stmt->bind_param(
"ssssdii",
$title,
$destination,
$start_date,
$end_date,
$budget,
$trip_id,
$user_id
);
if ($stmt->execute()) {
$message = "✅ اطلاعات سفر با موفقیت ویرایش شد.";
$trip["title"] = $title;
$trip["destination"] = $destination;
$trip["start_date"] = $start_date;
$trip["end_date"] = $end_date;
$trip["budget"] = $budget;
} else {
$error = "خطا در ویرایش سفر: " . $conn->error;
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
<title>ویرایش سفر | Travel Planner</title>
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
max-width: 800px;
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
color: #111827;
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
input {
width: 100%;
padding: 13px;
border: 1px solid #d1d5db;
border-radius: 10px;
font-family: inherit;
font-size: 15px;
outline: none;
}
input:focus {
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
.back:hover {
background: #d1d5db;
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
<h1>✏️ ویرایش سفر</h1>
<p class="subtitle">
اطلاعات سفر خود را تغییر دهید.
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
<label>عنوان سفر *</label>
<input
type="text"
name="title"
value="<?= htmlspecialchars($trip["title"]) ?>"
required
>
</div>
<div class="form-group">
<label>مقصد</label>
<input
type="text"
name="destination"
value="<?= htmlspecialchars($trip["destination"] ?? "") ?>"
placeholder="مثلاً کابل"
>
</div>
<div class="form-group">
<label>تاریخ شروع</label>
<input
type="date"
name="start_date"
value="<?= htmlspecialchars($trip["start_date"] ?? "") ?>"
>
</div>
<div class="form-group">
<label>تاریخ پایان</label>
<input
type="date"
name="end_date"
value="<?= htmlspecialchars($trip["end_date"] ?? "") ?>"
>
</div>
<div class="form-group">
<label>بودجه</label>
<input
type="number"
name="budget"
step="0.01"
min="0"
value="<?= htmlspecialchars($trip["budget"]) ?>"
>
</div>
<div class="buttons">
<button type="submit">
💾 ذخیره تغییرات
</button>
<a href="trips.php" class="back">
↩️ برگشت
</a>
</div>
</form>
</div>
</div>
</body>
</html>