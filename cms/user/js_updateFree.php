<?
include_once "../bin/system.php";

$period = inDB($_POST['period']);

$siteID = intval($_POST['siteID']);

$que="SELECT siteID, freeInWeekend FROM sites WHERE siteID=".$siteID." ";
$site = udb::single_row($que);

$que="SELECT * FROM sitesJumps WHERE siteID=".$siteID." AND period='".$period."' ";
$periodSite = udb::single_row($que);

if($period!="-2"){
    if(!$periodSite){
        $cp=Array();
        $cp['free']=1;
        $cp['siteID']=$siteID;
        $cp['period']=$period;
        udb::insert("sitesJumps", $cp);
        echo "בוצע";
    }  else {
        if($periodSite['free']==1){
            $cp=Array();
            $cp['free']=0;
            udb::update("sitesJumps", $cp, "siteID=".$siteID." AND period='".$period."' ");
            echo "הניפו דגל";
        } else {
            $cp=Array();
            $cp['free']=1;
            udb::update("sitesJumps", $cp, "siteID=".$siteID." AND period='".$period."'");
            echo "בוצע";
        }

    }
} else {
    if($site['freeInWeekend']==1){
        $cp=Array();
        $cp['freeInWeekend']=0;
        udb::update("sites", $cp, "siteID=".$siteID."");
        echo "הניפו דגל";
    } else {
        $cp=Array();
        $cp['freeInWeekend']=1;
        udb::update("sites", $cp, "siteID=".$siteID."");
        echo "בוצע";
    }
}
