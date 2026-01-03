<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/09/18
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/09/18 박선민 마지막 수정
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 ($HTTP_HOST, 배열 키 따옴표, 버그 수정)
// 25/11/07 Gemini AI SQL 구문 변수 중괄호 {} 및 WHERE 절 따옴표 오류 수정
//=======================================================
$HEADER=array(
	'priv' => 2, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
	'useBoard2' => 1, // privAuth()
	'useApp' => 1, // remote_addr()
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 기본 URL QueryString
$qs_basic = "";

$table_calendarinfo	= $SITE['th'] . "calendarinfo";
$table_groupinfo	= $SITE['th'] . "groupinfo";

// 기본 URL QueryString
$qs_basic = "";

// dbinfo 설정
$dbinfo=array();

// 공통적으로 사용할 $qs
$qs=array(
		"uid" =>	"post,trim",
		"groupid" =>	"post,trim",
		"title" =>	"post,trim",
		"cut_length" =>	"post,trim",
		"cut_content" =>	"post,trim",
		"priv_list" =>	"post,trim",
		"priv_read" =>	"post,trim",
		"priv_write" =>	"post,trim",
		"priv_delete" =>	"post,trim",
		"html_headpattern" =>	"post,trim",
		"html_headtpl" =>	"post,trim",
		"html_head" =>	"post,trim",
		"html_tail" =>	"post,trim",
	);
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'user':
	case 'group':
		$uid = write_ok($table_calendarinfo,$qs);
		if($_POST['mode'] == "user")
			$href['list'] = "/scalendar/index.php?db={$_SESSION['seUserid']}";
		elseif($_POST['mode'] == "group")
			$href['list'] = "/scalendar/index.php?db=@{$_POST['groupid']}"; // [!] FIX: URL 끝 { 제거
		go_url($href['list']);
		break;
	case 'infomodify':
		modify_ok($table_calendarinfo,$qs,"uid");
		go_url("./index.php?" . href_qs("uid={$_REQUEST['uid']}",$qs_basic));
		break;
	case 'infodelete':
		delete_ok($table_calendarinfo,"uid");
		go_url("./list.php?" . href_qs("",$qs_basic));
		break;	
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok($table,$qs){
	global $dbinfo, $table_calendarinfo,$table_groupinfo;

	//$qs['userid']	= "post,trim";

	// 넘어온값 체크
	$qs=check_value($qs);

	// 값 추가
	if($_POST['mode'] == "user"){
		$href['list'] = "/scalendar/index.php?db={$_SESSION['seUserid']}";

		$sql = "SELECT * from {$table_calendarinfo} WHERE db='{$_SESSION['seUserid']}'";
		if($list=db_arrayone($sql)){
			if($list['bid'] == $_SESSION['seUid']){
				back("이미 일정칼렌더가 생성되어있습니다 . 이동합니다",$href['list']);
			} else {
				back("관리자에게 문의하셔야 합니다.\\n다른 회원이 사용하여 일정칼렌더 생성이 불가능합니다");
			}
		}
		$qs['db']		= $_SESSION['seUserid'];
		$qs['bid']	= $_SESSION['seUid'];
		$qs['gid']	= 0;
	}
	elseif($_POST['mode'] == "group"){
		$href['list'] = "/scalendar/index.php?db=@{$_POST['groupid']}"; // [!] FIX: URL 끝 { 제거

		$sql = "SELECT * from {$table_calendarinfo} WHERE db='@{$_POST['groupid']}'"; // [!] FIX: _GET -> _POST
		if($list=db_arrayone($sql)){
			back("이미 그룹 일정칼렌더가 생성되어있습니다 . 이동합니다",$href['list']);
		} else { // 그룹정보가져와서 그룹개설자인지 여부
			// [!] FIX: _GET -> _POST, {$bid} -> bid (변수 -> 컬럼명)
			$sql = "SELECT * from {$table_groupinfo} WHERE groupid='{$_POST['groupid']}' and bid='{$_SESSION['seUid']}'"; 
			if(!$groupinfo=db_arrayone($sql)) back("해당 그룹이 없거나 그룹개설자가 아님니다");
		}
		$qs['db']		= "@{$_POST['groupid']}"; // [!] FIX: _GET -> _POST
		$qs['bid']	= $_SESSION['seUid'];
		$qs['gid']	= $groupinfo['uid'];
	}
	else back("잘못된 요청입니다");

	// $sql 완성
	$sql_set	= ",db='{$qs['db']}',bid='{$qs['bid']}',gid='{$qs['gid']}'"; // $sql_set 시작
	// [!] FIX: INTO 뒤 특수공백(NBSP) 제거
	// [!] FIX: SET 절 변수에 중괄호 {} 추가
	$sql="INSERT
			INTO 
				$table
			SET
				`title`			={$qs['title']},
				`cut_length`	={$qs['cut_length']},
				`cut_content`	={$qs['cut_content']},
				`priv_list`		={$qs['priv_list']},
				`priv_read`		={$qs['priv_read']},
				`priv_write`	={$qs['priv_write']},
				`priv_delete`	={$qs['priv_delete']},
				`html_headpattern`	={$qs['html_headpattern']},
				`html_headtpl`	={$qs['html_headtpl']},
				`html_head`		={$qs['html_head']},
				`html_tail`		={$qs['html_tail']}
				{$sql_set}
		";
	db_query($sql);

	return db_insert_id();
} // end func write_ok

function modify_ok($table,$qs,$field){
	global $dbinfo;

	//$qs['userid']	= "post,trim";
	// 넘어온값 체크
	$qs=check_value($qs);

	// 값 추가

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	// [!] FIX: and 뒤 특수공백(NBSP) 제거
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	$sql = "SELECT * FROM {$table} WHERE {$field}={$qs[$field]} and $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	// [!] FIX: PHP 7+ 배열 키 따옴표 추가
	$auth	= array('bid' => $list['bid'],'gid' => $list['gid'],'priv_level' => 99);
	if(!privAuth($auth, "priv_level",1)) back("수정 권한이 없습니다");
	unset($auth);

	// $sql 완성
	// [!] FIX: $sql_where 뒤 특수공백(NBSP) 제거
	// [!] FIX: SET 절 변수에 중괄호 {} 추가
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	$sql="UPDATE
				$table
			SET
				`title`			={$qs['title']},
				`cut_length`	={$qs['cut_length']},
				`cut_content`	={$qs['cut_content']},
				`priv_list`		={$qs['priv_list']},
				`priv_read`		={$qs['priv_read']},
				`priv_write`	={$qs['priv_write']},
				`priv_delete`	={$qs['priv_delete']},
				`html_headpattern`	={$qs['html_headpattern']},
				`html_headtpl`	={$qs['html_headtpl']},
				`html_head`		={$qs['html_head']},
				`html_tail`		={$qs['html_tail']}
			WHERE
				{$field}={$qs[$field]}
			AND
				 $sql_where 
		";
	db_query($sql);

	return db_count();
} // end func modify_ok

function delete_ok($table,$field){
	global $dbinfo;
	$qs=array(
			"$field" =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	// [!] FIX: and 뒤 특수공백(NBSP) 제거
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	$sql = "SELECT * FROM {$table} WHERE {$field}={$qs[$field]} and $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한 체크(자기 글, 관리자이면 모든 권한)
	// [!] FIX: PHP 7+ 배열 키 따옴표 추가
	$auth	= array('bid' => $list['bid'],'gid' => $list['gid'],'priv_level' => 99);
	if(!privAuth($auth, "priv_level",1)) back("삭제 권한이 없습니다");
	unset($auth);

	// [!] FIX: DELTE -> DELETE, AND 뒤 특수공백(NBSP) 제거
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	db_query("DELETE FROM {$table} WHERE {$field}={$qs[$field]} AND $sql_where ");

	return db_count();
} // end func delete_ok; ?>