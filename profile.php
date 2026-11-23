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

/* گرفتن اطلاعات کاربر */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, photo FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}


/* =========================
   بروزرسانی پروفایل
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($name === "" || $email === "") {

        $error = "لطفاً نام و ایمیل را وارد کنید.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "لطفاً یک ایمیل معتبر وارد کنید.";

    } else {

        /* بررسی ایمیل تکراری */

        $check = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $email,
            $user_id
        );

        mysqli_stmt_execute($check);

        $check_result = mysqli_stmt_get_result($check);

        $email_exists = mysqli_fetch_assoc($check_result);

        mysqli_stmt_close($check);


        if ($email_exists) {

            $error = "این ایمیل قبلاً برای حساب دیگری ثبت شده است.";

        } else {

            $photo_name = $user["photo"];


            /* =========================
               آپلود عکس
            ========================= */

            if (
                isset($_FILES["photo"]) &&
                $_FILES["photo"]["error"] === UPLOAD_ERR_OK
            ) {

                $allowed_types = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                $file_type = mime_content_type(
                    $_FILES["photo"]["tmp_name"]
                );


                if (!in_array($file_type, $allowed_types)) {

                    $error =
                        "فرمت عکس باید JPG، PNG یا WEBP باشد.";

                } elseif (
                    $_FILES["photo"]["size"] > 5 * 1024 * 1024
                ) {

                    $error =
                        "حجم عکس نباید بیشتر از 5MB باشد.";

                } else {

                    $upload_dir = __DIR__ . "/uploads/";


                    if (!is_dir($upload_dir)) {

                        mkdir(
                            $upload_dir,
                            0777,
                            true
                        );
                    }


                    $extension = strtolower(
                        pathinfo(
                            $_FILES["photo"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                    $new_name =
                        "user_" .
                        $user_id .
                        "_" .
                        time() .
                        "." .
                        $extension;


                    $target =
                        $upload_dir .
                        $new_name;


                    if (
                        move_uploaded_file(
                            $_FILES["photo"]["tmp_name"],
                            $target
                        )
                    ) {

                        /* حذف عکس قبلی */

                        if (
                            !empty($user["photo"]) &&
                            strpos(
                                $user["photo"],
                                "uploads/"
                            ) === 0
                        ) {

                            $old_file =
                                __DIR__ .
                                "/" .
                                $user["photo"];


                            if (
                                file_exists($old_file) &&
                                basename($old_file) !== "default.png"
                            ) {

                                @unlink($old_file);
                            }
                        }


                        $photo_name =
                            "uploads/" .
                            $new_name;

                    } else {

                        $error =
                            "آپلود عکس انجام نشد.";
                    }
                }
            }


            /* =========================
               ذخیره اطلاعات
            ========================= */

            if ($error === "") {

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET name = ?,
                         email = ?,
                         photo = ?
                     WHERE id = ?"
                );


                mysqli_stmt_bind_param(
                    $update,
                    "sssi",
                    $name,
                    $email,
                    $photo_name,
                    $user_id
                );


                if (mysqli_stmt_execute($update)) {

                    $_SESSION["name"] = $name;
                    $_SESSION["photo"] = $photo_name;


                    $user["name"] = $name;
                    $user["email"] = $email;
                    $user["photo"] = $photo_name;


                    $message =
                        "اطلاعات پروفایل با موفقیت بروزرسانی شد.";

                } else {

                    $error =
                        "خطا در بروزرسانی اطلاعات.";
                }


                mysqli_stmt_close($update);
            }
        }
    }
}


/* =========================
   آدرس عکس
========================= */

$photo_url = "";

if (!empty($user["photo"])) {

    $photo = trim($user["photo"]);

    if (
        strpos($photo, "http://") === 0 ||
        strpos($photo, "https://") === 0 ||
        strpos($photo, "/") === 0
    ) {

        $photo_url = $photo;

    } else {

        $photo_url = $photo;
    }
}


