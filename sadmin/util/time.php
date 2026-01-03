<?php
//03/09/01 마지막 수정
//03/12/29 마지막 수정


if($mode=="to_time") {
	$time_result = mktime($h, $i, $s, $m, $d, $y);
}
elseif($mode=="to_date") {
	$date_result = date("Y년 m월 d일 H시 i분 s초", $unixtimestamp);
}
?>
<div style="padding: 5px;">
DateTime 형식의 날짜를 UnixTimestamp 값으로 또는 반대로 상호 변환 해줍니다.
<form name="formTimeConverter">
<input type="button" value="현재시간" onclick="time_getNow();">
<input type="text" name="stringTime">
<input type="button" value="->" onclick="time_string2stamp(document.formTimeConverter.stringTime.value);"><input type="button" value="<-" onclick="time_stamp2string(document.formTimeConverter.timestamp.value);">
<input type="text" name="timestamp">
</form>
<script>
	function time_string2stamp(stringDate)
	{
		var ts = new String();
		ts = Date.parse(stringDate).toString();

		document.formTimeConverter.timestamp.value = ts.substr(0, ts.length - 3);
	}
	function time_stamp2string(timestamp)
	{
		var stringDate = new Date();
		stringDate.setTime(timestamp + "000");

		document.formTimeConverter.stringTime.value = stringDate.getFullYear() + "/" + (stringDate.getMonth() + 1) + "/" + stringDate.getDate() + " " + stringDate.getHours() + ":" + stringDate.getMinutes() + ":" + stringDate.getSeconds();
	}
	function time_getNow()
	{
		var ts = new String();
		var d = new Date();
		ts = d.getTime().toString();
		ts = ts.substr(0, ts.length - 3);
		document.formTimeConverter.timestamp.value = ts;
		time_stamp2string(ts);
	}
</script>
</div>

<table width="600" border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">현재 시간</font></td>
	<td width="446" bgcolor="#F4F4F4"><font size="2"><?php 
echo date("Y년 m월 d일 H시 i분 s초")  ?></font></td>
  </tr>
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">현재UnixTimestamp</font></td>
	<td width="446" bgcolor="#F4F4F4"> <font size="2"><?php 
echo time()  ?></font></td>
  </tr>
</table>
<font size="2"><br>
</font>
<form method="post" action="<?php 
echo $_SERVER['PHP_SELF'] 
?>" ENCTYPE=multipart/form-data  style='margin : 0px'>
<input type='hidden' name='mode' value='to_time'>
<table width="600" border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">현재 시간 -&gt; UnixTime</font></td>
	  <td width="446" bgcolor="#F4F4F4"><font size="2"> 
		<input type="text" name="y" size="4" value="<?php 
echo ($y ? $y : date(Y)) 
?>">
		년 
		<input type="text" name="m" size="2" value="<?php 
echo ($m ? $m : date(m)) 
?>">
		월 
		<input type="text" name="d" size="2" value="<?php 
echo ($d ? $d : date(d)) 
?>">
		일 
		<input type="text" name="h" size="2" value="<?php 
echo ($h ? $h : date(H)) 
?>">
		시 
		<input type="text" name="i" size="2" value="<?php 
echo ($i ? $i : date(i)) 
?>">
		분 
		<input type="text" name="s" size="2" value="<?php 
echo ($s ? $s : date(s)) 
?>">
		초 
		<input type="submit" name="Submit" value="변환">
		</font></td>
  </tr>
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">UnixTimestamp 처리결과</font></td>
	  <td width="446" bgcolor="#F4F4F4"><font size="2"><?php 
echo $time_result  ?></font></td>
  </tr>
</table>
</form>
<font size="2"><br>
</font>
<form method="post" action="<?php 
echo $_SERVER['PHP_SELF'] 
?>" style='margin : 0px'>
<input type='hidden' name='mode' value='to_date'>
<table width="600" border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">UnixTimestamp</font></td>
	<td width="446" bgcolor="#F4F4F4"><font size="2"> 
	  <input type="text" name="unixtimestamp">
	  <input type="submit" name="Submit2" value="변환">
	  </font></td>
  </tr>
  <tr> 
	<td width="154" bgcolor="#D4D4D4"><font size="2">변환 시간</font></td>
	<td width="446" bgcolor="#F4F4F4"> <font size="2"><?php 
echo $date_result  ?></font></td>
  </tr>
</table>
</form>
<p>&nbsp;</p>
