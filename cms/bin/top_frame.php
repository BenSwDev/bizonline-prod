<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <script src="<?=$root;?>/app/jquery.js"></script>
    <script src="<?=$root;?>/app/jquery-ui.min.js"></script>
    <script src="<?=$root;?>/app/lightbox/js/lightbox.js"></script>
    <script src="<?=$root;?>/app/jquery.ui.datepicker-he.js"></script>

	<script src="<?=$root;?>/app/app.js?v=<?=time()?>"></script>
    <link rel="stylesheet" href="<?=$root;?>/app/app.css?v<?=time()?>">
    <link rel="stylesheet" href="<?=$root;?>/app/jquery-ui.css?v<?=time()?>">
    <link rel="icon" href="<?=WEBSITE?>favicon.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="<?=WEBSITE?>favicon.ico?v=2" type="image/x-icon">
	<link rel="stylesheet" href="<?=$root;?>/app/fontawsome/css/font-awesome.css?v=1">
	<link rel="stylesheet" href="<?=$root;?>/app/lightbox/css/lightbox.css">



    <title></title>
</head>
<style>
body.dashboard{overflow:auto;padding-right:0;padding-top:20px;box-sizing:border-box;}

</style>
<body class="dashboard">
<section id="mainContainer">
    <div id="formError" style="display: none;"><i class="fa"></i><span class="title"></span><span class="text"></span></div>