<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

$title = "";
$destination = "";
$start_date = "";
$end_date = "";
$budget = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = (int)$_SESSION["user_id"];

    $title = trim($_POST["title"] ?? "");
    $destination = trim($_POST["destination"] ?? "");
    $start_date = $_POST["start_date"] ?? "";
    $end_date = $_POST["end_date"] ?? "";
    $budget = $_POST["budget"] ?? "";

    if (
        $title === "" ||
        $destination === "" ||
        $start_date === "" ||
        $end_date === ""
    ) {

        $message = "لطفاً تمام فیلدهای ضروری را پر کنید.";
        $message_type = "error";

    } elseif ($end_date < $start_date) {

        $message = "تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.";
        $message_type = "error";

    } else {

        $budget_value = ($budget === "") ? 0 : (float)$budget;

        $sql = "INSERT INTO trips
        (user_id, title, destination, start_date, end_date, budget)
        VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "issssd",
                $user_id,
                $title,
                $destination,
                $start_date,
                $end_date,
                $budget_value
            );

            if ($stmt->execute()) {

                $message = "🎉 سفر با موفقیت ثبت شد!";
                $message_type = "success";

                $title = "";
                $destination = "";
                $start_date = "";
                $end_date = "";
                $budget = "";

            } else {

                $message = "خطا در ثبت سفر: " . $stmt->error;
                $message_type = "error";
            }

            $stmt->close();

        } else {

            $message = "خطا در آماده‌سازی درخواست: " . $conn->error;
            $message_type = "error";
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

<title>ثبت سفر جدید | Travel Planner</title>

<style>

/* =========================
   RESET
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{

    font-family:
    Tahoma,
    Arial,
    sans-serif;

    min-height:100vh;

    color:#1e293b;

    background:
    radial-gradient(
        circle at 10% 10%,
        rgba(99,102,241,.30),
        transparent 30%
    ),
    radial-gradient(
        circle at 90% 20%,
        rgba(168,85,247,.25),
        transparent 30%
    ),
    radial-gradient(
        circle at 50% 100%,
        rgba(14,165,233,.18),
        transparent 35%
    ),
    #f8fafc;

    overflow-x:hidden;
}


/* =========================
   BACKGROUND SHAPES
========================= */

body::before{

    content:"";

    position:fixed;

    width:350px;
    height:350px;

    border-radius:50%;

    background:
    linear-gradient(
        135deg,
        rgba(99,102,241,.15),
        rgba(168,85,247,.08)
    );

    top:-130px;
    right:-100px;

    filter:blur(5px);

    animation:
    floating 8s ease-in-out infinite;

    pointer-events:none;

    z-index:-1;
}

body::after{

    content:"";

    position:fixed;

    width:300px;
    height:300px;

    border-radius:50%;

    background:
    rgba(14,165,233,.10);

    bottom:-120px;
    left:-100px;

    filter:blur(5px);

    animation:
    floating 10s ease-in-out infinite reverse;

    pointer-events:none;

    z-index:-1;
}


/* =========================
   ANIMATIONS
========================= */

@keyframes floating{

    0%,100%{
        transform:
        translateY(0)
        translateX(0);
    }

    50%{
        transform:
        translateY(-25px)
        translateX(15px);
    }
}


@keyframes fadeDown{

    from{
        opacity:0;
        transform:translateY(-30px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}


@keyframes fadeUp{

    from{
        opacity:0;
        transform:translateY(35px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}


@keyframes scaleIn{

    from{
        opacity:0;
        transform:scale(.92);
    }

    to{
        opacity:1;
        transform:scale(1);
    }
}


@keyframes shine{

    0%{
        left:-100%;
    }

    100%{
        left:150%;
    }
}


/* =========================
   HEADER
========================= */

.header{

    position:relative;

    width:100%;

    min-height:82px;

    padding:
    16px
    6%;

    display:flex;

    justify-content:space-between;

    align-items:center;

    color:white;

    background:
    linear-gradient(
        135deg,
        #312e81,
        #4f46e5,
        #7c3aed
    );

    box-shadow:
    0 12px 35px
    rgba(79,70,229,.25);

    overflow:hidden;

    animation:
    fadeDown .7s ease;
}


.header::before{

    content:"";

    position:absolute;

    width:250px;
    height:250px;

    border-radius:50%;

    background:
    rgba(255,255,255,.08);

    top:-160px;
    left:15%;

}


.logo{

    position:relative;

    font-size:24px;

    font-weight:bold;

    letter-spacing:.3px;

    z-index:2;
}


.logo span{

    display:inline-block;

    transition:.4s;
}


.logo:hover span{

    transform:
    rotate(-12deg)
    scale(1.2);
}


.back{

    position:relative;

    z-index:2;

    display:flex;

    align-items:center;

    gap:8px;

    color:white;

    text-decoration:none;

    padding:
    11px
    18px;

    border-radius:14px;

    background:
    rgba(255,255,255,.12);

    border:
    1px solid
    rgba(255,255,255,.20);

    backdrop-filter:blur(12px);

    transition:
    .35s ease;

}


.back:hover{

    background:white;

    color:#4338ca;

    transform:
    translateY(-4px);

    box-shadow:
    0 10px 25px
    rgba(0,0,0,.18);
}


/* =========================
   NAVIGATION
========================= */

.main-menu{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:8px;

    padding:14px;

    background:
    rgba(255,255,255,.88);

    border-bottom:
    1px solid
    rgba(226,232,240,.9);

    box-shadow:
    0 8px 25px
    rgba(15,23,42,.06);

    backdrop-filter:
    blur(15px);

    animation:
    fadeDown .8s ease;
}


.main-menu a{

    position:relative;

    display:flex;

    align-items:center;

    gap:6px;

    color:#475569;

    text-decoration:none;

    padding:
    11px
    16px;

    border-radius:12px;

    font-size:13px;

    transition:
    .3s ease;

    overflow:hidden;
}


.main-menu a::before{

    content:"";

    position:absolute;

    width:100%;
    height:100%;

    top:0;
    left:-100%;

    background:
    linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.35),
        transparent
    );

    transition:.5s;
}


.main-menu a:hover::before{

    left:100%;
}


.main-menu a:hover{

    color:#4338ca;

    background:#eef2ff;

    transform:
    translateY(-3px);

    box-shadow:
    0 8px 18px
    rgba(79,70,229,.10);
}


.main-menu .new-trip{

    color:white;

    font-weight:bold;

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );

    box-shadow:
    0 7px 18px
    rgba(79,70,229,.22);
}


.main-menu .new-trip:hover{

    color:white;

    background:
    linear-gradient(
        135deg,
        #4338ca,
        #6d28d9
    );

    transform:
    translateY(-4px)
    scale(1.03);
}


/* =========================
   MAIN CONTAINER
========================= */

.container{

    width:100%;

    max-width:1050px;

    margin:
    45px auto;

    padding:
    0 22px;

    animation:
    fadeUp .8s ease;
}


/* =========================
   HERO
========================= */

.hero{

    position:relative;

    margin-bottom:25px;

    padding:
    28px 30px;

    border-radius:25px;

    color:white;

    background:
    linear-gradient(
        135deg,
        rgba(49,46,129,.97),
        rgba(79,70,229,.95),
        rgba(124,58,237,.95)
    );

    box-shadow:
    0 18px 45px
    rgba(79,70,229,.22);

    overflow:hidden;
}


.hero::after{

    content:"✈️";

    position:absolute;

    font-size:130px;

    left:35px;
    top:-25px;

    opacity:.09;

    transform:
    rotate(-12deg);

    animation:
    floating 6s ease-in-out infinite;
}


.hero-content{

    position:relative;

    z-index:2;
}


.hero h1{

    font-size:28px;

    margin-bottom:9px;
}


.hero p{

    color:
    rgba(255,255,255,.78);

    font-size:14px;

    line-height:1.8;
}


/* =========================
   FORM CARD
========================= */

.form-card{

    position:relative;

    background:
    rgba(255,255,255,.88);

    border:
    1px solid
    rgba(255,255,255,.8);

    border-radius:28px;

    padding:
    38px;

    box-shadow:
    0 25px 70px
    rgba(15,23,42,.10);

    backdrop-filter:
    blur(20px);

    animation:
    scaleIn .8s ease;

    overflow:hidden;
}


.form-card::before{

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
        #7c3aed,
        #06b6d4
    );
}


/* =========================
   MESSAGE
========================= */

.message{

    display:flex;

    align-items:center;

    gap:10px;

    padding:
    16px 18px;

    margin-bottom:25px;

    border-radius:16px;

    font-size:14px;

    animation:
    scaleIn .5s ease;
}


.success{

    color:#166534;

    background:
    linear-gradient(
        135deg,
        #dcfce7,
        #f0fdf4
    );

    border:
    1px solid
    #bbf7d0;

    box-shadow:
    0 8px 20px
    rgba(22,101,52,.08);
}


.error{

    color:#991b1b;

    background:
    linear-gradient(
        135deg,
        #fee2e2,
        #fff1f2
    );

    border:
    1px solid
    #fecaca;
}


/* =========================
   FORM GRID
========================= */

.form-grid{

    display:grid;

    grid-template-columns:
    repeat(2,1fr);

    gap:24px;
}


.form-group{

    display:flex;

    flex-direction:column;

    animation:
    fadeUp .7s ease;
}


.full{

    grid-column:
    1 / -1;
}


label{

    display:flex;

    align-items:center;

    gap:4px;

    margin-bottom:9px;

    color:#334155;

    font-size:13px;

    font-weight:bold;
}


.required{

    color:#ef4444;
}


/* =========================
   INPUT
========================= */

.input-wrapper{

    position:relative;
}


.input-icon{

    position:absolute;

    right:14px;

    top:50%;

    transform:
    translateY(-50%);

    font-size:18px;

    opacity:.7;

    pointer-events:none;

    transition:.3s;
}


input{

    width:100%;

    padding:
    15px 45px 15px 15px;

    border:
    1px solid
    #dbe2ea;

    border-radius:15px;

    outline:none;

    background:
    rgba(248,250,252,.8);

    color:#1e293b;

    font-family:inherit;

    font-size:14px;

    transition:
    .35s ease;

}


input::placeholder{

    color:#94a3b8;
}


input:hover{

    border-color:#a5b4fc;

    background:white;

    transform:
    translateY(-2px);

    box-shadow:
    0 7px 20px
    rgba(79,70,229,.06);
}


input:focus{

    border-color:#6366f1;

    background:white;

    transform:
    translateY(-2px);

    box-shadow:
    0 0 0 4px
    rgba(99,102,241,.10),
    0 10px 25px
    rgba(79,70,229,.08);
}


.input-wrapper:focus-within
.input-icon{

    opacity:1;

    transform:
    translateY(-50%)
    scale(1.15);
}


/* =========================
   BUDGET
========================= */

.budget-label{

    display:flex;

    justify-content:space-between;

    align-items:center;
}


.optional{

    color:#94a3b8;

    font-size:11px;

    font-weight:normal;
}


/* =========================
   BUTTONS
========================= */

.buttons{

    display:grid;

    grid-template-columns:
    2fr 1fr;

    gap:14px;

    margin-top:32px;
}


.submit-btn,
.cancel-btn{

    position:relative;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    min-height:54px;

    border:none;

    border-radius:15px;

    font-family:inherit;

    font-size:14px;

    font-weight:bold;

    text-decoration:none;

    cursor:pointer;

    overflow:hidden;

    transition:
    .35s ease;
}


.submit-btn{

    color:white;

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #6366f1,
        #7c3aed
    );

    box-shadow:
    0 12px 25px
    rgba(79,70,229,.25);
}


