<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/02/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/02/26 박선민 처음제작
// 04/02/26 박선민 마지막수정
// 25/08/11 Gemini	PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useCheck' => 1, // 값 체크함수
	'useSkin' =>	1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

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
$qs_basic = "db=" . ($_REQUEST['db'] ?? $table) .			//table 이름
			"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
			"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
			"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
			"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
			"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
			"&sc_string=" . urlencode(stripslashes(isset($sc_string) ? $sc_string : '')) . //search string
			"&team=" . ($_REQUEST['team'] ?? '').
			"&html_headtpl=" . (isset($html_headtpl) ? $html_headtpl : '').
			"&pid=" . ($_REQUEST['pid'] ?? '').
			"&pname=" . ($_REQUEST['pname'] ?? '').
			"&goto=" . ($_REQUEST['goto'] ?? '').
			"&page=" . ($_REQUEST['page'] ?? '');

$qs_basic		= href_qs($qs_basic); // 해당값 초기화

$table		= $SITE['th'] . "board2info";

$qs=array(
		"uid" =>	"post,trim,notnull" . urlencode("고유 일련번호가 넘어오지 않았습니다."),
		"db" =>	"post,trim",
		"title" =>	"post,trim"
	);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'basic':
		basic_ok($table,$qs);
		back("수정되었습니다.", isset($_REQUEST['goto']) ? $_REQUEST['goto'] : "list.php");
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function basic_ok($table,$qs){
	global $dbinfo;
	// 권한체크
	if(!privAuth($dbinfo, "priv_write")) back("추가 권한이 없습니다");

	$qs=check_value($qs);

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'uid', 'bid', 'gid', 'db', 'rdate');
	if($fieldlist = userGetAppendFields($table, $skip_fields)){
		foreach($fieldlist as $value){
			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
		} // end foreach
	} // end if

	////////////////////////////////

	$sql = "UPDATE {$table} SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set}
			WHERE
				uid=".$qs['uid']."
		";
	
	db_query($sql);

} // end func write_ok

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>
