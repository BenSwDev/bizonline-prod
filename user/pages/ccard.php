<?php
    //$client = ($siteData['hasTerminal'] && strcasecmp($siteData['masof_type'], 'max') && count($_CURRENT_USER->sites()) == 1) ? YaadPay::getTerminal($_CURRENT_USER->sites(true)) : YaadPay::defaultTerminal();
    try {
        $termType = Terminal::hasTerminal($_CURRENT_USER->active_site());
        $client   = ($termType && strcasecmp($termType, 'max') && $_CURRENT_USER->single_site) ? Terminal::bySite($_CURRENT_USER->active_site()) : YaadPay::defaultTerminal();
        $link     = $client->initFrameCardTest();
    }
    catch (Exception $e){
        $link = ['url' => 'error_frame.php?' . http_build_query(['error' => $e->getMessage()])];
    }
?>
<div style="margin:20px 0; text-align:center;">
	<b style="font-weight:bold;font-size:20px;display:block;margin-bottom:6px">בדיקת תקינות כרטיס אשראי</b>
	<div>* הסכום יוחזר לבעל הכרטיס תוך 3 ימי עסקים</div>
</div>
<div class="">
    <iframe src="<?=$link['url']?>" class="ccFrame" ></iframe>
</div>
<style>

.ccFrame {width:100%;height:1100px}

@media (max-width:680px){
	.ccFrame{height:1360px;}
}
</style>
