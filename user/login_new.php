<?php
include '../functions.php';

list($path_base, $path_token) = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

if ($path_base == 'member'){
    include "login_member.php";
    return;
}
?>
<!doctype html>
<html lang="he" dir="rtl" class="no-js" style="height:100vh">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css" />
    <link rel="icon" href="/favicon.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico?v=2" type="image/x-icon">
	<link rel="manifest" href="/<?=$path_base?>/manifest.json">
	<link rel="stylesheet" href="/index/style.css?v=<?=time()?>">
    <title>BIZonline - ביז אונליין - כניסה לחשבון</title>
    
    <meta name="description" content="התחברות לאיזור האישי לניהול היומן וההזמנות שלך" />
	<meta name="keywords" content="כניסה לחשבון, כניסה לחשבון ביז אונליין, כניסה לחשבון BIZonline, איזור ניהול, איזור ניהול ביז אונליין, איזור ניהול BIZonline" />

    <style>
        /* LOGIN */
        div.bg { background-color: #dbf5f6; background-image: url(assets/img/login.jpg); background-repeat: no-repeat; background-size: cover; background-position: left -20vw top 0; position: absolute; top: 0; right: 0; left: 0; bottom: 0; filter: blur(20px); }

        section.login { width: 520px; position: relative; top: auto; border-radius: 10px; padding-top: 0; margin: auto; }
section.login > .logo{position: relative;box-sizing: border-box;background-position: center center;background-repeat: no-repeat;background-size: 80%;border-radius: 100%;z-index: 10;text-align: center;margin-bottom: 10px;font-size: 60px;color: #0dabb6;text-shadow: 0 0 5px white, 0 0 5px white, 0 0 5px white;}
section.login > .logo  img{ width:80%;max-width:250px}
section.login > .form{position: relative;top: 0;bottom: 0;left: 0;margin: auto;width: 100%;max-width:380px;}
section.login > .form > form > .user{width: 100%;margin-bottom: 5px;}
section.login > .form > form > .user > input { font-size: 18px; width: 100%; box-sizing: border-box; border: 1px #ccc solid; padding-right: 30px; outline: none; line-height: 40px; border-radius: 6px; background: white; padding-top: 15px; padding-bottom: 15px; }
section.login > .form > form > .pass{width: 100%;}
section.login > .form > form > .pass > input { font-size: 18px; width: 100%; box-sizing: border-box; border: 1px #ccc solid; padding-right: 30px; outline: none; line-height: 40px; border-radius: 6px; background: white; padding-top: 15px; padding-bottom: 15px; }
section.login > .form > form > .login { left: 0px; top: 10px; bottom: 0; height: 60px; box-sizing: border-box; overflow: hidden; margin: 10px auto; }
section.login > .form > form > .login > input { font-size: 20px; width: 100%; height: 100%; border: none; cursor: pointer; margin: 0; outline: none; background: #fdd320; background-image: linear-gradient(90deg, #fdd320 0, #f72b61 100%); background-color: transparent; display: flex ; align-items: center; padding: 10px 20px 10px 20px; color: #fff; text-decoration: none; gap: 5px; border-radius: 6px; transition: all .3s ease; position: relative; top: 0; font-weight: 600; }
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
    <div class="bg"></div>
	<div style="max-width: 1300px; width: 100%; position: relative; margin: auto; height: 100vh; display: flex ; align-items: center; justify-content: center;">
    <section class="login">
        <div class="logo"><img src="https://bizonline.co.il/wp-content/uploads/2024/12/bizonline_final_black.webp" alt="" /></div>
        <div class="form">
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
		<div class="callus">
			עדיין לא רשומים? התקשרו לתמיכה
			<a href="tel:053-7106102">053-7106102</a>
		</div>
        <div class="support">
            <a href="#" class="forgot">שחכתי סיסמה</a>
            <a href="#" class="error">לא מצליח להתחבר</a>
        </div>
    </section>

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
        localStorage.setItem("tfusa_username", this.user.value);
        localStorage.setItem("tfusa_password", this.pass.value);

        runLogin(this.user.value, this.pass.value);

        return false;
    });

<?php
    if (isset($_GET['logout'])){
?>
    localStorage.setItem("tfusa_username", '');
    localStorage.setItem("tfusa_password", '');
<?php
    } else {
?>
    if(localStorage.tfusa_username && localStorage.tfusa_password)
        runLogin(localStorage.tfusa_username, localStorage.tfusa_password, true);
<?php
    }
?>

    function runLogin(user, pass, noErr){
        if (user && pass){
            var data = {user:user, pass:pass, login:'login'};

            $(".login").css("opacity", 0.2);
            $(".loaderUser").show();

            $.post('js_login.php', data, function(res){
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
