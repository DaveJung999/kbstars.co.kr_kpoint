<?php
//=======================================================
// 설	명 : 인클루드 파일 - inc_input.php
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/09/16
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/09/16 박선민 마지막 수정
//=======================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 인쿨루드인 경우에만 허용
	if ($_SERVER["PATH_TRANSLATED"] == realpath(__FILE__)){
		echo "직접 호출되어서 거부함";
		exit;
	}
	if($_GET['mode'] == "edit"){
		$sql = "SELECT * FROM {$table_calendar} WHERE uid ='{$_GET['uid']}'";
		if(!$list=db_arrayone($sql))
			back("해당 일정이 없습니다");

		// 인증 체크(자기 글이면 무조건 보기)
		if(!privAuth($list, "priv_level",1)) back("비공개 일정이거나 레벨이 부족합니다");

		$list['title']	= htmlspecialchars($list['title'],ENT_QUOTES);
		$list['content']	= htmlspecialchars($list['content'],ENT_QUOTES);

		$list['start_timestamp'] = strtotime($list['startdate']) + $list['starthour']*3600 + $list['startmin']*60;
		$list['end_timestamp'] = strtotime($list['enddate']) + $list['endhour']*3600 + $list['endmin']*60;
	} else {
		$list['startdate']	= $_GET['date'] ? $_GET['date'] : date("Y-m-d");
		$list['enddate']		= $list['startdate'];
		$list['starthour']	= $_GET['starthour'] ? $_GET['starthour']: 9;
		$list['endhour']		= $list['starthour'] +1;
		$list['dtype']		= "day";

		$_GET['mode'] == "input";
	}
	$form_input = "name=cal action='ok.php' method=post >";
	$form_input .= substr(href_qs("mode={$_GET['mode']}&uid={$_GET['uid']}",$qs_basic,1),0,-1);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<script LANGUAGE="JavaScript" src="/scommon/js/chkform.js" type="Text/JavaScript"></script>
