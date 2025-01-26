<?
include_once "../bin/system.php";

$siteID = intval($_POST['siteID']);
$period = intval($_POST['period']);
$que="SELECT siteID, jumpActive, jumpTime FROM sites WHERE siteID=".$siteID." ";
$site = udb::single_row($que);


$que="SELECT * FROM sitesJumps WHERE siteID=".$siteID." AND period='".$period."' ";
$periodSite = udb::single_row($que);

if($period!="-2"){
    if(!$periodSite){
        $cp=Array();
        $cp['active']=1;
        $cp['free']=0;
        $cp['time']=date("Y-m-d H:i:s");
        $cp['siteID']=$siteID;
        $cp['period']=$period;
        udb::insert("sitesJumps", $cp);
        echo "ההקפצה בוצעה";
    }  else {
        if($periodSite['active']!=1){
            $cp=Array();
            $cp['active']=1;
            $cp['time']=date("Y-m-d H:i:s");
            udb::update("sitesJumps", $cp, "siteID=".$siteID." AND period='".$period."'");
            echo "ההקפצה בוצעה";
        }

    }
} else {
    if($site['jumpActive']!=1){
        $cp=Array();
        $cp['jumpActive']=1;
        $cp['jumpTime']=date("Y-m-d H:i:s");
        udb::update("sites", $cp, "siteID=".$siteID."");
        echo "ההקפצה בוצעה";
    }
}