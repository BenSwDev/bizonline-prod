<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

$buserID = intval($_POST['buserID'] ?? $_GET['buserID']);
$error = '';
$buser = $us = [];

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $data = typemap($_POST, [
            'active' => 'int',
            'showstats' => 'int',
            'access' => 'int',
            'userType' => 'int',
            'name'   => 'string',
            'phone'  => 'string',
            'phone2' => 'string',
            'email'  => 'email',
            'username' => 'string',
            'pwd' => 'string'
        ]);

		if (!$data['username'])
            throw new LocalException('חייב להיות שם משתמש');
        if (mb_strlen($data['username'], 'UTF-8') > 31)
            throw new LocalException('שם משתמש ארוך מדיי');

        $exists = udb::single_value("SELECT `buserID` FROM `biz_users` WHERE `username` = '" . udb::escape_string($data['username']) . "' AND `buserID` <> " . $buserID);
        if ($exists)
            throw new LocalException('שם משתמש כבר קיים');

        // main user data
        $hData = [
            'name'   => $data['name'],
            'active' => isset($data['active']) ? 1 : 0,
            'showstats' => isset($data['showstats']) ? 1 : 0,
            'email'  => $data['email'],
            'phone'  => $data['phone'],
            'phone2' => $data['phone2'],
            'username' => $data['username'],
            'userType' => $data['userType'],
            'access' => $data['access'] ?: 1
        ];

        if ($data['pwd']){
            if (mb_strlen($data['pwd'], 'UTF-8') < 6)
                throw new LocalException('סיסמא פשוטה מדיי');

            $hData['password'] = password_hash($data['pwd'], PASSWORD_DEFAULT);
        }


        if (!$buserID){      // opening new
            $buserID = udb::insert('biz_users', $hData);
        } else {
            udb::update('biz_users', $hData, '`buserID` = ' . $buserID);
        }

        udb::query("DELETE FROM `sites_users` WHERE `buserID` = " . $buserID);

        $sites = typemap($_POST['sites'], ['int']);
        if (count($sites))
            udb::query("INSERT INTO `sites_users`(`buserID`, `siteID`) SELECT '" . $buserID . "', `siteID` FROM `sites` WHERE `siteID` IN (" . implode(',', $sites) . ")");

?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
    }
    catch (LocalException $e){
        $error = $e->getMessage();
    }

    $buser = typemap($_POST, ['string' => 'string']);
    $us    = array_combine(typemap($_POST['sites'] ?? [], ['int']), typemap($_POST['sites'] ?? [], ['int']));
}
elseif ($buserID){
    $buser = udb::single_row("SELECT * FROM `biz_users` WHERE `buserID` = " . $buserID);
    $us    = udb::key_value("SELECT `siteID`, `buserID` FROM `sites_users` WHERE `buserID` = " . $buserID);
}

$sites = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE active=1 ORDER BY `siteName`");
//print_r($buser);
?>
<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=($buserID ? ($buser['name'] ?: $buser['username']) : "הוספת משתמש חדש")?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
            <div class="inputLblWrap">
                <div class="labelTo">שם מלא</div>
                <input type="text" placeholder="שם מלא" name="name" value="<?=js_safe($buser['name'])?>" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">טלפון</div>
                <input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($buser['phone'])?>" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">טלפון נוסף</div>
                <input type="text" placeholder="טלפון נוסף" name="phone2" value="<?=js_safe($buser['phone2'])?>" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">דוא"ל</div>
                <input type="text" placeholder="דוא&quot;ל" name="email" value="<?=js_safe($buser['email'])?>" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">שם משתמש</div>
                <input type="text" placeholder="שם משתמש" name="username" value="<?=js_safe($buser['username'])?>" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">סיסמא</div>
                <input type="text" placeholder="<?=($buser['password'] ? '********' : 'סיסמא')?>" name="pwd" />
            </div>
            <div class="inputLblWrap">
                <div class="switchTtl">פעיל</div>
                <label class="switch">
                    <input type="checkbox" name="active" value="1" <?=($buser['active']==1 || !$buserID)?"checked":""?> />
                    <span class="slider round"></span>
                </label>
            </div>
			<div class="inputLblWrap">
                <div class="switchTtl">סטטיסטיקות</div>
                <label class="switch">
                    <input type="checkbox" name="showstats" value="1" <?=($buser['showstats']==1 || !$buserID)?"checked":""?> />
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">רמת גישה</div>
                <select name="access" title="רמת גישה">
                    <option value="255" <?=($buser['access'] == 255 ? 'selected' : '')?>>גישה מלאה</option>
                    <option value="1" <?=($buser['access'] == 1 ? 'selected' : '')?>>ללא עריכה</option>
                </select>
            </div>
			<div class="inputLblWrap">
                <div class="labelTo">תצוגת תפריט</div>
                <select name="userType" title="סוג משתמש">
                    <option value="0" <?=($buser['userType'] == 0 ? 'selected' : '')?>>הכל</option>
                    <option value="2" <?=($buser['userType'] == 2 ? 'selected' : '')?>>מסכי עובד ספא</option>
                    <option value="1" <?=($buser['userType'] == 1 ? 'selected' : '')?>>הצהרות בריאות בלבד</option>
                </select>
            </div>
		</div>
        <div class="catName">מתחמים</div>
        <input type="text" style="border: 1px solid #ccc; background-color: white" id="siteSearch" placeholder="חפש מתחם" />
        <div class="checksWrap" id="siteList">

<?php
    foreach($sites as $siteID => $siteName) {
?>
            <div class="checkLabel checkIb">
                <div class="checkBoxWrap">
                    <input class="checkBoxGr" type="checkbox" name="sites[]" <?=($us[$siteID] ? "checked" : "")?> value="<?=$siteID?>" id="ch<?=$siteID?>" />
                    <label for="ch<?=$siteID?>"></label>
                </div>
                <label class="outer" for="ch<?=$siteID?>"><?=$siteName?></label>
            </div>
<?php
    }
?>
        </div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$buserID?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>
<script>
$(function(){
    var ts = null;

    function search(){
        var text = this.value, reg = new RegExp(text.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'i');

        $('#siteList').find('label.outer').each(function(){
            $(this.parentNode).css('display', (!text || reg.test(this.innerText)) ? 'inline-block' : 'none');
        });

        ts = null;
    }

    $('#siteSearch').on('keyup paste', function(){
        if (ts)
            window.clearTimeout(ts);
        ts = window.setTimeout(search.bind(this), 100);
    });
<?php
    if ($error)
        echo 'alert("' , $error , '")';
?>
});
</script>
