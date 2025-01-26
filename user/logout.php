<?php
include_once "auth.php";

$_CURRENT_USER->logout();

unset($_SESSION['tfusa'], $_SESSION['tfusa_new'], $_SESSION['tfusa_active']);

?>
<!doctype html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <script>
        localStorage.removeItem("tfusa_username");
        localStorage.removeItem("tfusa_password");
        localStorage.removeItem("member_username");
        localStorage.removeItem("member_password");
        window.location.href = "login.php";
    </script>
</head>
<body></body>
</html>
