<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$pHash    = typemap($_GET['id'], 'string');
$siteID   = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);

$json   = udb::single_value("SELECT `pixels` FROM `sites` WHERE `siteID` = " . $siteID);
$pixels = json_decode($json ?: "[]", true);

if ($siteID && 'POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $data = typemap($_POST, [
            'pTitle'  => 'string',
            'pCode'   => 'html',
            '!active' => 'int',
            '!hash'   => 'string'
        ]);

        if (empty($data['pTitle']))
            throw new LocalException("Please enter pixel title");
        if (empty($data['pCode']))
            throw new LocalException("Empty pixel body");

        if (!$data['hash']){
            $lim = 10;
            do {
                if ($lim-- <= 0)
                    throw new LocalException("Failed to create hash for pixel");
                $data['hash'] = dechex(crc32($data['pCode'] . $data['hash']));
            }
            while(isset($pixels[$data['hash']]));
        }

        $pixels[$data['hash']] = [
            'title'  => $data['pTitle'],
            'code'   => $data['pCode'],
            'active' => $data['active']
        ];

        udb::update("sites", ['pixels' => json_encode($pixels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], "`siteID` = " . $siteID);

        echo '<script>window.parent.location.reload(); window.parent.closeTab();</script></section></body></html>';
        exit;
    }
    catch (LocalException $e){
        $error = $e->getMessage();
    }
}

$pixel = $pHash ? ($pixels[$pHash] ?? []) : [];

?>
<div class="editItems">
	<div class="frameContent">
		<form action="" method="post">
            <input type="hidden" name="hash" value="<?=$pHash?>" />
            <div class="inputLblWrap">
                <div class="labelTo">שם התוספת</div>
                <input type="text" placeholder="שם הפיקסל" value="<?=($pixel['title'] ?? '')?>" name="pTitle" style="width:450px" />
            </div>
            <div class="inputLblWrap">
                <div class="switchTtl">פיקסל פעיל</div>
                <label class="switch">
                    <input type="checkbox" name="active" value="1" <?=(($pHash && empty($pixel['active'])) ? '' : 'checked')?> />
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">קוד הפיקסל</div>
                <textarea name="pCode" style="width:600px;height:300px;direction:ltr" title="קוד הפיקסל"><?=htmlspecialchars($pixel['code'])?></textarea>
            </div>

			<div class="clear"></div>
			<input type="submit" value="שמור" class="submit">
		</form>	
	</div>
</div>
<?php
include_once "../../../bin/footer.php";
