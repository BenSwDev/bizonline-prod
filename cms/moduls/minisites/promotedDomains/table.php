<?php
include_once "../../../bin/system.php";
$siteID=intval($_GET['sid']);
$domainID = intval($_GET['domainID']);
if(intval($_GET['activate'])){
    udb::query("update  `promotedDomains` set active= !active where siteID=".$siteID." and domainID=".$domainID);
    exit;
}
if($_GET['getsites']) {
    $sites = udb::full_list("select sites.siteID,sites.siteName from sites left join sites_domains using (siteID) where sites_domains.active=1 group by siteID order by siteName ASC");
    foreach ($sites as $site) {
        echo '<option value="'.$site['siteID'].'">'.$site['siteName'].'</option>';
    }
    exit;
}
include_once "../../../bin/top.php";


$langID   = LangList::active();

$domains = udb::key_row("select * from domains where domainID < 100","domainID");
if(intval($_GET['del'])){
    udb::query("DELETE FROM `promotedDomains` WHERE siteID=".$siteID." and domainID=".$domainID);


}



if ('POST' == $_SERVER['REQUEST_METHOD']) {

    $data =  [
        'siteID' => intval($_POST['siteID']),
        'domainID' => intval($_POST['domainID']),
        'active' => 1
        ];

    udb::insert("promotedDomains",$data,true);

}


$sql = "SELECT promotedDomains.*,sites.siteName,domains.domainName from promotedDomains left join sites using (siteID) left join domains using (domainID) ";
$promoteds  = udb::full_list($sql);


?>
<div class="editItems">
    <div class="manageItems" id="manageItems">
        <h1>מקומות חמים</h1>
        <div style="display: inline-block;max-width: 320px;margin: 20px auto;">
            <form method="post" action="table.php">
                <div><label for="siteID">בחירת דומיין</label>
                    <select name="domainID" id="domainID">
                        <?
                        foreach ($domains as $site) {
                            ?><option value="<?=$site['domainID']?>"><?=$site['domainName']?></option><?
                        }
                        ?>
                    </select></div>
                <div><label for="siteID">בחירת אתר</label>
            <select name="siteID" id="siteID">
                <?
                    $sites = udb::full_list("select siteID,siteName from sites");
                    foreach ($sites as $site) {
                        ?><?
                    }
                ?>
            </select></div>

                <div style="margin:20px;"><input type="submit" value="הוסף" id="addP"></div>
            </form>
        </div>
        <table>
            <thead>
            <tr>
                <th>מתחם</th>
                <th>דומיין</th>
                <th width="40">מוצג</th>
                <td>#</td>
            </tr>
            </thead>
            <tbody id="sortRow">
            <?php
            if (count($promoteds)){
                foreach($promoteds as $extra){
                    ?>
                    <tr>
                        <td><?=$extra['siteName']?></td>
                        <td ><?=$extra['domainName']?></td>
                        <td>
                            <div class="inputLblWrap">
                                <label class="switch">
                                    <input type="checkbox" class="updatepromoted" data-sid="<?=$extra['siteID']?>" data-did="<?=$extra['domainID']?>" name="activeCal<?=$extra['siteID'].$extra['domainID']?>" value="1" <?=($extra['active'] ? 'checked="checked"' : '')?> >
                                    <span class="slider round"></span>
                                </label>
                            </div></td>
                        <td><div onclick="if(confirm('האם אתה בטוח רוצה למחוק את התוספת?')){location.href='table.php?del=1&sid=<?=$extra['siteID']?>&domainID=<?=$extra['domainID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    $(".updatepromoted").on("change",function () {
        var siteID = $(this).data("sid");
        var domainID = $(this).data("did");
        $.get("table.php?activate=1&sid=" + siteID + "&domainID=" + domainID,function (re) {

        })
    });

    $("#domainID").on("change",function () {
        var v = $(this).val();
        $.get('table.php?getsites=1&domainID=' + v , function (resp) {
            $("#siteID options").remove();
            $('#siteID').append(resp);
        })
    });
</script>
<?php
include_once "../../../bin/footer.php";
?>
