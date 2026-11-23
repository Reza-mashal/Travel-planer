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
/*
اول بررسی می‌کنیم که این سفر متعلق به کاربر فعلی باشد.
*/
$stmt = $conn->prepare(
"SELECT id
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
/*
شروع تراکنش
*/
$conn->begin_transaction();
try {
// حذف مقصدهای مربوط به سفر
$stmt = $conn->prepare(
"DELETE FROM destinations WHERE trip_id = ?"
);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
// حذف مصارف مربوط به سفر
$stmt = $conn->prepare(
"DELETE FROM expenses WHERE trip_id = ?"
);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
// حذف برنامه سفر
$stmt = $conn->prepare(
"DELETE FROM itinerary WHERE trip_id = ?"
);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
// حذف خود سفر
$stmt = $conn->prepare(
"DELETE FROM trips
WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
// ثبت نهایی
$conn->commit();
header("Location: trips.php?deleted=1");
exit();
} catch (Exception $e) {
$conn->rollback();
die("خطا در حذف سفر: " . $e->getMessage());
}
?>