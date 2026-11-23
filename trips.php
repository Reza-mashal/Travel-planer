<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$name = $_SESSION["name"] ?? "کاربر";

$photo = $_SESSION["photo"] ?? "";
if ($photo == "" || !file_exists($photo)) {
    $photo = "uploads/default.png";
}

/* حذف سفر */
if (isset($_GET["delete"])) {

    $delete_id = (int)$_GET["delete"];

    // ابتدا اطلاعات وابسته حذف می‌شود
    mysqli_query(
        $conn,
        "DELETE FROM itinerary
         WHERE trip_id = $delete_id
         AND trip_id IN (
             SELECT id FROM trips WHERE user_id = $user_id
         )"
    );

    mysqli_query(
        $conn,
        "DELETE FROM expenses
         WHERE trip_id = $delete_id
         AND trip_id IN (
             SELECT id FROM trips WHERE user_id = $user_id
         )"
    );

    mysqli_query(
        $conn,
        "DELETE FROM destinations
         WHERE trip_id = $delete_id
         AND trip_id IN (
             SELECT id FROM trips WHERE user_id = $user_id
         )"
    );

    mysqli_query(
        $conn,
        "DELETE FROM trips
         WHERE id = $delete_id
         AND user_id = $user_id"
    );

    header("Location: trips.php?deleted=1");
    exit();
}


/* پیام */
$message = "";

if (isset($_GET["deleted"])) {
    $message = "سفر با موفقیت حذف شد.";
}


/* جستجو */
$search = trim($_GET["search"] ?? "");

if ($search != "") {

    $safe_search = mysqli_real_escape_string(
        $conn,
        $search
    );

    $sql = "
        SELECT
            id,
            title,
            destination,
            start_date,
            end_date,
            budget
        FROM trips
        WHERE user_id = $user_id
        AND (
            title LIKE '%$safe_search%'
            OR destination LIKE '%$safe_search%'
        )
        ORDER BY id DESC
    ";

} else {

    $sql = "
        SELECT
            id,
            title,
            destination,
            start_date,
            end_date,
            budget
        FROM trips
        WHERE user_id = $user_id
        ORDER BY id DESC
    ";
}

$trips = mysqli_query($conn, $sql);


/* تعداد سفر */
$count_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM trips
     WHERE user_id = $user_id"
);

$total_trips =
    mysqli_fetch_assoc($count_query)["total"] ?? 0;


/* مجموع بودجه */
$budget_query = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(budget),0) AS total
     FROM trips
     WHERE user_id = $user_id"
);

$total_budget =
    mysqli_fetch_assoc($budget_query)["total"] ?? 0;

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>سفرهای من | Travel Planner</title>

<link rel="stylesheet" href="output.css">

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Tahoma,Arial,sans-serif;
    color:#1e293b;

    background:
    radial-gradient(
        circle at top right,
        #dbeafe,
        transparent 35%
    ),
    radial-gradient(
        circle at bottom left,
        #ede9fe,
        transparent 35%
    ),
    #f8fafc;
}


/* ANIMATION */

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(25px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes slideRight{
    from{
        opacity:0;
        transform:translateX(40px);
    }

    to{
        opacity:1;
        transform:translateX(0);
    }
}

@keyframes float{

    0%,100%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-8px);
    }
}


/* SIDEBAR */

.sidebar{

    position:fixed;

    right:0;
    top:0;

    width:270px;
    height:100vh;

    padding:25px 18px;

    background:
    linear-gradient(
        145deg,
        #312e81,
        #4f46e5,
        #7c3aed
    );

    box-shadow:
    -10px 0 30px
    rgba(30,41,59,.18);

    z-index:100;

    animation:slideRight .7s ease;
}

.sidebar::before{

    content:"";

    position:absolute;

    width:170px;
    height:170px;

    border-radius:50%;

    background:
    rgba(255,255,255,.08);

    top:-70px;
    right:-60px;
}

