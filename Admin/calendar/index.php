<?php
//=======================================================
// 설 명 : 일정관리(index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/06
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/06 박선민 버그 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv'		 => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard' => 1,
	'useApp'	 => 1,
	'html_echo'	 => 0, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

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
$thisPath			= dirname(__FILE__);
include_once("{$thisPath}/userfuntions.php");
$thisUrl			= "/Admin/calendar"; // 마지막 "/"이 빠져야함

$db = $_GET['db'] ?? '';
// 기본 URL QueryString
$qs_basic = "db=".urlencode($db) . "&html_headpattern=".urlencode($_GET['html_headpattern'] ?? '') . "&html_headtpl=".urlencode($_GET['html_headtpl'] ?? '') . "&getinfo=".urlencode($_GET['getinfo'] ?? '');

$table_calendarinfo	= "{$SITE['th']}calendarinfo";

if(isset($db)) {
	$sql = "SELECT * FROM {$table_calendarinfo} WHERE `db`='" . db_escape($db) . "'";
	if( !$dbinfo=db_arrayone($sql) )
		back("사용하지 않은 DB입니다.","infoadd.php?mode=user");

	$table_calendar	= "{$SITE['th']}calendar_" . $dbinfo['table_name']; // 게시판 테이블
	$sql_where_cal = " `infouid`='" . (int)$dbinfo['uid'] . "' ";
	$dbinfo['enable_getinfo']='Y';
}
else back("DB 값이 없습니다");

// 넘어온 값에 따라 $dbinfo값 변경
if(($dbinfo['enable_getinfo'] ?? '')=='Y') {
	if(isset($_GET['pern']))			$dbinfo['pern']		= (int)$_GET['pern'];
	if(isset($_GET['row_pern']))		$dbinfo['row_pern']	= (int)$_GET['row_pern'];
	if(isset($_GET['cut_length']))	$dbinfo['cut_length']	= (int)$_GET['cut_length'];
	if(isset($_GET['cateuid']))			$dbinfo['cateuid']		= (int)$_GET['cateuid'];

	// 사이트 해더테일 변경
	if(isset($_GET['html_headpattern']))	$dbinfo['html_headpattern'] = $_GET['html_headpattern'];
	if( isset($_GET['html_headtpl']) && preg_match("/^[_a-z0-9]+$/i", $_GET['html_headtpl'])
		&& is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$_GET['html_headtpl']}.php") )
		$dbinfo['html_headtpl'] = $_GET['html_headtpl'];
}

// 넘어온 mode값 체크
$mode = $_GET['mode'] ?? 'month';

// 넘오온 date값 체크
$req_date = $_GET['date'] ?? date("Y-m-d");
if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0-3]?[0-9]$/", $req_date) ) {
	$req_date = date("Y-m-d");
}
$req_date = date("Y-m-d",strtotime($req_date));

// 각종 날짜변수
$NowThisYear	= date("Y");
$NowThisMonth	= date("m");
$NowThisDay		= date("d");

$intThisTimestamp	= strtotime($req_date);
$intThisYear	= date("Y",$intThisTimestamp);
$intThisMonth	= date("m",$intThisTimestamp);
$intThisDay		= date("d",$intThisTimestamp);
$intThisWeekday	= date("w",$intThisTimestamp);
$weekdays = ["일", "월", "화", "수", "목", "금", "토"];
$varThisWeekday = $weekdays[$intThisWeekday];

$prevMonthDate = strtotime("-1 month", $intThisTimestamp);
$intPrevYear = date("Y", $prevMonthDate);
$intPrevMonth = date("m", $prevMonthDate);

$nextMonthDate = strtotime("+1 month", $intThisTimestamp);
$intNextYear = date("Y", $nextMonthDate);
$intNextMonth = date("m", $nextMonthDate);

$intLastDay		= date('t', $intThisTimestamp);
$intFirstWeekday = date('w', strtotime("{$intThisYear}-{$intThisMonth}-01"));

$thisFullDate	= date("Y년 n월 j일",$intThisTimestamp) . " {$varThisWeekday}요일\n<br>";

