<?php
    include_once "bin/system.php";



if(!isset($root)){
    $root = "http://www.bizonline.co.il/cms";
}

?>
<!doctype html>
<html lang="en" class="no-js">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <link rel="stylesheet" href="<?=$root?>/app/app.css?v=<?=time()?>">
	<meta name="robots" content="noindex" />
    <link rel="icon" href="" type="image/x-icon">
    <link rel="shortcut icon" href="" type="image/x-icon">

	<title><?=TITLE?>  - מערכת ניהול</title>
</head>
<body>

    <section class="login">
        <div class="logo"></div>
        
        <div class="form">
            <form method="post" action="">
                <div class="user">
                    <input type="text" name="user" id="user" placeholder="שם משתמש" style="-webkit-user-select: text !important;">
                </div>
                <div class="pass">
                    <input type="password" name="pass" id="pass" placeholder="סיסמה" style="-webkit-user-select: text !important;">
                </div>
                <div class="login">
                    <input type="submit" id="login" name="login" value="">
                </div>
            </form>
        </div>
        <div class="support">
            <a href="#" class="forgot">שכחתי סיסמה</a>
            <a href="#" class="error">לא מצליח להתחבר</a>
        </div>
<?php
    if(isset($_POST['login'])){
        $username = inDB($_POST['user']);
        $password = sha1(sha1(sha1($_POST['pass'])));

        $que = "SELECT * FROM `users` WHERE username='" . $username . "' AND password='" . $password . "'";
        $user = udb::single_row($que);

        if($user){
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['name']       = $user['name'];
            $_SESSION['permission'] = $user['permission'];

            $que = "SELECT `listID`, `file` FROM `users_access` WHERE `userID` = " . $user['id'];
            $_SESSION['access'] = udb::key_value($que, array('listID',null), 'file');
?>

        <script>
            localStorage.setItem("username", "<?=$username?>");
            localStorage.setItem("password", "<?=$password?>");
        </script>
        <script>
            window.location.href = "/cms/";
        </script>

<?php
            exit;
        } 
      }
        ?>
    </section>
    <div class="loaderUser"></div>
	<footer>
        Copyright © 2018 <a href="http://www.ssd.co.il" target="_blank">SSD</a>, All rights reserved.
    </footer>
    <script src="<?=$root?>/app/jquery.js"></script>
    <script src="<?=$root?>/app/jquery-ui.min.js"></script>
    <script src="<?=$root?>/app/app.js"></script>
    <script>
        app();
    </script>

	<script>

	if(localStorage.username && localStorage.password){
		$(".loaderUser").show();
		$(".login").css("opacity", "0.2");
		var data = {user:localStorage.username, password:localStorage.password};
		$.ajax({
			url: '//'+window.location.hostname+'/cms/js_login.php',
			type: 'POST',
			data: data,
			async: false,
			dataType: "text",
			success: function (returndata) {
				if(returndata=="error"){
					
				} else {
					if(returndata!=""){
						window.location.href=returndata;
					} else {
						$(".loaderUser").hide();
						$(".login").css("opacity",1);
						localStorage.setItem("username", "");
						localStorage.setItem("password", "");
					}
				}
			}
		});
	}	
</script>
</body>
</html>