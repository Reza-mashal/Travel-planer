<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

/* =========================
   حذف مصرف
========================= */
if (isset($_GET["delete"])) {

    $delete_id = intval($_GET["delete"]);

    $stmt = $conn->prepare("
        DELETE e
        FROM expenses e
        INNER JOIN trips t ON e.trip_id = t.id
        WHERE e.id = ? AND t.user_id = ?
    ");

    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: expenses.php");
    exit();
}


/* =========================
   ثبت مصرف
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $trip_id = intval($_POST["trip_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $amount = floatval($_POST["amount"] ?? 0);
    $expense_date = $_POST["expense_date"] ?? "";

    if (
        $trip_id <= 0 ||
        $title === "" ||
        $amount <= 0 ||
        $expense_date === ""
    ) {

        $message = "لطفاً تمام معلومات را درست وارد کنید.";

    } else {

        /* بررسی مالکیت سفر */

        $check = $conn->prepare("
            SELECT id
            FROM trips
            WHERE id = ? AND user_id = ?
        ");

        $check->bind_param("ii", $trip_id, $user_id);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows === 0) {

            $message = "این سفر متعلق به شما نیست.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO expenses
                (trip_id, title, amount, expense_date)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isds",
                $trip_id,
                $title,
                $amount,
                $expense_date
            );

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();

                header("Location: expenses.php");
                exit();

            } else {

                $message = "ثبت مصرف انجام نشد.";

                $stmt->close();
            }
        }

        $check->close();
    }
}


/* =========================
   گرفتن سفرها
========================= */

$trips = [];

$stmt = $conn->prepare("
    SELECT id, title
    FROM trips
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

$stmt->close();


/* =========================
   گرفتن مصارف
========================= */

$expenses = [];

$stmt = $conn->prepare("
    SELECT
        e.id,
        e.trip_id,
        e.title,
        e.amount,
        e.expense_date,
        t.title AS trip_title
    FROM expenses e
    INNER JOIN trips t ON e.trip_id = t.id
    WHERE t.user_id = ?
    ORDER BY e.expense_date DESC, e.id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

$stmt->close();


/* =========================
   محاسبه مجموع
========================= */

$total_expenses = 0;

foreach ($expenses as $expense) {
    $total_expenses += (float)$expense["amount"];
}

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    مدیریت مصارف | Travel Planner
</title>

<style>

/* ========================================
   RESET
======================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    min-height: 100vh;

    color: #f8fafc;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(99,102,241,.20),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(139,92,246,.18),
            transparent 30%
        ),
        #070b16;

    overflow-x: hidden;
}


/* ========================================
   BACKGROUND ANIMATION
======================================== */

body::before {

    content: "";

    position: fixed;

    width: 350px;
    height: 350px;

    top: 10%;
    left: -150px;

    border-radius: 50%;

    background:
        rgba(59,130,246,.10);

    filter: blur(80px);

    animation:
        floating 8s ease-in-out infinite;

    pointer-events: none;
}

body::after {

    content: "";

    position: fixed;

    width: 300px;
    height: 300px;

    bottom: 5%;
    right: -120px;

    border-radius: 50%;

    background:
        rgba(168,85,247,.10);

    filter: blur(70px);

    animation:
        floating2 10s ease-in-out infinite;

    pointer-events: none;
}

@keyframes floating {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(35px);
    }
}

@keyframes floating2 {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-40px);
    }
}


/* ========================================
   PAGE
======================================== */

.page {

    width: 100%;

    max-width: 1350px;

    margin: auto;

    padding:
        35px 25px 60px;

    position: relative;

    z-index: 1;
}


/* ========================================
   TOPBAR
======================================== */

.topbar {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 30px;

    animation:
        slideDown .7s ease;
}

.title-box h1 {

    font-size: 34px;

    font-weight: 900;

    letter-spacing: -.5px;

    background:
        linear-gradient(
            90deg,
            #ffffff,
            #a5b4fc
        );

    -webkit-background-clip: text;

    -webkit-text-fill-color:
        transparent;
}

.title-box p {

    color: #94a3b8;

    margin-top: 9px;

    font-size: 14px;
}

.back-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    text-decoration: none;

    color: white;

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.12);

    padding:
        12px 20px;

    border-radius: 14px;

    backdrop-filter: blur(10px);

    transition:
        .35s;
}

