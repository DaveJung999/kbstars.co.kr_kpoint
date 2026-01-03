<?php
//=======================================================
// 설  명 : 인트라넷 - 출근(beginwork.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/10
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/10 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck'=>1, // check_value()
	'useApp'	=>1, // remote_addr()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo = db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");


	$table = $SITE['th'] . "intranet_attendance";

	// 새벽 6시 이전에는 출근 못함
	if(date("H")<6) {
		echo "새벽 6시 이전에는 출근 못합니다.";
		exit;
	}

	// 출근 여부 확인
	$rs_tmp=db_query("select * from $table where workday='".date("Ymd")."' and bid={$_SESSION['seUid']}");
	if(db_count()) {
		$list_tmp=db_array($rs_tmp);
		back("[{$qs['workday']}] 이미 {$list_tmp['type']}으로 되어 있습니다.\n 즐거운 하루 되시기 바랍니다.","./attendance.php");
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
if($mode) {
	$qs=array(
				"mode"	=> "post,trim",
				"memo"	=> "post,trim",
		);
	$qs=check_value($qs);

	$qs['workday']	= date("Ymd");
	$qs['ip']			= remote_addr();
	
	switch($mode) {
		case "출근" :
			$qs['type']="출근";
			break;
		case "휴가" :
			$qs['type']="휴가";
			break;
		case "출장" :
			$qs['type']="출장";
			break;
		default :
			back("지원되지 않는 모드입니다.");
	} // end switch

	$qs['workday']	= date("Ymd"); // 출근일

	$sql="INSERT
				`$table` 
			SET
				`workday`	={$qs['workday']} , 
				`bid`		={$_SESSION['seUid']} , 
				`userid`	={$_SESSION['seUserid']} , 
				`type`		={$qs['type']} , 
				`begintime` =UNIX_TIMESTAMP() , 
				`begintimeip`={$qs['ip']} , 					
				`finishtime` ={$qs['finishtime']} ,
				`finishtimeip`='' ,					 
				`dayhours`	={$qs['dayhours']} , 
				`overhours` ={$qs['overhours']} , 
				`nighthours`={$qs['nighthours']} , 
				`signbid`	={$qs['signbid']} , 
				`signdate`	={$qs['signdate']} , 
				`memo`		={$qs['memo']}
		";
	db_query($sql);

	go_url("attendance.php"); // 출근부 보기
	exit;
} // end if($mode)


?>
<html>
<?=$pageinfo['html_header']	 // 스타일시트?>
<body bgcolor="<?=$pageinfo['right_bgcolor']?>" background="<?=$pageinfo['right_background']?>">
<form method=post action="<?=$PHP_SELF?>">
  <table width="500" border=0 cellpadding='<?=$pageinfo['table_cellpadding']?>' cellspacing='<?=$pageinfo['table_cellspacing']?>' bgcolor='<?=$pageinfo['table_linecolor']?>'>
	<tr> 
	  <td bgcolor='<?=$pageinfo['table_titlecolor']?>'><b><?=$SITE['company']?> 출근하기</b></td>
	</tr>
	<tr> 
	  <td bgcolor='<?=$pageinfo['table_tdcolor']?>'>
		  <table width="400" border=0 align="center" cellpadding='<?=$pageinfo['table_cellpadding']?>' cellspacing='<?=$pageinfo['table_cellspacing']?>' bgcolor='<?=$pageinfo['table_linecolor']?>'>
		  <tr> 
			<td bgcolor='<?=$pageinfo['table_thcolor']?>'><b> 
			  <input name="mode" type="radio" onClick="javascript: this.form.submit.value='출근하기'" value="출근" checked>
			  <a href="javascript: document.forms[0].mode[0].checked=true; void(0)">출근하기 </a>
			  <input name="mode" type="radio" onClick="javascript: this.form.submit.value='휴가중'" value="휴가">
			  <a href="javascript: document.forms[0].mode[1].checked=true; void(0)">휴가확인(유급의경우필히)</a>
			  <input name="mode" type="radio" onClick="javascript: this.form.submit.value='출장중';" value="출장">
			  <a href="javascript: document.forms[0].mode[2].checked=true; void(0)">출장확인</a></b></td>
		  </tr>
		  <tr> 
			<td align="center" bgcolor='<?=$pageinfo['table_tdcolor']?>'> <b> 
			  <?=date("Y-m-d H시 i분");?>
			  </b> </td>
		  </tr>
		  <tr> 
			<td align="center" bgcolor='<?=$pageinfo['table_thcolor']?>'><div align="left">메모</div></td>
		  </tr>
		  <tr> 
			<td bgcolor='<?=$pageinfo['table_tdcolor']?>'><textarea name="memo" cols="50" rows="6" id="memo"></textarea></td>
		  </tr>
		</table>
	  </td>
	</tr>
	<tr> 
	  <td bgcolor='<?=$pageinfo['table_tdcolor']?>' align="center"><input name="submit" type="submit" id="submit" value="출근합니다."></td>
	</tr>
  </table>
</form>
</body>
</html>