<?php
session_start();

include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);

/* گرفتن شناسه سفر */
if (isset($_GET["trip_id"]) && is_numeric($_GET["trip_id"])) {
    $trip_id = intval($_GET["trip_id"]);
} elseif (isset($_POST["trip_id"]) && is_numeric($_POST["trip_id"])) {
    $trip_id = intval($_POST["trip_id"]);
} else {
    die("شناسه سفر نامعتبر است.");
}

/* بررسی سفر */
$check = $conn->query(
    "SELECT * FROM trips
     WHERE id = $trip_id
     AND user_id = $user_id"
);

if (!$check || $check->num_rows == 0) {
    die("این سفر پیدا نشد.");
}

$trip = $check->fetch_assoc();

$message = "";

/* ثبت برنامه */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $day_number = intval($_POST["day_number"]);
    $activity = trim($_POST["activity"]);
    $location = trim($_POST["location"]);
    $activity_date = trim($_POST["activity_date"]);
    $start_time = trim($_POST["start_time"]);
    $end_time = trim($_POST["end_time"]);
    $notes = trim($_POST["notes"]);

    if ($day_number <= 0 || $activity == "") {

        $message = "شماره روز و فعالیت را وارد کنید.";

    } else {

        $activity = $conn->real_escape_string($activity);
        $location = $conn->real_escape_string($location);
        $notes = $conn->real_escape_string($notes);

        $activity_date = $activity_date != ""
            ? "'" . $conn->real_escape_string($activity_date) . "'"
            : "NULL";

        $start_time = $start_time != ""
            ? "'" . $conn->real_escape_string($start_time) . "'"
            : "NULL";

        $end_time = $end_time != ""
            ? "'" . $conn->real_escape_string($end_time) . "'"
            : "NULL";

        $sql = "INSERT INTO itinerary
                (trip_id, day_number, activity, location,
                 activity_date, start_time, end_time, notes)
                VALUES
                ($trip_id, $day_number, '$activity', '$location',
                 $activity_date, $start_time, $end_time, '$notes')";

        if ($conn->query($sql)) {

            header("Location: trips_details.php?id=trip_id");
            exit();

        } else {

            $message = "خطا در ثبت برنامه: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>افزودن برنامه سفر</title>

<link rel="stylesheet" href="output.css">

</head>

<body class="bg-slate-100 min-h-screen">

<div class="max-w-3xl mx-auto px-4 py-10">

<a href="trips_details.php?id=<?php echo $trip_id; ?>"
class="text-blue-600 hover:text-blue-800">

← برگشت به جزئیات سفر

</a>

<div class="bg-white rounded-3xl shadow-lg p-8 mt-6">

<h1 class="text-3xl font-bold mb-2">
افزودن برنامه سفر 📅
</h1>

<p class="text-slate-500 mb-8">

سفر:

<span class="font-bold text-slate-900">
<?php echo htmlspecialchars($trip["title"]); ?>
</span>

</p>

<?php if ($message != ""): ?>

<div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST" class="space-y-5">

<input
type="hidden"
name="trip_id"
value="<?php echo $trip_id; ?>">


<!-- شماره روز -->

<div>

<label class="block mb-2 font-semibold">
شماره روز
</label>

<input
type="number"
name="day_number"
min="1"
placeholder="مثلاً 1"
required
class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">

</div>


<!-- فعالیت -->

<div>

<label class="block mb-2 font-semibold">
فعالیت
</label>

<input
type="text"
name="activity"
placeholder="مثلاً بازدید از بند امیر"
required
class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">

</div>


<!-- مکان -->

<div>

<label class="block mb-2 font-semibold">
مکان
</label>

<input
type="text"
name="location"
placeholder="مثلاً بامیان"
class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">

</div>


<!-- تاریخ -->

<div>

<label class="block mb-2 font-semibold">
تاریخ
</label>

<input
type="date"
name="activity_date"
class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">

</div>


<!-- ساعت -->

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

<div>

<label class="block mb-2 font-semibold">
ساعت شروع
</label>

<input
type="time"
name="start_time"
class="w-full border border-slate-300 rounded-xl px-4 py-3">

</div>


<div>

<label class="block mb-2 font-semibold">
ساعت پایان
</label>

<input
type="time"
name="end_time"
class="w-full border border-slate-300 rounded-xl px-4 py-3">

</div>

</div>


<!-- توضیحات -->

<div>

<label class="block mb-2 font-semibold">
یادداشت
</label>

<textarea
name="notes"
rows="4"
placeholder="توضیحات مربوط به این برنامه..."
class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

</div>


<button
type="submit"
class="w-full bg-blue-600 text-white py-3 rounded-xl hover:bg-blue-700 transition font-semibold">

+ ثبت برنامه

</button>

</form>

</div>

</div>

</body>

</html>