.sidebar::after{

    content:"";

    position:absolute;

    width:130px;
    height:130px;

    border-radius:50%;

    background:
    rgba(255,255,255,.06);

    bottom:-40px;
    left:-50px;
}


.logo{

    color:white;

    font-size:23px;

    font-weight:bold;

    text-align:center;

    padding:17px 10px;

    margin-bottom:25px;

    border-radius:18px;

    background:
    rgba(255,255,255,.12);

    border:
    1px solid
    rgba(255,255,255,.2);

    backdrop-filter:blur(10px);

    animation:
    float 4s ease-in-out infinite;
}


/* PROFILE */

.profile{

    display:flex;

    align-items:center;

    gap:12px;

    padding:14px;

    margin-bottom:25px;

    border-radius:18px;

    background:
    rgba(255,255,255,.12);

    border:
    1px solid
    rgba(255,255,255,.2);

    transition:.3s;
}

.profile:hover{
    transform:translateY(-4px);
}

.profile img{

    width:52px;
    height:52px;

    border-radius:50%;

    object-fit:cover;

    border:3px solid white;

    box-shadow:
    0 5px 15px
    rgba(0,0,0,.2);
}

.profile-name{
    color:white;
    font-weight:bold;
    font-size:14px;
}

.profile-text{
    color:rgba(255,255,255,.7);
    font-size:11px;
    margin-top:5px;
}


/* MENU */

.menu{

    display:flex;
    flex-direction:column;
    gap:8px;
}

.menu a{

    text-decoration:none;

    color:white;

    display:flex;

    align-items:center;

    gap:12px;

    padding:14px 15px;

    border-radius:14px;

    font-size:14px;

    transition:.3s;
}

.menu a:hover{

    transform:translateX(-6px);

    background:
    rgba(255,255,255,.16);

    box-shadow:
    0 8px 20px
    rgba(0,0,0,.1);
}

.menu a.active{

    background:
    rgba(255,255,255,.22);
}

.menu-icon{
    font-size:20px;
}


/* MAIN */

.main{

    margin-right:270px;

    padding:30px;

    animation:fadeUp .7s ease;
}


/* HEADER */

.header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    padding:30px;

    margin-bottom:25px;

    border-radius:25px;

    background:
    linear-gradient(
        135deg,
        white,
        #f8fafc
    );

    border:
    1px solid #e2e8f0;

    box-shadow:
    0 15px 40px
    rgba(15,23,42,.07);
}

.header h1{

    margin:0;

    color:#1e1b4b;

    font-size:27px;
}

.header p{

    margin:8px 0 0;

    color:#64748b;

    font-size:14px;
}

.header-btn{

    text-decoration:none;

    color:white;

    font-weight:bold;

    padding:13px 20px;

    border-radius:14px;

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );

    box-shadow:
    0 10px 20px
    rgba(79,70,229,.25);

    transition:.3s;

    white-space:nowrap;
}

.header-btn:hover{

    transform:
    translateY(-4px)
    scale(1.03);
}


/* MESSAGE */

.message{

    padding:15px 18px;

    border-radius:15px;

    margin-bottom:20px;

    background:#dcfce7;

    color:#166534;

    border:
    1px solid #bbf7d0;

    animation:fadeUp .5s ease;
}


/* STATS */

.stats{

    display:grid;

    grid-template-columns:
    repeat(2,1fr);

    gap:20px;

    margin-bottom:25px;
}

.stat{

    padding:22px;

    border-radius:22px;

    background:
    rgba(255,255,255,.85);

    border:
    1px solid #e2e8f0;

    box-shadow:
    0 10px 30px
    rgba(15,23,42,.06);

    transition:.3s;
}

.stat:hover{

    transform:
    translateY(-7px)
    scale(1.01);
}

