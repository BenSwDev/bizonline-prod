<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=4;

?>
    <div class="userTabs">
        <?php foreach($menu as $men){ ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>
    <a class="supportPic" href="tel:04-9825135"></a>
    <?=getFixedButtons()?>
    <script>
        function openCalendar(id){
            $(".loaderUser").show();
            indexTabs++;
            $("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div></div></div>');
            window.setTimeout(function(){
                $(".loaderUser").hide();
            }, 300);
        }
    </script>

<?php
include_once "../bin/footer.php";
?>