<script LANGUAGE="JavaScript" src="/scommon/js/inputcalendar.js" type="Text/JavaScript"></script>
<table width="100%" border="0" align="center" cellpadding="3" cellspacing="1" bordercolorlight="#999999" bgcolor="#cccccc">
	<tr >
		<td height="30" align="center" bgcolor="#D2BF7E" colspan="2"><font face="굴림"><span class="style1">일정내용</span></font></td>
	</tr>
	<form onsubmit="return chkForm(this)" <?php echo $form_input ; ?>>
	<tr>
		<td width="21%" height="30" align="center" bgcolor="#F0EBD6">제&nbsp;&nbsp;&nbsp; 목</td>
		<td width="79%" valign="top" bgcolor="#F8F8EA"><input type="text" name="title" size="70" maxlength="40" class="ccbox"	value="<?php echo $list['title'] ; ?>" hname="일정 제목을 입력하여 주세요." required="required" />
		</font> </td>
	</tr>
	<tr>
		<td align="center" bgcolor="#F0EBD6">장&nbsp;&nbsp;&nbsp; 소</td>
		<td bgcolor="#F8F8EA"><font>
		<input name="place" type="text" class="ccbox" value="<?php echo $list['place'] ; ?>" size="70" maxlength="40" />
		</font> </td>
	</tr>
	<tr>
				<td height=30 align="center" bgcolor="#F0EBD6">일정구분</td>
				<td bgcolor="#F8F8EA">
					
					<select name="kind">
						<option value="훈련" <?php if ($list['kind'] == "훈련" ) echo "selected" ; ?>>훈련</option>
						<option value="전지훈련" <?php if ($list['kind'] == "전지훈련" ) echo "selected" ; ?>>전지훈련</option>
						<option value="경기" <?php if ($list['kind'] == "경기" ) echo "selected" ; ?>>경기</option>
						<option value="연습경기" <?php if ($list['kind'] == "연습경기" ) echo "selected" ; ?>>연습경기</option>
						<option value="생일" <?php if ($list['kind'] == "생일" ) echo "selected" ; ?>>생일</option>
						<option value="휴식" <?php if ($list['kind'] == "휴식" ) echo "selected" ; ?>>휴식</option>
						<option value="휴가" <?php if ($list['kind'] == "휴가" ) echo "selected" ; ?>>휴가</option>
					</select>		
					<input type=text name="priv_level" value='<?php echo (int)$list['priv_level'] ; ?>' size=4	class="ccbox">레벨 이상(0:모두에게공개)		</td>
	</tr>
	<tr>
		<td height="30" align="center" bgcolor="#F0EBD6">일&nbsp;&nbsp;&nbsp; 자</td>
		<td bgcolor="#F8F8EA"><input name="startdate"	class="ccbox" type="text" id="startdate" onclick="Calendar(this);" value="<?php echo $list['startdate'] ; ?>" size='10' readonly="readonly" />		</td>
	</tr>
	<tr>
		<td height="30" align="center" bgcolor="#F0EBD6">시간 구분</td>
		<td bgcolor="#F8F8EA"><font color="black">
		<input type="radio" name='dtype' value='hour' <?php if ($list['dtype'] == "hour" ) echo "checked" ; ?> />
		시간단위 일정&nbsp;&nbsp;
		<input type="radio" name='dtype' value='day' <?php if ($list['dtype'] == "day" ) echo "checked" ; ?> />
		하루 종일&nbsp;&nbsp;<!--
		<input type="radio" name='dtype' value='month' <?php if ($list['dtype'] == "month" ) echo "checked" ; ?> />
		월중행사&nbsp;&nbsp; --></font> </td>
	</tr>
	<tr>
		<td align="center" bgcolor="#F0EBD6">내&nbsp;&nbsp;&nbsp; 용</td>
		<td bgcolor="#F8F8EA"><textarea name="content" rows="5" class="textarea02" cols="50" wrap="soft" hname="일정 내용을 입력하여 주세요." required="required" style="width:100%">
<?php echo $list['content'] ; ?></textarea></td>
	</tr>
	<tr>
		<td height="35" align="center" bgcolor="#F0EBD6">시작시간</td>
		<td bgcolor="#F8F8EA"><font>
		<select name="starthour" onchange="changeEndHour(this.form)">
<?php
for($i=0; $i < 24; $i++) {
	// 앞에 0	붙이기
	$i = str_pad($i,2,"0",STR_PAD_LEFT); 

	echo "<option value=".$i;
	if(intval($list['starthour']) == $i )
		echo " selected ";
	echo ">".$i."\n";

/*	if($i < 12)
		echo " >".$i." AM \n";
	elseif ($i == 12)
		echo " >12 PM \n";
	else
		echo " >" . ($i-12) . " PM \n";
*/						
} 

?>
		</select> 시
		</font><font>
			<select name="startmin" onchange="changeEndMin(this.form)">
<?php
for($i=0; $i < 56 ; $i+=5){
	$i = str_pad($i,2,"0",STR_PAD_LEFT); 
	echo "<option value=".$i;
	if(intval($list['startmin']) == $i )
		echo " selected ";

	echo " >".$i.$vbCR;
} 

