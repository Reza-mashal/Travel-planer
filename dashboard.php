<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

/* =========================
   اطلاعات کاربر
========================= */

$user_name = $_SESSION["name"] ?? "کاربر";
$user_email = "";
$user_photo = $_SESSION["photo"] ?? "";

$stmt = mysqli_prepare(
    $conn,
    "SELECT name, email, photo
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    $user_name = $user["name"];
    $user_email = $user["email"];
    $user_photo = $user["photo"] ?? "";

    $_SESSION["name"] = $user_name;
    $_SESSION["photo"] = $user_photo;
}

mysqli_stmt_close($stmt);

/* =========================
   عکس پروفایل
========================= */

$photo_url = "";

if (!empty($user_photo)) {
    $photo_url = trim($user_photo);
}

$first_letter = mb_substr($user_name, 0, 1, "UTF-8");

/* =========================
   تعداد سفرها
========================= */

$total_trips = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM trips
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$total_trips = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);

/* =========================
   مجموع بودجه
========================= */

$total_budget = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(budget), 0) AS total
     FROM trips
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$total_budget = (float) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);

/* =========================
   مجموع مصارف
========================= */

$total_expenses = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(e.amount), 0) AS total
     FROM expenses e
     INNER JOIN trips t ON e.trip_id = t.id
     WHERE t.user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$total_expenses = (float) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);

/* =========================
   بودجه باقی مانده
========================= */

$remaining_budget = $total_budget - $total_expenses;

/* =========================
   درصد مصرف بودجه
========================= */

$budget_percent = 0;

if ($total_budget > 0) {
    $budget_percent = ($total_expenses / $total_budget) * 100;
}

$budget_percent = min(100, max(0, $budget_percent));

/* =========================
   آخرین سفرها
========================= */

