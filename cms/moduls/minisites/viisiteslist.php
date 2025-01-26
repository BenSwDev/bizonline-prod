<?

include_once "../../bin/system.php";
include_once "../../bin/top.php";
$where ="1 = 1 ";
$sitesActiveDom = udb::key_row("SELECT domainID, active,siteID FROM sites_domains",['siteID','domainID']);
$que="SELECT * FROM `domains` WHERE domainID = 1  order by domainID ASC";
$domains= udb::full_list($que);

$sites = udb::full_list("SELECT `sites`.`siteName`, `sites`.`active` ,`sites`.`masof_active` ,`sites`.`masof_invoice`, `sites`.`signature`, `sites`.`siteID`, `sites`.`phone`, `sites`.`email`, `sites_langs`.`owners` , sites_domains.phone, sites_domains.phone, sites_domains.active
FROM `sites` 
LEFT JOIN `settlements` USING (`settlementID`)
LEFT JOIN `areas` USING (`areaID`)
LEFT JOIN `sites_langs` USING (`siteID`) 
LEFT JOIN `sites_domains` USING (`siteID`) 

WHERE " . $where . " AND `sites_langs`.`langID`=1 AND sites_domains.domainID=1 AND (`sites`.`active`=1 || `sites`.`active`=0) AND `sites_langs`.`domainID`=1 ORDER BY `sites`.`active` DESC, `sites`.`siteName` ASC");
$count = 0;
?>
<table>
	<thead>
		<tr>
			<th>שם המקום</th>
			<th>טלפון</th>
			<th>מייל</th>
		</tr>
	</thead>
	<tbody>
	<?
	foreach($sites as $site) {
		if($sitesActiveDom[$site['siteID']][6]['active'] == 1) {
			$count++; 
			echo '<tr>
				<th>'.$site['siteName'].'</th>
				<th>'.$site['phone'].'</th>
				<th>'.$site['email'].'</th>
			</tr>';
		}
			
	}
	?>
	</tbody>
</table><?echo "<BR><BR>" . $count;?>