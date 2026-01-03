<?php
//=======================================================
// 설 명 : 회원db에서 email추출하여 메일 삽입
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/04
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/05/04 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
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

	global $SITE, $db_conn;
	$table_logon = $SITE['th'] . "logon";

	if(!isset($_GET['db']) || !$_GET['db']) back('db값이 넘어오지 않았습니다. err 23');

	// mode값에 따라 처리
	if(isset($_GET['mode'])){
		switch($_GET['mode']){
			case 'all' :
				$sql = "select email from {$table_logon} where yesmail>0";
				break;
			case 'allnospam' :
				$sql = "select email from {$table_logon}";
				break;
			default:
				$sql = "";
		}
		if ($sql){
			$count = insertToDmailTable($sql, $db_conn);
			back("{$count}개의 메일 리스트가 추가되었습니다.");
			exit;
		}
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// $sql_email : email 리스트가 있는 sql문
// - 추가기능 : $sql_email를 통해 리턴되는 필드와 동일한 dmail 필드가 있을 경우 함께 삽입
function insertToDmailTable($sql_email){
	global $SITE, $db_conn;
	
	$table_dmailinfo = $SITE['th'] . "dmailinfo";

	$dbinfo = db_arrayone("SELECT * FROM {$table_dmailinfo} WHERE db='{$_GET['db']}'") or back("db값이 정확하지 않습니다");
	$dbinfo['table'] = $SITE['th'] . "dmail_{$dbinfo['db']}";
	// 해당 dmail 필드 가져옮
	$skip_fields = array ('uid','readtime','status');
	$dmail_fields = userGetAppendFields($dbinfo['table'], $skip_fields);

	$rs_email = db_query($sql_email);
	$count_email = db_count($rs_email);
	$count_insert = 0; // init
	for($i=0;$i<$count_email;$i++){
		$list = db_array($rs_email);

		// sql_set 만들기
		$sql_set = array();
		foreach($list as $key =>	$value){
			if(in_array($key,$dmail_fields)){
				$sql_set[] = "$key='" . (isset($list[$key]) ? $list[$key] : '') . "'";
			}
		}
		$sql_set_str = implode(',',$sql_set);

		$sql = "insert ignore into {$dbinfo['table']} set {$sql_set_str}";
		db_query($sql);
		if(db_count()){
			echo (isset($list['email']) ? $list['email'] : '') . " 메일추가<br>\n";
			$count_insert++;
		}
		else echo (isset($list['email']) ? $list['email'] : '') . " 메일추가실패<br>\n";
	}

	return $count_insert;
}

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