?>
			</select>
			분
			</font> 부터 &nbsp;&nbsp;&nbsp; <font color="#339999" size="1">▶</font> <b>기간</b></FONT>
			<select name="durHour" onchange="_changeEndHour(this.form)">
				<option value="00" >00</option>
				<option value="01" selected="selected" >01</option>
				<option value="02" >02</option>
				<option value="03" >03</option>
				<option value="04" >04</option>
				<option value="05" >05</option>
				<option value="06" >06</option>
				<option value="07" >07</option>
				<option value="08" >08</option>
				<option value="09" >09</option>
				<option value="10" >10</option>
				<option value="11" >11</option>
				<option value="12" >12</option>
				<option value="13" >13</option>
				<option value="14" >14</option>
				<option value="15" >15</option>
				<option value="16" >16</option>
				<option value="17" >17</option>
				<option value="18" >18</option>
				<option value="19" >19</option>
				<option value="20" >20</option>
				<option value="21" >21</option>
				<option value="22" >22</option>
				<option value="23" >23</option>
			</select>
			시간
				<select name="durmin" onchange="_changeEndMin(this.form)">
				<option selected="selected" value="00">00 </option>
				<option value="05">05 </option>
				<option value="10">10 </option>
				<option value="15">15 </option>
				<option value="20">20 </option>
				<option value="25">25 </option>
				<option value="30">30 </option>
				<option value="35">35 </option>
				<option value="40">40 </option>
				<option value="45">45 </option>
				<option value="50">50 </option>
				<option value="55">55 </option>
				</select>
		분 동안</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#F0EBD6">종료시간</td>
		<td bgcolor="#F8F8EA"><font>
		<select name="endhour" onchange="changeDurHour(this.form)">
<?php
for($i=0; $i < 23; $i++){
	// 앞에 0	붙이기
	$i = str_pad($i,2,"0",STR_PAD_LEFT); 

	echo "<option value=".$i;
	if(intval($list['endhour']) == $i )
		echo " selected ";
	echo ">".$i."\n";
} 

?>
		</select>
		시
		</font> <font>
			<select name="endmin" onchange="changeDurMin(this.form)">
<?php
for($i=0; $i < 56; $i+=5) {	
	// 앞에 0 채우기
	$i = str_pad($i,2,"0",STR_PAD_LEFT); 
	
	echo "<option value=".$i;
	if(intval($list['endmin']) == $i )
		echo " selected ";
	echo " >".$i.$vbCR;
} 

?>
			</select>
			분
		</font> 까지 </td>
	</tr>
	<tr>
		<td rowspan="2" align="center" bgcolor="#F0EBD6"><strong>반복옵션 </strong></td>
		<td height="50" bgcolor="#F8F8EA"> 반복적인 일정의 기간을 선택합니다.<br />
			<font>
			<select name="retimes">
				<option value="0" <?php if ($list['retimes'] == 0 ) echo "selected" ; ?> >반복하지 않는다</option>
				<option value="1" <?php if ($list['retimes'] == 1 ) echo "selected" ; ?>>매</option>
				<option value="2" <?php if ($list['retimes'] == 2 ) echo "selected" ; ?>>둘째</option>
				<option value="3" <?php if ($list['retimes'] == 3 ) echo "selected" ; ?>>셋째</option>
				<option value="4" <?php if ($list['retimes'] == 4 ) echo "selected" ; ?>>넷째</option>
			</select>
			</font> <font>
			<select name="retype">
				<option value='day' <?php if ($list['retype'] == 'day' ) echo "selected" ; ?>>일</option>
				<option value='week' <?php if ($list['retype'] == 'week' ) echo "selected" ; ?> >주</option>
				<option value='month' <?php if ($list['retype'] == 'month' ) echo "selected" ; ?>>월</option>
				<option value='year' <?php if ($list['retype'] == 'year' ) echo "selected" ; ?>>년</option>
			</select>
		</font> </td>
	</tr>
	<tr>
		<td height="50" bgcolor="#F8F8EA"> 입력한 반복 일정의 종료 기간을 선택합니다.<br />
			<input name="enddate" type="text" id="enddate" onclick="Calendar(this);" value="<?php echo $list['enddate'] ; ?>" size='10' readonly="readonly" />
		<b>까지 </b> </td>
	</tr>
	<tr>
		<td height="30" colspan="2" align="center" bgcolor="#efefef">&nbsp;
		<input type="image" src="images/btn_save.gif" name="Submit" value="확인" />
		&nbsp; 
		<a href='javascript:history.back(-1)'><img src="images/btn_cancel.gif" width="40" height="17" border="0" /></a> </td>
	</tr>
	</form>
</table>