.submit-btn::before{

    content:"";

    position:absolute;

    width:60px;
    height:100%;

    top:0;
    left:-100px;

    background:
    linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.35),
        transparent
    );

    transform:
    skewX(-20deg);

    transition:.6s;
}


.submit-btn:hover::before{

    left:120%;
}


.submit-btn:hover{

    transform:
    translateY(-5px);

    box-shadow:
    0 18px 35px
    rgba(79,70,229,.35);
}


.submit-btn:active{

    transform:
    translateY(-1px)
    scale(.98);
}


.cancel-btn{

    color:#475569;

    background:
    #f1f5f9;

    border:
    1px solid
    #e2e8f0;
}


.cancel-btn:hover{

    color:#334155;

    background:white;

    transform:
    translateY(-5px);

    box-shadow:
    0 12px 25px
    rgba(15,23,42,.08);
}


/* =========================
   INFO BOX
========================= */

.info{

    position:relative;

    display:flex;

    gap:12px;

    margin-top:28px;

    padding:
    18px;

    border-radius:17px;

    color:#3730a3;

    background:
    linear-gradient(
        135deg,
        #eef2ff,
        #f5f3ff
    );

    border:
    1px solid
    #ddd6fe;

    font-size:12px;

    line-height:2;

    transition:
    .3s ease;
}


