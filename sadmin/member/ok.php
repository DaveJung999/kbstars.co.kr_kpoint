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
	global $SITE;

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
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? '') {
	case "paymentdelete" :
		// 넘어온 값 필터링
		$qs	= array(
					'uid'	 =>	"get,trim,notnull",
					'bid'	 =>	"get,trim,notnull"
				);
		$qs=check_value($qs);

		$uid_safe = db_escape($qs['uid']);
		$bid_safe = db_escape($qs['bid']);

		// 상품 DB가 있을 경우 삭제안됨
		db_query("DELETE FROM `{$table_payment}` WHERE uid='{$uid_safe}' AND bid='{$bid_safe}'");
		if(db_count()) go_url($_SERVER['HTTP_REFERER']);
		else back("해당 고유번호와 회원 고유번호가 달라서 삭제할 수 없습니다.");
		break;
	case "joininfoadd" :
		joininfoadd();
		go_url(($_REQUEST['goto'] ?? '') ? $_REQUEST['goto'] : "joininfo.php?msc_column=".urlencode($_REQUEST['msc_column']) . "&msc_string=".urlencode($_REQUEST['msc_string']));
		break;
	case "joininfomodify" :
		joininfoModify();
		go_url(($_REQUEST['goto'] ?? '') ? $_REQUEST['goto'] : "joininfo.php?msc_column=".urlencode($_REQUEST['msc_column']) . "&msc_string=".urlencode($_REQUEST['msc_string']));
		break;
	case "joininfodelete" :
		// 넘어온 값 필터링
		$qs	= array(
					'gid'	 =>	"get,trim,notnull",
					'bid'	 =>	"get,trim,notnull"
				);
		$qs=check_value($qs);
		
		$gid_safe = db_escape($qs['gid']);
		$bid_safe = db_escape($qs['bid']);

		db_query("DELETE FROM `{$table_joininfo}` WHERE gid='{$gid_safe}' AND bid='{$bid_safe}'");
		if(db_count()) go_url($_SERVER['HTTP_REFERER']);
		else back("해당 고유번호와 회원 고유번호가 달라서 삭제할 수 없습니다.");
		break;
	case 'userinfomodify' :
		$dbinfo_userinfo=array(
				'table_logon'	 =>	$table_logon,
			'priv_write'	 =>	1,
			'priv_delete'	 =>	99
			);
		userinfoModify($dbinfo_userinfo);
		back();
		break;
	case 'userinfoadd' :
		$dbinfo_userinfo=array(
				'table_logon'	 =>	$table_logon,
				'table_log_userinfo' => $table_log_userinfo,
			'priv_write'	 =>	'운영자',
			'priv_delete'	 =>	'운영자'
			);
		$uid = userinfoAdd($dbinfo_userinfo);
		back("회원 추가되었습니다(회원고유번호: {$uid})");
		break;		
	case 'loguserwrite' :
		$loguserinfo=array(
				'table'		 =>	$table_log_userinfo,
			'priv_write'	 =>	'운영자',
			'priv_delete'	 =>	'운영자'
			);		
		loguserWrite($loguserinfo);
		back();
		break;
	case "loguserdelete" :
		// 넘어온 값 필터링
		$qs	= array(
					'uid'	 =>	"get,trim,notnull",
					'bid'	 =>	"get,trim"
				);
		$qs=check_value($qs);

		$uid_safe = db_escape($qs['uid']);
		$bid_safe = db_escape($qs['bid']);

		// 상품 DB가 있을 경우 삭제안됨
		db_query("DELETE FROM `{$table_log_userinfo}` WHERE uid='{$uid_safe}' AND bid='{$bid_safe}'");
		if(db_count()) go_url($_SERVER['HTTP_REFERER']);
		else back("해당 고유번호와 회원 고유번호가 달라서 삭제할 수 없습니다.");
		break;
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function joininfoadd() {
	GLOBAL $table_logon, $table_groupinfo, $table_joininfo;

	// 넘어온 값 필터링
	$qs	= array(
			'userid'		 =>	"post,trim,notnull",
				'groupid'		 =>	"post,trim,notnull",
			);
	$qs=check_value($qs);

	$userid_safe = db_escape($qs['userid']);
	$groupid_safe = db_escape($qs['groupid']);

	// 해당 회원이 존재하는지 체크
	$sql = "SELECT * from {$table_logon} WHERE userid='{$userid_safe}'";
	$result = db_query($sql);
	$logon = $result ? db_array($result) : null;
	if(!$logon) back("해당 회원 아이디가 없습니다.");
	$qs['bid']= $logon['uid'];

	// 해당 그룹이 존재하는지 체크
	$sql = "SELECT * from {$table_groupinfo} WHERE groupid='{$groupid_safe}'";
	$result = db_query($sql);
	$groupinfo = $result ? db_array($result) : null;
	if(!$groupinfo) back("해당 그룹이 없습니다.");
	$qs['gid']= $groupinfo['uid'];
	
	// 이미 가입되어 있는지 체크
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE bid='{$logon['uid']}' AND gid='{$groupinfo['uid']}'";
	$result = db_query($sql);
	if($result && db_count($result) > 0) back("이미 가입되어 있습니다");

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
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
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

function joininfoModify() {
	GLOBAL $table_logon, $table_groupinfo, $table_joininfo;

	// 넘어온 값 필터링
	$qs	= array(
				'bid'			 =>	"post,trim,notnull",
				'gid'			 =>	"post,trim,notnull",
			);
	$qs=check_value($qs);
	
	$bid_safe = db_escape($qs['bid']);
	$gid_safe = db_escape($qs['gid']);
	
	// 해당 회원이 존재하는지 체크
	$sql = "SELECT * from {$table_logon} WHERE uid='{$bid_safe}'";
	$result = db_query($sql);
	if(!($result && db_count($result) > 0)) back("해당 회원 아이디가 없습니다.");

	// 이미 가입되어 있는지 체크
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE bid='{$bid_safe}' AND gid='{$gid_safe}'";
	$result = db_query($sql);
	if(!($result && db_count($result) > 0)) back("가입되어 있지 않습니다");

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
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
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

function userinfoAdd($dbinfo)
{
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
	$qs=array(	"userid"	 =>	"post,trim,notnull=" . urlencode("회원아이디를 입력하시기 바랍니다."),
				"passwd"	 =>	"post,trim,notnull=" . urlencode("패스워드를 입력하시기 바랍니다."),
				"name"		 =>	"post,trim,notnull=" . urlencode("회원님 이름을 입력하시기 바랍니다."),
		);
	$qs=check_value($qs);
	// eregi를 preg_match로 변경
	if(!preg_match("/^[a-z][a-z0-9]+$/i", $qs['userid']))
		back("아이디는 2-10자까지 숫자, 영문자의 조합만 가능합니다. 첫문자는 영문자여야 합니다.");
	// -nickname
	if(empty($qs['nickname'])) $qs['nickname'] = $qs['name'];	

	// DB에 등록된 userid인지 체크
	$userid_safe = db_escape($qs['userid']);
	$sql = "SELECT * FROM {$dbinfo['table_logon']} WHERE userid = '{$userid_safe}'";
	$result = db_query($sql);
	if($result && db_count($result) > 0)
		back("이미등록되어 있는 아이디 입니다. ");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_logon'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'priv': // set 필드
					if(is_array($_POST['priv']) && count($_POST['priv']))
						$qs['priv'] = implode(',',$_POST['priv']);
					else
						$qs['priv'] = '';
					break;
				case 'open': // set 필드
					if(is_array($_POST['open']) && count($_POST['open']))
						$qs['open'] = implode(',',$_POST['open']);
					else
						$qs['open'] = '';
					break;
				case 'email' :
					$qs['email'] = check_email($_POST['email']);
					break;
				case 'passwd' :
					if(!trim($_POST['passwd'])) continue 2; // 비밀번호가 입력되어 있지 않으면 넘김
					$qs['passwd'] = $_POST['passwd'];
					break;
				case 'zip' :
					$qs['zip'] = ($_POST['zip1'] ?? '') .'-'. ($_POST['zip2'] ?? '');
					break;
				case 'c_zip':
					$qs['c_zip'] = ($_POST['c_zip1'] ?? '') .'-'. ($_POST['c_zip2'] ?? '');
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'host' :
					$qs['host'] = $_SERVER['HTTP_HOST'];
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
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////
	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table_logon']} SET {$sql_set}";
	db_query($sql);
	$qs['bid'] = db_insert_id();

	// 로그화 : log_userinfo 회원가입 로그
	$tmp_date = date("Y-m-d [H:i]");
	$host_safe = db_escape($qs['host']);
	$ip_safe = db_escape($qs['ip']);
	$content_safe = db_escape("{$tmp_date} : {$qs['ip']}에서 가입");
	
	$sql="INSERT INTO {$dbinfo['table_log_userinfo']}
			SET
				`host`		= '{$host_safe}',
				`bid`		= '" . ($_SESSION['seUid'] ?? 0) . "',
				`userbid`	= {$qs['bid']},
				`title`		= '관리자회원가입',
				`content`	= '{$content_safe}',
				`ip`		= '{$ip_safe}',
				`rdate`		= UNIX_TIMESTAMP()
			";
	db_query($sql);	
	return $qs['bid'];
}

function userinfoModify($dbinfo)
{
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");

	// $qs 추가,변경
	$qs=array(
				'bid'		 =>	"post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
		);
	$qs=check_value($qs);

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_logon'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'priv': // set 필드
					if(is_array($_POST['priv']) && count($_POST['priv']))
						$qs['priv'] = implode(',',$_POST['priv']);
					else
						$qs['priv'] = '';
					break;
				case 'open': // set 필드
					if(is_array($_POST['open']) && count($_POST['open']))
						$qs['open'] = implode(',',$_POST['open']);
					else
						$qs['open'] = '';
					break;
				case 'email' :
					$qs['email'] = check_email($_POST['email']);
					break;
				case 'passwd' :
					if(!trim($_POST['passwd'])) continue 2; // 비밀번호가 입력되어 있지 않으면 넘김
					$qs['passwd'] = $_POST['passwd'];
					break;
				case 'zip' :
					$qs['zip'] = ($_POST['zip1'] ?? '') .'-'. ($_POST['zip2'] ?? '');
					break;
				case 'c_zip':
					$qs['c_zip'] = ($_POST['c_zip1'] ?? '') .'-'. ($_POST['c_zip2'] ?? '');
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'host' :
					$qs['host'] = $_SERVER['HTTP_HOST'];
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
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////
	$sql_set = implode(', ', $set_parts);
	$bid_safe = db_escape($_POST['bid']);
	$sql="UPDATE {$dbinfo['table_logon']}
			SET
				{$sql_set}
			WHERE
				uid = '{$bid_safe}'
		";
	db_query($sql);
	return ;
} // end func.
function loguserWrite($dbinfo)
{
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");

	// $qs 추가,변경
	$qs=array(
				'title'		 =>	"post,trim,notnull=" . urlencode("제목을 입력하시기 바랍니다."),
				'content'		 =>	"post,trim"
		);
	$qs=check_value($qs);

	$sql_where = " 1 ";
	$set_parts = [];

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'host' :
					$qs['host'] = $_SERVER['HTTP_HOST'];
					break;
				case 'num' :
					$sql_num = "SELECT max(num) as max_num FROM {$dbinfo['table']} WHERE  $sql_where ";
					$res_num = db_query($sql_num);
					$row_num = $res_num ? db_array($res_num) : null;
					$qs['num'] = ($row_num['max_num'] ?? 0) + 1;
					break;
				case 'bid'	:
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' : // email과 함께 처리
				case 'email' :
					if(isset($_SESSION['seUid'])) {
						switch($dbinfo['enable_userid'] ?? 'userid') {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
						$qs['email']	= $_SESSION['seEmail'];
					}
					else $qs['email']	= check_email($qs['email'] ?? '');
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
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		}
	}
	////////////////////////////////

	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table']} SET `rdate` = UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "");

	db_query($sql);
	$uid = db_insert_id();

	return $uid;
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