.back-btn:hover {

    background:
        rgba(255,255,255,.14);

    transform:
        translateY(-4px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.25);
}


/* ========================================
   STATS
======================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 22px;

    margin-bottom: 28px;
}

.stat-card {

    position: relative;

    overflow: hidden;

    padding: 27px;

    border-radius: 24px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.10),
            rgba(255,255,255,.04)
        );

    border:
        1px solid
        rgba(255,255,255,.10);

    backdrop-filter:
        blur(18px);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.25);

    transition:
        .4s;

    animation:
        slideUp .7s ease;
}

.stat-card::before {

    content: "";

    position: absolute;

    width: 130px;
    height: 130px;

    border-radius: 50%;

    top: -65px;
    left: -65px;

    background:
        rgba(99,102,241,.15);
}

.stat-card:hover {

    transform:
        translateY(-8px)
        scale(1.01);

    border-color:
        rgba(129,140,248,.35);

    box-shadow:
        0 25px 60px
        rgba(79,70,229,.20);
}

.stat-icon {

    font-size: 30px;

    margin-bottom: 12px;
}

.stat-card span {

    display: block;

    color: #94a3b8;

    font-size: 13px;

    margin-bottom: 9px;
}

.stat-card strong {

    font-size: 29px;

    background:
        linear-gradient(
            90deg,
            #fff,
            #a5b4fc
        );

    -webkit-background-clip: text;

    -webkit-text-fill-color:
        transparent;
}


/* ========================================
   MAIN GRID
======================================== */

.main-grid {

    display: grid;

    grid-template-columns:
        370px 1fr;

    gap: 25px;

    align-items: start;
}


/* ========================================
   GLASS CARD
======================================== */

.card {

    position: relative;

    padding: 27px;

    border-radius: 25px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.095),
            rgba(255,255,255,.035)
        );

    border:
        1px solid
        rgba(255,255,255,.10);

    backdrop-filter:
        blur(20px);

    box-shadow:
        0 20px 55px
        rgba(0,0,0,.25);

    animation:
        slideUp .8s ease;

    overflow: hidden;
}

.card::after {

    content: "";

    position: absolute;

    top: 0;
    right: 0;

    width: 100%;
    height: 2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #6366f1,
            #8b5cf6,
            transparent
        );
}

.card h2 {

    margin: 0 0 23px;

    font-size: 22px;

    color: white;
}


/* ========================================
   FORM
======================================== */

.form-group {

    margin-bottom: 19px;
}

.form-group label {

    display: block;

    margin-bottom: 9px;

    color: #cbd5e1;

    font-size: 13px;

    font-weight: bold;
}

.form-group input,
.form-group select {

    width: 100%;

    padding:
        14px 15px;

    border-radius: 14px;

    border:
        1px solid
        rgba(255,255,255,.12);

    outline: none;

    background:
        rgba(2,6,23,.65);

    color: white;

    font-size: 14px;

    transition:
        .3s;
}

.form-group input::placeholder {

    color: #64748b;
}

.form-group input:hover,
.form-group select:hover {

    border-color:
        rgba(129,140,248,.5);

    transform:
        translateY(-1px);
}

.form-group input:focus,
.form-group select:focus {

    border-color:
        #818cf8;

    box-shadow:
        0 0 0 4px
        rgba(99,102,241,.12);

    transform:
        translateY(-2px);
}

.form-group select option {

    color: #111827;

    background: white;
}


/* ========================================
   DATE
======================================== */

.date-wrapper {

    position: relative;
}

.date-hint {

    margin-top: 7px;

    font-size: 11px;

    color: #64748b;
}

.date-hint span {

    color: #a5b4fc;

    font-weight: bold;
}


/* ========================================
   SUBMIT BUTTON
======================================== */

.submit-btn {

    position: relative;

    overflow: hidden;

    width: 100%;

    border: none;

    cursor: pointer;

    padding: 15px;

    border-radius: 15px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    font-size: 16px;

    font-weight: bold;

    transition:
        .35s;

    box-shadow:
        0 10px 25px
        rgba(79,70,229,.20);
}

.submit-btn::before {

    content: "";

    position: absolute;

    top: 0;

    left: -120%;

    width: 80%;

    height: 100%;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.35),
            transparent
        );

    transform:
        skewX(-20deg);

    transition:
        .65s;
}

