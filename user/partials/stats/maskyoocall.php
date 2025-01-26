<h2>שיחות מסקיו</h2>
<?
$disposition=Array();
$disposition['ANSWER']="נענה";
$disposition['NOANSWER']="לא נענה";
$disposition['CANCEL']="בוטל";
$disposition['BUSY']="תפוס";
$disposition['CALLER CANCEL']="לקוח ביטל";
$disposition['IVR_HANGUP']="IVR_HANGUP"; //??
?>
<table style="width: 100%">
    <thead>
        <tr>
            <th>#</th>
            <th>מאת</th>
            <th>אל</th>
            <th>שעת התחלה</th>
            <th>שעת סיום</th>
            <th>משך השיחה</th>
            <th>סטטוס</th>
        </tr>
    </thead>
    <tbody>
    <?
    $maskyooSql = "SELECT * FROM maskyooCalls WHERE ".$adding.$wherem . " order by start_call DESC";
    $maskyoo = udb::full_list($maskyooSql);
    $counter = 0;
    foreach ($maskyoo as $call) {
        $counter++;
        $datetime_1 = $call['end_call'];
        $datetime_2 = $call['start_call'];

        $start_datetime = new DateTime($datetime_1);
        $diff = $start_datetime->diff(new DateTime($datetime_2));
        $call_duration = $diff->i . ":" . $diff->s;

        ?>
    <tr>
        <td><?=$counter?></td>
        <td><A href="tel:<?=str_replace("972","0",$call['cdr_ani'])?>"><?=str_replace("972","0",$call['cdr_ani'])?></A></td>
        <td><?=str_replace("972","0",$call['cdr_ddi'])?></td>
        <td><?=$call['start_call']?></td>
        <td><?=$call['end_call']?></td>
        <td><?=$call_duration?></td>
        <td><?=$disposition[$call['call_status']]?></td>
    </tr>
    <?}?>
    </tbody>
</table>