/* حرف اول نام */

$first_letter =
    mb_substr(
        $user["name"],
        0,
        1,
        "UTF-8"
    );

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
    پروفایل | Travel Planner
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

    color: white;

    padding: 30px;
}


/* =========================
   CONTAINER
========================= */

.container {

    width: min(
        1100px,
        94%
    );

    margin: auto;
}


/* =========================
   HEADER
========================= */

.topbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 25px;
}


.logo {

    font-size: 25px;

    font-weight: 800;
}


.logo span {

    color: #60a5fa;
}


.back {

    color: #cbd5e1;

    text-decoration: none;

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.08);

    padding:
        11px 17px;

    border-radius: 12px;

    transition: .3s;
}


.back:hover {

    background:
        rgba(255,255,255,.13);

    color: white;

    transform:
        translateY(-2px);
}


/* =========================
   MAIN GRID
========================= */

.profile-box {

    display: grid;

    grid-template-columns:
        330px 1fr;

    gap: 25px;
}


/* =========================
   PROFILE CARD
========================= */

.profile-card {

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.12);

    backdrop-filter:
        blur(18px);

    border-radius: 25px;

    padding: 35px 25px;

    text-align: center;

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.25);

    height: fit-content;
}


.avatar {

    width: 150px;

    height: 150px;

    margin:
        0 auto 20px;

    border-radius: 50%;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    border:
        4px solid
        rgba(255,255,255,.2);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 55px;

    font-weight: bold;
}


.avatar img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;
}


.profile-card h2 {

    font-size: 21px;

    margin-bottom: 8px;
}


.profile-card > p {

    color: #94a3b8;

    font-size: 13px;
}


/* =========================
   USER INFO
========================= */

.info {

    margin-top: 25px;

    text-align: right;
}


.info div {

    background:
        rgba(255,255,255,.05);

    border:
        1px solid
        rgba(255,255,255,.05);

    padding: 13px;

    border-radius: 12px;

    margin-bottom: 10px;

    color: #cbd5e1;

    font-size: 13px;
}


/* =========================
   FORM CARD
========================= */

.form-card {

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.12);

    backdrop-filter:
        blur(18px);

    border-radius: 25px;

    padding: 35px;
}


.form-card h1 {

    font-size: 25px;

    margin-bottom: 8px;
}


.subtitle {

    color: #94a3b8;

    margin-bottom: 28px;

    line-height: 1.7;

    font-size: 13px;
}


/* =========================
   MESSAGE
========================= */

.message {

    background:
        rgba(34,197,94,.13);

    border:
        1px solid
        rgba(34,197,94,.3);

    color: #86efac;

    padding: 14px;

    border-radius: 12px;

    margin-bottom: 20px;
}


.error {

    background:
        rgba(239,68,68,.13);

    border:
        1px solid
        rgba(239,68,68,.3);

    color: #fca5a5;

    padding: 14px;

    border-radius: 12px;

    margin-bottom: 20px;
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


.field input {

    width: 100%;

    padding:
        14px 16px;

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


.field input:focus {

    border-color:
        #60a5fa;

    box-shadow:
        0 0 0 3px
        rgba(96,165,250,.12);
}


/* =========================
   UPLOAD
========================= */

.upload {

    border:
        2px dashed
        rgba(255,255,255,.18);

    padding: 22px;

    border-radius: 15px;

    text-align: center;

    transition: .3s;

    color: #cbd5e1;
}


.upload:hover {

    border-color:
        #60a5fa;

    background:
        rgba(96,165,250,.05);
}


.upload input {

    margin-top: 12px;

    color: #cbd5e1;

    width: 100%;
}


/* =========================
   BUTTON
========================= */

.save-btn {

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
}


.save-btn:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 12px 30px
        rgba(37,99,235,.3);
}


/* =========================
   CHANGE PASSWORD
========================= */

.change-password {

    display: block;

    text-align: center;

    margin-top: 15px;

    padding: 14px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.05);

    border:
        1px solid
        rgba(255,255,255,.08);

    color: #93c5fd;

    text-decoration: none;

    transition: .3s;
}