.submit-btn:hover::before {

    left: 140%;
}

.submit-btn:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 17px 35px
        rgba(79,70,229,.35);
}

.submit-btn:active {

    transform:
        translateY(-1px);
}


/* ========================================
   MESSAGE
======================================== */

.message {

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 13px;

    margin-bottom: 18px;

    border-radius: 13px;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid
        rgba(239,68,68,.25);

    color: #fca5a5;

    animation:
        shake .5s ease;
}

@keyframes shake {

    0%,100% {
        transform: translateX(0);
    }

    25% {
        transform: translateX(5px);
    }

    75% {
        transform: translateX(-5px);
    }
}


/* ========================================
   TABLE
======================================== */

.table-wrapper {

    overflow-x: auto;

    scrollbar-width: thin;
}

table {

    width: 100%;

    border-collapse:
        separate;

    border-spacing:
        0 8px;

    min-width: 650px;
}

th {

    color: #94a3b8;

    font-size: 12px;

    font-weight: normal;

    padding:
        10px 13px;

    text-align: right;
}

tbody tr {

    background:
        rgba(255,255,255,.035);

    transition:
        .3s;
}

tbody tr:hover {

    background:
        rgba(99,102,241,.10);

    transform:
        scale(1.005);
}

td {

    padding:
        15px 13px;

    color: #e2e8f0;

    font-size: 13px;

    border-top:
        1px solid
        rgba(255,255,255,.04);

    border-bottom:
        1px solid
        rgba(255,255,255,.04);
}

td:first-child {

    border-radius:
        0 12px 12px 0;
}

td:last-child {

    border-radius:
        12px 0 0 12px;
}


/* ========================================
   AMOUNT
======================================== */

.amount {

    font-weight: bold;

    color: #fbbf24;

    white-space: nowrap;
}


/* ========================================
   DELETE
======================================== */

.delete-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    color: #fca5a5;

    background:
        rgba(239,68,68,.08);

    border:
        1px solid
        rgba(239,68,68,.16);

    padding:
        8px 12px;

    border-radius: 10px;

    transition:
        .3s;
}

.delete-btn:hover {

    color: white;

    background:
        rgba(239,68,68,.25);

    transform:
        translateY(-2px)
        scale(1.04);

    box-shadow:
        0 8px 18px
        rgba(239,68,68,.15);
}


/* ========================================
   EMPTY
======================================== */

.empty {

    text-align: center;

    padding:
        55px 20px;

    color: #94a3b8;
}

.empty-icon {

    font-size: 55px;

    margin-bottom: 14px;

    animation:
        floating 3s ease-in-out infinite;
}


/* ========================================
   ANIMATIONS
======================================== */

