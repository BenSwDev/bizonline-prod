<h2>התחלות שיחות ווטסאפ</h2>
<?
$conWhatsapp = udb::full_list("SELECT * FROM contact_whatsapp WHERE ".$adding.$wherew . " order by id DESC");
?>
<div style="width: 100%;text-align: right;max-width: 360px;">
    <input type="text" placeholder="חיפוש בטבלה" name="searchinTbl" id="searchinTbl" onkeyup="search_table();"  />
</div>
<table style="width: 100%" id="resTable">
    <thead>
    <tr>
        <th>#</th>
        <th>מאת</th>
        <th>טלפון</th>
        <th>תאריך פניה</th>
        <th>תאריך מבוקש</th>
        <th>מספר אורחים</th>

    </tr>
    </thead>
    <tbody>
    <?
    $counter = 0;
    foreach ($conWhatsapp as $call) {
        $counter++;
        ?>
        <tr>
            <td><?=$counter?></td>
            <td><?=$call['name']?></td>
            <td><A href="tel:0<?=$call['phone']?>">0<?=$call['phone']?></A></td>
            <td><?=date("d/m/Y",strtotime($call['created']))?></td>
            <td><?=($call['date'] && $call['date'] != '0000-00-00') ?  date("d/m/Y",strtotime($call['date'])) : '';?></td>
            <td><?=$call['peoplec'] ? $call['peoplec'] : ''?></td>
        </tr>
    <?}?>
    </tbody>
</table>