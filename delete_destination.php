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
بررسی مالکیت مقصد
*/
$stmt = $conn->prepare(
"SELECT destinations.id
FROM destinations
INNER JOIN trips
ON destinations.trip_id = trips.id
WHERE destinations.id = ?
AND trips.user_id = ?"
);
$stmt->bind_param(
"ii",
$destination_id,
$user_id
);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
header("Location: destinations.php");
exit();
}
/*
حذف مقصد
*/
$stmt = $conn->prepare(
"DELETE FROM destinations
WHERE id = ?"
);
$stmt->bind_param(
"i",
$destination_id
);
$stmt->execute();
header("Location: destinations.php?deleted=1");
exit();
?>