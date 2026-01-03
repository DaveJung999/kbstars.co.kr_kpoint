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
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// Ready.. . (변수 초기화 및 넘어온값 필터링)
//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num', 'mid', 'cur_sid', 'pay_cate', 'writeinfo', 'data1', 'data2'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

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
			"&page=" . ($_REQUEST['page'] ?? '');

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$thisPath	= dirname(__FILE__);
$thisUrl	= "/admin/recommend"; // 마지막 "/"이 빠져야함

$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) : dirname(__FILE__) ;
// 넘어온값 기본 처리
$sql_set="";
$sql_where = '';

// $sql_where, $sql_set
if(($dbinfo['enable_type'] ?? '') == 'Y'){
	$sql_where	= ($mode == "write" && $writeinfo == "info") ? " type='info' " : " type='docu' ";
	$sql_set	= ($mode == "write" && $writeinfo == "info") ? ", type='info' " : ", type='docu' ";
}

$qs=array(
			'querystr' =>	"post,trim,notnull=" . urlencode("선택된 강좌가 없습니다."),
			"count" =>	"post,trim"
	);
	
if(empty($sql_where)) $sql_where = "1";
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// info 테이블 정보 가져와서 $dbinfo로 저장
	
if(isset($mode)){	
	switch($mode){
		case 'modify_todaysbook':
			modify_todaysbook_ok();
			go_url("todaysbook.php?" . href_qs("uid={$uid}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'modify_newbook':
			modify_newbook_ok();
			go_url("newbook.php?" . href_qs("uid={$uid}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'modify_recmdbook':
			modify_recmdbook_ok();
			go_url("recmdbook.php?" . href_qs("uid={$uid}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'hist_write':
			hist_write_ok();
			back();
			break;
		case 'hist_del':
			hist_del_ok();
			back();
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다");
	}
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function modify_todaysbook_ok(){
	global $table;

	$qs=array(
		'querystr' =>	"post,trim,notnull=" . urlencode("선택된 강좌가 없습니다."),
	);
	$qs=check_value($qs);
	
	$sql = "UPDATE
				{$table}
			SET
				`data11`	='0'
		";
	db_query($sql);

	$data = explode("|", $qs['querystr']);
	$count = count($data);
	
	for($i=0; $i<$count;$i++)
	{
		$uid_data = explode(";", $data[$i]);
		$uid = (int)$uid_data['0'];
		if ($uid > 0) {
			$sql_where = " uid={$uid} ";

			// 값 추가
			$sql = "UPDATE
						{$table}
					SET
						`data11`	=" . ($i + 1) . "
					WHERE
						 $sql_where 
				";
			db_query($sql);
		}
	}
	return true;
} // end func.

function modify_newbook_ok(){
	global $table;

	$qs=array(
		'querystr' =>	"post,trim,notnull=" . urlencode("선택된 강좌가 없습니다."),
	);
	$qs=check_value($qs);
	
	$sql = "UPDATE
				{$table}
			SET
				`data12`	='0'
		";
	db_query($sql);
	$data = explode("|", $qs['querystr']);
	$count = count($data);
	
	for($i=0; $i<$count;$i++)
	{
		$uid_data = explode(";", $data[$i]);
		$uid = (int)$uid_data['0'];
		if ($uid > 0) {
			$sql_where = " uid={$uid} ";

		// 값 추가
			$sql = "UPDATE
						{$table}
					SET
						`data12`	=" . ($i + 1) . "
					WHERE
						 $sql_where 
				";
			db_query($sql);
		}
	}
	return true;
} // end func.

function modify_recmdbook_ok(){
	global $table;

	$qs=array(
		'querystr' =>	"post,trim,notnull=" . urlencode("선택된 강좌가 없습니다."),
	);
	$qs=check_value($qs);
	
	$sql = "UPDATE
				{$table}
			SET
				`data13`	='0'
		";
	db_query($sql);

	$data = explode("|", $qs['querystr']);
	$count = count($data);
	
	for($i=0; $i<$count;$i++)
	{
		$uid_data = explode(";", $data[$i]);
		$uid = (int)$uid_data['0'];
		if ($uid > 0) {
			$sql_where = " uid={$uid} ";

			// 값 추가
			$sql = "UPDATE
						{$table}
					SET
						`data13`	=" . ($i + 1) . "
					WHERE
						 $sql_where 
				";
			db_query($sql);
		}
	}
	return true;
} // end func.

function hist_write_ok(){
	global $dbinfo, $table, $table_hist, $data1, $data2, $db_conn, $_SESSION;

	$qs=array(
		'querystr' =>	"post,trim,notnull=" . urlencode("선택된 강좌가 없습니다."),
	);
	$qs=check_value($qs);
	
	$data = explode("|", $qs['querystr']);
	$count = count($data);
	
	$qs['content'] = '';
	$qs['title'] = '';

	for($i=0; $i<$count;$i++)
	{
		$uid_data = explode(";", $data[$i]);
		$uid = (int)$uid_data['0'];

		if ($uid > 0) {
			$sql_where = " uid={$uid} ";

			// 값 추가
			$sql = "select * from {$table}	WHERE $sql_where ";
			$list = db_arrayone($sql);
			if ($list) {
				$qs['content'] .= " *** {$list['title']} ***
- 구분 : {$list['catetitle']}
- 도서명 : {$list['title']}
- 지은이 : {$list['data2']}\n
";
								
				$qs['title'] .= "{$list['title']} ,	";
			}
		}
	}
	
	
	if(isset($_POST['docu_type']) and strtolower($_POST['docu_type']) != "html") $qs['docu_type']="text";
	else $qs['docu_type'] = 'html';

	$qs['priv_level']=(int)($_POST['priv_level'] ?? 0);
	if(isset($_POST['catelist'])) $qs['cateuid'] = $_POST['catelist'];

	// 값 추가
	if(isset($_SESSION['seUid'])){
		$qs['bid']	= $_SESSION['seUid'];
		switch($dbinfo['enable_userid']){
			case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
			case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
			default			: $qs['userid'] = $_SESSION['seUserid']; break;
		}
		$qs['email']	= $_SESSION['seEmail'];
	} else {
		$qs['email']	= check_email($_POST['email'] ?? '');
	}
	$qs['ip']		= remote_addr();
	// - num의 최대값 구함
	
	$sql = "SELECT max(num) FROM {$table_hist}";
	$qs['num'] = db_resultone($sql,0,"max(num)") + 1;

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$sql_set = '';
	$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate', 'data1', 'data2');
	if($fieldlist = userGetAppendFields($table, $skip_fields)){
		foreach($fieldlist as $value){
			if(isset($_POST[$value])) $sql_set .= ", `{$value}` = '" . $_POST[$value] . "' ";
		}
	}
	$sql_set_file = ''; // 파일 업로드 로직이 없으므로 빈 값으로 초기화

	$sql="INSERT
			INTO
				{$table_hist}
			SET
				`num`		='{$qs['num']}',
				`bid`		='{$qs['bid']}',
				`userid`	='{$qs['userid']}',
				`passwd`	=password('{$_POST['passwd']}'),
				`email`	='{$qs['email']}',
				`title`	='{$qs['title']}',
				`content`	='{$qs['content']}',
				`docu_type`='{$qs['docu_type']}',
				`rdate`	= UNIX_TIMESTAMP(),
				`ip`		='{$qs['ip']}',
				`cateuid` ='{$qs['cateuid']}',
				`data1`='{$data1}',
				`data2`='{$data2}',
				`priv_level`	='{$qs['priv_level']}'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	$uid = db_insert_id();
} // end func.
// 삭제
function hist_del_ok(){
	global $table_hist, $uid;

	if (empty($uid)){
		back("고유번호가 넘어오지 않았습니다");
	}
	
	$sql = "DELETE FROM {$table_hist} where uid=" . (int)$uid;
	db_query($sql);

} // end func delete_ok()

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
