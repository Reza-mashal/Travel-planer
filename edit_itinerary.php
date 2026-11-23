<?php
session_start();
include "database.php";
if (!isset($_SESSION["user_id"])) {
header("Location: login.php");
exit();
}
$user_id = intval($_SESSION["user_id"]);
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
die("شناسه برنامه نامعتبر است.");
}
$itinerary_id = intval($_GET["id"]);
/* دریافت برنامه و بررسی مالکیت */
$sql = "SELECT itinerary.*, trips.user_id
FROM itinerary
INNER JOIN trips ON itinerary.trip_id = trips.id
WHERE itinerary.id = $itinerary_id
AND trips.user_id = $user_id";
$result = $conn->query($sql);
if (!$result) {
die("خطا در دیتابیس: " . $conn->error);
}
if ($result->num_rows == 0) {
die("این برنامه پیدا نشد.");
}
$item = $result->fetch_assoc();
$trip_id = intval($item["trip_id"]);
/* ویرایش برنامه */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$day_number = intval($_POST["day_number"]);
$activity = $conn->real_escape_string(
$_POST["activity"]
);
$location = $conn->real_escape_string(
$_POST["location"]
);
$activity_date = $conn->real_escape_string(
$_POST["activity_date"]
);
$start_time = $conn->real_escape_string(
$_POST["start_time"]
);
$end_time = $conn->real_escape_string(
$_POST["end_time"]
);
$notes = $conn->real_escape_string(
$_POST["notes"]
);
$update_sql = "UPDATE itinerary SET
day_number = $day_number,
activity = '$activity',
location = '$location',
activity_date = '$activity_date',
start_time = '$start_time',
end_time = '$end_time',
notes = '$notes'
WHERE id = $itinerary_id";
if ($conn->query($update_sql)) {
header(
"Location: trips_details.php?id=trip_id"
);
exit();
} else {
die(
"خطا در ویرایش برنامه: "
. $conn->error
);
}
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">
<title>ویرایش برنامه سفر</title>
<link rel="stylesheet" href="output.css">
</head>
<body class="bg-slate-100 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-10">
<div class="bg-white
rounded-2xl
shadow-lg
p-6">
<h1 class="text-3xl
font-bold
mb-6">
✏️ ویرایش برنامه سفر
</h1>
<form method="POST" class="space-y-5">
<!-- روز -->
<div>
<label class="block
font-semibold
mb-2">
شماره روز
</label>
<input
type="number"
name="day_number"
min="1"
value="<?php echo htmlspecialchars($item["day_number"]); ?>"
required
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- فعالیت -->
<div>
<label class="block
font-semibold
mb-2">
فعالیت
</label>
<input
type="text"
name="activity"
value="<?php echo htmlspecialchars($item["activity"]); ?>"
required
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- مکان -->
<div>
<label class="block
font-semibold
mb-2">
مکان
</label>
<input
type="text"
name="location"
value="<?php echo htmlspecialchars($item["location"]); ?>"
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- تاریخ -->
<div>
<label class="block
font-semibold
mb-2">
تاریخ
</label>
<input
type="date"
name="activity_date"
value="<?php echo htmlspecialchars($item["activity_date"]); ?>"
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- زمان شروع -->
<div>
<label class="block
font-semibold
mb-2">
زمان شروع
</label>
<input
type="time"
name="start_time"
value="<?php echo htmlspecialchars($item["start_time"]); ?>"
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- زمان پایان -->
<div>
<label class="block
font-semibold
mb-2">
زمان پایان
</label>
<input
type="time"
name="end_time"
value="<?php echo htmlspecialchars($item["end_time"]); ?>"
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
>
</div>
<!-- یادداشت -->
<div>
<label class="block
font-semibold
mb-2">
یادداشت
</label>
<textarea
name="notes"
rows="5"
class="w-full
border border-slate-300
rounded-xl
px-4 py-3
outline-none
focus:ring-2
focus:ring-purple-500"
><?php echo htmlspecialchars($item["notes"]); ?></textarea>
</div>
<!-- ذخیره -->
<button
type="submit"
class="w-full
bg-purple-600
text-white
py-3
rounded-xl
font-semibold
hover:bg-purple-700
transition"
>
💾 ذخیره تغییرات
</button>
</form>
<!-- برگشت -->
<a
href="trips_details.php?id=<?php echo $trip_id; ?>"
class="block
text-center
mt-4
bg-slate-200
py-3
rounded-xl
hover:bg-slate-300
transition"
>
← برگشت به جزئیات سفر
</a>
</div>
</div>
</body>
</html>