<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 07/11/2021
 * Time: 15:30
 */
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";
include_once "../../../bin/top.php";


UserUtilsNew::init(null);
//



$arrival = udb::full_list('SELECT * FROM `payTypes` WHERE `parent`=11');
//print_r($arrival);

if(isset($_POST['id'])) {
    $postData = typemap($_POST, [
        'id' => 'int',
        'fullname'    => 'string',
        'shortname'   => 'string',
        'hexColor'   => 'string',
        'letterSign'   => 'string',
        'spa'   => 'int',
        'host'=> 'int',
        'active'=> 'int'
    ]);

    // main extra data
    $que = [
       
        'shortname'    => $postData['shortname'],
        'fullname'    => $postData['fullname'],
        'hexColor'    => $postData['hexColor'],
        'letterSign'    => $postData['letterSign'],
        'parent'    => 11,
        'active'=>$postData['active'],
        'host'=>$postData['host'],
        'spa'=>$postData['spa']
    ];



    if($postData['id'] != 0) {
        udb::update("payTypes" , $que , " id=".$postData['id']);
    }
    else {
        $que['key'] = time();
        udb::insert("payTypes" , $que );

        $que['payTypeKey'] = $que['key'];
        udb::insert("arrivalSources" , $que );
    }
    echo '<script> location.href = "/cms/moduls/minisites/arriveSource/table.php"; </script>';
    exit;
}
$item = [];
if($_GET['id']) {
    $item = udb::single_row("select * from payTypes where id=".intval($_GET['id']));
}


?>
<style>
.sign{width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;border-radius:50%;color:black}
green{color:green}
red{color:red}
</style>
<div class="pagePop" <?=(isset($_GET['id']) || isset($_GET['new'])) ? " style='display: block;' " : "";?>><div class="pagePopCont">
        <section id="mainContainer">
            <div class="editItems">
                <h1><?=isset($_GET['id']) ? "עריכת פריט" : "הוספת פריט חדש";?></h1>
                <form method="POST" id="myform" enctype="multipart/form-data">
                    <div class="frm">
                        <div class="inputLblWrap">
                            <div class="labelTo">כותרת מקור הגעה</div>
                            <input type="text" placeholder="כותרת קופון" name="shortname" value="<?=$item['shortname']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">שם מלא</div>
                            <input type="text" placeholder="כותרת קופון" name="fullname" value="<?=$item['fullname']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">אותיות מייצגות</div>
                            <input type="text" placeholder="" name="letterSign" maxlength="2" value="<?=$item['letterSign']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">צבע</div>
                            <input type="color" placeholder="כותרת קופון" name="hexColor" value="<?=$item['hexColor']?>" />
                        </div>
						<div></div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">פעיל</div>
                            <label class="switch">
                                <input type="checkbox" name="active" value="1" <?=($item['active']==1 || !$item)?"checked":""?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
						<div class="inputLblWrap">
                            <div class="switchTtl">ספא</div>
                            <label class="switch">
                                <input type="checkbox" name="spa" value="1" <?=($item['spa']==1 || !$item)?"checked":""?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
						<div class="inputLblWrap">
                            <div class="switchTtl">מתחמים</div>
                            <label class="switch">
                                <input type="checkbox" name="host" value="1" <?=($item['host']==1 || !$item)?"checked":""?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
						
                        <div class="inputLblWrap">
                            <input type="submit" name="submit" id="submit" class="submit" value="שמור" style="position: absolute;">
                        </div>
                    </div>
                    <input type="hidden" name="id" id="id" value="<?=$_GET['id']?>">
                </form>
            </div>
        </section>
        <div class="tabCloser" onclick="closepop()">x</div>
    </div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול ספקים</h1>
    <div style="margin-top: 20px;">
        <input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location='?new=1'">
    </div>
    <div class="tblMobile">
        <table style="width:auto">
            <thead>
                <tr>
					<th>#</th>
                    <th>שם</th>
                    <th>סימון</th>
                    <th>פעיל</th>
                    <th style="width:50px">ספא</th>
                    <th style="width:50px">מתחמים</th>
                </tr>
            </thead>
            <tbody>
            <?
            foreach ($arrival as $k=>$item) {
                //foreach ($item as $c) {
                    ?>
                    <tr onclick="location.href = '?id=<?=$item['id']?>'">
                        <td><?= $item['id'] ?></td>
						<td><?= $item['fullname'] ?></td>
                        <td><div class="sign" style="background:<?= $item['hexColor']?>"><?= $item['letterSign'] ?></div></td>
						<td><?= $item['active']? "<green>פעיל</green>" : "<red>לא פעיל</red>" ?></td>
						<td><?= $item['spa']? "<green>פעיל</green>" : "<red>לא פעיל</red>" ?></td>
						<td><?= $item['host']? "<green>פעיל</green>" : "<red>לא פעיל</red>" ?></td>
						
                    </tr>
                    <?
               // }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    function openPop(id){
        $("#myform")[0].reset();
        $("#myform input[type='text']").val("");
        $("#id").val("0");
        $(".pagePop").show();
    }
    function closepop(){
        $("#myform")[0].reset();
        $(".pagePop").hide();
    }
</script>
<?php
include_once "../../../bin/footer.php";
?>

