<!doctype html>
<html lang="he" class="no-js">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<link href="https://fonts.googleapis.com/css?family=Rubik:400,700" rel="stylesheet">

	<script type="text/javascript">
		var langID = 1;
		<?php if($_GET['langID']!=1) { ?>
			langID = <?=intval($_GET['langID'])?>;
		<?php } ?>
	</script>
    <link rel="stylesheet" href="<?=$root;?>/app/app.css?v=<?=time()?>">
    <link rel="stylesheet" href="<?=$root;?>/app/jquery-ui.css?v<?=time()?>">
    <link rel="stylesheet" href="<?=$root;?>/app/jquery.timepicker.min.css">
    <script src="<?=$root;?>/app/jquery.js"></script>
    <script src="<?=$root;?>/app/jquery-ui.min.js"></script>
    <script src="<?=$root;?>/app/jquery.timepicker.min.js"></script>
    <script src="<?=$root;?>/app/jquery.ui.datepicker-he.js"></script>
	<script src="<?=$root;?>/app/jquery.table2excel.min.js"></script>

    <script src="<?=$root;?>/app/app.js?v=<?=time()?>"></script>
	<meta name="robots" content="noindex,nofollow" />
    <link rel="icon" href="" type="image/x-icon">
    <link rel="shortcut icon" href="" type="image/x-icon">
    <title><?=outDb(TITLE)?> - מערכת ניהול</title>

</head>
<body class="dashboard">

<nav>
    <div class="profile">
        <div class="image" style="background-image:url('<?=WEBSITE?>webimages/logo.png')"></div>
        <div class="info">
            <div class="name">שלום <?=$_SESSION['name'];?></div>
            <div class="links">
                <a href="<?=$root?>/logout.php">התנתק</a>
            </div>
        </div>
        <div style="clear: both"></div>
    </div>
    <ul>
        <?php
        if($_SESSION['permission']==100) {
            $menu = include "menu.php";
        } else {
            $menu = array();
            $temp_menu = include "menu.php";
            $temp_accs = $_SESSION['access'][1];

            foreach($temp_menu as $key => $val){
                if (is_numeric($key) && count($val['sub'])){
                    $subtemp = array();
                    foreach($val['sub'] as $subkey => $subval)
                        in_array($subkey, $temp_accs) and $subtemp[$subkey] = $subval;

                    count($subtemp) and $menu[$key] = array_merge($val, array('sub' => $subtemp));
                }
                elseif (in_array($key, $temp_accs))
                    $menu[$key] = $val;
            }
            unset($temp_menu, $temp_accs, $key, $val, $subkey, $subval);
        }
        foreach($menu as $item){
            if($item['sub']){ ?>
                <li class="hasSub">
                    <div class="subFix"></div>
                    <img src="<?=$root;?>/<?=$item['icon'];?>">
                    <span><?=$item['name'];?></span>
                    <ul>
                        <?php
                        foreach($item['sub'] as $sub){ ?>
                            <li><a onclick="window.location.href='<?=$root;?>/<?=$sub['href'];?>'"><?=$sub['name']?></a></li>
                        <?php } ?>
                    </ul>
                    <div class="opener"></div>
                </li>
            <?php }else{ ?>
                <li onclick="window.location.href='<?=$root;?>/<?=$item['href'];?>'">
                    <img src="<?=$root;?>/<?=$item['icon'];?>">
                    <span><?=$item['name'];?></span>
                </li>
            <?php } } ?>
    </ul>
</nav>
<div class="openMenu"></div>
	

<?php if($_SESSION['permission']==100){ ?>
    <div id="leftTabsOpener"></div>
    <div id="leftTabs">
        <ul></ul>
    </div>
<?php } ?>
<div id="theTabs"></div>
<section id="mainContainer">
    <div id="formError" style="display: none;"><i class="fa"></i><span class="title"></span><span class="text"></span></div>