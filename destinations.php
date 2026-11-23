<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"] ?? "کاربر";

$message = "";
$messageType = "";


/* =========================================================
   DELETE DESTINATION
========================================================= */

if (isset($_GET["delete"])) {

    $destination_id = intval($_GET["delete"]);

    $stmt = $conn->prepare("
        DELETE d
        FROM destinations d
        INNER JOIN trips t ON d.trip_id = t.id
        WHERE d.id = ? AND t.user_id = ?
    ");

    $stmt->bind_param("ii", $destination_id, $user_id);

    if ($stmt->execute()) {

        $message = "مقصد با موفقیت حذف شد.";
        $messageType = "success";

    } else {

        $message = "حذف مقصد انجام نشد.";
        $messageType = "error";
    }

    $stmt->close();
}


/* =========================================================
   ADD DESTINATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $trip_id = intval($_POST["trip_id"] ?? 0);

    $destination_name =
        trim($_POST["name"] ?? "");

    $country =
        trim($_POST["country"] ?? "");

    $description =
        trim($_POST["description"] ?? "");


    if ($trip_id <= 0 || $destination_name === "") {

        $message =
            "لطفاً سفر و نام مقصد را انتخاب کنید.";

        $messageType = "error";

    } else {

        /* بررسی مالکیت سفر */

        $check = $conn->prepare("
            SELECT id
            FROM trips
            WHERE id = ? AND user_id = ?
        ");

        $check->bind_param(
            "ii",
            $trip_id,
            $user_id
        );

        $check->execute();

        $check->store_result();


        if ($check->num_rows === 0) {

            $message =
                "این سفر متعلق به حساب شما نیست.";

            $messageType = "error";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO destinations
                (trip_id, name, country, description)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isss",
                $trip_id,
                $destination_name,
                $country,
                $description
            );


            if ($stmt->execute()) {

                $message =
                    "مقصد با موفقیت ثبت شد.";

                $messageType = "success";

            } else {

                $message =
                    "خطا در ثبت مقصد.";

                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/* =========================================================
   GET USER TRIPS
========================================================= */

$trips = [];

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        destination,
        start_date,
        end_date
    FROM trips
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result =
    $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $trips[] = $row;
}

$stmt->close();


/* =========================================================
   GET DESTINATIONS
========================================================= */

$destinations = [];

$stmt = $conn->prepare("
    SELECT
        d.id,
        d.trip_id,
        d.name,
        d.country,
        d.description,
        t.title AS trip_title,
        t.destination AS trip_destination
    FROM destinations d
    INNER JOIN trips t
        ON d.trip_id = t.id
    WHERE t.user_id = ?
    ORDER BY d.id DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result =
    $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $destinations[] = $row;
}

$stmt->close();


$totalDestinations =
    count($destinations);

$totalTrips =
    count($trips);


/* =========================================================
   34 PROVINCES OF AFGHANISTAN
========================================================= */

$provinces = [

    "بدخشان",
    "بدغیس",
    "بغلان",
    "بلخ",
    "بامیان",
    "دایکندی",
    "فراه",
    "فاریاب",
    "غزنی",
    "غور",
    "هلمند",
    "هرات",
    "جوزجان",
    "کابل",
    "قندهار",
    "کاپیسا",
    "خوست",
    "کنر",
    "کندز",
    "لغمان",
    "لوگر",
    "ننگرهار",
    "نیمروز",
    "نورستان",
    "پکتیا",
    "پکتیکا",
    "پنجشیر",
    "پروان",
    "سمنگان",
    "سرپل",
    "تخار",
    "ارزگان",
    "میدان وردک",
    "زابل"

];

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
    مقصدها | Travel Planner
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;
}


/* =========================================================
   BODY
========================================================= */

body {

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:

        radial-gradient(
            circle at top right,
            #243b55 0%,
            #101827 40%,
            #070b13 100%
        );

    color: #ffffff;

    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    right: 0;
    top: 0;

    width: 250px;

    height: 100vh;

    padding: 25px 15px;

    background:
        rgba(8, 15, 28, .95);

    backdrop-filter:
        blur(18px);

    border-left:
        1px solid
        rgba(255,255,255,.08);

    z-index: 1000;
}


.logo {

    text-align: center;

    font-size: 24px;

    font-weight: bold;

    margin-bottom: 35px;

    background:
        linear-gradient(
            90deg,
            #38bdf8,
            #a78bfa
        );

    -webkit-background-clip: text;

    background-clip: text;

    color: transparent;
}


.menu {

    display: flex;

    flex-direction: column;

    gap: 8px;
}


.menu a {

    display: block;

    padding: 13px 15px;

    border-radius: 13px;

    text-decoration: none;

    color: #cbd5e1;

    transition:
        .3s ease;
}


.menu a:hover,
.menu a.active {

    color: #ffffff;

    background:

        linear-gradient(
            90deg,
            rgba(56,189,248,.18),
            rgba(139,92,246,.18)
        );

    transform:
        translateX(-5px);
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-right: 250px;

    padding: 35px;

    max-width: 1500px;
}


/* =========================================================
   HEADER
========================================================= */

.header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 20px;

    margin-bottom: 30px;
}


