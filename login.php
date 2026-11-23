<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include "database.php";


/* =========================
   اگر قبلاً وارد شده
========================= */

if (isset($_SESSION["user_id"])) {

    header("Location: dashboard.php");

    exit();
}


$error = "";


/* =========================
   پردازش ورود
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";


    /* بررسی خالی نبودن */

    if ($email === "" || $password === "") {

        $error =
            "لطفاً ایمیل و رمز عبور را وارد کنید.";

    } else {


        /* =========================
           پیدا کردن کاربر
        ========================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                id,
                name,
                email,
                password,
                photo
             FROM users
             WHERE email = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error =
                "خطا در اتصال به اطلاعات کاربر.";

        } else {


            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute($stmt);


            $result =
                mysqli_stmt_get_result($stmt);


            $user =
                mysqli_fetch_assoc($result);


            /* =========================
               بررسی رمز عبور
            ========================= */

            if (
                $user &&
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {


                /* امنیت Session */

                session_regenerate_id(true);


                /* =========================
                   ذخیره اطلاعات کاربر
                ========================= */

                $_SESSION["user_id"] =
                    (int) $user["id"];


                $_SESSION["name"] =
                    $user["name"];


                $_SESSION["email"] =
                    $user["email"];


                /* =========================
                   ذخیره عکس
                ========================= */

                if (!empty($user["photo"])) {

                    $_SESSION["photo"] =
                        trim($user["photo"]);

                } else {

                    $_SESSION["photo"] = "";
                }


                /* =========================
                   رفتن به داشبورد
                ========================= */

                header(
                    "Location: dashboard.php"
                );

                exit();


            } else {

                $error =
                    "ایمیل یا رمز عبور اشتباه است.";
            }


            mysqli_stmt_close($stmt);
        }
    }
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
    ورود | Travel Planner
</title>


<style>

/* =========================
   RESET
========================= */

* {

    box-sizing: border-box;

    margin: 0;

    padding: 0;

    font-family:
        Tahoma,
        Arial,
        sans-serif;
}


/* =========================
   BODY
========================= */

body {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    background:

        radial-gradient(
            circle at 20% 20%,
            #8b5cf6,
            transparent 35%
        ),

        radial-gradient(
            circle at 80% 80%,
            #06b6d4,
            transparent 35%
        ),

        linear-gradient(
            135deg,
            #0f172a,
            #312e81,
            #111827
        );

    padding: 20px;
}


/* =========================
   BACKGROUND CIRCLES
========================= */

.circle {

    position: fixed;

    border-radius: 50%;

    filter: blur(2px);

    opacity: .25;

    animation:
        float 7s ease-in-out infinite;

    pointer-events: none;
}


.circle1 {

    width: 180px;

    height: 180px;

    background: #a78bfa;

    top: 5%;

    left: 8%;
}


.circle2 {

    width: 130px;

    height: 130px;

    background: #22d3ee;

    bottom: 8%;

    right: 10%;

    animation-delay: 2s;
}


.circle3 {

    width: 80px;

    height: 80px;

    background: #f472b6;

    top: 65%;

    left: 20%;

    animation-delay: 4s;
}


@keyframes float {

    0%, 100% {

        transform:
            translateY(0)
            scale(1);
    }

    50% {

        transform:
            translateY(-30px)
            scale(1.1);
    }
}


/* =========================
   LOGIN CARD
========================= */

.login-card {

    width: 100%;

    max-width: 430px;

    padding:
        38px 32px;

    background:
        rgba(255,255,255,.13);

    border:
        1px solid
        rgba(255,255,255,.25);

    border-radius: 28px;

    backdrop-filter:
        blur(25px);

    box-shadow:

        0 25px 70px
        rgba(0,0,0,.4),

        inset 0 1px 1px
        rgba(255,255,255,.2);

    animation:
        cardShow .8s ease forwards;

    position: relative;

    z-index: 5;
}


@keyframes cardShow {

    from {

        opacity: 0;

        transform:
            translateY(50px)
            scale(.9);
    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);
    }
}


/* =========================
   ICON
========================= */

.icon {

    width: 75px;

    height: 75px;

    margin:
        0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    font-size: 34px;

    background:
        linear-gradient(
            135deg,
            #8b5cf6,
            #06b6d4
        );

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.3);

    animation:
        pulse 2.5s infinite;
}


@keyframes pulse {

    0%, 100% {

        transform:
            scale(1);
    }

    50% {

        transform:
            scale(1.07);
    }
}


/* =========================
   TITLE
========================= */

h1 {

    text-align: center;

    color: white;

    font-size: 28px;

    margin-bottom: 8px;
}


.subtitle {

    text-align: center;

    color:
        rgba(255,255,255,.7);

    font-size: 14px;

    margin-bottom: 30px;
}


