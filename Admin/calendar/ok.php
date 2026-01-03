<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/09/17
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/09/17 박선민 마지막 수정
// 25/08/11 Gemini	PHP 7.x, MariaDB 호환성 업데이트
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
	'useBoard2' => 1, // href_qs(),privAuth()
	'useApp' => 1, // remote_addr()
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================

// 기본 URL QueryString
$qs_basic = "db=" . ($_REQUEST['db'] ?? ($table ?? '')) .			//table 이름
			"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
			"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
			"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
			"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
			"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
			"&sc_string=" . urlencode(stripslashes($_REQUEST['sc_string'] ?? '')) . //search string
			"&team=" . ($_REQUEST['team'] ?? '').
			"&html_headtpl=" . ($_REQUEST['html_headtpl'] ?? '').
			"&pid=" . ($_REQUEST['pid'] ?? '').
			"&pname=" . ($_REQUEST['pname'] ?? '').
			"&page=" . ($_REQUEST['page'] ?? '');
			
$_REQUEST['getinfo'] = $_REQUEST['getinfo'] ?? '';
if($_REQUEST['getinfo'] == "cont")
	$qs_basic .= "&html_headpattern=" . ($_REQUEST['html_headpattern'] ?? '') . "&html_headtpl=" . ($_REQUEST['html_headtpl'] ?? '') . "&pern=" . ($_REQUEST['pern'] ?? '') . "&skin=" . ($_REQUEST['skin'] ?? '') . "&getinfo=" . ($_REQUEST['getinfo'] ?? '');
	

$table_calendarinfo = ($SITE['th'] ?? '') . "calendarinfo";
$table_logon	= ($SITE['th'] ?? '') . "logon";
$table_groupinfo= ($SITE['th'] ?? '') . "groupinfo";

if($_REQUEST['db'] ?? ''){
	$db_safe = db_escape($_REQUEST['db']);
	$sql = "SELECT * FROM {$table_calendarinfo} WHERE db='{$db_safe}'";
	if( !$dbinfo=db_arrayone($sql) )
		back("사용하지 않은 DB입니다.","infoadd.php?mode=user");

	$table_calendar	= "{$SITE['th']}calendar_" . $dbinfo['table_name']; // 게시판 테이블
}
else back("DB 값이 없습니다");

// 공통적으로 사용할 $qs
$qs=array(
		"uid" =>	"post,trim",
		"title" =>	"post,trim",
		"place" =>	"post,trim",
		"kind" =>	"post,trim",
		"priv_level" =>	"post,trim",
		"startdate" =>	"post,trim",
		"dtype" =>	"post,trim",
		"content" =>	"post,trim",
		"starthour" =>	"post,trim",
		"startmin" =>	"post,trim",
		"endhour" =>	"post,trim",
		"endmin" =>	"post,trim",
		"retimes" =>	"post,trim",
		"retype" =>	"post,trim",
		"enddate" =>	"post,trim",
	);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
$mode = $_REQUEST['mode'] ?? '';
switch($mode){
	case 'input':
		$uid = write_ok($table_calendar,$qs);
		go_url("./index.php?" . href_qs("uid={$uid}",$qs_basic));
		break;
	case 'edit':
		modify_ok($table_calendar,$qs,"uid");
		go_url("./index.php?" . href_qs("uid=" . ($_REQUEST['uid'] ?? ''),$qs_basic));
		break;
	case 'delete':
		delete_ok($table_calendar,"uid");
		go_url("./index.php?" . href_qs("",$qs_basic));
		break;	
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok($table,$qs){
	global $dbinfo;
	// 권한체크
	if(!privAuth($dbinfo, "priv_write")) back("일정 추가 권한이 없습니다");

	// 넘어온값 체크
	$qs=check_value($qs);
	if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0123]?[0-9]$/",$qs['startdate']) ){
		back("시작날짜가 잘못되었습니다");
	}
	if(($qs['retimes'] ?? 0) > 0) {// 반복설정이면
		$qs['startdate'] = date("Y-m-d",strtotime($qs['startdate']));
		if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0123]?[0-9]$/",$qs['enddate']) ){
			back("반복날짜가 잘못되었니다");
		}
		$qs['enddate'] = date("Y-m-d",strtotime($qs['enddate']));
	}

	if (empty($qs['dtype'])){
		$qs['dtype'] = "day";
	} elseif ($qs['dtype'] == "week" )	{
		$qs['starthour']	= "00";
		$qs['startmin']	= "00";
		$qs['endhour']	= "23";
		$qs['endmin']	= "59";
	} elseif ($qs['dtype'] == "month" )	{
		$qs['startdate']	= substr($qs['date'],0,-2) . "01";
		$qs['starthour']	= "00";
		$qs['startmin']	= "00";
		$qs['endhour']	= "23";
		$qs['endmin']		= "59";
		$qs['retimes']	= "0"; // 월중행사는 반복하지 않음
	}

	// 값 추가
	$qs['bid']	= (int)($_SESSION['seUid'] ?? 0);

	// $sql 완성
	$sql_set	= " ,
					`bid`	='" . db_escape($qs['bid']) . "',
					`infouid`	='" . db_escape($dbinfo['uid']) . "'
				"; // $sql_set 시작
	$sql="INSERT
			INTO
				{$table}
			SET
				`title`		='" . db_escape($qs['title']) . "',
				`place`		='" . db_escape($qs['place']) . "',
				`kind`		='" . db_escape($qs['kind']) . "',
				`priv_level`='" . db_escape($qs['priv_level']) . "',
				`startdate`	='" . db_escape($qs['startdate']) . "',
				`dtype`		='" . db_escape($qs['dtype']) . "',
				`content`	='" . db_escape($qs['content']) . "',
				`starthour`	='" . db_escape($qs['starthour']) . "',
				`startmin`	='" . db_escape($qs['startmin']) . "',
				`endhour`	='" . db_escape($qs['endhour']) . "',
				`endmin`	='" . db_escape($qs['endmin']) . "',
				`retimes`	='" . db_escape($qs['retimes']) . "',
				`retype`	='" . db_escape($qs['retype']) . "',
				`enddate`	='" . db_escape($qs['enddate']) . "'
				{$sql_set}
		";
	db_query($sql);

	return db_insert_id();
} // end func write_ok