.stat-icon{

    width:50px;
    height:50px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:16px;

    background:
    linear-gradient(
        135deg,
        #6366f1,
        #8b5cf6
    );

    color:white;

    font-size:23px;

    margin-bottom:12px;
}

.stat-title{

    color:#64748b;

    font-size:13px;
}

.stat-number{

    color:#4338ca;

    font-size:24px;

    font-weight:bold;

    margin-top:6px;
}


/* SEARCH */

.search-box{

    display:flex;

    gap:10px;

    margin-bottom:25px;

    padding:20px;

    background:white;

    border:
    1px solid #e2e8f0;

    border-radius:20px;

    box-shadow:
    0 10px 30px
    rgba(15,23,42,.05);
}

.search-input{

    flex:1;

    border:
    1px solid #cbd5e1;

    border-radius:13px;

    padding:13px 16px;

    font-family:inherit;

    font-size:14px;

    outline:none;

    transition:.3s;
}

.search-input:focus{

    border-color:#6366f1;

    box-shadow:
    0 0 0 4px
    rgba(99,102,241,.1);
}

.search-btn{

    border:none;

    cursor:pointer;

    padding:0 25px;

    border-radius:13px;

    color:white;

    font-family:inherit;

    font-weight:bold;

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );

    transition:.3s;
}

.search-btn:hover{
    transform:translateY(-3px);
}

.clear-btn{

    display:flex;

    align-items:center;

    justify-content:center;

    text-decoration:none;

    padding:0 18px;

    border-radius:13px;

    background:#f1f5f9;

    color:#475569;

    font-size:13px;
}


/* SECTION */

.section{

    padding:25px;

    border-radius:25px;

    background:
    rgba(255,255,255,.82);

    border:
    1px solid #e2e8f0;

    box-shadow:
    0 15px 40px
    rgba(15,23,42,.06);
}

.section-title{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:20px;
}

.section-title h2{

    margin:0;

    color:#1e1b4b;

    font-size:20px;
}

.section-title span{

    color:#64748b;

    font-size:12px;
}


/* TRIP GRID */

.trip-grid{

    display:grid;

    grid-template-columns:
    repeat(3,1fr);

    gap:20px;
}

.trip-card{

    position:relative;

    padding:22px;

    border-radius:22px;

    background:white;

    border:
    1px solid #e2e8f0;

    box-shadow:
    0 10px 25px
    rgba(15,23,42,.06);

    transition:.35s;

    overflow:hidden;
}

.trip-card::before{

    content:"";

    position:absolute;

    top:0;
    right:0;

    width:100%;
    height:5px;

    background:
    linear-gradient(
        90deg,
        #4f46e5,
        #8b5cf6
    );
}

.trip-card:hover{

    transform:
    translateY(-8px)
    scale(1.01);

    box-shadow:
    0 18px 35px
    rgba(15,23,42,.1);
}

.trip-icon{

    width:52px;
    height:52px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:16px;

    background:
    linear-gradient(
        135deg,
        #eef2ff,
        #ede9fe
    );

    font-size:25px;

    margin-bottom:15px;
}

.trip-title{

    color:#1e1b4b;

    font-size:18px;

    font-weight:bold;

    margin-bottom:15px;
}

.trip-info{

    display:flex;

    flex-direction:column;

    gap:9px;

    margin-bottom:18px;
}

.info-row{

    display:flex;

    justify-content:space-between;

    gap:10px;

    font-size:12px;

    color:#64748b;

    padding-bottom:8px;

    border-bottom:
    1px dashed #e2e8f0;
}

.info-row strong{

    color:#334155;

    text-align:left;
}

.budget{

    color:#16a34a !important;
}


/* ACTIONS */

.trip-actions{

    display:grid;

    grid-template-columns:
    1fr 1fr;

    gap:8px;
}

.trip-btn{

    text-decoration:none;

    text-align:center;

    padding:11px 8px;

    border-radius:12px;

    font-size:12px;

    font-weight:bold;

    transition:.3s;
}

