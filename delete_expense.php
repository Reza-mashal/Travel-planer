<?php
session_start();
include "database.php";
if (!isset($_SESSION["user_id"])) {
header("Location: login.php");
exit();
}
$user_id = intval($_SESSION["user_id"]);
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
die("شناسه هزینه نامعتبر است.");
}
$expense_id = intval($_GET["id"]);
/* بررسی مالکیت هزینه */
$sql = "SELECT expenses.trip_id
FROM expenses
INNER JOIN trips
ON expenses.trip_id = trips.id
WHERE expenses.id = $expense_id
AND trips.user_id = $user_id";
$result = $conn->query($sql);
if (!$result) {
die("خطا در دیتابیس: " . $conn->error);
}
if ($result->num_rows == 0) {
die("این هزینه پیدا نشد.");
}
$expense = $result->fetch_assoc();
$trip_id = intval($expense["trip_id"]);
/* حذف هزینه */
$delete_sql = "DELETE FROM expenses
WHERE id = $expense_id";
if ($conn->query($delete_sql)) {
header("Location: trips_details.php?id=trip_id");
exit();
} else {
die("خطا در حذف هزینه: " . $conn->error);
}
?>