function modify_ok($table,$qs,$field="uid"){
	global $dbinfo;
	// 권한체크
	if(!privAuth($dbinfo, "priv_write")) back("일정 추가 권한이 없습니다");

	// 넘어온값 체크
	$qs=check_value($qs);
	if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0123]?[0-9]$/",$qs['startdate']) ){
		back("시작날짜가 잘못되었습니다");
	}
	if(($qs['retimes'] ?? 0) > 0) {// 반복설정이면
		$qs['startdate'] = date("Y-m-d",strtotime($qs['startdate']));
		if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0123]?[0-9]$/",$qs['enddate']) ){
			back("반복날짜가 잘못되었니다");
		}
		$qs['enddate'] = date("Y-m-d",strtotime($qs['enddate']));
	}

	if (empty($qs['dtype'])){
		$qs['dtype'] = "day";
	} elseif ($qs['dtype'] == "week" )	{
		$qs['starthour']	= "00";
		$qs['startmin']	= "00";
		$qs['endhour']	= "23";
		$qs['endmin']	= "59";
	} elseif ($qs['dtype'] == "month" )	{
		$qs['startdate']	= substr($qs['date'],0,-2) . "01";
		$qs['starthour']	= "00";
		$qs['startmin']	= "00";
		$qs['endhour']	= "23";
		$qs['endmin']		= "59";
		$qs['retimes']	= "0"; // 월중행사는 반복하지 않음
	}

	// 값 추가
	$qs['bid']	= (int)($_SESSION['seUid'] ?? 0);

	// 해당 데이터 읽기
	$sql_where	= " infouid	='" . db_escape($dbinfo['uid']) . "' "; // $sql_where 시작
	$sql = "SELECT * FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!privAuth($dbinfo, "priv_delete")){
		if(($list['bid'] ?? '') != ($_SESSION['seUid'] ?? '')) back("수정 권한이 없습니다");
	}

	// $sql 완성
	$sql="UPDATE
				{$table}
			SET
				`title`		='" . db_escape($qs['title']) . "',
				`place`		='" . db_escape($qs['place']) . "',
				`kind`		='" . db_escape($qs['kind']) . "',
				`priv_level`='" . db_escape($qs['priv_level']) . "',
				`startdate`	='" . db_escape($qs['startdate']) . "',
				`dtype`		='" . db_escape($qs['dtype']) . "',
				`content`	='" . db_escape($qs['content']) . "',
				`starthour`	='" . db_escape($qs['starthour']) . "',
				`startmin`	='" . db_escape($qs['startmin']) . "',
				`endhour`	='" . db_escape($qs['endhour']) . "',
				`endmin`	='" . db_escape($qs['endmin']) . "',
				`retimes`	='" . db_escape($qs['retimes']) . "',
				`retype`	='" . db_escape($qs['retype']) . "',
				`enddate`	='" . db_escape($qs['enddate']) . "'
			WHERE
				`{$field}`='" . db_escape($qs[$field]) . "'
			AND
				 $sql_where 
		";
	db_query($sql);

	return db_count();
} // end func write_ok

function delete_ok($table,$field="uid"){
	global $dbinfo;

	$qs=array(
			"$field" =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " infouid	='" . db_escape($dbinfo['uid']) . "' "; // $sql_where 시작
	$sql = "SELECT * FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!privAuth($dbinfo, "priv_delete")){
		if(($list['bid'] ?? '') != ($_SESSION['seUid'] ?? '')) back("삭제 권한이 없습니다");
	}

	db_query("DELETE FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ");

	return db_count();
} // end func delete_ok
?>
