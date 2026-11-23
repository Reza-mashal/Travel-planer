<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


/* =========================
   تغییر رمز عبور
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /* بررسی خالی نبودن */

    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error = "لطفاً تمام قسمت‌ها را تکمیل کنید.";

    }


    /* بررسی طول رمز */

    elseif (strlen($new_password) < 6) {

        $error = "رمز جدید باید حداقل 6 کاراکتر باشد.";

    }


    /* بررسی تکرار رمز */

    elseif ($new_password !== $confirm_password) {

        $error = "رمز جدید و تکرار آن یکسان نیست.";

    }


    else {

        /* گرفتن رمز فعلی از دیتابیس */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT password FROM users WHERE id = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$user) {

            $error = "کاربر پیدا نشد.";

        }


        /* بررسی رمز فعلی */

        elseif (
            !password_verify(
                $current_password,
                $user["password"]
            )
        ) {

            $error = "رمز عبور فعلی اشتباه است.";

        }


        else {

            /* هش کردن رمز جدید */

            $new_password_hash = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


            /* ذخیره رمز جدید */

            $update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET password = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "si",
                $new_password_hash,
                $user_id
            );


            if (mysqli_stmt_execute($update)) {

                $message =
                    "رمز عبور شما با موفقیت تغییر کرد.";

                $_POST = [];

            } else {

                $error =
                    "در تغییر رمز عبور مشکلی به وجود آمد.";
            }


            mysqli_stmt_close($update);
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
    تغییر رمز عبور | Travel Planner
</title>


<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================
   BODY
========================= */

body {

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    min-height: 100vh;

    color: #fff;

    background:

        radial-gradient(
            circle at top right,
            #243b67,
            transparent 35%
        ),

        radial-gradient(
            circle at bottom left,
            #172554,
            transparent 35%
        ),

        #07111f;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 25px;
}


/* =========================
   CONTAINER
========================= */

.container {

    width: 100%;

    max-width: 520px;
}


/* =========================
   TOP
========================= */

.top {

    text-align: center;

    margin-bottom: 20px;
}


.logo {

    font-size: 25px;

    font-weight: 800;

    margin-bottom: 8px;
}


.logo span {

    color: #60a5fa;
}


.subtitle {

    color: #94a3b8;

    font-size: 13px;
}


/* =========================
   CARD
========================= */

.card {

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.12);

    backdrop-filter:
        blur(20px);

    border-radius: 25px;

    padding: 35px;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.3);

    animation:
        showCard .5s ease;
}


@keyframes showCard {

    from {

        opacity: 0;

        transform:
            translateY(20px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }
}


/* =========================
   TITLE
========================= */

.card h1 {

    font-size: 24px;

    margin-bottom: 8px;
}


.description {

    color: #94a3b8;

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 25px;
}


/* =========================
   ALERT
========================= */

.success {

    background:
        rgba(34,197,94,.13);

    border:
        1px solid
        rgba(34,197,94,.3);

    color: #86efac;

    padding: 14px;

    border-radius: 13px;

    margin-bottom: 20px;

    line-height: 1.7;
}


.error {

    background:
        rgba(239,68,68,.13);

    border:
        1px solid
        rgba(239,68,68,.3);

    color: #fca5a5;

    padding: 14px;

    border-radius: 13px;

    margin-bottom: 20px;

    line-height: 1.7;
}


/* =========================
   FIELD
========================= */

.field {

    margin-bottom: 20px;
}


.field label {

    display: block;

    margin-bottom: 8px;

    color: #cbd5e1;

    font-size: 14px;
}


/* =========================
   PASSWORD BOX
========================= */

.password-box {

    position: relative;
}


.password-box input {

    padding-left: 55px;
}


.password-box input {

    width: 100%;

    padding-top: 14px;

    padding-bottom: 14px;

    padding-right: 16px;

    border-radius: 13px;

    border:
        1px solid
        rgba(255,255,255,.12);

    background:
        rgba(0,0,0,.22);

    color: white;

    outline: none;

    font-size: 15px;

    transition: .3s;
}


.password-box input:focus {

    border-color:
        #60a5fa;

    box-shadow:
        0 0 0 3px
        rgba(96,165,250,.12);
}


.toggle-password {

    position: absolute;

    left: 10px;

    top: 50%;

    transform:
        translateY(-50%);

    background:
        transparent;

    border: none;

    color: #94a3b8;

    cursor: pointer;

    font-size: 18px;

    width: 35px;

    height: 35px;

    padding: 0;

    transition: .3s;
}


.toggle-password:hover {

    color: white;

    transform:
        translateY(-50%)
        scale(1.1);
}


/* =========================
   BUTTON
========================= */

.submit-btn {

    width: 100%;

    border: none;

    padding: 15px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;

    margin-top: 5px;
}


.submit-btn:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 15px 30px
        rgba(37,99,235,.3);
}


