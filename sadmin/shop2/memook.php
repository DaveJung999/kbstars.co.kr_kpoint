<?php
//=======================================================
// 설  명 : 쇼핑몰 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/12 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환)
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // file_upload(),remote_addr()
	'useCheck'	=>1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'shop2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'이 빠져야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic = "mode=&db={$_REQUEST['db']}&cateuid={$_REQUEST['cateuid']}&pern={$_REQUEST['pern']}&cut_length={$_REQUEST['cut_length']}";
	if($_REQUEST['getinfo']=='cont') 
	$qs_basic .= "&={$_REQUEST[]}&html_skin={$_REQUEST['html_skin']}&skin={$_REQUEST['skin']}";

	// table
	$table_dbinfo	= $SITE['th'].$prefix.'info';

	// 3. info 테이블 정보 가져와서 $dbinfo로 저장
	if($_REQUEST['db']) {
		$sql = "SELECT * from {$table_dbinfo} WHERE db='{$_REQUEST['db']}' LIMIT 1";
		$dbinfo=db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');

		// redirect 유무
		if($dbinfo['redirect']) go_url($dbinfo['redirect']);

		$dbinfo['table']	= $SITE['th'].$prefix.'_'.$dbinfo['db'].'_memo'; // 게시판 테이블
	}
	else back('DB 값이 없습니다');
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case "memolevel_zero":
		memoLevel($dbinfo['table'],0); 
		back();
		break;
	case "memolevel_high":
		memoLevel($dbinfo['table'],99);
		back();
		break;
	case "memodeletes":
		memoDeletes($dbinfo['table']);
		back();
		break;
	case "memodelete":
		// $table_memo 변수가 정의되지 않았으므로 dbinfo['table']을 사용
		memoDelete_uid($dbinfo['table'],"uid");
		back();
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function memoLevel($table,$priv_hidelevel) {
	GLOBAL $dbinfo;
	// 권한체크
	if(!$dbinfo['enable_memo']=='Y') back("메모 기능을 사용하고 있지 않습니다.");
	// 권한 검사
	if(!privAuth($dbinfo, 'priv_reply')) back('레벨설정 할 권한이 없습니다.');

	if(is_array($_POST['uids']) and count($_POST['uids'])) {
		foreach($_POST['uids'] as $value) {
			if($value) {
				// [!] FIX: $dbinfo['table'] 변수 중괄호 {} 적용
				$sql="update {$dbinfo['table']} set priv_hidelevel='{$priv_hidelevel}' where uid='{$value}'";
				db_query($sql);
			}
		}
	}
}
function memoDeletes($table) {
	GLOBAL $dbinfo;
	// 권한체크
	if(!$dbinfo['enable_memo']=='Y') back("메모 기능을 사용하고 있지 않습니다.");
	if(!privAuth($dbinfo, 'priv_delete')) back("삭제 권한이 없습니다");

	if(is_array($_POST['uids']) and count($_POST['uids'])) {
		foreach($_POST['uids'] as $value) {
			if($value) {
				// [!] FIX: $dbinfo['table'] 변수 중괄호 {} 적용
				$sql="delete from {$dbinfo['table']} where uid='{$value}'";
				db_query($sql);
			}
		}
	}
}
function memoDelete_uid($table,$uid) {
	GLOBAL $dbinfo;
	// 권한체크
	if(!$dbinfo['enable_memo']=='Y') back("메모 기능을 사용하고 있지 않습니다.");
	if(!boardAuth($dbinfo, "priv_delete")) back("삭제 권한이 없습니다");

	if($uid) {
		// [!] FIX: $table 변수 중괄호 {} 적용
		$sql	= "delete from {$table} where uid='{$uid}'";
		db_query($sql);
	}
}

// 추가 입력해야할 필드
function userGetAppendFields($table,$skip_fields='') { // 05/02/03 박선민
	global $SITE;

	if(!is_array($skip_fields) or sizeof($skip_fields)<1)
		$skip_fields = array();
	
	$fieldlist = array();
	
	// [!] FIX: mysql_* 함수들을 db_query/db_array/db_free 함수를 사용하는 SHOW COLUMNS로 대체
	$safe_table = '`' . str_replace('`', '', $table) . '`';
	$safe_db = '`' . str_replace('`', '', $SITE['database']) . '`';
	
	$sql = "SHOW COLUMNS FROM {$safe_table} IN {$safe_db}";
	$result = db_query($sql);

	if ($result) {
		// db_array()가 mysqli_fetch_assoc() 처럼 연관 배열을 반환한다고 가정
		while ($row = db_array($result)) {
			$a_fields = $row['Field'];
			
			if(!in_array($a_fields,$skip_fields)) {
				$fieldlist[] = $a_fields;
			}
		}
		db_free($result);
	}

	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}
?>