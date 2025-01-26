<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 17/11/2021
 * Time: 11:01
 */



define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");

include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";

if(isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $result = [];
    $result['status'] = "error";
    switch($_POST['action']) {
        case 'del':
            if($id) {
                udb::query("delete from sites_cupons where id=".$id);
            }
            $result['status'] = "ok";
            break;
        case 'save':
            break;
    }
    echo json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    die;
}
$siteID = intval($_GET['siteID']);
$where = ' 1=1 ';
if($siteID) $where .= " and siteID=".$siteID;
$pageNum = intval($_GET["pageNum"])? intval($_GET["pageNum"]) : 1;
$totalPages=udb::single_value(" SELECT count(*)  FROM `sites_cupons` WHERE ".$where);
$pageTotal = 150;
$totalPages = ceil($totalPages/$pageTotal);
$cupons = udb::full_list("select * from sites_cupons where ".$where . "  LIMIT " . ( ($pageNum-1)*$pageTotal ).",".$pageTotal );

?>
    <style>
        .manageItems span.icon,.signOpt .icon{margin:5px 3px 0 0;height:34px;display:inline-block;cursor:pointer;width:34px;text-align:center;border-radius:5px;overflow:hidden;position:relative}
        .manageItems span.icon>*,.signOpt .icon>*{position:absolute;margin:0;top:0;left:0;bottom:0;right:0;margin:auto}
        .manageItems span.icon.whatsapp>*,.signOpt .icon.whatsapp>*{width:24px;height:24px}
        .manageItems span.mail,.signOpt .mail{background-color:#e89b14}
        .manageItems span.whatsapp,.signOpt .whatsapp{background-color:#64b161}
        .manageItems span.sms,.signOpt .sms{background-color:#ffb400}
        #manageItems div input.datePick {width: 200px;}
        #manageItems div input.datePickDM {width: 200px;}
        .inputLblWrap {margin: 1%;}
        .filter input[type='button'] {
            color: #ffffff;
            width: 10%;
            float: left;
            margin-left: 10px;
            font-weight: bold;
            background: #2FC2EB;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            cursor: pointer;
        }
    </style>


    <div class="pagePop"><div class="pagePopCont"></div></div>
    <div class="manageItems" id="manageItems">
        <h1><?=Dictionary::translate("קופוני הנחה")?></h1>
        <?php minisite_domainTabs($domainID,"2")?>
        <?=showTopTabs(0)?>
        <div class="numbers">
            <?for($i=1;$i<=$totalPages;$i++){?>
                <input class="pageNum <?=$pageNum==$i? "active" : ""?>" value="<?=$i?>" onclick="window.location.href='?pageNum=<?=$i?>'">
            <?}?>
        </div>
        <button class=" submit" onclick="openPopCpn(0)" style="padding:5px;margin:0 7px;">הוסף קופון</button>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th style="width:200px">כותרת</th>
                <th style="width:200px">קוד קופון</th>
                <th>מומשו</th>
                <th style="width:140px; text-align: center;">הנחה</th>
                <th style="width:140px; text-align: center;">סוג הנחה</th>
                <th style="text-align: center;">תוקף</th>
                <th><span style="margin-left:20px;">פעולות</span></th>

            </tr>
            </thead>
            <tbody>
            <?php
            foreach($cupons as $cupon) {
//                $used = udb::single_value("select count(cuponNumber) from pack_orders where cuponNumber='".$cupon['cCode']."' and ordersID != 0");
//                $used += udb::single_value("select count(cuponNumber) from pack_purchases where cuponNumber='".$cupon['cCode']."' and ordersID != 0");
                $used = 0;

                ?>
                <tr>
                    <td><?=$cupon['id']?></td>
                    <td><?=$cupon['title']?></td>
                    <td><?=$cupon['cCode']?></td>
                    <td>(<?=$used?>)</td>
                    <td style="direction: ltr;text-align: center;"><?=$cupon['amount']?></td>
                    <td style="direction: ltr;text-align: center;"><?=$cupon['cType'] == 1 ? 'סכום' : 'אחוזים';?></td>
                    <td><?=date("d/m/Y", strtotime($cupon['expire']))?></td>
                    <td>
                        <a data-id="<?=$cupon['id']?>" class="del-item submit" style="padding:5px;margin:0 7px;">מחק</a>
                        <a data-id="<?=$cupon['id']?>" class="edit-item submit" onclick="openPopCpn(<?=$cupon['id']?>)" style="padding:5px;margin:0 7px;">ערוך</a>
                    </td>

                </tr>
                <?
            }?>
            </tbody>
        </table>
    </div>
    <script>

        $( ".datePick" ).datepicker({
            viewMode: "months",
            dateFormat: 'dd/mm/yy',
            changeYear: true
        });




        function openPopCpn(pageID){
            $(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'&siteID=<?=$siteID?>"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
            $(".pagePop").show();
        }
        function closeTab(){
            $(".pagePopCont").html('');
            $(".pagePop").hide();
        }
        function closeTabAndReload(){
            $(".pagePopCont").html('');
            $(".pagePop").hide();
            window.location.reload();
        }
        $(document).ready(function(){

            $(".del-item").on("click",function(){
                let id = $(this).data("id");
                swal(
                    {
                        type: "question",
                        title: "האם אתם בטוחים שברצונכם למחוק את הפריט?",
                        showCancelButton: true,
                        confirmButtonText: 'כן! מחק',
                        cancelButtonText: 'לא! בטל'
                    }
                ).then(function(answer){
                    if(answer){
                        $.ajax({
                            url: "table.php",
                            method: "post",
                            data: {id: id , action: 'del'},
                            success: function (response) {
                                try{
                                    var res = JSON.parse(response);
                                } catch (e) {
                                    var res = response;
                                }
                                if(res.status == 'ok') {
                                    swal({
                                        type: "success",
                                        title: "הפריט נמחק בהצלחה"
                                    }).then(function(){
                                        window.location.reload();
                                    });
                                }
                                else {
                                    swal({
                                        type: "error",
                                        title: "פעולה נכשלה"
                                    }).then(function(){

                                    });
                                }

                            }
                        },'json');
                    }
                },function(answer){

                })
            });

        });
    </script>
<?
include_once "../../../bin/footer.php";