$recent_trips = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        title,
        destination,
        start_date,
        end_date,
        budget
     FROM trips
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 5"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($trip = mysqli_fetch_assoc($result)) {
    $recent_trips[] = $trip;
}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>داشبورد | Travel Planner</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, Arial, sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #07111f,
                    #0d1b2e,
                    #111827
                );

            color: #fff;

            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           Sidebar
        ========================= */

        .sidebar {
            width: 270px;

            background:
                rgba(10, 18, 32, 0.96);

            border-left:
                1px solid rgba(255,255,255,0.08);

            padding: 25px 18px;

            position: fixed;

            right: 0;
            top: 0;
            bottom: 0;

            z-index: 100;

            backdrop-filter: blur(20px);

            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 65px;
            height: 65px;

            margin: auto;

            border-radius: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            box-shadow:
                0 15px 35px rgba(37,99,235,0.35);
        }

        .logo h2 {
            margin-top: 12px;
            font-size: 20px;
        }

        .logo p {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 5px;
        }

        /* =========================
           Profile
        ========================= */

        .profile-box {
            padding: 15px;

            border-radius: 18px;

            background:
                rgba(255,255,255,0.05);

            border:
                1px solid rgba(255,255,255,0.07);

            margin-bottom: 20px;
        }

        .profile-link {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-circle {
            width: 48px;
            height: 48px;

            min-width: 48px;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #9333ea
                );

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
            font-weight: bold;

            overflow: hidden;
        }

        .profile-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            overflow: hidden;
        }

        .profile-info strong {
            display: block;

            font-size: 14px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .profile-info span {
            color: #94a3b8;
            font-size: 11px;
        }

        /* =========================
           Menu
        ========================= */

        .menu-title {
            color: #64748b;

            font-size: 11px;

            margin:
                20px 10px 8px;
        }

        .menu a {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 15px;

            margin-bottom: 6px;

            border-radius: 13px;

            color: #cbd5e1;

            transition: 0.3s ease;
        }

        .menu a:hover {
            background:
                rgba(37,99,235,0.15);

            color: #fff;

            transform:
                translateX(-4px);
        }

        .menu a.active {
            background:
                linear-gradient(
                    135deg,
                    rgba(37,99,235,0.35),
                    rgba(124,58,237,0.25)
                );

            color: #fff;

            border:
                1px solid
                rgba(96,165,250,0.15);
        }

        .menu-icon {
            width: 25px;

            text-align: center;

            font-size: 18px;
        }

        .logout {
            margin-top: 20px !important;

            color: #fca5a5 !important;
        }

        .logout:hover {
            background:
                rgba(239,68,68,0.12) !important;

            color: #fecaca !important;
        }

        /* =========================
           Main
        ========================= */

        .main {
            width: calc(100% - 270px);

            margin-right: 270px;

            padding: 30px;
        }

        .topbar {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 30px;
        }

        .top-title h1 {
            font-size: 30px;

            margin-bottom: 7px;
        }

        .top-title p {
            color: #94a3b8;

            font-size: 14px;
        }

        .top-profile {
            display: flex;

            align-items: center;

            gap: 10px;

            padding: 8px 12px;

            border-radius: 14px;

            background:
                rgba(255,255,255,0.05);

            border:
                1px solid rgba(255,255,255,0.07);

            transition: 0.3s;
        }

        .top-profile:hover {
            transform:
                translateY(-2px);

            background:
                rgba(255,255,255,0.08);
        }

        .top-profile img,
        .top-profile .avatar {
            width: 38px;
            height: 38px;

            border-radius: 50%;
        }

        .top-profile img {
            object-fit: cover;
        }

        .top-profile .avatar {
            display: flex;

            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            font-weight: bold;
        }

        /* =========================
           Welcome
        ========================= */

        .welcome {
            padding: 30px;

            border-radius: 25px;

            background:
                linear-gradient(
                    135deg,
                    rgba(37,99,235,0.25),
                    rgba(124,58,237,0.22)
                );

            border:
                1px solid rgba(255,255,255,0.08);

            margin-bottom: 25px;

            position: relative;

            overflow: hidden;
        }

        .welcome h2 {
            font-size: 25px;

            margin-bottom: 8px;
        }

        .welcome p {
            color: #cbd5e1;
        }

        /* =========================
           Stats
        ========================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }

        .stat-card {
            padding: 22px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.045);

            border:
                1px solid rgba(255,255,255,0.07);

            transition:
                0.35s ease;
        }

        .stat-card:hover {
            transform:
                translateY(-6px);

            border-color:
                rgba(96,165,250,0.3);

            box-shadow:
                0 15px 35px
                rgba(0,0,0,0.18);
        }

        .stat-icon {
            width: 48px;
            height: 48px;

            border-radius: 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 23px;

            background:
                rgba(37,99,235,0.15);

            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 25px;

            margin-bottom: 5px;
        }

        .stat-card p {
            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================
           Content
        ========================= */

        .content-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.8fr)
                minmax(280px, 1fr);

            gap: 22px;
        }

        .panel {
            background:
                rgba(255,255,255,0.045);

            border:
                1px solid rgba(255,255,255,0.07);

            border-radius: 22px;

            padding: 22px;

            overflow: hidden;
        }

        .panel-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;
        }

        .panel-header h2 {
            font-size: 19px;
        }

        .panel-header a {
            color: #60a5fa;

            font-size: 12px;
        }

        .panel-header a:hover {
            color: #93c5fd;
        }

        /* =========================
           Trip
        ========================= */

        .trip-card {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 17px;

            border-radius: 17px;

            background:
                rgba(255,255,255,0.035);

            border:
                1px solid rgba(255,255,255,0.055);

            margin-bottom: 12px;

            transition: 0.3s ease;
        }

        .trip-card:hover {
            transform:
                translateY(-3px);

            background:
                rgba(37,99,235,0.09);

            border-color:
                rgba(96,165,250,0.18);
        }

        .trip-info {
            min-width: 0;
        }

        .trip-info h3 {
            font-size: 16px;

            margin-bottom: 8px;
        }

        .trip-info p {
            display: inline-block;

            color: #94a3b8;

            font-size: 12px;

            margin-left: 15px;

            margin-bottom: 4px;
        }

        /* فقط لینک مشاهده جزئیات */

        .trip-card > a {
            flex-shrink: 0;

            padding: 10px 15px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #fff;

            font-size: 12px;

            transition: 0.3s ease;
        }

        .trip-card > a:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(37,99,235,0.3);
        }

        /* =========================
           Empty
        ========================= */

        .empty-state {
            text-align: center;

            padding: 35px 15px;

            color: #94a3b8;
        }

        .empty-icon {
            font-size: 45px;

            margin-bottom: 12px;
        }

        .empty-state h3 {
            color: #fff;

            margin-bottom: 7px;
        }

        .empty-state p {
            font-size: 13px;

            margin-bottom: 18px;
        }

        .add-trip-btn {
            display: inline-block;

            padding: 11px 18px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            font-size: 13px;

            transition: 0.3s;
        }

        .add-trip-btn:hover {
            transform:
                translateY(-3px);

            box-shadow:
                0 10px 25px
                rgba(37,99,235,0.3);
        }

        /* =========================
           Budget
        ========================= */

        .budget-box {
            text-align: center;
        }

        .circle {
            width: 180px;
            height: 180px;

            margin:
                10px auto 25px;

            border-radius: 50%;

            background:
                conic-gradient(
                    #3b82f6
                    <?= $budget_percent ?>%,
                    rgba(255,255,255,0.07)
                    <?= $budget_percent ?>%
                );

            display: flex;

            align-items: center;
            justify-content: center;

            position: relative;
        }

        .circle::before {
            content: "";

            position: absolute;

            width: 140px;
            height: 140px;

            border-radius: 50%;

            background:
                #101b2c;
        }

        .circle-content {
            position: relative;

            z-index: 2;
        }

        .circle-content strong {
            display: block;

            font-size: 27px;
        }

        .circle-content span {
            color: #94a3b8;

            font-size: 11px;
        }

        .budget-details {
            text-align: right;
        }

        .budget-row {
            display: flex;

            justify-content: space-between;

            padding: 11px 0;

            border-bottom:
                1px solid
                rgba(255,255,255,0.06);

            font-size: 13px;
        }

        .budget-row:last-child {
            border-bottom: none;
        }

        .budget-row span:first-child {
            color: #94a3b8;
        }

        /* =========================
           Quick Actions
        ========================= */

        .quick-actions {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;

            margin-top: 20px;
        }

        .quick-action {
            padding: 14px;

            border-radius: 14px;

            background:
                rgba(255,255,255,0.04);

            border:
                1px solid rgba(255,255,255,0.06);

            text-align: center;

            font-size: 12px;

            transition: 0.3s;
        }

        .quick-action:hover {
            background:
                rgba(37,99,235,0.12);

            transform:
                translateY(-3px);
        }

        .quick-action div {
            font-size: 22px;

            margin-bottom: 7px;
        }

        /* =========================
           Responsive
        ========================= */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns:
                    1fr;
            }

        }

        @media (max-width: 800px) {

            .layout {
                display: block;
            }

            .sidebar {
                position: relative;

                width: 100%;

                right: auto;

                min-height: auto;
            }

            .main {
                width: 100%;

                margin-right: 0;

                padding: 18px;
            }

            .menu {
                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

                gap: 5px;
            }

            .menu a {
                margin-bottom: 0;
            }

        }

        @media (max-width: 600px) {

            .topbar {
                align-items: flex-start;

                gap: 15px;
            }

            .top-title h1 {
                font-size: 23px;
            }

            .top-profile {
                padding: 6px;
            }

            .top-profile span {
                display: none;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h2 {
                font-size: 21px;
            }

            .stats {
                grid-template-columns:
                    1fr 1fr;

                gap: 10px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-card h3 {
                font-size: 20px;
            }

            .trip-card {
                align-items: flex-start;

                flex-direction: column;
            }

            .trip-card > a {
                width: 100%;

                text-align: center;
            }

            .trip-info p {
                display: block;

                margin-left: 0;
            }

            .panel {
                padding: 16px;
            }

            .menu {
                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <!-- =========================
         Sidebar
    ========================= -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                ✈️
            </div>

            <h2>
                Travel Planner
            </h2>

            <p>
                مدیریت هوشمند سفر
            </p>

        </div>

        <div class="profile-box">

            <a href="profile.php" class="profile-link">

                <div class="profile-circle">

                    <?php if (!empty($photo_url)): ?>

                        <img
                            src="<?= htmlspecialchars($photo_url) ?>"
                            alt="عکس پروفایل"
                            onerror="this.style.display='none';"
                        >

                    <?php else: ?>

                        <?= htmlspecialchars($first_letter) ?>

                    <?php endif; ?>

                </div>

                <div class="profile-info">

                    <strong>
                        <?= htmlspecialchars($user_name) ?>
                    </strong>

                    <span>
                        پروفایل من
                    </span>

                </div>

            </a>

        </div>

        <div class="menu-title">
            منوی اصلی
        </div>

        <nav class="menu">

            <a href="dashboard.php" class="active">
                <span class="menu-icon">🏠</span>
                داشبورد
            </a>

            <a href="trips.php">
                <span class="menu-icon">✈️</span>
                سفرهای من
            </a>

            <a href="add_trip.php">
                <span class="menu-icon">➕</span>
                ثبت سفر جدید
            </a>

            <a href="destinations.php">
                <span class="menu-icon">📍</span>
                مقصدها
            </a>

            <a href="itinerary.php">
                <span class="menu-icon">🗓️</span>
                برنامه سفر
            </a>

            <a href="expenses.php">
                <span class="menu-icon">💰</span>
                مصارف
            </a>

            <a href="provinces.php">
                <span class="menu-icon">🗺️</span>
                ولایات افغانستان
            </a>

            <div class="menu-title">
                حساب کاربری
            </div>

            <a href="profile.php">
                <span class="menu-icon">👤</span>
                پروفایل من
            </a>

            <a href="profile.php">
                <span class="menu-icon">⚙️</span>
                تنظیمات پروفایل
            </a>

            <a href="logout.php" class="logout">
                <span class="menu-icon">🚪</span>
                خروج از حساب
            </a>

        </nav>

    </aside>

    <!-- =========================
         Main
    ========================= -->

    <main class="main">

        <div class="topbar">

            <div class="top-title">

                <h1>
                    داشبورد
                </h1>

                <p>
                    مدیریت سفرها و برنامه‌های شما
                </p>

            </div>

            <a href="profile.php" class="top-profile">

                <?php if (!empty($photo_url)): ?>

                    <img
                        src="<?= htmlspecialchars($photo_url) ?>"
                        alt="پروفایل"
                    >

                <?php else: ?>

                    <div class="avatar">
                        <?= htmlspecialchars($first_letter) ?>
                    </div>

                <?php endif; ?>

                <span>
                    <?= htmlspecialchars($user_name) ?>
                </span>

            </a>

        </div>

        <!-- Welcome -->

        <section class="welcome">

            <h2>
                سلام <?= htmlspecialchars($user_name) ?> 👋
            </h2>

            <p>
                به Travel Planner خوش آمدید.
                سفرهای خود را آسان‌تر برنامه‌ریزی و مدیریت کنید.
            </p>

        </section>

        <!-- Stats -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-icon">
                    ✈️
                </div>

                <h3>
                    <?= number_format($total_trips) ?>
                </h3>

                <p>
                    مجموع سفرها
                </p>

            </div>

            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <h3>
                    <?= number_format($total_budget) ?>
                </h3>

                <p>
                    مجموع بودجه
                </p>

            </div>

            <div class="stat-card">

                <div class="stat-icon">
                    💳
                </div>

                <h3>
                    <?= number_format($total_expenses) ?>
                </h3>

                <p>
                    مجموع مصارف
                </p>

            </div>

            <div class="stat-card">

                <div class="stat-icon">
                    💵
                </div>

                <h3>
                    <?= number_format($remaining_budget) ?>
                </h3>

                <p>
                    بودجه باقی‌مانده
                </p>

            </div>

        </section>

        <!-- Content -->

        <section class="content-grid">

            <!-- =========================
                 آخرین سفرها
            ========================= -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        آخرین سفرها
                    </h2>

                    <a href="trips.php">
                        مشاهده همه
                    </a>

                </div>

                <?php if (!empty($recent_trips)): ?>

                    <?php foreach ($recent_trips as $trip): ?>

                        <div class="trip-card">

                            <div class="trip-info">

                                <h3>
                                    <?= htmlspecialchars($trip["title"]) ?>
                                </h3>

                                <p>
                                    📍
                                    <?= htmlspecialchars($trip["destination"]) ?>
                                </p>

                                <p>
                                    📅
                                    <?= htmlspecialchars($trip["start_date"]) ?>
                                    تا
                                    <?= htmlspecialchars($trip["end_date"]) ?>
                                </p>

                                <p>
                                    💰
                                    <?= number_format((float)$trip["budget"]) ?>
                                    افغانی
                                </p>

                            </div>

                            <a href="trips_details.php?id=<;?= (int)$trip["id"] ?>">
                                مشاهده جزئیات ←
                            </a>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ✈️
                        </div>

                        <h3>
                            هنوز سفری ثبت نشده است
                        </h3>

                        <p>
                            اولین سفر خود را ایجاد کنید.
                        </p>

                        <a
                            href="add_trip.php"
                            class="add-trip-btn"
                        >
                            + ثبت سفر جدید
                        </a>

                    </div>

                <?php endif; ?>

            </div>

            <!-- =========================
                 وضعیت بودجه
            ========================= -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        وضعیت بودجه
                    </h2>

                </div>

                <div class="budget-box">

                    <div class="circle">

                        <div class="circle-content">

                            <strong>
                                <?= round($budget_percent) ?>%
                            </strong>

                            <span>
                                مصرف شده
                            </span>

                        </div>

                    </div>

                    <div class="budget-details">

                        <div class="budget-row">

                            <span>
                                بودجه کل
                            </span>

                            <strong>
                                <?= number_format($total_budget) ?>
                                افغانی
                            </strong>

                        </div>

                        <div class="budget-row">

                            <span>
                                مصارف
                            </span>

                            <strong>
                                <?= number_format($total_expenses) ?>
                                افغانی
                            </strong>

                        </div>

                        <div class="budget-row">

                            <span>
                                باقی‌مانده
                            </span>

                            <strong>
                                <?= number_format($remaining_budget) ?>
                                افغانی
                            </strong>

                        </div>

                    </div>

                </div>

                <!-- Quick Actions -->

                <div class="quick-actions">

                    <a
                        href="add_trip.php"
                        class="quick-action"
                    >
                        <div>➕</div>
                        سفر جدید
                    </a>

                    <a
                        href="destinations.php"
                        class="quick-action"
                    >
                        <div>📍</div>
                        مقصد جدید
                    </a>

                    <a
                        href="itinerary.php"
                        class="quick-action"
                    >
                        <div>🗓️</div>
                        برنامه سفر
                    </a>

                    <a
                        href="expenses.php"
                        class="quick-action"
                    >
                        <div>💰</div>
                        ثبت مصرف
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>