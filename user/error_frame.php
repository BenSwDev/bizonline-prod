<?php
include_once "auth.php";
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <title>Biz online</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="/user/assets/css/style.css">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="/user/assets/js/jquery-2.2.4.min.js"></script>
</head>
<body>
<script>
    $(function(){
        //$("html , body",window.parent.document).animate({scrollTop:0});
        $("html , body",window.parent.document).scrollTop(0);
    });
</script>
<div style="text-align:center;font-size:20px;margin-top:20px">
    <b style="color:red"><?=(strip_tags($_GET['error']) ?: 'Oops... some error happened !')?></b>
</div>
</body>
</html>