.header h1 {

    font-size: 32px;

    margin-bottom: 8px;
}


.header p {

    color: #94a3b8;

    font-size: 14px;
}


.user {

    padding:
        11px 18px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.08);
}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 15px 18px;

    border-radius: 14px;

    margin-bottom: 20px;

    animation:
        messageIn .4s ease;
}


.success {

    color: #86efac;

    background:
        rgba(34,197,94,.12);

    border:
        1px solid
        rgba(34,197,94,.25);
}


.error {

    color: #fca5a5;

    background:
        rgba(239,68,68,.12);

    border:
        1px solid
        rgba(239,68,68,.25);
}


@keyframes messageIn {

    from {

        opacity: 0;

        transform:
            translateY(-10px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* =========================================================
   STATS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}


.stat {

    padding: 24px;

    border-radius: 20px;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid
        rgba(255,255,255,.08);

    backdrop-filter:
        blur(15px);

    transition:
        .3s ease;
}


.stat:hover {

    transform:
        translateY(-5px);

    border-color:
        rgba(56,189,248,.5);
}


.stat span {

    display: block;

    color: #94a3b8;

    margin-bottom: 8px;
}


.stat strong {

    color: #38bdf8;

    font-size: 30px;
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    display: grid;

    grid-template-columns:
        1fr 1.3fr;

    gap: 25px;

    margin-bottom: 25px;
}


/* =========================================================
   CARD
========================================================= */

.card {

    padding: 25px;

    border-radius: 22px;

    background:
        rgba(255,255,255,.055);

    border:
        1px solid
        rgba(255,255,255,.08);

    backdrop-filter:
        blur(18px);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.20);
}


.card h2 {

    font-size: 20px;

    margin-bottom: 20px;
}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom: 16px;
}


.form-group label {

    display: block;

    color: #cbd5e1;

    font-size: 14px;

    margin-bottom: 8px;
}


input,
select,
textarea {

    width: 100%;

    padding: 13px;

    border-radius: 12px;

    border:
        1px solid
        rgba(255,255,255,.1);

    outline: none;

    color: #ffffff;

    background:
        rgba(15,23,42,.8);

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    transition:
        .3s ease;
}


input:focus,
select:focus,
textarea:focus {

    border-color:
        #38bdf8;

    box-shadow:
        0 0 0 3px
        rgba(56,189,248,.1);
}


select option {

    background:
        #111827;

    color:
        #ffffff;
}


textarea {

    min-height: 110px;

    resize: vertical;
}


.btn {

    width: 100%;

    border: none;

    padding: 14px;

    border-radius: 13px;

    color: #ffffff;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    background:

        linear-gradient(
            135deg,
            #0ea5e9,
            #8b5cf6
        );

    transition:
        .3s ease;
}


.btn:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 12px 30px
        rgba(14,165,233,.25);
}


/* =========================================================
   MAP
========================================================= */

.map-box {

    padding: 10px;

    border-radius: 18px;

    background:
        rgba(15,23,42,.65);

    overflow: hidden;
}


.map-box img {

    display: block;

    width: 100%;

    height: 390px;

    object-fit: contain;

    border-radius: 15px;

    transition:
        .4s ease;
}


