<!doctype html>
<html lang="he" dir="rtl" class="no-js" style="height:100vh">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css" />
    <link rel="icon" href="/favicon.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico?v=2" type="image/x-icon">
	<link rel="manifest" href="/member/manifest.json">
	<link rel="stylesheet" href="/index/style.css?v=<?=time()?>">
    <!-- link rel="icon" sizes="192x192" href="/webimages/logo192.png?v=1">
    <link rel="icon" sizes="128x128" href="/webimages/logo128.png?v=1">
    <link rel="apple-touch-icon" sizes="128x128" href="/webimages/logo128.png?v=1">
    <link rel="apple-touch-icon-precomposed" sizes="128x128" href="/webimages/logo128.png?v=1" -->
    <title>BIZonline - ביז אונליין - כניסה לחשבון</title>
    
    <meta name="description" content="התחברות לאיזור האישי לניהול היומן וההזמנות שלך" />
	<meta name="keywords" content="כניסה לחשבון, כניסה לחשבון ביז אונליין, כניסה לחשבון BIZonline, איזור ניהול, איזור ניהול ביז אונליין, איזור ניהול BIZonline" />

    <style>
        /* LOGIN */
body {background-color: #dbf5f6;background-image: url(assets/img/login.jpg);background-repeat: no-repeat;background-size: cover;background-position: left -20vw top 80px;}

section.login {width: 520px;position: absolute;top: 10vh;right: 0;left: 0;background: rgba(255,255,255,0.8);padding: 10px;border-radius: 10px;box-shadow: 0 0 10px rgba(0,0,0,0.3);padding-top: 30px;margin: auto;}
section.login > .logo{position: relative;box-sizing: border-box;background-position: center center;background-repeat: no-repeat;background-size: 80%;border-radius: 100%;z-index: 10;text-align: center;margin-bottom: 10px;font-size: 60px;color: #0dabb6;text-shadow: 0 0 5px white, 0 0 5px white, 0 0 5px white;}
section.login > .logo  img{ width:80%}
section.login > .form{position: relative;top: 0;bottom: 0;left: 0;margin: auto;width: 100%;max-width:380px;}
section.login > .form > form > .user{width: 100%;margin-bottom: 5px;}
section.login > .form > form > .user > input {font-size: 18px; width: 100%;box-sizing: border-box;border: 1px #ccc solid;padding-right: 30px;outline: none;line-height: 40px;border-radius: 20px;background: white;}
section.login > .form > form > .pass{width: 100%;}
section.login > .form > form > .pass > input{font-size: 18px; width: 100%;box-sizing: border-box;border: 1px #ccc solid;padding-right: 30px;outline: none;line-height: 40px;border-radius: 20px;background: white;}
section.login > .form > form > .login {left: 0px;top: 10px;bottom: 0;height: 60px;box-sizing: border-box;border-radius: 30px;overflow: hidden;margin:10px auto}
section.login > .form > form > .login > input {font-size: 20px;color: #fff;display: block;width: 100%;height: 100%;background: linear-gradient(180deg,#ec5b47,#e73219);background-size: 50%;border: none;cursor: pointer;margin: 0;padding: 0;outline: none;}
section.login > .support{ display:none;   position: relative;    bottom:10px;    right: 100px;    width: 100%;}
section.login > .support > a{    display: inline-block;    font-size: 14px;    line-height: 20px;    padding-right: 20px;    background-repeat: no-repeat;    background-size: auto 80%;    background-position: right center;}
section.login > .support > a.forgot {    background-image: url("assets/img/lock.png");    padding-left: 7px;    margin-left: 3px;    border-left: 1px solid rgba(0,0,0,0.1);}
section.login > .support > a.error {    background-image: url("assets/img/error.png");}
section.login > .callus{text-align:center;color:#777}
section.login > .callus a {display: block;text-decoration: none;color: #088d96;font-weight: bold;font-size: 18px;}
footer {position:absolute;bottom:0;left:0;right:0;text-align:center;padding:10px;background:rgba(255,255,255,0.6);color:#555}
footer * {color:#555}

.video {position: absolute;max-width: 540px;width: 90%;right: 0;left: 0;top: calc(10vh + 240px);height: auto;display: block;border-radius: 10px;overflow: hidden;box-shadow: 0 0 10px rgba(0,0,0,0.5);background: white;margin: 20px auto 100px;}
.video::after {content: "";display: block;width: 100%;padding-bottom: 56%;}
video#myVideo {position: absolute;width: 100%;height: 100%;}

@media (max-width:800px){
	section.login {right: 0;left: 0;margin: auto;width: calc(100% - 40px);}
	.video {right: 10px;width: calc(100% - 20px);left: 10px;}
}
    </style>
</head>
<body >
    <?php include "../partials/header.php"; ?>
	<div style="max-width:1300px;width:100%;position:relative;margin:auto;height:100%;">
    <section class="login">
       
        <div class="form">
            <div style="text-align:Center;font-size:18px;font-weight:bold;margin-bottom:10px">כניסת מטפלים</div>
			<form id="loginForm" method="post">
                <div class="user">
                    <input type="text" name="user" id="user" placeholder="שם משתמש" style="-webkit-user-select: text !important;">
                </div>
                <div class="pass">
                    <input type="password" name="pass" id="pass" placeholder="סיסמה" style="-webkit-user-select: text !important;">
                </div>
                <div class="login">
                    <input type="submit" id="login" name="login" value="התחבר">
                </div>
            </form>
        </div>
		<div class="callus" style="display:none">
			עדיין לא רשומים? התקשרו לתמיכה
			<a href="tel:053-7106102">053-7106102</a>
		</div>
        <div class="support">
            <a href="#" class="forgot">שחכתי סיסמה</a>
            <a href="#" class="error">לא מצליח להתחבר</a>
        </div>
    </section>
	<div class="video">
		<iframe width="100%" height="100%" src="https://www.youtube.com/embed/M-V7hE1JxDs" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
    </div>
	
    <div class="loaderUser"></div>
	<?/*<footer>
        Copyright © 2020 <a href="http://www.ssd.co.il" target="_blank">SSD</a>, All rights reserved.
    </footer>*/?>
    <script src="assets/js/jquery-2.2.4.min.js"></script>
    <script src="assets/js/sweetalert2.min.js"></script>
	<script>
$(function(){
    var bUrl = '<?=str_replace("'", "\\'", typemap(base64_decode($_GET['back']), 'string'))?>';

    $('#loginForm').submit(function(){
        localStorage.setItem("member_username", this.user.value);
        localStorage.setItem("member_password", this.pass.value);

        runLogin(this.user.value, this.pass.value);

        return false;
    });

<?php
    if (isset($_GET['logout'])){
?>
    localStorage.setItem("member_username", '');
    localStorage.setItem("member_password", '');
<?php
    } else {
?>
    if(localStorage.member_username && localStorage.member_password)
        runLogin(localStorage.member_username, localStorage.member_password, true);
<?php
    }
?>

    function runLogin(user, pass, noErr){
        if (user && pass){
            var data = {user:user, pass:pass, login:'login'};

            $(".login").css("opacity", 0.2);
            $(".loaderUser").show();

            $.post('js_login_member.php', data, function(res){
                if (res.success)
                    window.location.href = (bUrl || res.link);
                else {
                    $(".loaderUser").hide();
                    $(".login").css("opacity", 1);

                    noErr || swal('שגיאה!', res.error || res._txt || '', 'error');
                }
            }, 'json');
        }
    }
});
</script>

</body>
</html>
