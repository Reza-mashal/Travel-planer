<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

/* گرفتن تمام سفرهای موجود */
$trips = mysqli_query(
    $conn,
    "SELECT id, title, budget FROM trips ORDER BY id DESC"
);

if (!$trips) {
    die("خطا در دریافت سفرها: " . mysqli_error($conn));
}


/* =========================
   ثبت مصرف
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $trip_id = intval($_POST["trip_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $amount = floatval($_POST["amount"] ?? 0);
    $expense_date = $_POST["expense_date"] ?? "";

    if ($trip_id <= 0) {

        $message = "❌ لطفاً یک سفر را انتخاب کنید.";

    } elseif ($title == "") {

        $message = "❌ عنوان مصرف را وارد کنید.";

    } elseif ($amount <= 0) {

        $message = "❌ مبلغ مصرف را وارد کنید.";

    } elseif ($expense_date == "") {

        $message = "❌ تاریخ مصرف را انتخاب کنید.";

    } else {

        /*
         بررسی می‌کنیم سفر انتخاب‌شده
         متعلق به کاربر فعلی باشد
        */

        $check_sql = "
            SELECT id
            FROM trips
            WHERE id = $trip_id
            AND user_id = $user_id
        ";

        $check = mysqli_query($conn, $check_sql);

        if (!$check) {

            $message = "❌ خطا در بررسی سفر: " . mysqli_error($conn);

        } elseif (mysqli_num_rows($check) == 0) {

            $message = "❌ این سفر متعلق به حساب شما نیست.";

        } else {

            $safe_title = mysqli_real_escape_string($conn, $title);
            $safe_date = mysqli_real_escape_string($conn, $expense_date);

            $sql = "
                INSERT INTO expenses
                (trip_id, title, amount, expense_date)
                VALUES
                ($trip_id, '$safe_title', $amount, '$safe_date')
            ";

            if (mysqli_query($conn, $sql)) {

                header("Location: expenses.php");
                exit();

            } else {

                $message = "❌ خطا در ثبت مصرف: " . mysqli_error($conn);
            }
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

<title>ثبت مصرف | Travel Planner</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Tahoma, Arial, sans-serif;
    background: linear-gradient(135deg,#eef2ff,#f8fafc);
    min-height: 100vh;
}

.container {
    width: 95%;
    max-width: 600px;
    margin: 40px auto;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,.10);
}

h1 {
    text-align: center;
    color: #1e293b;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #334155;
}

input,
select {
    width: 100%;
    padding: 14px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 15px;
    background: white;
    outline: none;
}

input:focus,
select:focus {
    border-color: #4f46e5;
}

select {
    cursor: pointer;
}

button,
.back {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    display: block;
    text-align: center;
    text-decoration: none;
}

button {
    background: #4f46e5;
    color: white;
    cursor: pointer;
    margin-top: 10px;
}

button:hover {
    background: #4338ca;
}

.back {
    background: #e2e8f0;
    color: #334155;
    margin-top: 10px;
}

.message {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

.budget {
    display: none;
    margin-top: 10px;
    padding: 12px;
    background: #eef2ff;
    color: #3730a3;
    border-radius: 10px;
    font-weight: bold;
}

.no-trip {
    background: #fff7ed;
    color: #9a3412;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h1>💰 ثبت مصرف جدید</h1>

<div class="subtitle">
مصرف مربوط به سفر خود را ثبت کنید
</div>


<?php if ($message != "") { ?>

<div class="message">

<?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>


<?php if (mysqli_num_rows($trips) > 0) { ?>


<form method="POST">


<!-- انتخاب سفر -->

<div class="form-group">

<label for="trip_id">
✈️ انتخاب سفر
</label>

<select
name="trip_id"
id="trip_id"
required
>

<option value="">
-- یک سفر را انتخاب کنید --
</option>


<?php while ($trip = mysqli_fetch_assoc($trips)) { ?>

<option
value="<?php echo $trip["id"]; ?>"
data-budget="<?php echo $trip["budget"]; ?>"
>

<?php echo htmlspecialchars($trip["title"]); ?>

&nbsp; | &nbsp;

بودجه:

<?php echo number_format($trip["budget"]); ?>

افغانی

</option>

<?php } ?>


</select>


<div
class="budget"
id="budgetBox"
>

💰 بودجه سفر:

<span id="budgetAmount">
0
</span>

افغانی

</div>

</div>


<!-- عنوان -->

<div class="form-group">

<label for="title">
📝 عنوان مصرف
</label>

<input
type="text"
name="title"
id="title"
placeholder="مثلاً غذا، هوتل، کرایه موتر"
required
>

</div>


<!-- مبلغ -->

<div class="form-group">

<label for="amount">
💵 مبلغ
</label>

<input
type="number"
name="amount"
id="amount"
placeholder="مثلاً 500"
min="1"
step="0.01"
required
>

</div>


<!-- تاریخ -->

<div class="form-group">

<label for="expense_date">
📅 تاریخ مصرف
</label>

<input
type="date"
name="expense_date"
id="expense_date"
value="<?php echo date('Y-m-d'); ?>"
required
>

</div>


<button type="submit">
💾 ثبت مصرف
</button>


<a
href="expenses.php"
class="back"
>
↩️ برگشت
</a>


</form>


<?php } else { ?>


<div class="no-trip">

⚠️ هیچ سفری در دیتابیس پیدا نشد.

<br><br>

<a href="add_trip.php">
➕ ثبت سفر جدید
</a>

</div>


<?php } ?>


</div>

</div>


<script>

const tripSelect =
document.getElementById("trip_id");

const budgetBox =
document.getElementById("budgetBox");

const budgetAmount =
document.getElementById("budgetAmount");


if (tripSelect) {

    tripSelect.addEventListener(
        "change",
        function() {

            const option =
            this.options[this.selectedIndex];

            const budget =
            option.getAttribute(
                "data-budget"
            );

            if (budget) {

                budgetAmount.innerText =
                Number(budget)
                .toLocaleString("fa-IR");

                budgetBox.style.display =
                "block";

            } else {

                budgetBox.style.display =
                "none";
            }

        }
    );

}

</script>

</body>

</html>

بعد برو به:

http://localhost/travel_planner/add_expense.php

حالا داخل انتخاب سفر باید "kabul"‌ها دیده شوند.

اگر این نسخه "kabul" را نشان داد ولی هنگام ثبت گفت «این سفر متعلق به حساب شما نیست»، آن موقع فقط "add_trip.php" را اصلاح می‌کنیم تا سفرهای جدید با کاربر درست ذخیره شوند.