.map-box img:hover {

    transform:
        scale(1.02);
}


/* =========================================================
   PROVINCE SELECT
========================================================= */

.province-title {

    margin-top: 20px;

    margin-bottom: 12px;

    color: #cbd5e1;

    font-size: 14px;
}


.province-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 8px;
}


.province {

    border: none;

    padding: 10px 5px;

    border-radius: 10px;

    color: #cbd5e1;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid
        rgba(255,255,255,.07);

    cursor: pointer;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    transition:
        .25s ease;
}


.province:hover {

    color: #ffffff;

    background:
        rgba(56,189,248,.20);

    border-color:
        #38bdf8;

    transform:
        translateY(-2px);
}


.province.selected {

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #0ea5e9,
            #8b5cf6
        );

    border-color:
        transparent;

    box-shadow:
        0 8px 20px
        rgba(14,165,233,.20);
}


/* =========================================================
   SELECTED PROVINCE
========================================================= */

.selected-place {

    display: none;

    margin-top: 14px;

    padding: 13px;

    text-align: center;

    border-radius: 12px;

    color: #7dd3fc;

    background:
        rgba(56,189,248,.10);

    border:
        1px solid
        rgba(56,189,248,.15);
}


/* =========================================================
   SEARCH
========================================================= */

.search {

    margin-bottom: 20px;
}


.search input {

    padding:
        14px 18px;
}


/* =========================================================
   DESTINATION GRID
========================================================= */

.destination-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}


.destination {

    padding: 20px;

    border-radius: 18px;

    background:
        rgba(255,255,255,.05);

    border:
        1px solid
        rgba(255,255,255,.08);

    transition:
        .3s ease;

    animation:
        cardIn .5s ease;
}


.destination:hover {

    transform:
        translateY(-6px);

    border-color:
        rgba(56,189,248,.4);

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.18);
}


