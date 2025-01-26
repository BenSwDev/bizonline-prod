<?
$thisdate = date("Y/m/d");
	
$setnew[0]['from'] = date("01/m/Y");
$setnew[0]['to'] = date("t/m/Y");
$setnew[0]['name'] = 'החודש';

$setnew[1]['from'] = date('d/m/Y',  strtotime($thisdate." -".date('w',strtotime($thisdate))." day"));
$setnew[1]['to'] = date('d/m/Y',  strtotime($thisdate." +".(6 - date('w',strtotime($thisdate)))." day"));
$setnew[1]['name'] = 'השבוע';

$setnew[2]['from'] = date("d/m/Y");
$setnew[2]['to'] = date("d/m/Y");
$setnew[2]['name'] = 'היום';


if(isset($_SESSION[$_GET['page']])){
	$repRange = intval($_SESSION[$_GET['page']]);
	if(!$_GET['from']){
		$_GET['from'] = $setnew[$repRange]['from'];
		$_GET['to'] = $setnew[$repRange]['to'];
	}
	
}else{
	$repRange = 0;
}


function selectReportRange(){
	global $repRange,$setnew;
	?>
	<div id="setReportRange" data='<?=$_SESSION[$_GET['page']]?>'>
		<?foreach($setnew as $key => $set) {?>
		<div id='setRange<?=$key?>' onclick="setReportRange($(this),'<?=$_GET['page']?>',<?=$key?>)" class="<?=$repRange == $key? "active" : ""?>" 	data-from='<?=$setnew[$key]['from']?>' data-to='<?=$setnew[$key]['to']?>''>
			<?=$setnew[$key]['name']?>
		</div>
		
		<?}?>
	</div>
	<style>
	#setReportRange {display: flex;box-sizing: border-box;background: white;border-radius: 10px;padding: 0;border: 1px #0dabb6 solid;left: 4px;top: -58px;line-height: 1;align-items: stretch;overflow: hidden;justify-content: space-between;width:300px;margin:0 auto;height:50px}
	#setReportRange div {cursor:pointer;display: flex;vertical-align: middle;box-sizing: border-box;margin: 0;text-decoration: none;color: #0dabb6;position: relative;border: 0;align-items: center;padding: 0 4px;text-align: center;width: 35%;justify-content: center;}
	#setReportRange div.active {background: #0dabb6;color: white;}
	</style>
	<script>
	function setReportRange(elm,type,tvalue){	
		set_session_global(type,tvalue);
		$('#searchForm input[name="from"]').val(elm.data('from'));
		$('#searchForm input[name="to"]').val(elm.data('to'));
		localStorage.setItem(type, tvalue);
		$('#setReportRange > div').removeClass('active');
		$('#setRange'+tvalue).addClass('active');
		setTimeout(function(){
			$('#searchForm').removeClass('hide').submit();
		}
		,500);
		
	}
	</script>
<?}?>


	