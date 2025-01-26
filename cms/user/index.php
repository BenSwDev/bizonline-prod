<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

include "../classes/class.PriceCache.php";

$menu = include "menu_user.php";
$position=1;
if($_SESSION['permission']==10 || $_SESSION['permission']==100){
    $date = date("w")==4 || date("w")==5 ? date("Y-m-d") : date("Y-m-d", strtotime('next thursday'));
    $nights=date("w")==5 ? "1" : "2";
    $nextdate=date('Y-m-d', strtotime($date." +".$nights." days"));



    $que="SELECT * FROM sitesPriceCache WHERE siteID=".$site['siteID']." AND cacheType=1";
    $freeArr=udb::single_row($que);

    $thisTime = date("H:i");

    if($thisTime >="10:00" && $thisTime <="18:00"){
        $time = "תוכלו לקפיץ שוב בין השעות 02:00 - 18:00";
    } else if( $thisTime >="02:00" && $thisTime <="10:00"){
        $time = "תוכלו לקפיץ שוב בין השעות 18:00 - 10:00";
    } else {
        $time = "תוכלו להקפיץ שוב בין השעות 10:00 - 02:00";
    }

    $que="SELECT * FROM sitesJumps WHERE siteID='".$site['siteID']."' ";
    $siteJumps=udb::key_row($que, "period");

    $que="SELECT periodID, dateFrom, dateTo, periodName FROM sitesPeriods WHERE siteID=0 AND ('".date("Y-m-d")."' BETWEEN dateFromShow AND dateToShow) ";
    $sitePeriods=udb::key_row($que, "periodID");

    ?>

    <div class="userTabs">
        <?php foreach($menu as $men){ ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>
    <div class="boxesUpdate">
        <div class="period">
            <div class="info">
                <div class="periodTitle">פנוי בסופ"ש הקרוב</div>
                <div class="periodDates"><?=date("d.m.Y", strtotime($date))." - ".date("d.m.Y", strtotime($nextdate))?></div>
                <?php if($freeArr){ ?>
				<div class="periodValidity" id="periodValidity-2"><?=$site['jumpActive']==1?$time:"תכלו להקפיץ כעת"?></div>
				<?php } else { ?>
				<div class="periodValidity" id="periodValidity-2"></div>
                <?php } ?>
            </div>
            <div class="buttons">
                <div id="spPeriodFree-2" data-period="-2" class="free<?=$site['freeInWeekend']==1?" active":""?>"><span ><?=($site['freeInWeekend']==1?'בוצע':"הניפו דגל")?></span></div>
                <?php if(!$freeArr){ ?>
                    <div class="nofree"><span>תפוס</span></div>
                <?php } else { ?>

                    <div id="spPeriodJump-2" data-period="-2" class="jump<?=$site['jumpActive']==1?" active":""?>"><span ><?=$site['jumpActive']==1?"ההקפצה בוצעה":"הקפיצו כעת"?></span></div>
                <?php } ?>
            </div>
        </div>
        <?php
            $tomorrowPrices = PriceCache::getPriceData(date("d.m.Y", strtotime("+1 day")), 1, 1, $site['siteID'], $sprm);
            $friday = date("w")!="5" ? date("d.m.Y", strtotime("next friday")) : date("d.m.Y") ;
            $fridayOneNightPrices = PriceCache::getPriceData($friday, 1, 1, $site['siteID'], $sprm);



        ?>
        <div class="period">
            <div class="info">
                <div class="periodTitle">פנוי למחר</div>
                <div class="periodDates"><?=date("d.m.Y", strtotime("+1 day"))?></div>
                <div class="periodValidity" id="periodValidity-1"><?=$siteJumps[-1]['active']==1?$time:"תכלו להקפיץ כעת"?></div>
            </div>
            <div class="buttons">
                <div id="spPeriodFree-1" data-period="-1" class="free<?=$siteJumps[-1]['free']==1?" active":""?>"><span><?=($siteJumps[-1]['free']==1?'בוצע':"הניפו דגל")?></span></div>
                <?php if(!$tomorrowPrices){ ?>
                    <div class="nofree"><span>תפוס</span></div>
                <?php } else { ?>
                <div id="spPeriodJump-1" data-period="-1" class="jump<?=$siteJumps[-1]['active']==1?" active":""?>"><span><?=$siteJumps[-1]['active']==1?"ההקפצה בוצעה":"הקפיצו כעת"?></span></div>
                <?php } ?>
            </div>
        </div>
        <div class="period">
            <div class="info">
                <div class="periodTitle" >פנוי ללילה אחד בשישי</div>
                <div class="periodDates"><?=date("w")!="5" ? date("d.m.Y", strtotime("next friday")) : date("d.m.Y")?></div>
                <div class="periodValidity" id="periodValidity-3"><?=$siteJumps[-3]['active']==1?$time:"תכלו להקפיץ כעת"?></div>
            </div>
            <div class="buttons">
                <div id="spPeriodFree-3" data-period="-3" class="free<?=$siteJumps[-3]['free']==1?" active":""?>"><span><?=($siteJumps[-3]['free']==1?'בוצע':"הניפו דגל")?></span></div>
                <?php if(!$fridayOneNightPrices){ ?>
                    <div class="nofree"><span>תפוס</span></div>
                <?php } else { ?>
                <div id="spPeriodJump-3" data-period="-3" class="jump<?=$siteJumps[-3]['active']==1?" active":""?>"><span><?=$siteJumps[-3]['active']==1?"ההקפצה בוצעה":"הקפיצו כעת"?></span></div>
                <?php } ?>
            </div>
        </div>
        <?php
            if($sitePeriods) {
                foreach ($sitePeriods as $period) { ?>
                    <div class="period">
                        <div class="info">
                            <div class="periodTitle"><?= $period['periodName'] ?></div>
                            <div
                                class="periodDates"><?= date("d.m.Y", strtotime($period['dateFrom'])) . " - " . date("d.m.Y", strtotime($period['dateTo'])) ?></div>
                            <?php if ($freeArr) { ?>
                                <div class="periodValidity" id="periodValidity<?= $period['periodID'] ?>"><?= $siteJumps[$period['periodID']]['active'] == 1 ? $time : "תכלו להקפיץ כעת" ?></div>
                            <?php } else { ?>
                                <div class="periodValidity" id="periodValidity<?= $period['periodID'] ?>"></div>
                            <?php } ?>
                        </div>
                        <div class="buttons">
                            <div id="spPeriodFree<?= $period['periodID'] ?>" data-period="<?= $period['periodID'] ?>" class="free<?= $siteJumps[$period['periodID']]['free'] == 1 ? " active" : "" ?>">
                                <span><?= ($siteJumps[$period['periodID']]['free'] == 1 ? 'בוצע' : "הניפו דגל") ?></span>
                            </div>
                            <div id="spPeriodJump<?= $period['periodID'] ?>" data-period="<?= $period['periodID'] ?>" class="jump<?= $siteJumps[$period['periodID']]['active'] == 1 ? " active" : "" ?>">
                                <span><?= $siteJumps[$period['periodID']]['active'] == 1 ? "ההקפצה בוצעה" : "הקפיצו כעת" ?></span>
                            </div>
                        </div>
                    </div>
                <?php }
            } ?>
    </div>
    <?=getFixedButtons()?>
    
    <script>
        $(document).ready(function(){
            $(".free").click(function(){
                var period = $(this).data("period");

                var data = {siteID:<?=$site['siteID']?>, period:period};
                $.ajax({
                    url: 'http://'+window.location.hostname+'/cms/user/js_updateFree.php',
                    type: 'POST',
                    data: data,
                    async: false,
                    dataType: "text",
                    success: function (returndata) {
                        if(returndata=="error"){

                        } else {
                            if(returndata=="בוצע"){
                                $("#spPeriodFree"+period+" > span").html(returndata);
                                $("#spPeriodFree"+period+"").addClass("active");
                            } else {
                                $("#spPeriodFree"+period+" > span").html(returndata);
                                $("#spPeriodFree"+period+"").removeClass("active");
                            }
                        }
                    }
                });
            });
            $(".jump").click(function(){
                var period = $(this).data("period");
                var data = {siteID:<?=$site['siteID']?>, period:period};
                $.ajax({
                    url: 'http://'+window.location.hostname+'/cms/user/js_updateJump.php',
                    type: 'POST',
                    data: data,
                    async: false,
                    dataType: "text",
                    success: function (returndata) {
                        if(returndata=="error"){

                        } else {
                            if(returndata){
                                $("#spPeriodJump"+period+" > span").html(returndata);
                                $("#spPeriodJump"+period+"").addClass("active");
                                $("#periodValidity"+period+"").html("<?=$time?>");
                            }
                        }
                    }
                });
            });
            
        });

        function openCalendar(id){
          $(".loaderUser").show();
            indexTabs++;
            $("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div></div></div>');
            window.setTimeout(function(){
                $(".loaderUser").hide();
            }, 300);
        }
    </script>
<?php } ?>
<?php
include_once "../bin/footer.php";
?>