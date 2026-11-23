<?php

session_start();
include "database.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "" || $confirm_password === "") {

        $error = "لطفاً تمام قسمت‌ها را پر کنید.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "لطفاً یک ایمیل معتبر وارد کنید.";

    } elseif (strlen($password) < 6) {

        $error = "رمز عبور باید حداقل ۶ حرف باشد.";

    } elseif ($password !== $confirm_password) {

        $error = "رمز عبور و تکرار آن یکسان نیست.";

    } elseif (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {

        $error = "لطفاً عکس پروفایل خود را انتخاب کنید.";

    } else {

        $email_safe = mysqli_real_escape_string($conn, $email);

        $check = mysqli_query(
            $conn,
            "SELECT id FROM users
             WHERE email = '$email_safe'
             LIMIT 1"
        );

        if ($check && mysqli_num_rows($check) > 0) {

            $error = "این ایمیل قبلاً ثبت شده است.";

        } else {

            $file = $_FILES["photo"];

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (!in_array($file["type"], $allowed_types)) {

                $error = "فقط عکس JPG، PNG یا WEBP مجاز است.";

            } elseif ($file["size"] > 5 * 1024 * 1024) {

                $error = "حجم عکس نباید بیشتر از ۵MB باشد.";

            } else {

                $upload_dir = "uploads/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $extension = strtolower(
                    pathinfo(
                        $file["name"],
                        PATHINFO_EXTENSION
                    )
                );

                $new_name =
                    "user_" .
                    time() .
                    "_" .
                    rand(1000, 9999) .
                    "." .
                    $extension;

                $photo_path =
                    $upload_dir .
                    $new_name;

                if (move_uploaded_file(
                    $file["tmp_name"],
                    $photo_path
                )) {

                    $name_safe =
                        mysqli_real_escape_string(
                            $conn,
                            $name
                        );

                    $password_hash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    $sql = "INSERT INTO users
                            (name, email, password, photo)
                            VALUES
                            ('$name_safe',
                             '$email_safe',
                             '$password_hash',
                             '$photo_path')";

                    if (mysqli_query($conn, $sql)) {

                        $success =
                            "ثبت‌نام با موفقیت انجام شد.";

                    } else {

                        if (file_exists($photo_path)) {
                            unlink($photo_path);
                        }

                        $error =
                            "ثبت‌نام انجام نشد. دوباره تلاش کنید.";
                    }

                } else {

                    $error =
                        "آپلود عکس با مشکل مواجه شد.";
                }
            }
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

<title>ثبت‌نام</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:
        linear-gradient(
            120deg,
            #0f172a,
            #312e81,
            #4f46e5,
            #7c3aed,
            #111827
        );

    background-size: 400% 400%;

    animation:
        gradientMove 12s ease infinite;

    overflow-x: hidden;
}

@keyframes gradientMove {

    0% {
        background-position: 0% 50%;
    }

    50% {
        background-position: 100% 50%;
    }

    100% {
        background-position: 0% 50%;
    }
}


/* Background */

.shape {

    position: fixed;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);

    pointer-events: none;
}

.shape1 {

    width: 350px;
    height: 350px;

    top: -150px;
    right: -100px;

    animation:
        float1 7s ease-in-out infinite;
}

.shape2 {

    width: 280px;
    height: 280px;

    bottom: -130px;
    left: -80px;

    animation:
        float2 9s ease-in-out infinite;
}

@keyframes float1 {

    0%,100% {
        transform: translate(0,0);
    }

    50% {
        transform: translate(-30px,40px);
    }
}

@keyframes float2 {

    0%,100% {
        transform: translate(0,0);
    }

    50% {
        transform: translate(40px,-30px);
    }
}


/* Card */

.register-card {

    position: relative;

    z-index: 5;

    width: 100%;

    max-width: 470px;

    padding: 35px;

    border-radius: 30px;

    background:
        rgba(255,255,255,.96);

    backdrop-filter:
        blur(20px);

    box-shadow:
        0 30px 80px
        rgba(0,0,0,.35);

    animation:
        cardShow .8s
        cubic-bezier(.17,.67,.3,1.3);
}

@keyframes cardShow {

    from {

        opacity: 0;

        transform:
            translateY(60px)
            scale(.85);
    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);
    }
}


/* Top */

.top-icon {

    width: 70px;
    height: 70px;

    margin:
        0 auto 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    font-size: 32px;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );

    box-shadow:
        0 12px 30px
        rgba(79,70,229,.35);

    animation:
        iconFloat 3s
        ease-in-out infinite;
}

@keyframes iconFloat {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-7px);
    }
}

.title {

    text-align: center;

    margin-bottom: 25px;
}

.title h1 {

    font-size: 26px;

    color: #111827;

    margin-bottom: 8px;
}

.title p {

    font-size: 13px;

    color: #64748b;
}


/* Messages */

.error,
.success {

    padding: 13px;

    border-radius: 13px;

    text-align: center;

    margin-bottom: 18px;

    font-size: 13px;
}

.error {

    color: #991b1b;

    background: #fee2e2;

    border: 1px solid #fecaca;

    animation: shake .4s;
}

.success {

    color: #166534;

    background: #dcfce7;

    border: 1px solid #bbf7d0;
}

@keyframes shake {

    0%,100% {
        transform: translateX(0);
    }

    25% {
        transform: translateX(7px);
    }

    75% {
        transform: translateX(-7px);
    }
}


/* Photo */

.photo-area {

    text-align: center;

    margin-bottom: 20px;
}

.photo-preview {

    width: 105px;
    height: 105px;

    margin: auto;

    border-radius: 50%;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #ede9fe
        );

    border: 4px solid white;

    box-shadow:
        0 10px 30px
        rgba(79,70,229,.20);

    transition: .4s;
}

