<?php
    include_once "bin/system.php";
/*if($_SESSION['siteID']){
    udb::query("UPDATE `sites_users` SET `lastLogout` = NOW() WHERE `siteID` = ".$_SESSION['siteID']);
}*/

    session_destroy();
    
?>
<script>
localStorage.removeItem("username");
localStorage.removeItem("password");
  window.location.href = "/cms/";
</script>