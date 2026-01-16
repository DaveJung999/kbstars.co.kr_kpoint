<?php
	function selectBox($name, $optSize, $initVal, $disabled="", $selected="", $title=""){
	
		$o="<select name={$name} {$disabled}>";
		if (!$slected and $title) {
			$o.="<option selected value=''>".$title."</option>\n";
		}
		else $slected--;
		
		/// $initVal이 배열일경우
		if (is_array($initVal)){
			$i=0;
			/// list함수를 이용해 배열의 키값과 배열의 값을 얻는다. mysql_fetch_array와 같이 다음 배열의 값을 읽는다. 배열이 종료되면 널을 리턴
			while (list($key,$val)=each($initVal)){
				//키값과 셀렉트값이 일치하면 $bingo변수에 selected라는 문자열 넣어준다. 
				if ($selected) {if($key==$selected)$bingo="selected";else $bingo="";}
				$o.="<option value='{$key}' name='{$key}' {$bingo}>{$val}</option>\n";
				$i++;
			}
		}
		// $initVal이 배열이 아닐경우..숫자인경우 $initVal~$optSize(숫자)로 이루어진 셀렉트메뉴를 만든다.
		else{
			for ($i=0;$i<$optSize;$i++){
				// $initVal과 $selected값이 같으면 그 값을 셀렉트된 값으로 한다.
				if ($selected){
					if($initVal==$selected)$bingo="selected";
					else $bingo="";
				}
				if (strlen($initVal)==1) $initVal="0".$initVal;
					$o.="<option value='{$initVal}' name='{$initVal}' {$bingo}> {$initVal} </option>\n";
				$initVal++;
			}
		}
		clearstatcache(); //파일의 stat캐시를 비운다. 파일을 다룰때 사용되었던 메모리를 비우는 역활을 한다.
		$o.="</select>";
		return $o;
	}

	if( $date ){
		$exp_date = explode("-", $date);
		$gyear=$exp_date['0'];
		$gmonth=$exp_date['1'];
		$gday=$exp_date['2'];
	}

	if ( !$gyear ) {
		$gyear=date("Y");
		$gmonth=date("n");
		$gday=date("j");
	}
	
	$sd = date("w", mktime(0,0,0,$gmonth,1,$gyear)); //요일 구하기 (num)
	$ed	= date("t", mktime(0,0,0,$gmonth,1,$gyear)); //마지막날 구하기 
	$jucnt=ceil(($sd+$ed)/7);

$thisPath	= dirname(__FILE__);
$thisUrl	= "/scalendar"; // 마지막 "/"이 빠져야함
?>

<script>
function onChg()
{
	var date;
	date = document.change.gyear.value+"-"+document.change.gmonth.value+"-01";
	document.change.date.value=date;
	document.change.submit();
}
</script>
<table width="100%" border=0 align=center cellpadding=1 cellspacing=1 bgcolor=#DBDBDB>
<FORM name="change" METHOD=get ACTION="<?php echo $PHP_SELF?>">
	<input type="hidden" name="cate" value="<?php echo $cate?>">
	<input type="hidden" name="db" value="<?php echo $_GET['db']?>">
	<input type="hidden" name="date" value="">
	<input type="hidden" name="skin_info" value="<?php echo $_GET['skin_info']?>">
	<input type="hidden" name="m_category" value="1">
	<tr> 
	<td bgcolor="#ffffff" colspan=7 height=20> <table cellspacing=0 cellpadding=0 border=0 align=center>
		<tr> 
			<td align=center><font size="2" color="#666666"> <?php echo $gyear?> 년 <?php echo $gmonth?> 월</font> </td>
		</tr>
		</table></td>
	</tr>
	<tr> 
	<td width="14%" height="8" align=center	bgcolor="#e2cbcb"><b><font size="2" color="#C45B4D">일</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#f5f5f5"><b><font size="2" color="#666666">월</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#f5f5f5"><b><font size="2" color="#666666">화</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#f5f5f5"><b><font size="2" color="#666666">수</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#f5f5f5"><b><font size="2" color="#666666">목</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#f5f5f5"><b><font size="2" color="#666666">금</font></b></td>
	<td width="14%" height="8" align=center valign=center	bgcolor="#cbd5e2"><b><font size="2" color="#646777">토</font></b></td>
	</tr>
<?php
$day=-$sd+1;
for ( $ju=0 ; $ju < $jucnt ; $ju++ ) {
?>
	<tr bgcolor=#ffffff> 
<?php
		for ( $i=0 ; $i < 7 ; $i++, $day++ ) {
/*				switch ( $i ) {
						case '0' : $__tcolor="bgcolor=#fcf5f5"; break;
						case '6' : $__tcolor="bgcolor=#eff4f9"; break;
						default : $__tcolor="";
				}
*/				if ( $day == $gday ) $__tcolor="bgcolor=#EFEFEF";
				if ( $day > 0 && $day <= $ed ) $__day="<a href='{$thisUrl}/index.php?db={$_GET['db']}&mode=day&date={$gyear}-{$gmonth}-{$day}&m_category=1&skin_info={$_GET['skin_info']}'><font face=Tahoma size='1' color='#000000'>{$day}</font></a>";
				else $__day="";	
?>
	<td height="8" align=center valign="top"<?php echo $__tcolor?>><?php echo $__day?></td>
<?php
}
?>
	</tr>
<?php
}
?>
	<tr> 
		<td colspan="7" align=center bgcolor="#FFFFFF"> <font size="2"> 
		<?php echo selectBox(gyear,(date("Y")-2000+7),2000,"onchange=\"onChg();\"",$gyear,"");?> 년 &nbsp; 
		<?php echo selectBox(gmonth,12,1,"onchange=\"onChg();\"",$gmonth,"");?> 월 </font> </td>
	</tr>
	</form>
</table> 
