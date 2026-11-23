<?php

session_start();

/* حذف تمام اطلاعات Session */

$_SESSION = [];


/* حذف Cookie مربوط به Session */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/* پایان Session */

session_destroy();


/* برگشت به Login */

header("Location: login.php");

exit();

?>