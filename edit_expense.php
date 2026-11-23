<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: expenses.php");
    exit();
}

/* گرفتن اطلاعات مصرف */
$sql = "
    SELECT
        e.id,
        e.trip_id,
        e.title,
        e.amount,
        e.expense_date,
        t.title AS trip_title,
        t.budget
    FROM expenses e
    INNER JOIN trips t ON e.trip_id = t.id
    WHERE e.id = $id
    AND t.user_id = $user_id
";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: expenses.php");
    exit();
}

$expense = mysqli_fetch_assoc($result);


/* گرفتن سفرهای کاربر */
$trips = mysqli_query(
    $conn,
    "SELECT id, title, budget
     FROM trips
     WHERE user_id = $user_id
     ORDER BY id DESC"
);


/* ویرایش */
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

        $message = "❌ مبلغ باید بیشتر از صفر باشد.";

    } elseif ($expense_date == "") {

        $message = "❌ تاریخ را انتخاب کنید.";

    } else {

        /* بررسی سفر */
        $check = mysqli_query(
            $conn,
            "SELECT id
             FROM trips
             WHERE id = $trip_id
             AND user_id = $user_id"
        );

        if (!$check || mysqli_num_rows($check) == 0) {

            $message = "❌ این سفر متعلق به حساب شما نیست.";

        } else {

            $safe_title =
                mysqli_real_escape_string($conn, $title);

            $safe_date =
                mysqli_real_escape_string($conn, $expense_date);

            $update = "
                UPDATE expenses
                SET
                    trip_id = $trip_id,
                    title = '$safe_title',
                    amount = $amount,
                    expense_date = '$safe_date'
                WHERE id = $id
            ";

            if (mysqli_query($conn, $update)) {

                header("Location: expenses.php");
                exit();

            } else {

                $message =
                    "❌ خطا در ویرایش: " .
                    mysqli_error($conn);
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

<title>ویرایش مصرف | Travel Planner</title>

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
    box-shadow: 0 10px 30px rgba(0,0,0,.1);
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
    outline: none;
    background: white;
}

input:focus,
select:focus {
    border-color: #4f46e5;
}

.budget {
    margin-top: 10px;
    padding: 12px;
    background: #eef2ff;
    color: #3730a3;
    border-radius: 10px;
    font-weight: bold;
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

</style>

</head>

<body>

<div class="container">

<div class="card">

<h1>✏️ ویرایش مصرف</h1>

<div class="subtitle">
اطلاعات مصرف را ویرایش کنید
</div>


<?php if ($message != "") { ?>

<div class="message">
<?php echo htmlspecialchars($message); ?>
</div>

<?php } ?>


<form method="POST">


<div class="form-group">

<label>
✈️ انتخاب سفر
</label>

<select name="trip_id" id="trip_id" required>

<?php while ($trip = mysqli_fetch_assoc($trips)) { ?>

<option
value="<?php echo $trip["id"]; ?>"
data-budget="<?php echo $trip["budget"]; ?>"
<?php
if ($trip["id"] == $expense["trip_id"]) {
    echo "selected";
}
?>
>

<?php echo htmlspecialchars($trip["title"]); ?>

— بودجه:

<?php echo number_format($trip["budget"]); ?>

افغانی

</option>

<?php } ?>

</select>


<div class="budget">

💰 بودجه:

<span id="budgetAmount">
<?php echo number_format($expense["budget"]); ?>
</span>

افغانی

</div>

</div>


<div class="form-group">

<label>
📝 عنوان مصرف
</label>

<input
type="text"
name="title"
value="<?php echo htmlspecialchars($expense["title"]); ?>"
required
>

</div>


<div class="form-group">

<label>
💵 مبلغ
</label>

<input
type="number"
name="amount"
value="<?php echo $expense["amount"]; ?>"
min="1"
step="0.01"
required
>

</div>


<div class="form-group">

<label>
📅 تاریخ مصرف
</label>

<input
type="date"
name="expense_date"
value="<?php echo $expense["expense_date"]; ?>"
required
>

</div>


<button type="submit">
💾 ذخیره تغییرات
</button>

<a href="expenses.php" class="back">
↩️ برگشت
</a>

</form>

</div>

</div>


<script>

const select =
document.getElementById("trip_id");

const budget =
document.getElementById("budgetAmount");

select.addEventListener("change", function() {

    const option =
        this.options[this.selectedIndex];

    const amount =
        option.getAttribute("data-budget");

    if (amount) {

        budget.innerText =
            Number(amount).toLocaleString("fa-IR");

    }

});

</script>

</body>

</html>

2. "delete_expense.php"