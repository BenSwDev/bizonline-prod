<?php
include_once "../../../bin/system.php";
if($_GET['tab']){
    include_once "../../../bin/top_frame.php";
    include_once "../mainTopTabs.php";
    //include_once "innerMenu.php";
}else{
    include_once "../../../bin/top.php";
}
include_once "../../../_globalFunction.php";
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
if ('POST' == $_SERVER['REQUEST_METHOD']){
    try{
        $data = typemap($_POST, [
            'bankName'  =>  'string',
            'bankNumber'  =>  'string',
            'bankBranch'  =>  'string',
            'bankAccount'  =>  'string',
            'bankAcoountOwner'  =>  'string',
            'giftCardCommission'  =>  'float',
            'onlineCommission'  =>  'float',
            'vvouchers'  =>  'int'
        ]);

        $cp = [];
        $cp['bankName'] = $data['bankName'];
        $cp['bankNumber'] = $data['bankNumber'];
        $cp['bankBranch'] = $data['bankBranch'];
        $cp['bankAccount'] = $data['bankAccount'];
        $cp['bankAcoountOwner'] = $data['bankAcoountOwner'];
        $cp['giftCardCommission'] = $data['giftCardCommission'];
        $cp['onlineCommission'] = $data['onlineCommission'];
        $cp['vvouchers'] = $data['vvouchers'];
        udb::update("sites",$cp," siteID=".$siteID);
        if(intval($data['vvouchers'])) {
            $has = udb::full_list("SELECT * FROM `giftCards` where siteID=".$siteID . " and giftType=2 and deleted=0");
            if(!$has) {
                $que = [];
                $que['siteID'] = $siteID;
                $que['giftType'] = 2;
                $que['title'] = 'שובר גיפט קארד - שובר בסכום לבחירתך';
                $que['daysValid'] = 12;
                $que['showOrder'] = 1;
                udb::insert("giftCards",$que);
            }
        }

        $termData = typemap($_POST, [
            'masof_sync'    => 'int',
            'masof_active'  => 'int',
            'masof_type'    => 'string',
            'masof_number'  => 'string',
            'masof_key'     => 'string',
            'masof_pwd'     => 'string',
            'masof_tokens'  => 'int',
            'masof_invoice' => ['int'],
            'masof_swipe'   => 'int',
            'masof_noVAT'   => 'int',
            'masof_info_text' => 'text',
        ]);

        $termExist = TerminalModel::find_by_target($siteID, 'vouchers') ?: new TerminalModel;

        if ($termData['masof_sync']){
            $termExist->set([
                'siteID'    => $siteID,
                'active'    => $termData['masof_active'],
                'mode'      => TerminalModel::MODE_SYNC,        // synced terminal
                'info_text' => $termData['masof_info_text']
            ]);

            $termExist->id() ? $termExist->save() : $termExist->create();

            TerminalModel::sync_terminals($siteID, $termExist->id());
        }
        elseif ($termData['masof_type'] && $termData['masof_number']){
            $termExist->set([
                'siteID'       => $siteID,
                'active'       => $termData['masof_active'],
                'mode'         => 0,                              // not synced
                'masof_type'   => $termData['masof_type'],
                'masof_number' => $termData['masof_number'],
                'masof_key'    => $termData['masof_key'],
                'masof_pwd'    => $termData['masof_pwd'],
                'invoice'      => $termData['masof_invoice'],
                'flag_tokens'  => $termData['masof_tokens'],
                'flag_swipe'   => $termData['masof_swipe'],
                'flag_noVAT'   => $termData['masof_noVAT'],
                'info_text'    => $termData['masof_info_text']
            ]);

            $termExist->id() ? $termExist->save() : $termExist->create();
        }
        elseif ($termExist->id())
            $termExist->delete();

    } catch (LocalException $e){
        // show error
        $errorMsg = $e->getMessage();
    }
}
$siteData = udb::single_row("select * from sites where siteID = " . $siteID);
$siteName = $_GET['siteName'];
?>
<style>
    #invoicewrap:not(.checked) ~ .invplus{display:none}
    input:checked:disabled + .slider {
        background-color: #aad9ff;
    }
</style>
<div class="editItems">
<div class="siteMainTitle"><?=$_GET['siteName'];?></div>
<div class="frameContent">
    <form method="post" enctype="multipart/form-data" >
        <?php minisite_domainTabs($domainID,"2")?>
        <?=showTopTabs()?>
        <div class="mainSectionWrapper open">
            <div class="sectionName">חשבון בנק</div>
            <div class="inSectionWrap">
                                    <div class="inputLblWrap">
                    <div class="labelTo">שם בנק</div>
                    <input type="text" placeholder="שם הבנק" name="bankName" value="<?=$siteData['bankName']?>">
                </div>

                <div class="inputLblWrap">
                    <div class="labelTo">מספר בנק</div>
                    <input type="text" placeholder="מספר בנק" name="bankNumber" value="<?=$siteData['bankNumber']?>">
                </div>

                <div class="inputLblWrap">
                    <div class="labelTo">מספר סניף</div>
                    <input type="text" placeholder="מספר סניף" name="bankBranch" value="<?=$siteData['bankBranch']?>">
                </div>

                <div class="inputLblWrap">
                    <div class="labelTo">מספר חשבון</div>
                    <input type="text" placeholder="מספר חשבון" name="bankAccount" value="<?=$siteData['bankAccount']?>">
                </div>

                <div class="inputLblWrap">
                    <div class="labelTo">שם בעל החשבון</div>
                    <input type="text" placeholder="שם הבעל החשבון" name="bankAcoountOwner" value="<?=$siteData['bankAcoountOwner']?>">
                </div>
            </div>
        </div>


        <div class="mainSectionWrapper">
            <div class="sectionName">עמלות</div>
            <div class="inputLblWrap">
                <div class="labelTo">עמלת גיפטקארד ב %</div>
                <input type="text" placeholder='עמלת גיפטקארד' name="giftCardCommission" value="<?=$siteData['giftCardCommission']?>" />
            </div>

            <div class="inputLblWrap">
                <div class="switchTtl">אשר Vouchers</div>
                <label class="switch">
                  <input type="checkbox" name="vvouchers" value="1" <?=($siteData['vvouchers']==1)?"checked":""?>>
                  <span class="slider round"></span>
                </label>
            </div>
            <div></div>

            <div class="inputLblWrap">
                <div class="labelTo">עמלת אונליין ב %</div>
                <input type="text" placeholder='עמלת אונליין' name="onlineCommission" value="<?=$siteData['onlineCommission']?>" />
            </div>

            <input type="submit" value="שמור" class="submit">
        </div>

