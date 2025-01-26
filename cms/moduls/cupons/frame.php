<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 17/11/2021
 * Time: 12:54
 */

include_once "../../bin/system.php";
$isframe = true;
include_once "../../bin/top.php";
$inputs = [];

$inputs['title'] = array("type"=>'text' , 'title' => 'כותרת');
$inputs['cCode'] = array("type"=>'text' , 'title' => 'קוד קופון');
$inputs['amount'] = array("type"=>'text' , 'title' => 'סכום');
$inputs['cType'] = array("type"=>'select' , 'title' => 'סוג קופון' ,  'list' => array('1'=>'סכום' , '2'=> 'אחוזים' ));
$inputs['expire'] = array("type"=>'text' , 'class'=>' class="datePick" ' , 'title' => 'תוקף');
$inputs['cDesc'] = array("type"=>'textarea' , 'title' => 'תאור פנימי');
$inputs['maxDiscount'] = array("type"=>'text' , 'title' => 'סכום מקסימלי להנחה');
if(isset($_POST['pageID'])) {
    $id = intval($_POST['pageID']);
    $double = udb::single_value("select id from sites_cupons where id!=".$id." and cCode='".$_POST['cCode']."'");
    if($double) {
        echo '<script> alert("קוד קופון לא יכול להופיע פעמיים במערכת");</script>';
    }
    else {
        $que = [];
        foreach ($inputs as $k=>$item) {
            if($item['class']) {
                if($_POST[$k]) {
                    $kdate = explode("/",$_POST[$k]);
                    $kdate = array_reverse($kdate);
                    $kdate = implode("-",$kdate);
                    $kdate = date("Y-m-d" , strtotime($kdate));
                    if($kdate  != '1970-01-01'){
                        $que[$k] = $kdate;
                    }
                    else {
                        $que[$k] = null;
                    }
                }
                else {
                    $que[$k] = null;
                }
            }
            else {
                $que[$k] = $_POST[$k];
            }
        }
        if($id ==0) {
            udb::insert("sites_cupons",$que);
        }
        else {
            udb::update("sites_cupons",$que , " id=".$id);
        }
        echo '<script> window.parent.closeTabAndReload();</script>';
    }

    die;
}

$id = intval($_GET['pageID']);
if($id) {
    $page = udb::single_row("select * from sites_cupons where id=".$id);
    foreach ($inputs as $k=>$item) {
        if($item['class']) {
            $inputs[$k]['value'] = $page[$k] ? date("d/m/Y" , strtotime($page[$k])) : '';
        }
        else {
            $inputs[$k]['value'] = $page[$k];
        }
    }
}




?>
    <section id="mainContainer">
        <div class="editItems">
            <form id="myform" method="post" enctype="multipart/form-data">
                <div class="frm">
                    <input type="hidden" name="pageID" value="<?=$id?>">
                    <?
                    foreach ($inputs as $k=>$item) {
                        switch($item['type']) {
                            case 'text':
                                ?>
                                <div class="inputLblWrap">
                                    <div class="labelTo"><?=$item['title']?></div>
                                    <input type="text" placeholder="<?=$item['title']?>" <?=$item['class'] ? $item['class'] :''; ?> name="<?=$k?>" value="<?=$item['value'] ? $item['value'] :''; ?>">
                                </div>
                                <?
                                break;
                            case 'textarea':
                                ?>
                                <div class="inputLblWrap">
                                    <div class="labelTo"><?=$item['title']?></div>
                                    <textarea placeholder="<?=$item['title']?>" <?=$item['class'] ? $item['class'] :''; ?> name="<?=$k?>"><?=$item['value'] ? $item['value'] :''; ?></textarea>
                                </div>
                                <?
                                break;
                            case 'checkbox':
                                ?>
                                <div class="inputLblWrap">
                                    <div class="switchTtl"><?=$item['title']?></div>
                                    <label class="switch">
                                        <input type="checkbox" name="<?=$k?>" value="1" <?=$item['value'] ? ' checked ' :''; ?>   />
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <?
                                break;
                            case 'select':
                                ?>
                                <div class="inputLblWrap">
                                    <div class="labelTo"><?=$item['title']?></div>
                                    <select name="<?=$k?>" id="<?=$k?>" >
                                        <?
                                        foreach ($item['list'] as $key=>$value) {
                                            $selected = (intval($item['value']) == intval($key)) ? ' selected ' : '';
                                            echo '<option value="'.$key.'" '.$selected.' >'.$value.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?
                                break;
                        }
                    }
                    ?>

                </div>
                <div style="clear:both;"></div>
                <div class="section sub">
                    <div class="inptLine">
                        <input type="submit" value="שמור" class="submit">
                    </div>
                </div>
            </form>
        </div>
    </section>
    <script>
        $( ".datePick" ).datepicker({
            viewMode: "months",
            dateFormat: 'dd/mm/yy',
            changeYear: false
        });


    </script>
<?
include_once "../../bin/footer.php";