/* =========================
   BACK
========================= */

.back {

    display: flex;

    justify-content: center;

    margin-top: 22px;
}


.back a {

    color: #93c5fd;

    text-decoration: none;

    font-size: 14px;

    transition: .3s;
}


.back a:hover {

    color: white;

    transform:
        translateX(3px);
}


/* =========================
   PASSWORD RULES
========================= */

.rules {

    background:
        rgba(255,255,255,.04);

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius: 14px;

    padding: 15px;

    margin-bottom: 22px;
}


.rules-title {

    font-size: 13px;

    margin-bottom: 9px;

    color: #cbd5e1;
}


.rules p {

    color: #64748b;

    font-size: 12px;

    line-height: 1.8;
}


/* =========================
   MOBILE
========================= */

@media(max-width: 550px) {

    body {

        padding: 15px;
    }


    .card {

        padding: 25px 20px;

        border-radius: 20px;
    }


    .card h1 {

        font-size: 21px;
    }


    .logo {

        font-size: 21px;
    }
}

</style>

</head>


<body>


<div class="container">


    <!-- LOGO -->

    <div class="top">

        <div class="logo">

            ✈️ Travel
            <span>Planner</span>

        </div>

        <div class="subtitle">

            مدیریت امن حساب کاربری

        </div>

    </div>


    <!-- CARD -->

    <div class="card">


        <h1>
            🔐 تغییر رمز عبور
        </h1>


        <p class="description">

            برای امنیت بیشتر حساب خود،
            رمز عبور فعلی را وارد کرده و
            یک رمز جدید انتخاب کنید.

        </p>


        <?php if ($message): ?>

            <div class="success">

                ✓
                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="error">

                ⚠️
                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- RULES -->

        <div class="rules">

            <div class="rules-title">
                💡 نکته امنیتی
            </div>

            <p>
                رمز جدید باید حداقل 6 کاراکتر داشته باشد.
                بهتر است از ترکیب حروف و اعداد استفاده کنید.
            </p>

        </div>


        <form
            method="POST"
            autocomplete="off"
        >


            <!-- CURRENT -->

            <div class="field">

                <label>
                    رمز عبور فعلی
                </label>


                <div class="password-box">

                    <input
                        type="password"
                        name="current_password"
                        id="current_password"
                        placeholder="رمز فعلی را وارد کنید"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword(
                            'current_password',
                            this
                        )"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- NEW -->

            <div class="field">

                <label>
                    رمز عبور جدید
                </label>


                <div class="password-box">

                    <input
                        type="password"
                        name="new_password"
                        id="new_password"
                        placeholder="رمز جدید را وارد کنید"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword(
                            'new_password',
                            this
                        )"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- CONFIRM -->

            <div class="field">

                <label>
                    تکرار رمز عبور جدید
                </label>


                <div class="password-box">

                    <input
                        type="password"
                        name="confirm_password"
                        id="confirm_password"
                        placeholder="رمز جدید را دوباره وارد کنید"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword(
                            'confirm_password',
                            this
                        )"
                    >
                        👁
                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                🔒 تغییر رمز عبور

            </button>


        </form>


        <!-- BACK -->

        <div class="back">

            <a href="profile.php">

                ← بازگشت به پروفایل

            </a>

        </div>


    </div>

</div>


<script>

/* =========================
   SHOW / HIDE PASSWORD
========================= */

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(inputId);


    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";

    } else {

        input.type = "password";

        button.textContent = "👁";

    }
}

</script>


</body>

</html>