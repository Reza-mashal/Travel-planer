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
/* بررسی مالکیت برنامه */
$sql = "SELECT itinerary.trip_id
FROM itinerary
INNER JOIN trips
ON itinerary.trip_id = trips.id
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
/* حذف برنامه */
$delete_sql = "DELETE FROM itinerary
WHERE id = $itinerary_id";
if ($conn->query($delete_sql)) {
header(
"Location: trips_details.php?id=trip_id"
);
exit();
} else {
die(
"خطا در حذف برنامه: "
. $conn->error
);
}
?>