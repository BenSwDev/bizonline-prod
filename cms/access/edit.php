<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

if($_SESSION['permission']!=100){ ?><script>window.location.href="/cms/";</script><?php	exit; }

$userID  = intval($_POST['uID']) ? intval($_POST['uID']) : intval($_GET['uID']);
$frameID = intval($_GET['frame']);

if('POST' == $_SERVER['REQUEST_METHOD']) {
    $cp = array(
          'name'       => inDb($_POST['name'])
        , 'username'   => inDb($_POST['username'])
        , 'permission' => intval($_POST['permission'])
    );
	print_r($_POST); echo "<br><br>";
    if ($userID){
        $cp['isActive'] = intval($_POST['isActive']);
    }
	
	if($_POST['newpass1'] && ($_POST['newpass1'] == $_POST['newpass2'])){
        $cp['password'] = sha1(sha1(sha1($_POST['newpass1'])));
		echo "ok";		
	}

	if($userID){
		udb::update("users", $cp, "`id` = " . $userID);
	} else{
		$cp['password'] = sha1(sha1(sha1($_POST['password'])));
		$userID = udb::insert("users", $cp, false);
	}


    if (is_file('../bin/menu.php') && isset($_POST['mfile'])){
        $menu  = include '../bin/menu.php';
        $files = array();

        if (is_array($_POST['mfile']))
            foreach($_POST['mfile'] as $mf)
                $files[] = inDb($mf);

        $keys = array_intersect(flattenMenuKeys($menu), $files);

        udb::query("DELETE FROM `users_access` WHERE `userID` = " . $userID);

        if (count($keys)){
            $files = array();
            foreach($keys as $key)
                $files[] = "(" . $userID . ", 1, '" . $key . "')";

            udb::query("INSERT INTO `users_access`(`userID`, `listID`, `file`) VALUES" . implode(',', $files));
        }
    }

?>
		<script>//window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>);</script>
<?php
}

$que = "SELECT * FROM `users` WHERE `id` = " . $userID;
$user = udb::single_row($que);

$que = "SELECT `file` FROM `users_access` WHERE `userID` = " . $userID;
$ufiles = udb::single_column($que);

?>
<style type="text/css">
.sectionParams .param.submenu {padding-right:20px}
</style>
<div class="editItems">
    <h1><?=outDb($userID ? ($user['name'] ? $user['name'] : $user['username']) : 'משתמש חדש')?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="password" style="width:0;height:0;visibility:hidden;">
		<input type="text" style="width:0;height:0;visibility:hidden;">
		<b>פרטי גישה</b>
		<div class="section">
			<div class="inptLine">
				<div class="label">שם: </div>
				<input type="text" value="<?=$user['name']?>" name="name" class="inpt" autocomplete="off" title="" />
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">שם משתמש: </div>
				<input type="text" value="<?=$user['username']?>" name="username" class="inpt" autocomplete="off" title="" />
			</div>
		</div>
<?php
	if ($userID){
?>
		<div class="section">
			<div class="inptLine">
				<div class="label">משתמש פעיל: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=($user['isActive'] ? "checked='checked'" : '')?> name="isActive" id="isActive<?=$userID?>" />
					<label for="isActive<?=$userID?>"></label>
				</div>
			</div>
		</div>
<?php
	} else {
?>
		<div class="section">
			<div class="inptLine">
				<div class="label">סיסמא:</div>
				<input type="password" value="" name="password" class="inpt" autocomplete="off">
			</div>
		</div>
<?php
	}
?>
        <div class="section">
            <div class="inptLine">
                <div class="label">סוג משתמש: </div>
                <select name="permission" style="width:98%">
                    <option value="50" <?=($user['permission']==50 ? "selected='selected'" : "")?>>משתמש רגיל</option>
                    <option value="100" <?=($user['permission']==100 ? "selected='selected'" : "")?>>מנהל ראשי</option>
                </select>
            </div>
        </div>
        <div  style="clear:both;"></div>
		<b>גישה למסכים</b>
		<div class="sectionParams" data-type="1" style="max-width:350px">
<?php
	if (is_file('../bin/menu.php')){
?>
			<b style="display:block;margin-bottom:5px;">תפריט ראשי</b>
<?php
        $i = 0;
		$menu = include "../bin/menu.php";
		foreach($menu as $key => $data){
			if (is_numeric($key) && count($data['sub'])) {
			    $checked = false;

			    ob_start();
                foreach($data['sub'] as $subkey => $subdata){
                    if (!is_numeric($subkey)){
                        $checked = in_array($subkey, $ufiles) ? true : $checked;
?>
                <div class="param submenu">
                    <input type="checkbox" name="mfile[]" <?=(in_array($subkey, $ufiles) ? 'checked="checked"' : '')?> value="<?=$subkey?>" id="mfile_<?=(++$i)?>" />
                    <label for="mfile_<?=$i?>"><?=outDB($subdata['name'])?></label>
                </div>
<?php
                    }
                }
                $subs = ob_get_clean();
?>
                <div class="param">
                    <input type="checkbox" <?=($checked ? 'checked="checked"' : '')?> id="mfile_<?=(++$i)?>" />
                    <label for="mfile_<?=$i?>"><?=outDB($data['name'])?></label>
                </div>
                <div class="submenus" data-papa="mfile_<?=$i?>"><?=$subs?></div>
<?php
			} else {
?>
                <div class="param">
                    <input type="checkbox" name="mfile[]" <?=(in_array($key, $ufiles) ? 'checked="checked"' : '')?> value="<?=$key?>" id="mfile_<?=(++$i)?>" />
                    <label for="mfile_<?=$i?>"><?=outDB($data['name'])?></label>
                </div>
<?php
			}
        }
    }
?>
		</div>

<?php
    if ($userID){
?>
        <div  style="clear:both;"></div>
		<b style="margin-top:10px">שינוי סיסמא</b>
        <div class="section">
            <div class="inptLine">
                <div class="label">סיסמא חדשה: </div>
                <input type="password" value="" name="newpass1" id="newpass1" class="inpt" autocomplete="off" title="" />
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">אשר סיסמא חדשה: </div>
                <input type="password" value="" name="newpass2" id="newpass2" class="inpt" autocomplete="off" title="" />
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <input type="buton" value="שנה" class="submit" onclick="changePass()" title="שנה" />
            </div>
        </div>
<?php
    }
?>
		<div  style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$userID?"שמור":"הוסף"?>" class="submit" />
			</div>
		</div>
	</form>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
$(document).ready(function(){
    $('.sectionParams').on('click', 'input[type=checkbox]', function(){
        var pd;

        if (this.name && this.checked){
            pd = $(this).parents('.submenus');
            if (pd.length)
                document.getElementById(pd.data('papa')).checked = true;
        }
        else if (!this.name && !this.checked){
            pd = $(this.parentNode).next('.submenus');
            if (pd.length)
                $('input[type=checkbox]', pd).prop('checked', false);
        }
    });
});

function changePass(){
    var p1 = document.getElementById('newpass1'), p2 = document.getElementById('newpass2');
    if (p1.value && p2.value && p1.value === p2.value){
        $.getJSON('<?=$root?>/access/js_pass.php', {uid:<?=$userID?>, newpass:p1.value}, function(res){
            if (res.complete) {
                window.formAlert("green", "סיסמא שונתה בהצלחה", "");
                p1.value = p2.value = '';
            } else
                window.formAlert("red", "שגיאה בשינוי סיסמא", "");
        });
    } else
        window.formAlert("red", "שגיאה בהזנת סיסמא חדשה", "");
}
</script>
</body>
</html>
