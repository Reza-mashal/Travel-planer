<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$trip_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($trip_id <= 0) {
    header("Location: trips.php");
    exit();
}

/* =========================
   اطلاعات سفر
========================= */

$stmt = $conn->prepare("
    SELECT id, title, destination, start_date, end_date, budget
    FROM trips
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();

$trip_result = $stmt->get_result();
$trip = $trip_result->fetch_assoc();

if (!$trip) {
    header("Location: trips.php");
    exit();
}

/* =========================
   محاسبه روزهای سفر
========================= */

$start = new DateTime($trip["start_date"]);
$end = new DateTime($trip["end_date"]);
$days = $start->diff($end)->days + 1;


/* =========================
   مقصدها
========================= */

$stmt = $conn->prepare("
    SELECT id, name, country, description
    FROM destinations
    WHERE trip_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$destinations = $stmt->get_result();


/* =========================
   برنامه سفر
========================= */

$stmt = $conn->prepare("
    SELECT id, day_number, activity, location,
           activity_date, start_time, end_time, note
    FROM itinerary
    WHERE trip_id = ?
    ORDER BY activity_date ASC, start_time ASC, day_number ASC, id ASC
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$itinerary = $stmt->get_result();


/* =========================
   مصارف
========================= */

$stmt = $conn->prepare("
    SELECT id, title, amount, expense_date
    FROM expenses
    WHERE trip_id = ?
    ORDER BY expense_date DESC, id DESC
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$expenses = $stmt->get_result();


/* =========================
   مجموع مصارف
========================= */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_expenses
    FROM expenses
    WHERE trip_id = ?
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$expense_result = $stmt->get_result()->fetch_assoc();

$total_expenses = (float)$expense_result["total_expenses"];
$budget = (float)$trip["budget"];
$remaining = $budget - $total_expenses;

if ($budget > 0) {
    $used_percent = ($total_expenses / $budget) * 100;
    $used_percent = min($used_percent, 100);
} else {
    $used_percent = 0;
}


/* =========================
   تعداد اطلاعات
========================= */

$destination_count = $destinations->num_rows;
$itinerary_count = $itinerary->num_rows;
$expense_count = $expenses->num_rows;

function formatMoney($number)
{
    return number_format((float)$number, 0);
}

function formatDateFa($date)
{
    if (!$date) {
        return "-";
    }

    return date("Y/m/d", strtotime($date));
}

function formatTime($time)
{
    if (!$time) {
        return "";
    }

    return date("H:i", strtotime($time));
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    جزئیات سفر | <?= htmlspecialchars($trip["title"]) ?>
</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at top right,
            #243b53,
            #0b1120 45%,
            #020617
        );

    color:#f8fafc;
    min-height:100vh;
}

a{
    text-decoration:none;
    color:inherit;
}

button,
a{
    -webkit-tap-highlight-color:transparent;
}

/* =========================
   Layout
========================= */

.layout{
    min-height:100vh;
    display:flex;
}

/* =========================
   Sidebar
========================= */

.sidebar{
    width:260px;
    background:rgba(15,23,42,.88);
    backdrop-filter:blur(20px);
    border-left:1px solid rgba(255,255,255,.08);
    padding:25px 18px;
    position:fixed;
    right:0;
    top:0;
    bottom:0;
    z-index:100;
}

.logo{
    font-size:24px;
    font-weight:900;
    text-align:center;
    margin-bottom:35px;

    background:
        linear-gradient(
            90deg,
            #38bdf8,
            #818cf8,
            #c084fc
        );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.menu{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.menu a{
    padding:14px 15px;
    border-radius:14px;
    color:#cbd5e1;
    transition:.3s;
    display:flex;
    align-items:center;
    gap:10px;
}

.menu a:hover{
    background:rgba(255,255,255,.08);
    color:white;
    transform:translateX(-4px);
}

.menu a.active{
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    color:white;
    box-shadow:
        0 10px 25px rgba(37,99,235,.25);
}

.logout{
    margin-top:30px;
    border:1px solid rgba(248,113,113,.2);
}

.logout:hover{
    background:rgba(239,68,68,.15)!important;
    color:#fca5a5!important;
}

/* =========================
   Main
========================= */

.main{
    width:calc(100% - 260px);
    margin-right:260px;
    padding:30px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:25px;
}

.back{
    display:inline-flex;
    align-items:center;
    gap:8px;

    padding:11px 18px;

    border-radius:12px;

    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.08);

    color:#cbd5e1;

    transition:.3s;
}

.back:hover{
    background:rgba(255,255,255,.12);
    transform:translateY(-2px);
}

/* =========================
   Hero
========================= */

.hero{
    position:relative;
    overflow:hidden;

    padding:32px;

    border-radius:28px;

    background:
        linear-gradient(
            135deg,
            rgba(37,99,235,.35),
            rgba(124,58,237,.28),
            rgba(15,23,42,.75)
        );

    border:1px solid rgba(255,255,255,.1);

    box-shadow:
        0 25px 60px rgba(0,0,0,.25);

    margin-bottom:25px;
}

.hero::before{
    content:"";
    position:absolute;

    width:230px;
    height:230px;

    background:#38bdf8;

    opacity:.08;

    border-radius:50%;

    left:-70px;
    top:-80px;

    filter:blur(5px);
}

.hero-content{
    position:relative;
    z-index:2;
}

.hero h1{
    font-size:36px;
    margin-bottom:12px;
}

.hero-destination{
    color:#cbd5e1;
    font-size:17px;
    margin-bottom:20px;
}

.hero-info{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}

.info-badge{
    padding:10px 15px;
    border-radius:12px;

    background:rgba(255,255,255,.08);

    border:1px solid rgba(255,255,255,.08);

    color:#e2e8f0;
}

/* =========================
   Stats
========================= */

.stats{
    display:grid;
    grid-template-columns:
        repeat(4,1fr);

    gap:16px;

    margin-bottom:25px;
}

.stat{
    padding:22px;

    border-radius:20px;

    background:rgba(15,23,42,.7);

    border:1px solid rgba(255,255,255,.08);

    transition:.3s;
}

.stat:hover{
    transform:translateY(-5px);
    border-color:rgba(96,165,250,.4);
    box-shadow:
        0 15px 30px rgba(0,0,0,.2);
}

.stat-icon{
    font-size:25px;
    margin-bottom:10px;
}

.stat-label{
    color:#94a3b8;
    font-size:13px;
    margin-bottom:7px;
}

.stat-value{
    font-size:23px;
    font-weight:900;
}

/* =========================
   Budget
========================= */

.budget-box{
    padding:25px;

    background:rgba(15,23,42,.72);

    border:1px solid rgba(255,255,255,.08);

    border-radius:22px;

    margin-bottom:25px;
}

.budget-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;

    margin-bottom:15px;
}

.budget-head h2{
    font-size:20px;
}

.progress{
    width:100%;
    height:12px;

    background:#1e293b;

    border-radius:50px;

    overflow:hidden;
}

.progress-bar{
    height:100%;

    width:<?= $used_percent ?>%;

    background:
        linear-gradient(
            90deg,
            #22c55e,
            #eab308,
            #ef4444
        );

    border-radius:50px;

    transition:
        width 1s ease;
}

.budget-info{
    display:flex;
    justify-content:space-between;

    margin-top:12px;

    color:#94a3b8;
    font-size:14px;
}

/* =========================
   Section
========================= */

.section{
    background:rgba(15,23,42,.7);

    border:1px solid rgba(255,255,255,.08);

    border-radius:22px;

    padding:24px;

    margin-bottom:25px;
}

.section-title{
    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:20px;
}

.section-title h2{
    font-size:21px;
}

.section-title span{
    color:#64748b;
    font-size:13px;
}

/* =========================
   Cards
========================= */

.cards{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:15px;
}

.card{
    padding:20px;

    border-radius:18px;

    background:
        rgba(255,255,255,.045);

    border:1px solid rgba(255,255,255,.07);

    transition:.3s;
}

.card:hover{
    transform:translateY(-5px);
    background:
        rgba(255,255,255,.075);

    border-color:
        rgba(96,165,250,.35);
}

.card-icon{
    width:45px;
    height:45px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    margin-bottom:15px;

    font-size:21px;
}

.card h3{
    font-size:17px;
    margin-bottom:10px;
}

.card p{
    color:#94a3b8;
    font-size:14px;
    line-height:1.8;
}

.empty{
    text-align:center;
    padding:35px 15px;
    color:#64748b;
}

/* =========================
   Table
========================= */

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:650px;
}

th{
    text-align:right;

    color:#94a3b8;

    font-size:13px;

    padding:14px;

    border-bottom:
        1px solid rgba(255,255,255,.08);
}

td{
    padding:15px 14px;

    border-bottom:
        1px solid rgba(255,255,255,.05);

    color:#e2e8f0;
}

tr{
    transition:.25s;
}

tbody tr:hover{
    background:
        rgba(255,255,255,.035);
}

.amount{
    color:#fbbf24;
    font-weight:800;
}

.date{
    color:#94a3b8;
}

/* =========================
   Actions
========================= */

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}

.btn{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    padding:12px 18px;

    border-radius:13px;

    border:1px solid rgba(255,255,255,.08);

    transition:.3s;

    font-size:14px;
}

.btn:hover{
    transform:translateY(-3px);
}

.btn-primary{
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    color:white;

    box-shadow:
        0 10px 25px rgba(37,99,235,.2);
}

.btn-secondary{
    background:
        rgba(255,255,255,.07);

    color:#e2e8f0;
}

/* =========================
   Mobile
========================= */

@media(max-width:1100px){

    .stats{
        grid-template-columns:
            repeat(2,1fr);
    }

    .cards{
        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:800px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
        bottom:auto;
        padding:15px;
    }

    .layout{
        display:block;
    }

    .logo{
        margin-bottom:15px;
    }

    .menu{
        display:grid;
        grid-template-columns:
            repeat(2,1fr);
    }

    .logout{
        margin-top:0;
    }

    .main{
        width:100%;
        margin-right:0;
        padding:18px;
    }

    .hero{
        padding:25px 20px;
    }

    .hero h1{
        font-size:28px;
    }

}

@media(max-width:600px){

    .topbar{
        flex-direction:column;
        align-items:stretch;
    }

    .stats{
        grid-template-columns:1fr;
    }

    .cards{
        grid-template-columns:1fr;
    }

    .hero-info{
        flex-direction:column;
    }

    .info-badge{
        width:100%;
    }

    .section{
        padding:18px;
    }

    .budget-head{
        flex-direction:column;
        align-items:flex-start;
    }

}

/* =========================
   Animation
========================= */

@keyframes fadeUp{

    from{
        opacity:0;
        transform:translateY(20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }

}

.hero,
.stat,
.section{
    animation:
        fadeUp .6s ease both;
}

</style>

</head>

<body>

<div class="layout">

    <!-- Sidebar -->

    <aside class="sidebar">

        <div class="logo">
            ✈ Travel Planner
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                🏠 داشبورد
            </a>

            <a href="trips.php" class="active">
                🧳 سفرهای من
            </a>

            <a href="add_trip.php">
                ➕ ثبت سفر جدید
            </a>

            <a href="destinations.php">
                📍 مقصدها
            </a>

            <a href="itinerary.php">
                🗓 برنامه سفر
            </a>

            <a href="expenses.php">
                💰 مصارف
            </a>

            <a href="provinces.php">
                🗺 ولایت‌ها
            </a>

            <a href="logout.php" class="logout">
                🚪 خروج
            </a>

        </nav>

    </aside>


    <!-- Main -->

    <main class="main">

        <div class="topbar">

            <a href="trips.php" class="back">
                ← برگشت به سفرها
            </a>

        </div>


        <!-- Hero -->

        <section class="hero">

            <div class="hero-content">

                <h1>
                    <?= htmlspecialchars($trip["title"]) ?>
                </h1>

                <div class="hero-destination">
                    📍 <?= htmlspecialchars($trip["destination"]) ?>
                </div>

                <div class="hero-info">

                    <div class="info-badge">
                        📅
                        <?= formatDateFa($trip["start_date"]) ?>
                        تا
                        <?= formatDateFa($trip["end_date"]) ?>
                    </div>

                    <div class="info-badge">
                        ⏱
                        <?= $days ?>
                        روز
                    </div>

                    <div class="info-badge">
                        💰
                        <?= formatMoney($budget) ?>
                        افغانی
                    </div>

                </div>

            </div>

        </section>


        <!-- Stats -->

        <section class="stats">

            <div class="stat">

                <div class="stat-icon">
                    📍
                </div>

                <div class="stat-label">
                    تعداد مقصدها
                </div>

                <div class="stat-value">
                    <?= $destination_count ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    🗓
                </div>

                <div class="stat-label">
                    برنامه‌ها
                </div>

                <div class="stat-value">
                    <?= $itinerary_count ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    💸
                </div>

                <div class="stat-label">
                    مجموع مصارف
                </div>

                <div class="stat-value">
                    <?= formatMoney($total_expenses) ?>
                    <small>؋</small>
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    💰
                </div>

                <div class="stat-label">
                    باقی‌مانده بودجه
                </div>

                <div class="stat-value"
                     style="color:<?= $remaining < 0 ? '#f87171' : '#4ade80' ?>">

                    <?= formatMoney($remaining) ?>
                    <small>؋</small>

                </div>

            </div>

        </section>


        <!-- Budget -->

        <section class="budget-box">

            <div class="budget-head">

                <h2>
                    📊 وضعیت بودجه
                </h2>

                <strong>
                    <?= round($used_percent) ?>%
                </strong>

            </div>

            <div class="progress">

                <div class="progress-bar"></div>

            </div>

            <div class="budget-info">

                <span>
                    مصرف شده:
                    <?= formatMoney($total_expenses) ?>
                    افغانی
                </span>

                <span>
                    بودجه:
                    <?= formatMoney($budget) ?>
                    افغانی
                </span>

            </div>

        </section>


        <!-- Destinations -->

        <section class="section">

            <div class="section-title">

                <h2>
                    📍 مقصدهای این سفر
                </h2>

                <span>
                    <?= $destination_count ?> مقصد
                </span>

            </div>


            <?php if ($destination_count > 0): ?>

                <div class="cards">

                    <?php while ($destination = $destinations->fetch_assoc()): ?>

                        <div class="card">

                            <div class="card-icon">
                                📍
                            </div>

                            <h3>
                                <?= htmlspecialchars($destination["name"]) ?>
                            </h3>

                            <?php if (!empty($destination["country"])): ?>

                                <p>
                                    🌍
                                    <?= htmlspecialchars($destination["country"]) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($destination["description"])): ?>

                                <p>
                                    <?= nl2br(htmlspecialchars($destination["description"])) ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty">
                    هنوز مقصدی برای این سفر ثبت نشده است.
                </div>

            <?php endif; ?>

        </section>


        <!-- Itinerary -->

        <section class="section">

            <div class="section-title">

                <h2>
                    🗓 برنامه سفر
                </h2>

                <span>
                    <?= $itinerary_count ?> برنامه
                </span>

            </div>


            <?php if ($itinerary_count > 0): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    روز
                                </th>

                                <th>
                                    فعالیت
                                </th>

                                <th>
                                    مکان
                                </th>

                                <th>
                                    تاریخ
                                </th>

                                <th>
                                    ساعت
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($item = $itinerary->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        روز
                                        <?= htmlspecialchars($item["day_number"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item["activity"]) ?>
                                    </td>

                                    <td>
                                        <?= !empty($item["location"])
                                            ? htmlspecialchars($item["location"])
                                            : "-" ?>
                                    </td>

                                    <td class="date">

                                        <?= formatDateFa($item["activity_date"]) ?>

                                    </td>

                                    <td>

                                        <?php

                                        $start_time = formatTime($item["start_time"]);
                                        $end_time = formatTime($item["end_time"]);

                                        if ($start_time || $end_time) {

                                            echo htmlspecialchars(
                                                $start_time .
                                                ($start_time && $end_time ? " - " : "") .
                                                $end_time
                                            );

                                        } else {

                                            echo "-";

                                        }

                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    هنوز برنامه‌ای برای این سفر ثبت نشده است.
                </div>

            <?php endif; ?>

        </section>


        <!-- Expenses -->

        <section class="section">

            <div class="section-title">

                <h2>
                    💰 مصارف سفر
                </h2>

                <span>
                    <?= $expense_count ?> مورد
                </span>

            </div>


            <?php if ($expense_count > 0): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    عنوان
                                </th>

                                <th>
                                    مبلغ
                                </th>

                                <th>
                                    تاریخ
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($expense = $expenses->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars($expense["title"]) ?>
                                    </td>

                                    <td class="amount">

                                        <?= formatMoney($expense["amount"]) ?>
                                        افغانی

                                    </td>

                                    <td class="date">

                                        <?= formatDateFa($expense["expense_date"]) ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    هنوز مصرفی برای این سفر ثبت نشده است.
                </div>

            <?php endif; ?>

        </section>


        <!-- Actions -->

        <section class="section">

            <div class="section-title">

                <h2>
                    ⚡ مدیریت سفر
                </h2>

            </div>

            <div class="actions">

                <a
                    href="destinations.php?trip_id=<;?= $trip_id ?>"
                    class="btn btn-primary"
                >
                    📍 افزودن مقصد
                </a>

                <a
                    href="itinerary.php?trip_id=<;?= $trip_id ?>"
                    class="btn btn-primary"
                >
                    🗓 افزودن برنامه
                </a>

                <a
                    href="expenses.php?trip_id=<;?= $trip_id ?>"
                    class="btn btn-primary"
                >
                    💰 افزودن مصرف
                </a>

                <a
                    href="trips.php"
                    class="btn btn-secondary"
                >
                    ← بازگشت
                </a>

            </div>

        </section>

    </main>

</div>


<script>

/* انیمیشن نوار بودجه */

window.addEventListener("load", function(){

    const bar =
        document.querySelector(".progress-bar");

    if(bar){

        const target =
            <?= round($used_percent, 2) ?>;

        bar.style.width = "0%";

        setTimeout(function(){

            bar.style.width =
                target + "%";

        }, 300);

    }

});

</script>

</body>
</html>