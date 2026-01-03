<?php
//=======================================================
// 설	명 : 인클루드 파일 - inc_view.php
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/09/16 박선민 마지막 수정
// 03/11/14 박선민 버그수정
//=======================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 인쿨루드인 경우에만 허용
if ($_SERVER["PATH_TRANSLATED"] == realpath(__FILE__)) {
	echo "직접 호출되어서 거부함";
	exit;
}

$sql = "SELECT * FROM {$table_calendar} WHERE uid ='{$_GET['uid']}'";
if(!$list=db_arrayone($sql))
	back("해당 일정이 없습니다");

// 인증 체크(자기 글이면 무조건 보기)
if(!privAuth($list, "priv_level",1)) back("비공개 일정이거나 레벨이 부족합니다");

$list['title']	= htmlspecialchars($list['title'],ENT_QUOTES);
$list['content']	= htmlspecialchars($list['content'],ENT_QUOTES);
$list['content']	= replace_string($list['content'], 'text');	// 문서 형식에 맞추어서 내용 변경

$list['start_timestamp'] = strtotime($list['startdate']) + $list['starthour']*3600 + $list['startmin']*60;
$list['end_timestamp'] = strtotime($list['enddate']) + $list['endhour']*3600 + $list['endmin']*60;

// URL Link
$href['edit']		= "./index.php?". href_qs("mode=edit&date={$_GET['date']}&uid={$_GET['uid']}", $qs_basic);
$href['delete']	= "./ok.php?".href_qs("mode=delete&date={$_GET['date']}&uid={$_GET['uid']}", $qs_basic);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<table width="100%" border="0" cellpadding="5" cellspacing="1" bordercolorlight="#999999" bgcolor=#CCCCCC>
	<tr >
		<td align="center" bgcolor="#D2BF7E" colspan="2"><font face="굴림"><span class="style1">일정내용</span></font></td>
	</tr>
	<tr>
	<td width=18% align="center" bgcolor="#F0EBD6"> 제&nbsp; 목 </td>
	<td width="82%" bgcolor="#F8F8EA"><span style="font-size: 9pt"> &nbsp;
			<?=$list['title']?>
	</span> </td>
	</tr>
	<tr>
	<td align="center" bgcolor="#F0EBD6"> 장&nbsp; 소 </td>
	<td height=25 bgcolor="#F8F8EA"><span style="font-size: 9pt"> &nbsp;
			<?=$list['place']?>
	&nbsp; </span> </td>
	</tr>
	<tr>
	<td align="center" bgcolor="#F0EBD6"> 일&nbsp; 자 </td>
	<td bgcolor="#F8F8EA">&nbsp;
<?php
	if	($list['dtype'] == "day" ) {
		$lhour= "";
		echo date("Y년 n월 j일",$list['start_timestamp']), $lhour;
	}
	elseif ($list['dtype'] == "month" ) {
		$lhour=$intThisMonth."월중 일정";
		echo $lhour;
	}
	else {
		$list['starthour'] = str_pad($list['starthour'],2,"0",STR_PAD_LEFT); 
		$list['startmin'] = str_pad($list['startmin'],2,"0",STR_PAD_LEFT); 
		$list['endhour'] = str_pad($list['endhour'],2,"0",STR_PAD_LEFT); 
		$list['endmin'] = str_pad($list['endmin'],2,"0",STR_PAD_LEFT); 

		$lhour=" [{$list['starthour']}:{$list['startmin']} ~ {$list['endhour']}:{$list['endmin']}]";
		echo date("Y년 n월 j일",$list['start_timestamp']), $lhour;
	}
?>
	</td>
	</tr>
<?php
	if ($list['retimes']>0){
?>
	<tr>
	<td align="center" bgcolor=#F0EBD6 > 반복설정 </td>
	<td bgcolor=#F8F8EA><span style="font-size: 9pt">&nbsp; 본 일정은
<?php
	switch ($list['retimes'])	{
		Case 1:	$txt_reid	= "매";		break;
		Case 2:	$txt_reid	= "둘째";	break;
		Case 3:	$txt_reid	= "셋째";	break;
		Case 4:	$txt_reid	= "넷째";	break;
	}

	switch ($list['retype'])	{
		Case 'day':	$txt_retype = "일";		break;
		Case 'week':	$txt_retype = "주";		break;
		Case 'month':	$txt_retype = "월";		break;
		Case 'year':	$txt_retype = "년";		break;
	}


	echo date("Y년 n월 j일까지",$list['end_timestamp']);
	echo " {$txt_reid}{$txt_retype}마다 반복 설정되었습니다";
?>
	</span></td>
	</tr>
<?php
	}	
?>
	<tr>
		<td height=30 align="center" bgcolor="#F0EBD6">일정구분</td>
		<td bgcolor="#F8F8EA">&nbsp; <?=$list['kind']?></td>
	</tr>
	<tr>
	<td height=120 align="center" bgcolor="#F0EBD6"> 일정내용</td>
	<td bgcolor="#F8F8EA">&nbsp;
		<?=$list['content']?>
	</td>
	</tr>
	<tr align="center">
	<td colspan="2" bgcolor=#efefef><a href='<?=$href['edit']?>'><img src="images/btn_modi.gif" width="40" height="17" border="0" ></a>&nbsp; 
	<a href='<?=$href['delete']?>' onClick="javascript: return confirm('해당 일정을 정말로 삭제하시겠습니까?');"><img src="images/btn_del.gif" width=40 height=17 border=0></a>&nbsp;
	<a href='javascript:history.back(-1)'><img src="images/btn_cancel.gif" width="40" height="17" border="0"></a></td>
	</tr>
</table>


