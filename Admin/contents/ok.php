<?php
//=======================================================
// 설	명 : 게시판 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/13
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/03/06 박선민 delete_ok() 버그 수정
// 03/10/13 박선민 마지막 수정
// 24/05/21 Gemini PHP 7 마이그레이션
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=array(
	'priv' => 10, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useImage' => 1, // thumbnail()
	'useClassSendmail' =>	1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
// page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//=================================================== // PHP 7에서 $HTTP_HOST 대신 $_SERVER['HTTP_HOST'] 사용

// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================

// 기본 URL QueryString
$qs_basic = "db=" . ($_REQUEST['db'] ?? '') .			//table 이름
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
			"&goto=" . ($_REQUEST['goto'] ?? '').
			"&page=" . ($_REQUEST['page'] ?? '');

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$qs=array(
			"title" =>	"post,trim",
			"db" =>	"post,trim",
			"content" =>	"post,trim",
			"data1" =>	"post,trim",
			"data2" =>	"post,trim",
			"data3" =>	"post,trim",
			"data4" =>	"post,trim",
			"data5" =>	"post,trim",
			"data6" =>	"post,trim",
			"data7" =>	"post,trim",
			"data8" =>	"post,trim",
			"data9" =>	"post,trim",
			"data10" =>	"post,trim"
	);
	

if(!isset($sql_where)) $sql_where = " 1 ";
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// info 테이블 정보 가져와서 $dbinfo로 저장
	
if(isset($_REQUEST['mode'])){	
	switch($_REQUEST['mode']){
		case 'modify':
			modify_ok();
			back("컨텐트가 수정되었습니다.");
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다 (1)");
	}
} else {
	back("잘못된 웹 페이지에 접근하였습니다 (2)");
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function modify_ok(){
	global $qs, $dbinfo, $table, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,trim,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);

	// 값 추가
	$qs['ip']		= remote_addr();
	
	$qs['bid']	= $_SESSION['seUid'] ?? 0;
	switch($dbinfo['enable_userid'] ?? 'userid'){
		case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
		case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
		default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
	}
	$qs['email']	= $_SESSION['seEmail'] ?? '';
	
	$sql_set=", `content`		=	'" . db_escape($qs['content']) . "'
				, `data1`		=	'" . db_escape($qs['data1']) . "'
				, `data2`		=	'" . db_escape($qs['data2']) . "'
				, `data3`		=	'" . db_escape($qs['data3']) . "'
				, `data4`		=	'" . db_escape($qs['data4']) . "'
				, `data5`		=	'" . db_escape($qs['data5']) . "'
				, `data6`		=	'" . db_escape($qs['data6']) . "'
				, `data7`		=	'" . db_escape($qs['data7']) . "'
				, `data8`		=	'" . db_escape($qs['data8']) . "'
				, `data9`		=	'" . db_escape($qs['data9']) . "'
				, `data10`		=	'" . db_escape($qs['data10']) . "' ";
	
	$sql = "UPDATE
				{$table}
			SET
				userid	='" . db_escape($qs['userid']) . "',
				email	='" . db_escape($qs['email']) . "',
				rdate	=UNIX_TIMESTAMP(),
				ip		=	'" . db_escape($qs['ip']) . "'
				{$sql_set}
			WHERE
				uid=" . (int)$qs['uid'] . "
		";
	db_query($sql);

	return true;
} // end func.
?>