.info:hover{

    transform:
    translateY(-3px);

    box-shadow:
    0 10px 25px
    rgba(79,70,229,.08);
}


.info-icon{

    flex-shrink:0;

    width:38px;
    height:38px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:white;

    font-size:18px;

    box-shadow:
    0 5px 15px
    rgba(79,70,229,.08);
}


/* =========================
   FOOTER
========================= */

.footer{

    text-align:center;

    margin:
    28px 0;

    color:#94a3b8;

    font-size:11px;

    line-height:2;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:800px){

    .header{

        padding:
        15px 20px;
    }

    .main-menu{

        justify-content:flex-start;

        overflow-x:auto;

        flex-wrap:nowrap;

        scrollbar-width:none;
    }

    .main-menu::-webkit-scrollbar{
        display:none;
    }

    .main-menu a{

        white-space:nowrap;
    }

    .form-card{

        padding:28px 22px;
    }

}


@media(max-width:650px){

    .header{

        min-height:70px;
    }

    .logo{

        font-size:18px;
    }

    .back{

        padding:
        9px 12px;

        font-size:12px;
    }

    .container{

        margin:
        25px auto;

        padding:
        0 12px;
    }

    .hero{

        padding:24px 20px;

        border-radius:20px;
    }

    .hero h1{

        font-size:22px;
    }

    .hero p{

        font-size:12px;
    }

    .form-card{

        padding:
        25px 17px;

        border-radius:22px;
    }

    .form-grid{

        grid-template-columns:1fr;

        gap:18px;
    }

    .full{

        grid-column:auto;
    }

    .buttons{

        grid-template-columns:1fr;

    }

    .submit-btn,
    .cancel-btn{

        width:100%;
    }

}


