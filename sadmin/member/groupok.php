<?php
//=======================================================
// 설	명 : 관리자 회원 관리 처리-그룹(groupok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // file_upload(),remote_addr()
	'useCheck'	 => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	// table
	$table_logon		= $SITE['th'].'logon';
	$table_groupinfo	= $SITE['th'].'groupinfo';
	$table_joininfo		= $SITE['th'].'joininfo';
	$table_joininfo_cate= $SITE['th'].'joininfo_cate';
	$table_payment		= $SITE['th'].'payment';
	$table_service		= $SITE['th'].'service';
	$table_log_userinfo	= $SITE['th'].'log_userinfo';
	$table_log_wtmp		= $SITE['th'].'log_wtmp';
	$table_log_lastlog	= $SITE['th'].'log_lastlog';

	// dbinfo 설정
	$gid_post = $_POST['gid'] ?? 0;
	$dbinfo_joininfo = array (
			'table' 			 =>	$table_joininfo,
			'table_cate' 		 =>	$table_joininfo_cate,
			'sql_where'			 =>	" gid='" . db_escape($gid_post) . "' ",
			'sql_where_cate'	 =>	" gid='" . db_escape($gid_post) . "' ",
			);
			
	// 공통적으로 사용할 $qs
	$qs_groupinfo=array(
			'mode'			 =>	'post,trim',
		'userid'		 =>	'post,trim,notnull',
			'groupid'		 =>	'post,trim,notnull',
			'name'			 =>	'post,trim,notnull',
			'default_level'	 =>	'post,trim',
			'content'		 =>	'post,trim'
		);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? '') {
	case 'groupinfoadd':
		$dbinfo_groupinfo=array(
				'table'		 =>	$table_groupinfo,
			'priv_write'	 =>	'운영자',
			'priv_delete'	 =>	'운영자'
			);	
		$uid = groupinfoWrite_ok($dbinfo_groupinfo, $qs_groupinfo, $_POST['userid'] ?? '');
		go_url("./gsearch.php?" . href_qs("mode=joininfo&gsc_column=gid&gsc_string={$uid}",($_REQUEST['qs_basic'] ?? '')));
		break;
	case 'groupinfomodify':
		$dbinfo_groupinfo=array(
				'table'		 =>	$table_groupinfo,
			'priv_write'	 =>	'운영자',
			'priv_delete'	 =>	'운영자'
			);	
		groupinfoModify_ok($dbinfo_groupinfo,$qs_groupinfo,"groupid");
		go_url("groupinfo.php?gsc_column=".urlencode($_REQUEST['gsc_column'] ?? '') . "&gsc_string=".urlencode($_REQUEST['gsc_string'] ?? ''));
		break;
	case 'groupinfodelete':
		groupinfoDelete_ok($table_groupinfo,"groupid");
		go_url("./gsearch.php");
		break;	
	case "gjoininfoadd" :
		gjoininfoadd();
		go_url(($_REQUEST['goto'] ?? '') ? $_REQUEST['goto'] : "gjoininfo.php?gsc_column=".urlencode($_REQUEST['gsc_column'] ?? '') . "&gsc_string=".urlencode($_REQUEST['gsc_string'] ?? '') . "&cateuid=".urlencode($_REQUEST['cateuid'] ?? ''));
		break;
	case "gjoininfomodify" :
		gjoininfoModify();
		go_url(($_REQUEST['goto'] ?? '') ? $_REQUEST['goto'] : "gjoininfo.php?gsc_column=".urlencode($_REQUEST['gsc_column'] ?? '') . "&gsc_string=".urlencode($_REQUEST['gsc_string'] ?? ''));
		break;
	case "gjoininfodelete" :
		// 넘어온 값 필터링
		$qs	= array(
					'gid'	 =>	"get,trim,notnull",
					'bid'	 =>	"get,trim,notnull"
				);
		$qs=check_value($qs);

		$gid_safe = db_escape($qs['gid']);
		$bid_safe = db_escape($qs['bid']);

		db_query("DELETE FROM `{$table_joininfo}` WHERE gid='{$gid_safe}' AND bid='{$bid_safe}'");
		if(db_count()) go_url($_SERVER['HTTP_REFERER'] ?? './');
		else back("해당 고유번호와 회원 고유번호가 달라서 삭제할 수 없습니다.");
		break;
	case "gjoininfo_catechange" :
		$gid_post_safe = db_escape($_POST['gid'] ?? '');
		$sql = "SELECT * FROM {$table_groupinfo} WHERE uid='{$gid_post_safe}'";
		$result = db_query($sql);
		if(!(db_count($result) > 0)) back('해당 그룹이 존재하지 않습니다');

		$qs	= array(
				'gid'	 =>	"post,trim,notnull",
				'uids'	 =>	"post,trim,notnull",
				'cateuid' => "post,trim,notnull"
				);	
		gjoininfoCateChange($dbinfo_joininfo,$qs);
		echo ("
				<script language = 'JavaScript'>
					if(opener)
					{
						opener.location.reload();
						self.close();
					}
				</script>
		");
		exit();		
	case "gjoininfo_levelchange" :
		$gid_post_safe = db_escape($_POST['gid'] ?? '');
		$sql = "SELECT * FROM {$table_groupinfo} WHERE uid='{$gid_post_safe}'";
		$result = db_query($sql);
		if(!(db_count($result) > 0)) back('해당 그룹이 존재하지 않습니다');
		
		$qs	= array(
				'gid' 	 =>	"post,trim,notnull",
				'uids'	 =>	"post,trim,notnull",
				'level'	 => "post,trim"
				);	
		gjoininfoLevelChange($dbinfo_joininfo,$qs);
		echo ("
				<script language = 'JavaScript'>
					if(opener)
					{
						opener.location.reload();
						self.close();
					}
				</script>
		");
		exit;
	case "gjoininfo_checkdel" :
		$gid_post_safe = db_escape($_POST['gid'] ?? '');
		$sql = "SELECT * FROM {$table_groupinfo} WHERE uid='{$gid_post_safe}'";
		$result = db_query($sql);
		if(!(db_count($result) > 0)) back('해당 그룹이 존재하지 않습니다');
	
		$qs	= array(
				'gid' 	 =>	"post,trim,notnull",
				);	
		gjoininfoCheckDel($dbinfo_joininfo,$qs);
		go_url(($_REQUEST['goto'] ?? '') ? $_REQUEST['goto'] : "gjoininfo.php?gsc_column=".urlencode($_REQUEST['gsc_column'] ?? '') . "&gsc_string=".urlencode($_REQUEST['gsc_string'] ?? '') . "&cateuid=".urlencode($_REQUEST['cateuid'] ?? ''));
		exit;
	default :
		back("잘못된 요청입니다.");
}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function gjoininfoCheckDel(&$dbinfo,$qs) {
	global $conn;
	$qs=check_value($qs);
	$uids = $_POST['uids'] ?? [];
	
	if(is_array($uids) && count($uids)>0) {
		$gid_safe = db_escape($qs['gid']);
		foreach($uids as $value) {
			$value = trim($value);
			if($value) {
				$bid_safe = db_escape($value);
				$sql = "DELETE FROM {$dbinfo['table']} WHERE gid='{$gid_safe}' AND bid='{$bid_safe}'";
				db_query($sql);
			}
		} // end foreach
	}
}
function gjoininfoLevelChange(&$dbinfo,$qs) {
	global $conn;
	$qs=check_value($qs);
	$level_safe = (int)$qs['level'];
	
	// 카테고리 변경
	$uids = explode(",", $qs['uids'] ?? '');
	if(is_array($uids) && count($uids)>0) {
		foreach($uids as $value) {
			$value = trim($value);
			if($value) {
				$bid_safe = db_escape($value);
				$sql = "UPDATE {$dbinfo['table']} SET level='{$level_safe}' WHERE {$dbinfo['sql_where']} AND bid='{$bid_safe}'";
				db_query($sql);
			}
		} // end foreach
	}
}
function gjoininfoCateChange(&$dbinfo,$qs) {
	global $conn;
	$qs=check_value($qs);
	
	// 해당 카테고리가 존재하는지
	$cateuid_safe = db_escape($qs['cateuid']);
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} AND uid='{$cateuid_safe}'";
	$result = db_query($sql);
	if(!(db_count($result) > 0)) back("해당 카테고리가 존재하지 않습니다");

	// 카테고리 변경
	$uids = explode(",", $qs['uids'] ?? '');
	if(is_array($uids) && count($uids)>0) {
		foreach($uids as $value) {
			$value = trim($value);
			if($value) {
				$bid_safe = db_escape($value);
				$sql = "UPDATE {$dbinfo['table']} SET cateuid='{$cateuid_safe}' WHERE {$dbinfo['sql_where']} AND bid='{$bid_safe}'";
				db_query($sql);
			}
		} // end foreach
	}
}
function gjoininfoadd() {
	GLOBAL $conn, $table_logon, $table_groupinfo, $table_joininfo;

	// 넘어온 값 필터링
	$qs	= array(
			'userid'		 =>	"post,trim,notnull",
				'groupid'		 =>	"post,trim,notnull",
			);
	$qs=check_value($qs);

	$userid_safe = db_escape($qs['userid']);
	$groupid_safe = db_escape($qs['groupid']);

	// 해당 회원이 존재하는지 체크
	$sql = "SELECT * FROM {$table_logon} WHERE userid='{$userid_safe}'";
	$result = db_query($sql);
	$logon = $result ? db_array($result) : null;
	if(!$logon) back("해당 회원 아이디가 없습니다.");
	$qs['bid']= $logon['uid'] ?? '';
	$qs['userid']=$logon['userid'] ?? '';

	// 해당 그룹이 존재하는지 체크
	$sql = "SELECT * FROM {$table_groupinfo} WHERE groupid='{$groupid_safe}'";
	$result = db_query($sql);
	$groupinfo = $result ? db_array($result) : null;
	if(!$groupinfo) back("해당 그룹이 없습니다.");
	$qs['gid']= $groupinfo['uid'] ?? '';
	
	// 이미 가입되어 있는지 체크
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE bid='".db_escape($logon['uid'] ?? '')."' AND gid='".db_escape($groupinfo['uid'] ?? '')."'";
	$result = db_query($sql);
	if(db_count($result) > 0) back("이미 가입되어 있습니다");

	$dbinfo['table'] = $table_joininfo;
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}

	// 그룹에 추가
	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table']} SET `rdate` = UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "");

	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func joininfoadd

function gjoininfoModify() {
	GLOBAL $conn, $table_logon, $table_groupinfo, $table_joininfo;

	// 넘어온 값 필터링
	$qs	= array(
				'bid'			 =>	"post,trim,notnull",
				'gid'			 =>	"post,trim,notnull",
			);
	$qs=check_value($qs);
	
	$bid_safe = db_escape($qs['bid']);
	$gid_safe = db_escape($qs['gid']);
	
	// 해당 회원이 존재하는지 체크
	$sql = "SELECT * FROM {$table_logon} WHERE uid='{$bid_safe}'";
	$result = db_query($sql);
	$logon = $result ? db_array($result) : null;
	if(!$logon) back("해당 회원 아이디가 없습니다.");
	$qs['userid'] = $logon['userid'] ?? '';

	// 이미 가입되어 있는지 체크
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE bid='{$bid_safe}' AND gid='{$gid_safe}'";
	$result = db_query($sql);
	if(!(db_count($result) > 0)) back("가입되어 있지 않습니다");

	$dbinfo['table'] = $table_joininfo;
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////
	
	// 그룹에 추가	
	$sql_set = implode(', ', $set_parts);
	$sql = "UPDATE {$dbinfo['table']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . " WHERE bid = '{$bid_safe}' AND gid = '{$gid_safe}'";
	db_query($sql);

	return true;
} // end func joininfoModify

function groupinfoWrite_ok($dbinfo,$qs,$userid)
{
	GLOBAL $conn, $table_logon;
	// 권한체크
	if(!privAuth($dbinfo, "priv_write")) back("추가 권한이 없습니다");

	// 넘어온값 체크
	$qs=check_value($qs);
	
	$groupid_safe = db_escape($qs['groupid']);
	$name_safe = db_escape($qs['name']);
	$userid_safe = db_escape($userid);

	// 해당 그룹이 이미 등록되어 있는지 체크
	$sql = "SELECT uid FROM {$dbinfo['table']} WHERE groupid='{$groupid_safe}'";
	$result = db_query($sql);
	if(db_count($result) > 0) back('해당 그룹아이디는 이미 등록되어 있습니다.');
	
	// 그룹 이름이 동일한 것이 있는지 체크
	$sql = "SELECT uid FROM {$dbinfo['table']} WHERE name='{$name_safe}'";
	$result = db_query($sql);
	if(db_count($result) > 0) back('해당 그룹명이 이미 등록되어 있습니다.');

	// 회원 정보 가져오기
	$sql = "SELECT * FROM {$table_logon} WHERE userid='{$userid_safe}'";
	$result = db_query($sql);
	$logon = $result ? db_array($result) : null;
	if(!$logon) back('개설자 아이디가 존재하지 않습니다');
	$qs['bid'] = $logon['uid'] ?? '';
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'num' :
					$sql_num = "SELECT max(num) as max_num FROM {$dbinfo['table']}";
					$res_num = db_query($sql_num);
					$row_num = $res_num ? db_array($res_num) : null;
					$qs['num'] = ($row_num['max_num'] ?? 0) + 1;
					break;
				case 'userid' : // email과 함께 처리
					switch($dbinfo['enable_userid'] ?? 'userid') {
						case 'name'		: $qs['userid'] = $logon['name'] ?? ''; break;
						case 'nickname'	: $qs['userid'] = $logon['nickname'] ?? ''; break;
						default			: $qs['userid'] = $logon['userid'] ?? ''; break;
					}
				case 'bid' :
					$qs['bid'] = $logon['uid'] ?? '';
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////
	
	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "");

	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func write_ok

function groupinfoModify_ok($dbinfo,$qs,$field){
	GLOBAL $conn, $table_logon;
	
	// $qs 추가,변경
	$qs["$field"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	// 수정 권한 체크와 해당 데이터 읽어오기
	if(!privAuth($dbinfo,"priv_delete")) back("수정 권한이 없습니다.");
	$field_val_safe = db_escape($qs[$field]);
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE `{$field}`='{$field_val_safe}'";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back("해당 그룹이 없습니다.");

	// 변경하려는 그룹 이름이 이미 사용중인지 체크
	$name_safe = db_escape($qs['name']);
	$sql = "SELECT uid FROM {$dbinfo['table']} WHERE name='{$name_safe}' AND uid<>'".db_escape($list['uid'] ?? '')."'";
	$result = db_query($sql);
	if(db_count($result) > 0) back('해당 그룹명이 이미 등록되어 있습니다.');

	// 회원 정보 가져오기
	$userid_safe = db_escape($qs['userid']);
	$sql = "SELECT * FROM {$table_logon} WHERE userid='{$userid_safe}'";
	$result = db_query($sql);
	$logon = $result ? db_array($result) : null;
	if(!$logon) back('개설자 아이디가 존재하지 않습니다');
	$qs['bid'] = $logon['uid'] ?? '';
	
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'userid' : // email과 함께 처리
					switch($dbinfo['enable_userid'] ?? 'userid') {
						case 'name'		: $qs['userid'] = $logon['name'] ?? ''; break;
						case 'nickname'	: $qs['userid'] = $logon['nickname'] ?? ''; break;
						default			: $qs['userid'] = $logon['userid'] ?? ''; break;
					}
				case 'bid' :
					$qs['bid'] = $logon['uid'] ?? '';
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////

	$sql_set = implode(', ', $set_parts);
	$sql = "UPDATE {$dbinfo['table']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . " WHERE `{$field}`='{$field_val_safe}'";
	db_query($sql);
	
	return true;

} // end func modify_ok

function groupinfoDelete_ok($table,$field){
	GLOBAL $conn, $dbinfo, $table_joininfo;
	$qs=array(
			"$field"	 =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$field_val_safe = db_escape($qs[$field]);
	$sql = "SELECT * FROM `{$table}` WHERE `{$field}`='{$field_val_safe}'";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back("해당 데이터가 없습니다");
	
	// 이미 회원가입된 회원이 있으면 삭제 안됨
	$sql = "SELECT count(*) as cnt FROM `{$table_joininfo}` WHERE gid='".db_escape($list['uid'] ?? '')."'";
	$result = db_query($sql);
	$count = $result ? (int)(db_array($result)['cnt'] ?? 0) : 0;
	if($count > 0) back('그룹에 가입한 회원이 있습니다.\\n가입회원이 없는 경우 삭제 가능합니다');
	
	// 권한체크
	if(!privAuth($dbinfo, "priv_delete")) {
		if(($list['bid'] ?? '') != ($_SESSION['seUid'] ?? '')) back("삭제 권한이 없습니다");
	}

	// 서브그룹도 삭제
	db_query("DELETE FROM `{$table}` WHERE num='".db_escape($list['num'] ?? '')."' AND length(re) > length('".db_escape($list['re'] ?? '')."') AND locate('".db_escape($list['re'] ?? '')."',re) = 1");

	db_query("DELETE FROM `{$table}` WHERE `{$field}`='{$field_val_safe}'");
	
	return db_count();
} // end func delete_ok

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