.view-btn{

    color:#4338ca;

    background:#eef2ff;
}

.view-btn:hover{

    color:white;

    background:#4f46e5;

    transform:translateY(-2px);
}

.delete-btn{

    color:#dc2626;

    background:#fef2f2;
}

.delete-btn:hover{

    color:white;

    background:#dc2626;

    transform:translateY(-2px);
}


/* EMPTY */

.empty{

    text-align:center;

    padding:60px 20px;

    color:#64748b;
}

.empty-icon{

    font-size:60px;

    margin-bottom:15px;

    animation:
    float 3s ease-in-out infinite;
}

.empty h3{

    margin:0 0 10px;

    color:#334155;
}

.empty p{

    font-size:13px;

    margin-bottom:20px;
}

.add-btn{

    display:inline-block;

    text-decoration:none;

    color:white;

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );

    padding:13px 22px;

    border-radius:13px;

    font-size:13px;

    font-weight:bold;
}


/* FOOTER */

.footer{

    text-align:center;

    margin-top:25px;

    color:#94a3b8;

    font-size:12px;
}


/* RESPONSIVE */

@media(max-width:1100px){

    .trip-grid{
        grid-template-columns:
        repeat(2,1fr);
    }

}


@media(max-width:800px){

    .sidebar{

        position:relative;

        width:100%;

        height:auto;
    }

    .main{

        margin-right:0;

        padding:18px;
    }

    .menu{

        display:grid;

        grid-template-columns:
        repeat(2,1fr);
    }

    .header{

        flex-direction:column;

        align-items:flex-start;
    }

    .header-btn{

        width:100%;

        text-align:center;
    }

}


@media(max-width:600px){

    .stats{
        grid-template-columns:1fr;
    }

    .trip-grid{
        grid-template-columns:1fr;
    }

    .search-box{
        flex-direction:column;
    }

    .search-btn{
        height:45px;
    }

    .clear-btn{
        height:42px;
    }

    .menu{
        grid-template-columns:1fr;
    }

    .header{
        padding:22px;
    }

    .header h1{
        font-size:21px;
    }

    .section{
        padding:20px;
    }

}

</style>

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        ✈️ Travel Planner
    </div>


    <div class="profile">

        <img
            src="<?php echo htmlspecialchars($photo); ?>"
            alt="Profile"
        >

        <div>

            <div class="profile-name">
                <?php echo htmlspecialchars($name); ?>
            </div>

            <div class="profile-text">
                برنامه‌ریز سفر
            </div>

        </div>

    </div>


    <nav class="menu">

        <a href="dashboard.php">
            <span class="menu-icon">🏠</span>
            داشبورد
        </a>

        <a href="trips.php" class="active">
            <span class="menu-icon">🧳</span>
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
            <span class="menu-icon">📅</span>
            برنامه سفر
        </a>

        <a href="expenses.php">
            <span class="menu-icon">💰</span>
            مصارف
        </a>

        <a href="provinces.php">
            <span class="menu-icon">🗺️</span>
            ولایات
        </a>

        <a href="logout.php">
            <span class="menu-icon">🚪</span>
            خروج
        </a>

    </nav>

</aside>


<!-- MAIN -->

