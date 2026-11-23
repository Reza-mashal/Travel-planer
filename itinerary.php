<?php
session_start();
include "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

/* =========================
   حذف برنامه
========================= */
if (isset($_GET["delete"])) {

    $delete_id = intval($_GET["delete"]);

    $stmt = $conn->prepare("
        DELETE i FROM itinerary i
        INNER JOIN trips t ON i.trip_id = t.id
        WHERE i.id = ? AND t.user_id = ?
    ");

    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: itinerary.php?deleted=1");
    exit();
}

/* =========================
   پیام حذف
========================= */
if (isset($_GET["deleted"])) {
    $message = "برنامه سفر با موفقیت حذف شد.";
}

/* =========================
   ثبت برنامه جدید
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $trip_id       = intval($_POST["trip_id"]);
    $day_number    = intval($_POST["day_number"]);
    $activity      = trim($_POST["activity"]);
    $location      = trim($_POST["location"]);
    $activity_date = $_POST["activity_date"] ?? "";
    $start_time    = $_POST["start_time"] ?? "";
    $end_time      = $_POST["end_time"] ?? "";
    $note          = trim($_POST["note"]);

    if ($trip_id <= 0 || $day_number <= 0 || $activity === "") {

        $message = "لطفاً معلومات ضروری را کامل وارد کنید.";

    } else {

        /* بررسی اینکه سفر متعلق به کاربر است */
        $check = $conn->prepare("
            SELECT id
            FROM trips
            WHERE id = ? AND user_id = ?
        ");

        $check->bind_param("ii", $trip_id, $user_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {

            $message = "این سفر مربوط به حساب شما نیست.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO itinerary
                (trip_id, day_number, activity, location,
                 activity_date, start_time, end_time, note)
                VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)
            ");

            $stmt->bind_param(
                "iissssss",
                $trip_id,
                $day_number,
                $activity,
                $location,
                $activity_date,
                $start_time,
                $end_time,
                $note
            );

            if ($stmt->execute()) {
                $message = "برنامه سفر با موفقیت ثبت شد.";

                $_POST = [];
            } else {
                $message = "خطا در ثبت برنامه.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

/* =========================
   دریافت سفرهای کاربر
========================= */
$trips = [];

$stmt = $conn->prepare("
    SELECT id, title, destination, start_date, end_date
    FROM trips
    WHERE user_id = ?
    ORDER BY start_date ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

$stmt->close();

/* =========================
   دریافت برنامه‌ها
========================= */
$itineraries = [];

$stmt = $conn->prepare("
    SELECT
        i.*,
        t.title AS trip_title,
        t.destination
    FROM itinerary i
    INNER JOIN trips t ON i.trip_id = t.id
    WHERE t.user_id = ?
    ORDER BY
        i.activity_date IS NULL,
        i.activity_date ASC,
        i.start_time ASC,
        i.day_number ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $itineraries[] = $row;
}

$stmt->close();

$name = $_SESSION["name"] ?? "کاربر";
$photo = $_SESSION["photo"] ?? "images/profile.png";

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>برنامه سفر | Travel Planner</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Tahoma, Arial, sans-serif;
}

body{
    background:
        radial-gradient(circle at top right,#e0e7ff 0%,transparent 30%),
        radial-gradient(circle at bottom left,#f3e8ff 0%,transparent 30%),
        #f8fafc;
    color:#1e293b;
}

/* ================= SIDEBAR ================= */

.sidebar{
    position:fixed;
    right:0;
    top:0;
    width:250px;
    height:100vh;
    background:linear-gradient(180deg,#4f46e5,#312e81);
    color:white;
    padding:25px 18px;
    z-index:1000;
    box-shadow:-8px 0 30px rgba(15,23,42,.15);
}

.logo{
    text-align:center;
    font-size:25px;
    font-weight:bold;
    margin-bottom:35px;
}

.profile{
    text-align:center;
    margin-bottom:30px;
}

.profile img{
    width:70px;
    height:70px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid white;
    margin-bottom:10px;
}

.profile p{
    font-size:14px;
    opacity:.9;
}

.menu a{
    display:block;
    text-decoration:none;
    color:white;
    padding:13px 15px;
    margin:7px 0;
    border-radius:12px;
    transition:.3s;
}

.menu a:hover,
.menu a.active{
    background:rgba(255,255,255,.18);
    transform:translateX(-5px);
}

.logout{
    margin-top:25px !important;
    background:rgba(239,68,68,.25);
}

/* ================= MAIN ================= */

.main{
    margin-right:250px;
    padding:35px;
    min-height:100vh;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.header h1{
    font-size:30px;
}

.header p{
    color:#64748b;
    margin-top:7px;
}

/* ================= ALERT ================= */

.alert{
    background:#ecfdf5;
    color:#047857;
    border:1px solid #a7f3d0;
    padding:14px 18px;
    border-radius:14px;
    margin-bottom:25px;
    animation:slideDown .5s ease;
}

@keyframes slideDown{
    from{
        opacity:0;
        transform:translateY(-15px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ================= FORM ================= */

.form-card{
    background:rgba(255,255,255,.88);
    backdrop-filter:blur(12px);
    padding:28px;
    border-radius:22px;
    box-shadow:0 15px 40px rgba(15,23,42,.08);
    margin-bottom:35px;
}

.form-title{
    font-size:21px;
    font-weight:bold;
    margin-bottom:22px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.input-group{
    display:flex;
    flex-direction:column;
}

.input-group.full{
    grid-column:1/-1;
}

label{
    margin-bottom:8px;
    font-size:14px;
    font-weight:bold;
    color:#334155;
}

input,
select,
textarea{
    width:100%;
    border:1px solid #cbd5e1;
    padding:13px 15px;
    border-radius:12px;
    outline:none;
    background:white;
    transition:.3s;
    font-size:14px;
}

input:focus,
select:focus,
textarea:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 4px rgba(99,102,241,.12);
    transform:translateY(-1px);
}

textarea{
    min-height:100px;
    resize:vertical;
}

.submit-btn{
    grid-column:1/-1;
    border:none;
    padding:15px;
    border-radius:13px;
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

.submit-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 25px rgba(79,70,229,.3);
}

/* ================= AUTO DATE ================= */

.date-info{
    margin-top:8px;
    font-size:12px;
    color:#64748b;
}

.date-info span{
    color:#4f46e5;
    font-weight:bold;
}

/* ================= LIST ================= */

.section-title{
    font-size:23px;
    margin-bottom:20px;
}

.itinerary-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.item-card{
    background:white;
    border-radius:20px;
    padding:22px;
    box-shadow:0 10px 30px rgba(15,23,42,.07);
    border:1px solid #eef2ff;
    transition:.3s;
    position:relative;
    overflow:hidden;
}

.item-card::before{
    content:"";
    position:absolute;
    top:0;
    right:0;
    width:100%;
    height:4px;
    background:linear-gradient(90deg,#6366f1,#8b5cf6);
}

.item-card:hover{
    transform:translateY(-7px);
    box-shadow:0 20px 40px rgba(79,70,229,.13);
}

.day-badge{
    display:inline-block;
    background:#eef2ff;
    color:#4338ca;
    padding:7px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    margin-bottom:13px;
}

.item-card h3{
    font-size:19px;
    margin-bottom:12px;
}

.info{
    color:#64748b;
    font-size:13px;
    line-height:2;
}

.note{
    background:#f8fafc;
    border-radius:12px;
    padding:10px;
    margin-top:12px;
    color:#475569;
    font-size:13px;
}

.delete-btn{
    display:block;
    text-align:center;
    text-decoration:none;
    color:#dc2626;
    background:#fef2f2;
    padding:10px;
    border-radius:10px;
    margin-top:15px;
    transition:.3s;
}

.delete-btn:hover{
    background:#fee2e2;
}

/* ================= EMPTY ================= */

.empty{
    background:white;
    padding:50px;
    text-align:center;
    border-radius:20px;
    color:#64748b;
    box-shadow:0 10px 30px rgba(15,23,42,.06);
}

/* ================= MOBILE ================= */

@media(max-width:1000px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .main{
        margin-right:0;
        padding:20px;
    }

    .menu{
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:5px;
    }

    .itinerary-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:650px){

    .header{
        display:block;
    }

    .header h1{
        font-size:24px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .input-group.full,
    .submit-btn{
        grid-column:auto;
    }

    .itinerary-grid{
        grid-template-columns:1fr;
    }

    .menu{
        grid-template-columns:1fr;
    }

    .form-card{
        padding:20px;
    }

}

</style>

</head>

<body>

<!-- ================= SIDEBAR ================= -->

<aside class="sidebar">

    <div class="logo">
        ✈️ Travel Planner
    </div>

    <div class="profile">

        <img
            src="<?php echo htmlspecialchars($photo); ?>"
            onerror="this.src='https://via.placeholder.com/70';"
            alt="Profile"
        >

        <p><?php echo htmlspecialchars($name); ?></p>

    </div>

    <nav class="menu">

        <a href="dashboard.php">🏠 داشبورد</a>

        <a href="trips.php">✈️ سفرهای من</a>

        <a href="add_trip.php">➕ ثبت سفر</a>

        <a href="destinations.php">📍 مقصدها</a>

        <a href="itinerary.php" class="active">📅 برنامه سفر</a>

        <a href="expenses.php">💰 مصارف</a>

        <a href="provinces.php">🗺️ ولایات</a>

        <a href="logout.php" class="logout">🚪 خروج</a>

    </nav>

</aside>


<!-- ================= MAIN ================= -->

<main class="main">

    <div class="header">

        <div>
            <h1>📅 برنامه سفر</h1>
            <p>برنامه روزانه سفرهای خود را مدیریت کنید.</p>
        </div>

    </div>


    <?php if ($message): ?>

        <div class="alert">
            ✅ <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- ================= ADD FORM ================= -->

    <section class="form-card">

        <div class="form-title">
            ➕ افزودن برنامه جدید
        </div>

        <form method="POST">

            <div class="form-grid">

                <!-- سفر -->

                <div class="input-group">

                    <label>✈️ انتخاب سفر *</label>

                    <select name="trip_id" required>

                        <option value="">-- انتخاب سفر --</option>

                        <?php foreach ($trips as $trip): ?>

                            <option
                                value="<?php echo $trip["id"]; ?>"
                                <?php
                                if (
                                    isset($_POST["trip_id"]) &&
                                    $_POST["trip_id"] == $trip["id"]
                                ) echo "selected";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $trip["title"] .
                                    " - " .
                                    $trip["destination"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- روز سفر -->

                <div class="input-group">

                    <label>📆 روز سفر *</label>

                    <input
                        type="number"
                        name="day_number"
                        min="1"
                        placeholder="مثلاً 1"
                        value="<?php echo htmlspecialchars($_POST["day_number"] ?? "1"); ?>"
                        required
                    >

                </div>


                <!-- فعالیت -->

                <div class="input-group">

                    <label>🎯 فعالیت *</label>

                    <input
                        type="text"
                        name="activity"
                        placeholder="مثلاً بازدید از برج توکیو"
                        value="<?php echo htmlspecialchars($_POST["activity"] ?? ""); ?>"
                        required
                    >

                </div>


                <!-- مکان -->

                <div class="input-group">

                    <label>📍 مکان</label>

                    <input
                        type="text"
                        name="location"
                        placeholder="مثلاً Tokyo Tower"
                        value="<?php echo htmlspecialchars($_POST["location"] ?? ""); ?>"
                    >

                </div>


                <!-- تاریخ -->

                <div class="input-group">

                    <label>🗓️ تاریخ فعالیت</label>

                    <input
                        type="date"
                        id="activity_date"
                        name="activity_date"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST["activity_date"] ??
                                date("Y-m-d")
                            );
                        ?>"
                    >

                    <div class="date-info">
                        تاریخ امروز به‌صورت خودکار پیشنهاد شده است:
                        <span id="todayText"></span>
                    </div>

                </div>


                <!-- شروع -->

                <div class="input-group">

                    <label>⏰ ساعت شروع</label>

                    <input
                        type="time"
                        name="start_time"
                        value="<?php echo htmlspecialchars($_POST["start_time"] ?? ""); ?>"
                    >

                </div>


                <!-- پایان -->

                <div class="input-group">

                    <label>⏰ ساعت پایان</label>

                    <input
                        type="time"
                        name="end_time"
                        value="<?php echo htmlspecialchars($_POST["end_time"] ?? ""); ?>"
                    >

                </div>


                <!-- یادداشت -->

                <div class="input-group full">

                    <label>📝 یادداشت</label>

                    <textarea
                        name="note"
                        placeholder="توضیحات یا یادداشت مربوط به این برنامه..."
                    ><?php echo htmlspecialchars($_POST["note"] ?? ""); ?></textarea>

                </div>


                <button type="submit" class="submit-btn">
                    ➕ ثبت برنامه سفر
                </button>

            </div>

        </form>

    </section>


    <!-- ================= LIST ================= -->

    <h2 class="section-title">
        📋 برنامه‌های ثبت‌شده
    </h2>


    <?php if (count($itineraries) > 0): ?>

        <div class="itinerary-grid">

            <?php foreach ($itineraries as $item): ?>

                <div class="item-card">

                    <span class="day-badge">
                        روز <?php echo intval($item["day_number"]); ?>
                    </span>

                    <h3>
                        <?php echo htmlspecialchars($item["activity"]); ?>
                    </h3>

                    <div class="info">

                        ✈️
                        <?php echo htmlspecialchars($item["trip_title"]); ?>

                        <br>

                        🌍
                        <?php echo htmlspecialchars($item["destination"]); ?>

                        <?php if (!empty($item["location"])): ?>

                            <br>

                            📍
                            <?php echo htmlspecialchars($item["location"]); ?>

                        <?php endif; ?>


                        <?php if (!empty($item["activity_date"])): ?>

                            <br>

                            🗓️
                            <?php echo htmlspecialchars($item["activity_date"]); ?>

                        <?php endif; ?>


                        <?php if (!empty($item["start_time"])): ?>

                            <br>

                            ⏰ شروع:
                            <?php echo substr($item["start_time"], 0, 5); ?>

                        <?php endif; ?>


                        <?php if (!empty($item["end_time"])): ?>

                            <br>

                            ⏰ پایان:
                            <?php echo substr($item["end_time"], 0, 5); ?>

                        <?php endif; ?>

                    </div>


                    <?php if (!empty($item["note"])): ?>

                        <div class="note">

                            📝
                            <?php echo nl2br(htmlspecialchars($item["note"])); ?>

                        </div>

                    <?php endif; ?>


                    <a
                        href="itinerary.php?delete=<?php echo intval($item["id"]); ?>"
                        class="delete-btn"
                        onclick="return confirm('آیا از حذف این برنامه مطمئن هستید؟');"
                    >
                        🗑️ حذف برنامه
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <div style="font-size:50px;margin-bottom:15px;">
                📅
            </div>

            <h3>هنوز برنامه‌ای ثبت نشده است</h3>

            <p style="margin-top:10px;">
                اولین برنامه سفر خود را از فرم بالا اضافه کنید.
            </p>

        </div>

    <?php endif; ?>

</main>


<script>

/* =========================
   تاریخ امروز کامپیوتر
========================= */

function setComputerDate(){

    const now = new Date();

    const year = now.getFullYear();

    const month = String(now.getMonth() + 1).padStart(2, "0");

    const day = String(now.getDate()).padStart(2, "0");

    const today = `${year}-${month}-${day}`;

    const dateInput = document.getElementById("activity_date");

    const todayText = document.getElementById("todayText");

    /*
       اگر تاریخ خالی باشد،
       تاریخ کامپیوتر را پیشنهاد می‌کنیم.
    */

    if (!dateInput.value) {
        dateInput.value = today;
    }

    todayText.textContent = today;

}

setComputerDate();

</script>

</body>
</html>