<?php
//=======================================================
// 설	명 : 관리자 회원 관리 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용
		'useApp' => 1, // file_upload(),remote_addr()
		'useCheck' => 1, // check_value()
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	global $SITE;

	// 기본 URL QueryString
	// 원본 코드의 변수들을 안전하게 가져오기 위해 null 병합 연산자 사용
	//===================================================
	// REQUEST 값 대입......2025-09-10
	$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num', 'mid', 'cur_sid', 'pay_cate'];
	foreach ($params as $param) {
		$$param = $_REQUEST[$param] ?? $$param ?? null;
	}
	//===================================================

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
				"&mid=" . ($_REQUEST['mid'] ?? '').
				"&s_id=" . ($_REQUEST['s_id'] ?? '').
				"&cur_sid=" . ($_REQUEST['cur_sid'] ?? '').
				"&sdate=" . ($_REQUEST['sdate'] ?? '').
				"&edate=" . ($_REQUEST['edate'] ?? '').
				"&search=" . ($_REQUEST['search'] ?? '').
				"&pay_cate=" . ($_REQUEST['pay_cate'] ?? '').
				"&term_id=" . ($_REQUEST['term_id'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');
				;

	include_once("{$thisPath}/dbinfo.php");	// $dbinfo 가져오기
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? '') {	
	case 'userinfomodify' :
		$accno = userinfoModify($dbinfo);
		back_close("회원 카드 번호가 등록되었습니다 . (카드번호 : {$accno})", "/kbstars/2011/d09/02.php");
		break;
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================

function userinfoModify($dbinfo){
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족) . 확인바랍니다.");

	// $qs 추가,변경
	$qs=array(
				'bid' =>  "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
				'accountno' =>  "post,trim"
		);
	$qs=check_value($qs);

	// SQL Injection 방지
	$bid_safe = db_escape($qs['bid']);
	$accountno_safe = db_escape($qs['accountno']);

	//다른 사람의 카드번호가 있는지 확인... . 카드번호 중복확인
	$sql_dup = "SELECT uid FROM `{$dbinfo['table_logon']}` WHERE accountno = '{$accountno_safe}' AND uid <> '{$bid_safe}'";
	$result_dup = db_query($sql_dup);
	if($result_dup && db_count($result_dup) > 0) {
		back("이미 등록된 카드번호 입니다 . \\n\\n자기 카드번호를 정확히 입력하셨는데도 계속 이 메세지를 보시면 \\n관리자에게 문의해 주세요.");
	}
	
	//해당 회원의 정보 가져오기
	$sql_logon = "SELECT uid, userid, name, priv, accountno FROM `{$dbinfo['table_logon']}` WHERE uid = '{$bid_safe}'";
	$result_logon = db_query($sql_logon);
	$list_logon = $result_logon ? db_array($result_logon) : null;
	if(!$list_logon) back("회원 정보를 찾을 수 없습니다.");
	
	// 기존 카드정보와 다르다면 분실 후 재 입력일 수 있음.... . =>  모든 카드정보를 최종카드정보로 변경
	if($list_logon['accountno'] != $qs['accountno']){
		// 시즌카드정보 업데이트
		$sql="UPDATE `{$dbinfo['table_kpointinfo']}` SET accountno = '{$accountno_safe}' WHERE bid = '{$list_logon['uid']}'";
		db_query($sql);
		
		// 포인트정보 업데이트
		$sql="UPDATE `{$dbinfo['table_kpoint']}` SET accountno = '{$accountno_safe}' WHERE bid = '{$list_logon['uid']}'";
		db_query($sql);
	}
	
	// 서포터즈가 없을때 추가. (ereg -> preg_match)
	if(!preg_match("/,서포터즈/",$list_logon['priv']))
		$list_logon['priv'] .=",서포터즈";
	
	//시즌정보
	$sql_season = " SELECT *, sid as s_id FROM `savers_secret`.season WHERE pnt_race = 1 AND kpoint_hide = 0 ORDER BY s_start DESC Limit 1 ";
	$result_season = db_query($sql_season);
	$list_season = $result_season ? db_array($result_season) : null;
	if(!$list_season) back("시즌 정보를 찾을 수 없습니다.");
	
	// 회원정보 업데이트
	$priv_safe = db_escape($list_logon['priv']);
	$sql="UPDATE `{$dbinfo['table_logon']}`
			SET
				mdate		= UNIX_TIMESTAMP(),
				accountno	= '{$accountno_safe}',
				priv		= '{$priv_safe}'
			WHERE
				uid = '{$list_logon['uid']}'
		";
	db_query($sql);
	
	// 시즌별 카드정보가 있는지 확인
	$sql = "SELECT uid FROM `{$dbinfo['table_kpointinfo']}` WHERE bid = '{$list_logon['uid']}' AND s_id = '{$list_season['s_id']}' Limit 1";
	$result_kpoint = db_query($sql);
	
	// 없으면 카드정보 입력
	if(!($result_kpoint && db_count($result_kpoint) > 0)){
		$userid_safe = db_escape($list_logon['userid']);
		$name_safe = db_escape($list_logon['name']);
		$s_name_safe = db_escape($list_season['s_name']);

		$sql_ins = "INSERT INTO `{$dbinfo['table_kpointinfo']}` (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`,	`balance`, `comment`, `errorno`, `errornotice`, `rdate`)
					VALUES ('{$list_logon['uid']}', '{$accountno_safe}','{$userid_safe}', '{$name_safe}', '{$list_season['s_id']}', '{$s_name_safe}', '{$s_name_safe} 적립포인트', '', '0', '', '0', '', UNIX_TIMESTAMP())";
		db_query($sql_ins);
	}
	
	return $qs['accountno'];
} // end func.

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
