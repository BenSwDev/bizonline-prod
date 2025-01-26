<?
define("_D", date("d"));
define("_M", date("m"));
define("_Y", date("Y"));

class SimpleDate
{
	public static function BirthdayDate($basenum = 1, $basename = NULL, $y = 0, $m = 0, $d = 0, $style = "font-size:11px")
	{
		$mDays = $m ? date("d", mktime(0,0,5,$m + 1,0,$y))+1 : 31;
		
		$buffer = '
<table cellpadding=0 cellspacing=1 border=0 dir="ltr">
	<tr>
		<td>
			<select id="d'.$basenum.'" name="d_'.$basename.'" style="'.$style.'">
				<option value="0">- -</option>
				';
		
		for($i=1; $i<$mDays; $i++)
			$buffer .= '<option value="'.$i.'" '.($d == $i ? "selected" : "").'>'.Addzero($i).'</option>';

		$buffer .= '
			</select>
		</td>
		<td>
			<select id="m'.$basenum.'" name="m_'.$basename.'" style="'.$style.'" onchange="SimpleDateVerify('.$basenum.')">
				<option value="0">- -</option>';

		for($i=1; $i<13; $i++)
			$buffer .= '<option value="'.$i.'" '.($m == $i ? "selected" : "").'>'.Addzero($i).'</option>';

		$buffer .= '
			</select>
		</td>
		<td>
			<select id="y'.$basenum.'" name="y_'.$basename.'" style="'.$style.'" onChange="if(2 == parseInt(document.getElementById(\'m'.$basenum.'\').value)){SimpleDateVerify('.$basenum.');}">
				<option value="0">- - - -</option>';

		for($i=_Y - 100; $i<=_Y; $i++)
			$buffer .= '<option value="'.$i.'" '.($y == $i ? "selected" : "").'>'.Addzero($i).'</option>';

		$buffer .= '
			</select>
		</td>
	</tr>
</table>
';
		return $buffer;
	}
	
	public static function GetSimpleDate($basenum = 1, $basename = NULL, $y = _Y, $m = _M, $d = _D, $cut = 0)
	{
//		if(!checkdate($m, $d, $y))
//		{
//			return false;
//		}
		
		$buffer = '';
		
		$buffer .= "
			<table cellpadding='0'  cellspacing='1'  border='0'  dir='ltr'>
			<tr>";
		if (!$cut) {
			$buffer .="<td>
						<select id='d{$basenum}' name='d_{$basename}' dir='ltr'  style='font-size:11px; width:38px'>
			";
		
			$mDays = date("t", mktime(0,0,1,$m, 1, $y))+1;
			for($i=1;$i<$mDays;$i++)
			{
				$buffer .= "<option value='{$i}'".($d == $i ? "selected" : "").">".Addzero($i)."</option>\n";
			}
			$buffer .= "
						</select>
					
					</td>";
		}
		$buffer .= "<td>
					<select id='m{$basenum}' name='m_{$basename}' dir='ltr'  style='font-size:11px; width:38px' onchange='SimpleDateVerify({$basenum});'>
		";
		
		for($i=1;$i<13;$i++)
		{
			$buffer .= "<option value='{$i}'".($m == $i ? "selected" : "").">".AddZero($i)."</option>\n";
		}
		
		$buffer .= "
					</select>
				</td>
				<td>
					<select id='y{$basenum}' name='y_{$basename}' dir='ltr'  style='font-size:11px; width:50px' onchange='if(\"2\" == document.getElementById(\"m{$basenum}\").value) {SimpleDateVerify({$basenum})}'>
		";
		
		for($i=date("Y")-5;$i<date("Y")+5;$i++)
		{
			$buffer .= "<option value='{$i}'".($y == $i ? "selected" : "").">$i</option>\n";
		}
		
		$buffer .= "
					</select>
				</td>
			</tr>
			</table>
		";
		return $buffer;
	}
	
