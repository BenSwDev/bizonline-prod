<?php
    include_once "bin/system.php";
if($_SESSION['permission']==10){
	?>
    <script>window.location.href="/cms/user/";</script>
    <?php

} else include_once "bin/top.php";
?>

<?php
    include_once "bin/footer.php";
?>