/* =========================
   ERROR
========================= */

.error {

    padding: 13px;

    margin-bottom: 20px;

    border-radius: 13px;

    text-align: center;

    color: #fecaca;

    background:
        rgba(239,68,68,.18);

    border:
        1px solid
        rgba(248,113,113,.3);

    animation:
        shake .4s;
}


@keyframes shake {

    0%,100% {

        transform:
            translateX(0);
    }

    25% {

        transform:
            translateX(-8px);
    }

    75% {

        transform:
            translateX(8px);
    }
}


/* =========================
   FORM
========================= */

.form-group {

    margin-bottom: 20px;
}


label {

    display: block;

    color: white;

    font-size: 14px;

    margin-bottom: 8px;
}


.input-box {

    position: relative;
}


input {

    width: 100%;

    padding:
        15px 18px;

    border:
        1px solid
        rgba(255,255,255,.2);

    outline: none;

    border-radius: 15px;

    background:
        rgba(255,255,255,.1);

    color: white;

    font-size: 15px;

    transition: .3s;
}


input::placeholder {

    color:
        rgba(255,255,255,.5);
}


input:focus {

    border-color:
        #67e8f9;

    background:
        rgba(255,255,255,.16);

    box-shadow:
        0 0 20px
        rgba(34,211,238,.2);

    transform:
        translateY(-2px);
}


/* =========================
   PASSWORD BUTTON
========================= */

.show-password {

    position: absolute;

    left: 12px;

    top: 50%;

    transform:
        translateY(-50%);

    border: none;

    background: transparent;

    color: white;

    cursor: pointer;

    font-size: 18px;

    padding: 5px;
}


/* =========================
   LOGIN BUTTON
========================= */

.login-btn {

    width: 100%;

    padding: 16px;

    border: none;

    border-radius: 16px;

    cursor: pointer;

    color: white;

    font-size: 16px;

    font-weight: bold;

    background:
        linear-gradient(
            135deg,
            #8b5cf6,
            #06b6d4
        );

    box-shadow:
        0 12px 30px
        rgba(6,182,212,.25);

    transition: .3s;

    position: relative;

    overflow: hidden;
}


.login-btn:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 18px 40px
        rgba(6,182,212,.4);
}


.login-btn:active {

    transform:
        scale(.97);
}


/* =========================
   REGISTER
========================= */

.register-link {

    text-align: center;

    margin-top: 25px;

    color:
        rgba(255,255,255,.7);

    font-size: 14px;
}


.register-link a {

    color: #67e8f9;

    text-decoration: none;

    font-weight: bold;

    transition: .3s;
}


.register-link a:hover {

    color: white;

    text-shadow:
        0 0 12px
        #67e8f9;
}


/* =========================
   MOBILE
========================= */

@media(max-width:480px) {

    .login-card {

        padding:
            30px 22px;

        border-radius:
            23px;
    }


    h1 {

        font-size: 24px;
    }


    .icon {

        width: 65px;

        height: 65px;

        font-size: 29px;
    }
}

</style>

</head>


<body>


<!-- پس‌زمینه -->

<div class="circle circle1"></div>

<div class="circle circle2"></div>

<div class="circle circle3"></div>


<!-- =========================
     LOGIN
========================= -->

<div class="login-card">


    <div class="icon">

        🔐

    </div>


    <h1>

        خوش آمدید

    </h1>


    <div class="subtitle">

        برای ورود، اطلاعات حساب خود را وارد کنید

    </div>


    <?php if (!empty($error)): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action=""
        autocomplete="on"
    >


        <!-- ایمیل -->

        <div class="form-group">

            <label>

                ایمیل

            </label>


            <input
                type="email"
                name="email"
                placeholder="ایمیل خود را وارد کنید"
                required
                autocomplete="email"
                value="<?= htmlspecialchars(
                    $_POST["email"] ?? ""
                ) ?>"
            >

        </div>


        <!-- رمز -->

        <div class="form-group">

            <label>

                رمز عبور

            </label>


            <div class="input-box">

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="رمز عبور خود را وارد کنید"
                    required
                    autocomplete="current-password"
                >


                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword()"
                >

                    👁

                </button>

            </div>

        </div>


        <!-- ورود -->

        <button
            type="submit"
            class="login-btn"
        >

            ورود به حساب

        </button>


    </form>


    <!-- ثبت نام -->

    <div class="register-link">

        حساب ندارید؟

        <a href="register.php">

            ثبت‌نام کنید

        </a>

    </div>


</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const button =
        document.querySelector(
            ".show-password"
        );


    if (
        password.type === "password"
    ) {

        password.type = "text";

        button.textContent = "🙈";

    } else {

        password.type = "password";

        button.textContent = "👁";
    }
}

</script>


</body>

</html>