<main class="main">


    <!-- HEADER -->

    <section class="header">

        <div>

            <h1>
                🧳 سفرهای من
            </h1>

            <p>
                سفرهای خود را مشاهده و مدیریت کنید.
            </p>

        </div>


        <a
            href="add_trip.php"
            class="header-btn"
        >
            ➕ ثبت سفر جدید
        </a>

    </section>


    <?php if ($message != ""): ?>

        <div class="message">
            ✅ <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- STATS -->

    <section class="stats">

        <div class="stat">

            <div class="stat-icon">
                🧳
            </div>

            <div class="stat-title">
                تعداد کل سفرها
            </div>

            <div class="stat-number">
                <?php echo number_format($total_trips); ?>
            </div>

        </div>


        <div class="stat">

            <div class="stat-icon">
                💰
            </div>

            <div class="stat-title">
                مجموع بودجه
            </div>

            <div class="stat-number">

                <?php
                echo number_format(
                    (float)$total_budget
                );
                ?>

                افغانی

            </div>

        </div>

    </section>


    <!-- SEARCH -->

    <form
        method="GET"
        class="search-box"
    >

        <input
            type="text"
            name="search"
            class="search-input"
            placeholder="🔎 جستجوی نام سفر یا مقصد..."
            value="<?php echo htmlspecialchars($search); ?>"
        >


        <button
            type="submit"
            class="search-btn"
        >
            جستجو
        </button>


        <?php if ($search != ""): ?>

            <a
                href="trips.php"
                class="clear-btn"
            >
                پاک کردن
            </a>

        <?php endif; ?>

    </form>


    <!-- TRIPS -->

    <section class="section">

        <div class="section-title">

            <h2>
                🗂️ لیست سفرها
            </h2>

            <span>
                <?php echo number_format($total_trips); ?>
                سفر
            </span>

        </div>


        <?php if ($trips && mysqli_num_rows($trips) > 0): ?>


            <div class="trip-grid">


                <?php while ($trip = mysqli_fetch_assoc($trips)): ?>


                    <div class="trip-card">


                        <div class="trip-icon">
                            ✈️
                        </div>


                        <div class="trip-title">

                            <?php
                            echo htmlspecialchars(
                                $trip["title"]
                            );
                            ?>

                        </div>


                        <div class="trip-info">


                            <div class="info-row">

                                <span>
                                    📍 مقصد
                                </span>

                                <strong>

                                    <?php

                                    echo !empty(
                                        $trip["destination"]
                                    )
                                    ? htmlspecialchars(
                                        $trip["destination"]
                                    )
                                    : "ثبت نشده";

                                    ?>

                                </strong>

                            </div>


                            <div class="info-row">

                                <span>
                                    📅 شروع
                                </span>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $trip["start_date"] ?? "-"
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div class="info-row">

                                <span>
                                    📅 پایان
                                </span>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $trip["end_date"] ?? "-"
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div class="info-row">

                                <span>
                                    💰 بودجه
                                </span>

                                <strong class="budget">

                                    <?php
                                    echo number_format(
                                        (float)$trip["budget"]
                                    );
                                    ?>

                                    افغانی

                                </strong>

                            </div>


                        </div>


                        <div class="trip-actions">


                            <a
                                href="trips_details.php?id=<?php echo (int)$trip["id"]; ?>"
                                class="trip-btn view-btn"
                            >
                                📋 جزئیات
                            </a>


                            <a
                                href="trips.php?delete=<?php echo (int)$trip["id"]; ?>"
                                class="trip-btn delete-btn"
                                onclick="return confirm('آیا مطمئن هستید که می‌خواهید این سفر را حذف کنید؟');"
                            >
                                🗑️ حذف
                            </a>


                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div class="empty">

                <div class="empty-icon">
                    🧳
                </div>

                <h3>

                    <?php

                    echo $search != ""
                        ? "سفری پیدا نشد"
                        : "هنوز سفری ثبت نشده است";

                    ?>

                </h3>


                <p>

                    <?php

                    echo $search != ""
                        ? "عبارت جستجوی دیگری را امتحان کنید."
                        : "اولین سفر خود را ثبت کنید.";

                    ?>

                </p>


                <?php if ($search == ""): ?>

                    <a
                        href="add_trip.php"
                        class="add-btn"
                    >
                        ➕ ثبت اولین سفر
                    </a>

                <?php endif; ?>

            </div>


        <?php endif; ?>

    </section>


    <div class="footer">

        Travel Planner © 2026

        <br>

        سیستم هوشمند برنامه‌ریزی سفر

    </div>


</main>

</body>
</html>