@keyframes cardIn {

    from {

        opacity: 0;

        transform:
            translateY(12px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


.icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    margin-bottom: 15px;

    font-size: 22px;

    background:

        linear-gradient(
            135deg,
            #0ea5e9,
            #8b5cf6
        );
}


.destination h3 {

    font-size: 18px;

    margin-bottom: 8px;
}


.country {

    color: #38bdf8;

    font-size: 13px;

    margin-bottom: 8px;
}


.trip {

    color: #94a3b8;

    font-size: 13px;

    margin-bottom: 12px;
}


.description {

    color: #cbd5e1;

    font-size: 14px;

    line-height: 1.8;

    min-height: 50px;

    margin-bottom: 15px;
}


.delete {

    display: inline-block;

    text-decoration: none;

    color: #fca5a5;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid
        rgba(239,68,68,.20);

    padding:
        8px 12px;

    border-radius: 9px;

    font-size: 12px;

    transition:
        .3s ease;
}


.delete:hover {

    color: #ffffff;

    background:
        rgba(239,68,68,.22);

    transform:
        translateY(-2px);
}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    text-align: center;

    padding: 45px 20px;

    color: #94a3b8;

    line-height: 2;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .content {

        grid-template-columns:
            1fr;
    }

    .destination-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media(max-width:800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        border-left: none;

        border-bottom:
            1px solid
            rgba(255,255,255,.08);
    }


    .main {

        margin-right: 0;

        padding: 20px;
    }


    .menu {

        display: grid;

        grid-template-columns:
            repeat(2, 1fr);
    }


    .header {

        flex-direction: column;

        align-items: flex-start;
    }
}


@media(max-width:600px) {

    .stats {

        grid-template-columns:
            1fr;
    }


    .province-grid {

        grid-template-columns:
            repeat(3, 1fr);
    }


    .destination-grid {

        grid-template-columns:
            1fr;
    }


    .map-box img {

        height: 280px;
    }


    .card {

        padding: 18px;
    }


    .header h1 {

        font-size: 25px;
    }
}

</style>

</head>


<body>


<!-- =======================================================
     SIDEBAR
======================================================= -->

<aside class="sidebar">

    <div class="logo">
        ✈ Travel Planner
    </div>


    <nav class="menu">

        <a href="dashboard.php">
            🏠 داشبورد
        </a>


        <a href="trips.php">
            🧳 سفرهای من
        </a>


        <a href="add_trip.php">
            ➕ ثبت سفر جدید
        </a>


        <a
            href="destinations.php"
            class="active"
        >
            📍 مقصدها
        </a>


        <a href="itinerary.php">
            📅 برنامه سفر
        </a>


        <a href="expenses.php">
            💰 مصارف
        </a>


        <a href="provinces.php">
            🗺 ولایت‌ها
        </a>


        <a href="logout.php">
            🚪 خروج
        </a>

    </nav>

</aside>


<!-- =======================================================
     MAIN
======================================================= -->

<main class="main">


<!-- HEADER -->

<header class="header">

    <div>

        <h1>
            📍 مقصدهای سفر
        </h1>

        <p>
            ولایت مورد نظر را انتخاب کن و مقصد خود را ثبت کن.
        </p>

    </div>


    <div class="user">

        👤
        <?= htmlspecialchars($name) ?>

    </div>

</header>


<!-- MESSAGE -->

<?php if ($message): ?>

    <div
        class="message <?= htmlspecialchars($messageType) ?>"
    >

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<!-- =======================================================
     STATS
======================================================= -->

<section class="stats">


    <div class="stat">

        <span>
            تعداد مقصدها
        </span>

        <strong>
            <?= $totalDestinations ?>
        </strong>

    </div>


    <div class="stat">

        <span>
            تعداد سفرها
        </span>

        <strong>
            <?= $totalTrips ?>
        </strong>

    </div>


</section>


<!-- =======================================================
     FORM + MAP
======================================================= -->

<section class="content">


<!-- =====================================================
     FORM
===================================================== -->

<div class="card">

    <h2>
        ➕ ثبت مقصد جدید
    </h2>


    <form method="POST">


        <!-- TRIP -->

        <div class="form-group">

            <label>
                انتخاب سفر
            </label>


            <select
                name="trip_id"
                required
            >

                <option value="">
                    -- یک سفر را انتخاب کنید --
                </option>


                <?php foreach ($trips as $trip): ?>

                    <option
                        value="<?= $trip["id"] ?>"
                    >

                        <?= htmlspecialchars(
                            $trip["title"]
                        ) ?>

                        —

                        <?= htmlspecialchars(
                            $trip["destination"]
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- NAME -->

        <div class="form-group">

            <label>
                نام مقصد / ولایت
            </label>


            <input
                type="text"
                name="name"
                id="destinationName"
                placeholder="مثلاً بلخ"
                required
            >

        </div>


        <!-- COUNTRY -->

        <div class="form-group">

            <label>
                کشور
            </label>


            <input
                type="text"
                name="country"
                id="country"
                value="افغانستان"
            >

        </div>


        <!-- DESCRIPTION -->

        <div class="form-group">

            <label>
                توضیحات
            </label>


            <textarea
                name="description"
                placeholder="توضیحات مربوط به مقصد..."
            ></textarea>

        </div>


        <button
            type="submit"
            class="btn"
        >

            📍 ثبت مقصد

        </button>


    </form>

</div>


<!-- =====================================================
     MAP
===================================================== -->

<div class="card">

    <h2>
        🗺 نقشه افغانستان
    </h2>


    <div class="map-box">

        <?php if (
            file_exists("afghanistan-map.jpg")
        ): ?>

            <img
                src="afghanistan-map.jpg"
                alt="نقشه افغانستان"
            >

        <?php else: ?>

            <div class="empty">

                ⚠️ تصویر نقشه پیدا نشد.

                <br>

                فایل زیر را داخل پوشه پروژه قرار بده:

                <br><br>

                <b>
                    afghanistan-map.jpg
                </b>

                <br><br>

                مسیر:

                <br>

                C:\xampp\htdocs\travel_planner\

            </div>

        <?php endif; ?>

    </div>


    <div class="province-title">

        📍 ولایت مورد نظر را انتخاب کن:

    </div>


    <div class="province-grid">


        <?php foreach (
            $provinces
            as $province
        ): ?>


            <button
                type="button"
                class="province"
                onclick="selectProvince(this, '<?= htmlspecialchars(
                    $province,
                    ENT_QUOTES
                ) ?>')"
            >

                <?= htmlspecialchars(
                    $province
                ) ?>

            </button>


        <?php endforeach; ?>


    </div>


    <div
        class="selected-place"
        id="selectedPlace"
    ></div>


</div>


</section>


<!-- =======================================================
     DESTINATIONS LIST
======================================================= -->

<div class="card">


    <h2>
        📌 مقصدهای ثبت‌شده
    </h2>


    <div class="search">

        <input
            type="text"
            id="search"
            placeholder="🔎 جستجو در مقصدها..."
            onkeyup="searchDestinations()"
        >

    </div>


    <?php if (
        count($destinations) > 0
    ): ?>


        <div
            class="destination-grid"
            id="destinationGrid"
        >


            <?php foreach (
                $destinations
                as $destination
            ): ?>


                <article
                    class="destination"
                    data-search="<?= htmlspecialchars(
                        strtolower(
                            $destination["name"]
                            . " "
                            . ($destination["country"] ?? "")
                            . " "
                            . ($destination["trip_title"] ?? "")
                        )
                    ) ?>"
                >


                    <div class="icon">
                        📍
                    </div>


                    <h3>

                        <?= htmlspecialchars(
                            $destination["name"]
                        ) ?>

                    </h3>


                    <?php if (
                        !empty(
                            $destination["country"]
                        )
                    ): ?>

                        <div class="country">

                            🌍

                            <?= htmlspecialchars(
                                $destination["country"]
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <div class="trip">

                        🧳

                        سفر:

                        <?= htmlspecialchars(
                            $destination["trip_title"]
                        ) ?>

                    </div>


                    <div class="description">

                        <?php if (
                            !empty(
                                $destination["description"]
                            )
                        ): ?>

                            <?= nl2br(
                                htmlspecialchars(
                                    $destination["description"]
                                )
                            ) ?>

                        <?php else: ?>

                            توضیحی برای این مقصد ثبت نشده است.

                        <?php endif; ?>

                    </div>


                    <a
                        href="destinations.php?delete=<;?= $destination["id"] ?>"
                        class="delete"
                        onclick="return confirm('آیا مطمئن هستید که می‌خواهید این مقصد را حذف کنید؟');"
                    >

                        🗑 حذف مقصد

                    </a>


                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            📍 هنوز هیچ مقصدی ثبت نشده است.

            <br>

            از فرم بالا اولین مقصد خود را اضافه کن.

        </div>


    <?php endif; ?>


</div>


</main>


<!-- =======================================================
     JAVASCRIPT
======================================================= -->

<script>


/* =========================================================
   SELECT PROVINCE
========================================================= */

function selectProvince(
    button,
    province
) {

    /*
       Remove previous selection
    */

    const buttons =
        document.querySelectorAll(
            ".province"
        );


    buttons.forEach(function(btn) {

        btn.classList.remove(
            "selected"
        );

    });


    /*
       Select current button
    */

    button.classList.add(
        "selected"
    );


    /*
       Get form fields
    */

    const nameInput =
        document.getElementById(
            "destinationName"
        );


    const countryInput =
        document.getElementById(
            "country"
        );


    const selectedPlace =
        document.getElementById(
            "selectedPlace"
        );


    /*
       Put province into form
    */

    nameInput.value =
        province;


    countryInput.value =
        "افغانستان";


    /*
       Show selected province
    */

    selectedPlace.style.display =
        "block";


    selectedPlace.innerHTML =
        "📍 ولایت انتخاب‌شده: <strong>"
        +
        province
        +
        "</strong>";


    /*
       Scroll to form
    */

    nameInput.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });


    /*
       Focus input
    */

    setTimeout(function() {

        nameInput.focus();

    }, 400);

}


/* =========================================================
   SEARCH DESTINATIONS
========================================================= */

function searchDestinations() {

    const input =
        document.getElementById(
            "search"
        );


    const value =
        input.value
        .toLowerCase()
        .trim();


    const cards =
        document.querySelectorAll(
            ".destination"
        );


    cards.forEach(function(card) {

        const text =
            card.dataset.search
            .toLowerCase();


        if (
            text.includes(value)
        ) {

            card.style.display =
                "";

        } else {

            card.style.display =
                "none";
        }

    });

}

</script>


</body>

</html>