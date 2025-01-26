<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$asid = $_CURRENT_USER->active_site() ?: 0;

$list = $_CURRENT_USER->sites();

if ('POST' == $_SERVER['REQUEST_METHOD']){
    
	$asid = typemap($_POST['asid'], 'int');
    if ($asid && !$_CURRENT_USER->has($asid)){
        echo 'Access denied';
        return;
    }

    $data = typemap($_POST, [       
        'agreement2'   => 'text',
        'agreement3'   => 'text',
        'agreement4'   => 'text',
        'agreement_rent'  => 'text',
        'defaultAgr'   => 'int'
    ]);

    if ($asid && in_array($asid, $list)){
        udb::update('sites_langs',$data, "domainID=1 AND langID=1 AND siteID = " . $asid);
        Translation::save_row('sites', $asid, $data, 1, 1);
?>
	<script type="text/javascript">
		window.location.href = window.location.href;
	</script>
<?php
        return;
    }
}

//$asid = intval($_GET['asid'])?intval($_GET['asid']):$_CURRENT_USER->active_site();
//
//if (!$asid || !in_array($asid, $list))
//    $asid = reset($list);

$que = "SELECT 	agreement1,	agreement2,	agreement3,agreement4, agreement_rent, defaultAgr FROM `sites_langs` WHERE domainID = 1 AND langID = 1 AND siteID = " . $asid;
$agreements = udb::single_row($que);

//$sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . implode(',', $list) . ")");
//include "partials/settings_menu.php"; 
?>
<section class="orders">
	<div class="last-orders">
		<div class="title">הסכמים</div>
		<form method="post" class="agreements">
		<input name="asid" value="<?=$asid?>" type="hidden">
            <!-- select name="asid" title="מתחם" onchange="window.location.href='?page=agreements&asite='+this.value">
<?php
//    foreach($list as $sid)
//        echo '<option value="' , $sid , '" ' , ($sid == $asid ? 'selected' : '') , '>' , $sname[$sid] , '</option>';
?>
            </select -->
			<div class="txtAreaWrap">
				<div class="ttl"><span>הסכם</span> 1</div>
				<div class="textEditorShow"><?=$agreements['agreement1']?></div><!-- name="agreement1" -->
				<div class="radioWrap">
					<input type="radio"  name="defaultAgr" value="1" id="defaultAgr1" 
					<?=($agreements['defaultAgr']==1 || !$agreements['defaultAgr']?"checked":"")?>>
					<label for="defaultAgr1">בחר הסכם זה כברירת מחדל</label>
				</div>
			</div>
			<div class="txtAreaWrap">
				<div class="ttl"><span>הסכם</span> 2</div>
				<textarea name="agreement2" class="textEditorX"><?=$agreements['agreement2']?></textarea>
				<div class="radioWrap">
					<input type="radio"  name="defaultAgr" value="2" id="defaultAgr2" <?=($agreements['defaultAgr']==2?"checked":"")?>>
					<label for="defaultAgr2">בחר הסכם זה כברירת מחדל</label>
				</div>
			</div>
			<div class="txtAreaWrap">
				<div class="ttl"><span>הסכם</span> 3</div>
				<textarea name="agreement3" class="textEditorX"><?=$agreements['agreement3']?></textarea>
				<div class="radioWrap">
					<input type="radio"  name="defaultAgr" value="3" id="defaultAgr3" <?=($agreements['defaultAgr']==3?"checked":"")?>>
					<label for="defaultAgr3">בחר הסכם זה כברירת מחדל</label>
				</div>
			</div>
			<div class="txtAreaWrap">
				<div class="ttl"><span>הסכם</span> 4</div>
				<textarea name="agreement4" class="textEditorX"><?=$agreements['agreement4']?></textarea>
				<div class="radioWrap">
					<input type="radio"  name="defaultAgr" value="4" id="defaultAgr4" <?=($agreements['defaultAgr']==4?"checked":"")?>>
					<label for="defaultAgr4">בחר הסכם זה כברירת מחדל</label>
				</div>
			</div>
			<div class="txtAreaWrap">
				<div class="ttl">הסכם שכירות</div>
				<textarea name="agreement_rent" class="textEditorX"><?=$agreements['agreement_rent']?></textarea>
				<div class="radioWrap">
					<input type="radio"  name="defaultAgr" value="10" id="defaultAgr10" <?=($agreements['defaultAgr']==10?"checked":"")?>>
					<label for="defaultAgr10">בחר הסכם זה כברירת מחדל</label>
				</div>
			</div>
			<input type="submit" value="עדכן הסכם">
		</form>
	</div>
</section>
