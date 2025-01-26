<h2>פניות קשר</h2>
<?
$conForm = udb::full_list("SELECT * FROM contactForm WHERE ".$adding.$wherec ." order by id DESC");
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
        <th>מייל</th>
        <th>הודעה</th>
    </tr>
    </thead>
    <tbody>
    <?
    $counter = 0;
    foreach ($conForm as $call) {
        $counter++;
        ?>
        <tr>
            <td><?=$counter?></td>
            <td><?=$call['fullName']?></td>
            <td><A href="tel:<?=$call['phone']?>"><?=$call['phone']?></A></td>
            <td><?=date("d/m/Y",strtotime($call['created']))?></td>
            <td><?=$call['mail']?></td>
            <td><?=$call['note']?></td>
        </tr>
    <?}?>
    </tbody>
</table>