<?php
    $terminal = TerminalModel::find_by_target($siteID, 'vouchers') ?: new TerminalModel;
    $synced = ($terminal->mode & TerminalModel::MODE_SYNC) ? 'disabled' : '';
?>
        <div class="mainSectionWrapper">
            <div class="sectionName">מסוף</div>
            <div class="inSectionWrap">
                <div class="inputLblWrap">
                    <div class="switchTtl">מסוף פעיל</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_active" value="1" <?=($terminal->active ? 'checked="checked"' : '')?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap">
                    <div class="switchTtl">מסונכרן עם המסוף הראשי</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_sync" id="masof_sync" value="1" <?=($synced ? 'checked="checked"' : '')?> />
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="inSectionWrap syncable">
                <div class="inputLblWrap">
                    <div class="labelTo">סוג מסוף</div>
                    <select name="masof_type" title="סוג מסוף" <?=$synced?>>
                        <option value="">- - - - - - - - - - - - - - -</option>
                        <option value="cardcom" <?=($terminal->masof_type == 'cardcom' ? 'selected' : '')?>>CardCom</option>
                    </select>
                </div>
            </div>
            <div style="margin:-30px 36px 0 0; font-size:smaller">* במידה והלקוח הוא לקוח של מקס (לא משנה אם גוייס דרכנו או לא) יש לבחור max</div>
            <div class="inSectionWrap syncable">
                <div class="inputLblWrap">
                    <div class="labelTo">מספר מסוף</div>
                    <input type="text" placeholder="מספר מסוף" name="masof_number" value="<?=$terminal->masof_number?>" <?=$synced?> />
                </div>
                <div class="inputLblWrap">
                    <div class="labelTo">מפתח מסוף</div>
                    <input type="text" placeholder="מפתח מסוף" name="masof_key" value="<?=$terminal->masof_key?>" <?=$synced?> />
                </div>
                <div class="inputLblWrap">
                    <div class="labelTo">סיסמת ביטולים</div>
                    <input type="text" placeholder="סיסמת ביטולים" name="masof_pwd" value="<?=$terminal->masof_pwd?>" <?=$synced?> />
                </div>
                <div class="inputLblWrap">
                    <div class="switchTtl">כרטיס לערבון</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_tokens" value="1" <?=$terminal->flag_tokens ? 'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap <?=$terminal->hasInvoice()?'checked' :""?>" id='invoicewrap'>
                    <div class="switchTtl">חשבוניות</div>
                    <label class="switch">
                        <input type="checkbox" onchange="if($(this).is(':checked')){$('#invoicewrap').addClass('checked')}else{$('#invoicewrap').removeClass('checked')}" name="masof_invoice[]" value="<?=TerminalModel::INVOICE_ACTIVE?>" <?=$terminal->hasInvoice()?'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap">
                    <div class="switchTtl">CardReader</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_swipe" value="1" <?=$terminal->flag_swipe?'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap invplus">
                    <div class="switchTtl">פירוט בחשבונית?</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_invoice[]" value="<?=TerminalModel::INVOICE_FULL_DESC?>" <?=$terminal->hasInvoice(TerminalModel::INVOICE_FULL_DESC)?'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap invplus">
                    <div class="switchTtl">פתיחת פופ חשבונית אוטומטית?</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_invoice[]" value="<?=TerminalModel::INVOICE_AUTO_OPEN?>" <?=$terminal->hasInvoice(TerminalModel::INVOICE_AUTO_OPEN)?'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap invplus">
                    <div class="switchTtl">חשבוניות בלי מע"מ</div>
                    <label class="switch">
                        <input type="checkbox" name="masof_noVAT" value="1" <?=$terminal->flag_noVAT?'checked="checked"':""?> <?=$synced?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap">
                    <div class="labelTo">טקסט מידע לדו"ח חשבוניות</div>
                    <textarea  placeholder='טקסט מידע לדו"ח חשבוניות' name="masof_info_text"  ><?=$terminal->info_text?></textarea>
                </div>
            </div>
        </div>

		</div>
    </form>
</div>
<script>
$(function(){
    $('#masof_sync').on('click', function(){
        $('.syncable').find('input, select').prop('disabled', this.checked);
    });
});
</script>
</div>
<?

if (!$_GET["tab"]) include_once "../../../bin/footer.php";
?>