.photo-preview:hover {

    transform:
        scale(1.08);

    box-shadow:
        0 15px 35px
        rgba(79,70,229,.30);
}

.photo-preview img {

    width: 100%;
    height: 100%;

    object-fit: cover;

    display: none;
}

.photo-icon {

    font-size: 38px;
}

#photo {

    display: none;
}

.photo-label {

    display: inline-block;

    margin-top: 12px;

    padding: 9px 17px;

    border-radius: 12px;

    color: #4f46e5;

    background: #eef2ff;

    border: 1px solid #e0e7ff;

    cursor: pointer;

    font-size: 12px;

    font-weight: bold;

    transition: .3s;
}

.photo-label:hover {

    color: white;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );

    transform:
        translateY(-3px);

    box-shadow:
        0 8px 20px
        rgba(79,70,229,.25);
}


/* Form */

.form-group {

    margin-bottom: 16px;
}

label {

    display: block;

    margin-bottom: 7px;

    color: #334155;

    font-size: 13px;

    font-weight: bold;
}

input {

    width: 100%;

    height: 50px;

    padding: 0 15px;

    border:
        1px solid #cbd5e1;

    border-radius: 13px;

    outline: none;

    background: #f8fafc;

    color: #1e293b;

    font-family: inherit;

    font-size: 14px;

    transition: .3s;
}

input:focus {

    background: white;

    border-color:
        #6366f1;

    box-shadow:
        0 0 0 4px
        rgba(99,102,241,.12);

    transform:
        translateY(-1px);
}


/* Button */

.register-btn {

    width: 100%;

    height: 53px;

    margin-top: 5px;

    border: none;

    border-radius: 14px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );

    font-family: inherit;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    transition: .35s;
}

.register-btn:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 14px 30px
        rgba(79,70,229,.35);
}

.register-btn:active {

    transform:
        scale(.98);
}


/* Login */

.login-link {

    text-align: center;

    margin-top: 20px;

    padding-top: 18px;

    border-top:
        1px solid #e2e8f0;

    color: #64748b;

    font-size: 13px;
}

.login-link a {

    color: #4f46e5;

    font-weight: bold;

    text-decoration: none;

    transition: .3s;
}

.login-link a:hover {

    color: #7c3aed;
}


/* Mobile */

@media (max-width: 500px) {

    body {
        padding: 15px;
    }

    .register-card {

        padding:
            28px 22px;

        border-radius: 25px;
    }

    .top-icon {

        width: 62px;
        height: 62px;

        font-size: 28px;
    }

    .title h1 {
        font-size: 23px;
    }
}

</style>

</head>

<body>


<div class="shape shape1"></div>

<div class="shape shape2"></div>


<div class="register-card">


    <div class="top-icon">
        👤
    </div>


    <div class="title">

        <h1>
            ساخت حساب جدید
        </h1>

        <p>
            اطلاعات خود را وارد کنید
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            ⚠️
            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="success">

            ✅
            <?php
            echo htmlspecialchars($success);
            ?>

            <br><br>

            <a
                href="login.php"
                style="color:#166534;font-weight:bold;"
            >
                ورود به حساب
            </a>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- PHOTO -->

        <div class="photo-area">

            <div class="photo-preview">

                <span class="photo-icon">
                    👤
                </span>

                <img
                    id="preview"
                    alt="پیش‌نمایش عکس"
                >

            </div>


            <label
                for="photo"
                class="photo-label"
            >
                📷 انتخاب عکس پروفایل
            </label>


            <input
                type="file"
                name="photo"
                id="photo"
                accept="image/jpeg,image/png,image/webp"
                required
            >

        </div>


        <!-- NAME -->

        <div class="form-group">

            <label>
                👤 نام کامل
            </label>

            <input
                type="text"
                name="name"
                placeholder="نام خود را وارد کنید"
                required
            >

        </div>


        <!-- EMAIL -->

        <div class="form-group">

            <label>
                📧 ایمیل
            </label>

            <input
                type="email"
                name="email"
                placeholder="example@gmail.com"
                required
            >

        </div>


        <!-- PASSWORD -->

        <div class="form-group">

            <label>
                🔒 رمز عبور
            </label>

            <input
                type="password"
                name="password"
                placeholder="حداقل ۶ حرف"
                required
            >

        </div>


        <!-- CONFIRM -->

        <div class="form-group">

            <label>
                🔐 تکرار رمز عبور
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="رمز عبور را دوباره وارد کنید"
                required
            >

        </div>


        <button
            type="submit"
            class="register-btn"
        >

            ✨ ساخت حساب

        </button>


    </form>


    <div class="login-link">

        قبلاً حساب ساخته‌اید؟

        <a href="login.php">
            وارد شوید
        </a>

    </div>


</div>


<script>

/* Preview */

const photoInput =
    document.getElementById("photo");

const preview =
    document.getElementById("preview");

const photoIcon =
    document.querySelector(".photo-icon");


photoInput.addEventListener(
    "change",
    function () {

        const file =
            this.files[0];

        if (!file) {

            preview.style.display = "none";

            photoIcon.style.display =
                "block";

            return;
        }

        if (!file.type.startsWith("image/")) {

            alert(
                "لطفاً یک عکس معتبر انتخاب کنید."
            );

            this.value = "";

            return;
        }

        const reader =
            new FileReader();

        reader.onload = function (e) {

            preview.src =
                e.target.result;

            preview.style.display =
                "block";

            photoIcon.style.display =
                "none";
        };

        reader.readAsDataURL(file);

    }
);

</script>


</body>

</html>