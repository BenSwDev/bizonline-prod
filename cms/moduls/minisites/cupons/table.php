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
$ptList = UserUtilsNew::$cuponsList;
$cList = UserUtilsNew::$CouponsfullList;
$dbCuponTypes = UserUtilsNew::$dbCuponTypes;
$cuponTypesKeys = [];
foreach ($dbCuponTypes as $item) {
    $cuponTypesKeys[$item['key']] = $item;
}


if(isset($_POST['parent'])) {
    $postData = typemap($_POST, [
        'id' => 'int',
        'parent'    => 'int',
        'shortname'   => 'string',
        'cuponPrice'   => 'int',
        'couponPayed'=> 'int',
        'active'=> 'int'
    ]);

    // main extra data
    $que = [
        'parent'    => $postData['parent'],
        'shortname'    => $postData['shortname'],
        'fullname'    => $postData['shortname'],
        'cuponPrice'  => $postData['cuponPrice'],
        'couponPayed' => $postData['couponPayed'],
        'active'=>$postData['active']
    ];



    if($postData['id'] != 0) {
        udb::update("payTypes" , $que , " id=".$postData['id']);
    }
    else {
        $que['key'] = time();
        udb::insert("payTypes" , $que );
    }
    echo '<script> location.href = "/cms/moduls/minisites/cupons/table.php"; </script>';
    exit;
}
$item = [];
if($_GET['id']) {
    $item = udb::single_row("select * from payTypes where id=".intval($_GET['id']));
}


?>
<div class="pagePop" <?=isset($_GET['id']) ? " style='display: block;' " : "";?>><div class="pagePopCont">
        <section id="mainContainer">
            <div class="editItems">
                <h1>הוספת פריט חדש</h1>
                <form method="POST" id="myform" enctype="multipart/form-data">
                    <div class="frm">
                        <div class="inputLblWrap">
                            <div class="labelTo">כותרת קופון</div>
                            <input type="text" placeholder="כותרת קופון" name="shortname" value="<?=$item['shortname']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סוג קופון</div>
                            <select name="parent">
                                <?
                                foreach ($dbCuponTypes as $l){
                                    $selected = ($item['parent'] == $l['id']) ? " selected " : "";

                                    echo '<option value="'.$l['id'].'" '.$selected.' >'.$l['fullname'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">עלות  רכישה</div>
                            <input type="text" placeholder="עלות  רכישה" name="couponPayed" value="<?=$item['couponPayed']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סכום קופון למימוש</div>
                            <input type="text" placeholder="סכום קופון למימוש" name="cuponPrice" value="<?=$item['cuponPrice']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">פעיל</div>
                            <label class="switch">
                                <input type="checkbox" name="active" value="1" <?=($item['active']==1 || !$item)?"checked":""?>>
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
    <h1>ניהול ספק / קופון / שובר</h1>
    <div style="margin-top: 20px;">
        <input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
    </div>
    <div class="tblMobile">
        <table>
            <thead>
                <tr>
                    <th>כותרת</th>
                    <th>סוג</th>
                    <th>שווי</th>
                    <th>עלות</th>
                </tr>
            </thead>
            <tbody>
            <?
            foreach ($ptList as $k=>$item) {
                foreach ($item as $c) {
                    ?>
                    <tr onclick="location.href = '?id=<?=$c['id']?>'">
                        <td><?= $c['fullname'] ?></td>
                        <td><?= $cuponTypesKeys[$k]['fullname'] ?></td>
                        <td><?= $c['cuponPrice'] ?></td>
                        <td><?= $c['couponPayed'] ?></td>
                    </tr>
                    <?
                }
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