.change-password:hover {

    background:
        rgba(96,165,250,.12);

    color: white;

    transform:
        translateY(-2px);
}


/* =========================
   MOBILE
========================= */

@media(max-width: 800px) {

    body {
        padding: 18px;
    }


    .container {
        width: 100%;
    }


    .profile-box {

        grid-template-columns: 1fr;
    }


    .form-card {

        padding: 25px 20px;
    }


    .topbar {

        gap: 15px;

        flex-direction: column;

        align-items: flex-start;
    }
}


@media(max-width: 450px) {

    .avatar {

        width: 125px;

        height: 125px;

        font-size: 45px;
    }


    .form-card h1 {

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


    <!-- HEADER -->

    <div class="topbar">

        <div class="logo">

            ✈️ Travel
            <span>Planner</span>

        </div>


        <a
            href="dashboard.php"
            class="back"
        >

            ← بازگشت به داشبورد

        </a>

    </div>


    <!-- MAIN -->

    <div class="profile-box">


        <!-- =====================
             LEFT PROFILE
        ====================== -->

        <div class="profile-card">


            <div class="avatar">


                <?php if (!empty($photo_url)): ?>

                    <img
                        src="<?= htmlspecialchars($photo_url) ?>"
                        alt="عکس پروفایل"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML='<?= htmlspecialchars($first_letter) ?>';
                        "
                    >

                <?php else: ?>

                    <?= htmlspecialchars($first_letter) ?>

                <?php endif; ?>


            </div>


            <h2>

                <?= htmlspecialchars($user["name"]) ?>

            </h2>


            <p>

                مسافر Travel Planner

            </p>


            <div class="info">


                <div>

                    👤
                    نام:

                    <?= htmlspecialchars(
                        $user["name"]
                    ) ?>

                </div>


                <div>

                    📧
                    ایمیل:

                    <?= htmlspecialchars(
                        $user["email"]
                    ) ?>

                </div>


            </div>


        </div>


        <!-- =====================
             RIGHT FORM
        ====================== -->

        <div class="form-card">


            <h1>

                👤 ویرایش پروفایل

            </h1>


            <p class="subtitle">

                اطلاعات حساب، نام، ایمیل و
                عکس پروفایل خود را مدیریت کنید.

            </p>


            <?php if ($message): ?>

                <div class="message">

                    ✓
                    <?= htmlspecialchars(
                        $message
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="error">

                    ⚠️
                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- NAME -->

                <div class="field">

                    <label>

                        نام

                    </label>


                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars(
                            $user["name"]
                        ) ?>"
                        placeholder="نام خود را وارد کنید"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="field">

                    <label>

                        ایمیل

                    </label>


                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $user["email"]
                        ) ?>"
                        placeholder="example@gmail.com"
                        required
                    >

                </div>


                <!-- PHOTO -->

                <div class="field">

                    <label>

                        📷 عکس پروفایل

                    </label>


                    <div class="upload">

                        انتخاب عکس جدید

                        <br>

                        <input
                            type="file"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                        >


                        <div
                            style="
                                margin-top:10px;
                                color:#64748b;
                                font-size:12px;
                            "
                        >

                            JPG / PNG / WEBP
                            — حداکثر 5MB

                        </div>

                    </div>

                </div>


                <!-- SAVE -->

                <button
                    type="submit"
                    class="save-btn"
                >

                    💾 ذخیره تغییرات

                </button>


            </form>


            <!-- CHANGE PASSWORD -->

            <a
                href="change_password.php"
                class="change-password"
            >

                🔐 تغییر رمز عبور

            </a>


        </div>


    </div>

</div>


</body>

</html>