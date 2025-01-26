<?php
include_once "../bin/system.php";
include_once "../bin/top.php";



?>
    <div class="popRoom">
        <div class="popRoomContent"></div>
    </div>
    <div class="manageItems" id="manageItems">
        <h1>תקופות כלליות</h1>


        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>שם הצימר</th>
                <th>פעילות אחרונה</th>
                <th>לוגין אחרון</th>
                <th>לוגות אחרון</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($sites as $site){ ?>
                <tr id="<?=$site['contactID']?>" tab-id="">
                    <td><?=($site['siteID'])?></td>
                    <td ><?=outDb($site['TITLE'])?></td>
                    <td style="direction:ltr;text-align:center;"><?=($site['lastCheck']!="0000-00-00 00:00:00" ? date("d.m.Y H:i:s", strtotime($site['lastCheck'])) : "")?></td>
                    <td style="direction:ltr;text-align:center;"><?=($site['lastLogin']!="0000-00-00 00:00:00" ? date("d.m.Y H:i:s", strtotime($site['lastLogin'])) : "" )?></td>
                    <td style="direction:ltr;text-align:center;"><?=($site['lastLogout']!="0000-00-00 00:00:00" ? date("d.m.Y H:i:s", strtotime($site['lastLogout'])) : "" )?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <style>
        .manageItems table > thead > tr > th:nth-child(2){width:200px;}
        .manageItems table > thead > tr > th:nth-child(3){width:10%;text-align:center;}
        .manageItems table > thead > tr > th:nth-child(4){width:10%;text-align:center;}
        .manageItems table > thead > tr > th:nth-child(5){width:10%;text-align:center;}
    </style>
    <input type="hidden" id="orderResult" name="orderResult" value="">
    <script>


        function openPnia(contactID, siteID){
            $(".popRoomContent").html('<iframe id="frame_'+contactID+'_'+siteID+'" frameborder=0 src="/cms/sites/minicontact.php?contactID='+contactID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+contactID+'_'+siteID+'\')">x</div>');
            $(".popRoom").show();
        }
        function closeTab(id){
            $(".popRoomContent").html('');
            $(".popRoom").hide();
        }
    </script>
<?php
include_once "../bin/footer.php";
?>