@media(max-width:400px){

    .logo{

        font-size:16px;
    }

    .back{

        font-size:11px;

        padding:
        8px 10px;
    }

    .main-menu a{

        font-size:11px;

        padding:
        9px 11px;
    }

    input{

        padding:
        14px 42px 14px 12px;

        font-size:13px;
    }

}

</style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="logo">

        <span>✈️</span>

        Travel Planner

    </div>


    <a
        href="dashboard.php"
        class="back"
    >

        <span>←</span>

        داشبورد

    </a>

</header>


<!-- =========================
     MENU
========================= -->

<nav class="main-menu">

    <a href="dashboard.php">
        🏠 داشبورد
    </a>

    <a href="trips.php">
        🧳 سفرهای من
    </a>

    <a
        href="add_trip.php"
        class="new-trip"
    >
        ➕ ثبت سفر جدید
    </a>

    <a href="expenses.php">
        💰 مصارف
    </a>

    <a href="itinerary.php">
        📅 برنامه سفر
    </a>

    <a href="destinations.php">
        🌍 مقصدها
    </a>

</nav>


<!-- =========================
     MAIN
========================= -->

<main class="container">


    <!-- HERO -->

    <section class="hero">

        <div class="hero-content">

            <h1>
                ✈️ سفر جدیدت را بساز
            </h1>

            <p>
                اطلاعات سفر را وارد کن تا بتوانی
                برنامه، هزینه‌ها و جزئیات آن را مدیریت کنی.
            </p>

        </div>

    </section>


    <!-- FORM -->

    <section class="form-card">


        <?php if ($message !== ""): ?>

            <div
                class="message
                <?= htmlspecialchars($message_type) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >


            <div class="form-grid">


                <!-- TITLE -->

                <div class="form-group">

                    <label>

                        عنوان سفر

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🧳
                        </span>

                        <input
                            type="text"
                            name="title"
                            placeholder="مثلاً سفر به بامیان"
                            value="<?= htmlspecialchars($title) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- DESTINATION -->

                <div class="form-group">

                    <label>

                        مقصد

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            📍
                        </span>

                        <input
                            type="text"
                            name="destination"
                            placeholder="مثلاً بامیان"
                            value="<?= htmlspecialchars($destination) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- START DATE -->

                <div class="form-group">

                    <label>

                        تاریخ شروع

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            📅
                        </span>

                        <input
                            type="date"
                            name="start_date"
                            value="<?= htmlspecialchars($start_date) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- END DATE -->

                <div class="form-group">

                    <label>

                        تاریخ پایان

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🗓️
                        </span>

                        <input
                            type="date"
                            name="end_date"
                            value="<?= htmlspecialchars($end_date) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- BUDGET -->

                <div class="form-group full">

                    <label class="budget-label">

                        <span>
                            💰 بودجه سفر
                        </span>

                        <span class="optional">
                            اختیاری
                        </span>

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            💵
                        </span>

                        <input
                            type="number"
                            name="budget"
                            min="0"
                            step="0.01"
                            placeholder="مثلاً 50000 افغانی"
                            value="<?= htmlspecialchars($budget) ?>"
                        >

                    </div>

                </div>


            </div>


            <!-- BUTTONS -->

            <div class="buttons">


                <button
                    type="submit"
                    class="submit-btn"
                >

                    💾

                    <span>
                        ثبت سفر
                    </span>

                </button>


                <a
                    href="dashboard.php"
                    class="cancel-btn"
                >

                    ↩️

                    انصراف

                </a>


            </div>


        </form>


        <!-- INFO -->

        <div class="info">

            <div class="info-icon">
                💡
            </div>

            <div>

                <strong>
                    نکته:
                </strong>

                اطلاعات سفر در حساب کاربری شما ذخیره می‌شود.
                بعد از ثبت سفر می‌توانید برنامه روزانه،
                مقصدها و مصارف مربوط به این سفر را اضافه کنید.

            </div>

        </div>


    </section>


    <!-- FOOTER -->

    <footer class="footer">

        Travel Planner © 2026

        <br>

        سیستم هوشمند برنامه‌ریزی سفر

    </footer>


</main>


</body>

</html>