@keyframes slideUp {

    from {

        opacity: 0;

        transform:
            translateY(30px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}

@keyframes slideDown {

    from {

        opacity: 0;

        transform:
            translateY(-25px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* ========================================
   RESPONSIVE
======================================== */

@media(max-width: 950px) {

    .main-grid {

        grid-template-columns:
            1fr;
    }

    .stats {

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width: 650px) {

    .page {

        padding:
            22px 13px 45px;
    }

    .topbar {

        flex-direction:
            column;

        align-items:
            stretch;
    }

    .title-box h1 {

        font-size: 26px;
    }

    .back-btn {

        width: 100%;
    }

    .stats {

        grid-template-columns:
            1fr;
    }

    .card {

        padding:
            20px 16px;
    }

    .stat-card {

        padding:
            21px;
    }

    .stat-card strong {

        font-size: 24px;
    }
}

</style>

</head>

<body>


<div class="page">


    <!-- ====================================
         HEADER
    ==================================== -->

    <header class="topbar">

        <div class="title-box">

            <h1>
                💰 مدیریت مصارف
            </h1>

            <p>
                هزینه‌های سفر خود را حرفه‌ای مدیریت و بررسی کنید
            </p>

        </div>


        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← بازگشت به داشبورد
        </a>

    </header>


    <!-- ====================================
         STATISTICS
    ==================================== -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                🧾
            </div>

            <span>
                تعداد کل مصارف
            </span>

            <strong>

                <?php
                echo count($expenses);
                ?>

            </strong>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <span>
                مجموع تمام مصارف
            </span>

            <strong>

                <?php
                echo number_format(
                    $total_expenses,
                    2
                );
                ?>

                افغانی

            </strong>

        </div>


    </section>


    <!-- ====================================
         MAIN
    ==================================== -->

    <div class="main-grid">


        <!-- ==================================
             ADD EXPENSE
        ================================== -->

        <section class="card">

            <h2>
                ➕ ثبت مصرف جدید
            </h2>


            <?php if ($message !== ""): ?>

                <div class="message">

                    ⚠️

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (count($trips) > 0): ?>


                <form
                    method="POST"
                    autocomplete="off"
                >


                    <!-- TRIP -->

                    <div class="form-group">

                        <label>
                            ✈️ سفر
                        </label>

                        <select
                            name="trip_id"
                            required
                        >

                            <option value="">
                                انتخاب سفر
                            </option>

                            <?php foreach ($trips as $trip): ?>

                                <option
                                    value="<?php
                                        echo $trip["id"];
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $trip["title"]
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- TITLE -->

                    <div class="form-group">

                        <label>
                            🧾 عنوان مصرف
                        </label>

                        <input
                            type="text"
                            name="title"
                            placeholder="مثلاً غذا، هوتل، کرایه..."
                            required
                        >

                    </div>


                    <!-- AMOUNT -->

                    <div class="form-group">

                        <label>
                            💵 مبلغ به افغانی
                        </label>

                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            placeholder="مثلاً 500"
                            required
                        >

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            🗓️ تاریخ مصرف
                        </label>

                        <div class="date-wrapper">

                            <input
                                type="date"
                                id="expense_date"
                                name="expense_date"
                                required
                            >

                        </div>

                        <div class="date-hint">

                            تاریخ امروز از کامپیوتر پیشنهاد می‌شود:
                            <span id="todayText"></span>

                        </div>

                    </div>


                    <!-- BUTTON -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        💾 ثبت مصرف

                    </button>

                </form>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        ✈️
                    </div>

                    <p>
                        هنوز هیچ سفری ثبت نشده است.
                    </p>


                    <a
                        href="add_trip.php"
                        class="back-btn"
                        style="
                            display:inline-flex;
                            margin-top:18px;
                        "
                    >
                        ➕ ثبت سفر جدید
                    </a>

                </div>


            <?php endif; ?>

        </section>


        <!-- ==================================
             EXPENSE LIST
        ================================== -->

        <section class="card">

            <h2>
                📋 لیست مصارف
            </h2>


            <?php if (count($expenses) > 0): ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    ✈️ سفر
                                </th>

                                <th>
                                    🧾 عنوان
                                </th>

                                <th>
                                    💰 مبلغ
                                </th>

                                <th>
                                    🗓️ تاریخ
                                </th>

                                <th>
                                    ⚙️ عملیات
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($expenses as $expense): ?>

                                <tr>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $expense["trip_title"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $expense["title"]
                                        );
                                        ?>

                                    </td>


                                    <td class="amount">

                                        <?php
                                        echo number_format(
                                            (float)$expense["amount"],
                                            2
                                        );
                                        ?>

                                        ؋

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $expense["expense_date"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="expenses.php?delete=<?php
                                                echo $expense["id"];
                                            ?>"
                                            class="delete-btn"
                                            onclick="
                                                return confirm(
                                                    'آیا مطمئن هستید که می‌خواهید این مصرف را حذف کنید؟'
                                                );
                                            "
                                        >

                                            🗑️ حذف

                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        💸
                    </div>

                    <p>
                        هنوز هیچ مصرفی ثبت نشده است.
                    </p>

                </div>


            <?php endif; ?>

        </section>


    </div>

</div>


<!-- ========================================
     COMPUTER DATE
======================================== -->

<script>

function setComputerDate() {

    const now = new Date();

    const year =
        now.getFullYear();

    const month =
        String(
            now.getMonth() + 1
        ).padStart(2, "0");

    const day =
        String(
            now.getDate()
        ).padStart(2, "0");

    const today =
        `${year}-${month}-${day}`;


    const dateInput =
        document.getElementById(
            "expense_date"
        );

    const todayText =
        document.getElementById(
            "todayText"
        );


    if (dateInput && !dateInput.value) {

        dateInput.value =
            today;
    }


    if (todayText) {

        todayText.textContent =
            today;
    }

}


/* اجرای تاریخ هنگام بازشدن صفحه */

setComputerDate();

</script>


</body>

</html>