	public static function GetSimpleDate2($basenum = 1, $basename = NULL, $y = _Y, $m = _M, $d = _D, $cut = 0)
	{
//		if(!checkdate($m, $d, $y))
//		{
//			return false;
//		}
		
		$buffer = '';
		
		$buffer .= "
			<table cellpadding='0'  cellspacing='1'  border='0'  dir='ltr'>
			<tr>";
		if (!$cut) {
			$buffer .="<td>
						<select id='d{$basenum}' name='d_{$basename}' dir='ltr'  style='font-size:11px; width:38px'>
			";
		
			//$mDays = date("t", mktime(0,0,1,$m, 1, $y))+1;
			for($i=1;$i<=31;$i++)
			{
				$buffer .= "<option value='{$i}'".(1 == $i ? "selected" : "").">".Addzero($i)."</option>\n";
			}
			$buffer .= "
						</select>
					
					</td>";
		}
		$buffer .= "<td>
					<select id='m{$basenum}' name='m_{$basename}' dir='ltr'  style='font-size:11px; width:38px' onchange='SimpleDateVerify({$basenum});'>
		";
		
		for($i=1;$i<13;$i++)
		{
			$buffer .= "<option value='{$i}'".(1 == $i ? "selected" : "").">".AddZero($i)."</option>\n";
		}
		
		$buffer .= "
					</select>
				</td>
				<td>
					<select id='y{$basenum}' name='y_{$basename}' dir='ltr'  style='font-size:11px; width:50px' onchange='if(\"2\" == document.getElementById(\"m{$basenum}\").value) {SimpleDateVerify({$basenum})}'>
		";
		
		for($i=date("Y")-5;$i<date("Y")+5;$i++)
		{
			$buffer .= "<option value='{$i}'".(2011 == $i ? "selected" : "").">$i</option>\n";
		}
		
		$buffer .= "
					</select>
				</td>
			</tr>
			</table>
		";
		return $buffer;
	}

	
	public static function GetSimpleDateLong($basenum = 1, $basename = NULL, $y = _Y, $m = _M, $d = _D)
	{
		$buffer = "<table cellpadding='0'  cellspacing='1'  border='0'  dir='ltr'><tr><td>
						<select id='d{$basenum}' name='d_{$basename}' dir='ltr'  style='font-size:11px; width:38px'>";
		
		for($i=1;$i<32;$i++)
			$buffer .= "<option value='{$i}'>".Addzero($i)."</option>\n";

		$buffer .= "</select></td><td>
					<select id='m{$basenum}' name='m_{$basename}' dir='ltr'  style='font-size:11px; width:38px' onchange='SimpleDateVerify({$basenum});'>";
		
		for($i=1;$i<13;$i++)
			$buffer .= "<option value='{$i}'>".AddZero($i)."</option>\n";
		
		$buffer .= "</select></td><td>
					<select id='y{$basenum}' name='y_{$basename}' dir='ltr'  style='font-size:11px; width:50px' onchange='if(\"2\" == document.getElementById(\"m{$basenum}\").value) {SimpleDateVerify({$basenum})}'>";
		
		for($i=date("Y")-80;$i<=date("Y");$i++)
			$buffer .= "<option value='{$i}' ".($i == 1970 ? 'selected' : '').">$i</option>\n";
		
		$buffer .= "</select></td></tr></table>";

		return $buffer;
	}

	
	public static function GetSimpleDateJS()
	{
		echo "
			<script>
				function SimpleDateVerify(basenum)
				{
					selM  = parseInt(document.getElementById('m'+basenum).value);
					selY  = parseInt(document.getElementById('y'+basenum).value);
					selY = selY ? selY : 2000;
					maxD  = new Date(selY, selM, 0).getDate();
					pselD = document.getElementById('d'+basenum);
					pselD.options.length = maxD;
					for(i=0;i<maxD;i++)
					{
						l = i + 1;
						pselD.options[i].text  = l<=9 ? '0'+l : l;
						pselD.options[i].value = l;
					}
				}
			</script>
		";
	}
}