// 음력 변환
$sol2lun_date = date("Ymd",$intThisTimestamp);
$sol2lun_sql = "select lunar_date from LunarToSolar where solar_date = '" . db_escape($sol2lun_date) . "'";
$sol2lun_result = db_resultone($sol2lun_sql, 0, "lunar_date");
if ($sol2lun_result) {
	$sol2lun = explode("-", $sol2lun_result);
	$thisFullDate	.= " (음력 {$sol2lun['1']}월 {$sol2lun['2']}일)";
}

// URL Link
$href['today']	= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("mode=day&date=".date("Y-m-d"),$qs_basic), ENT_QUOTES, 'UTF-8');
$href['day']		= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("mode=day&date=".$req_date,$qs_basic), ENT_QUOTES, 'UTF-8');
$href['week']		= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("mode=week&date=".$req_date,$qs_basic), ENT_QUOTES, 'UTF-8');
$href['month']	= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("mode=month&date=".$req_date,$qs_basic), ENT_QUOTES, 'UTF-8');
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 마무리

$val="\\1{$thisUrl}/stpl/{$dbinfo['skin']}/images/";
switch($dbinfo['html_headpattern'] ?? '') {
	case "ht":
		if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
		else
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");
		echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
		break;
	case "h":
		if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
		else
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");
		echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
		break;
	case "t":
		if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
		else
			@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");
		echo ($dbinfo['html_head'] ?? '');
		break;
	case "no":
		break;
	default:
		echo ($dbinfo['html_head'] ?? '');
}
?>
<style type = "text/css">
<!--
	A:link {font-size: 9pt; text-decoration: none; color: "#363688";}
	A:visited {font-size: 9pt; text-decoration: none; color: "#363688";}
	A:hover{font-size: 9pt; text-decoration: none; color: "black";}
	.style1 {
		font-weight: bold;
	}
-->
</style>
<style type="text/css">
<!--
body {
	margin-left: 5px;
	margin-top: 15px;
	margin-right: 5px;
	margin-bottom: 5px;
	background-color:F8F8EA;
}
-->
</style>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
					<td background="/images/admin/tbox_bg.gif"><strong>선수단 일정관리 관리 </strong></td>
					<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
				</tr>
			</table>
			<br>
			<table width='97%' border='0' align="center" cellpadding='4' cellspacing='1' bgcolor='#aaaaaa'>
				<tr height="25" bgcolor="#F8F8EA">
					<td height="25" bgcolor="#F0EBD6" align="center">선수단 일정관리 </td>
				</tr>
				<tr bgcolor="#F8F8EA">
					<td width="97%" bgcolor="#F8F8EA"><table border="0" cellpadding="0" cellspacing="0" width="97%" align="center">
						<tr>
							<td><div align="center">
								<table border="0" cellpadding="2" cellspacing="1" width="100%">
									<tr>
										<td height="30" valign="left"><table width="100%" border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td width="34%" align="center"><span style='font-size:10pt'><b>
<?php
						echo $thisFullDate;
?>
												</b></span></td>
												<td width="66%" align="right"><a href='<?= $href['today'] ?>'><font color="red">▒ </font> 오늘일정(
													<?=date("m월 d일")?>
													) </a> : <a href='<?= $href['day'] ?>'><font color="red">▒ </font> 일별일정 </a> : <a href='<?= $href['week'] ?>'><font color="red">▒ </font> 주별일정 </a> : <a href='<?= $href['month'] ?>'><font color="red">▒ </font> 월별일정 </a> </td>
											</tr>
										</table></td>
									</tr>
								</table>
								<br />
<?php
			if ($mode=="input" || $mode=="edit") {
				include("./inc_input.php");
			}
			elseif($mode=="view") {
				include("./inc_view.php");
			}
			elseif($mode=="day" ) {
				include("./inc_day.php");		
			}
			elseif($mode=="week" ) {
				include("./inc_week.php");		
			}
			elseif($mode=="month" ) {
				include("./inc_month.php");		
			}
?>
							</div></td>
						</tr>
					</table>
					<br /></td>
				</tr>
			</table></td>
	</tr>
</table>
<?php
// 마무리
switch($dbinfo['html_headpattern'] ?? '') {
	case "ht":
		echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
		break;
	case "h":
		echo ($dbinfo['html_tail'] ?? '');
		break;
	case "t":
		echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
		break;
	case "no":
		break;
	default:
		echo ($dbinfo['html_tail'] ?? '');
}
?>
