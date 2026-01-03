<?php
//=======================================================
// 설	명 : 게시판 카테고리 처리(cateok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/05/31 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1,
	'useBoard' => 1,
	'useApp'	 => 1,
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

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	$prefix 	= 'joininfo';
	$prefixurl 	= 'gjoininfo_';

	// GET/REQUEST 파라미터 안전하게 받기
	$gsc_column_req = $_REQUEST['gsc_column'] ?? '';
	$gsc_string_req = $_REQUEST['gsc_string'] ?? '';
	$gid_req = $_REQUEST['gid'] ?? 0;
	$mode_req = $_REQUEST['mode'] ?? '';
	$goto_req = $_REQUEST['goto'] ?? '';

	// 기본 URL QueryString
	$qs_basic = href_qs("gsc_column={$gsc_column_req}&gsc_string={$gsc_string_req}",'gsc_column=');
	
	// table	
	$table_groupinfo = $SITE['th'] . "groupinfo";
	
	// 넘어온값 처리
	$gid_safe = db_escape($gid_req);
	$sql= "SELECT * from {$table_groupinfo} WHERE uid='{$gid_safe}'";
	$result = db_query($sql);
	if(!($result && db_count($result) > 0)) back('해당 그룹이 없습니다. 잘못된 요청이십니다.');
		
	// $dbinfo값정의 - 기본 where절
	$dbinfo['table'] = "{$SITE['th']}{$prefix}"; // 테이블이름 가져오기
	$dbinfo['table_cate'] = {$dbinfo['table']} . '_cate';

	$dbinfo['sql_where'] 		= " gid='{$gid_safe}' ";
	$dbinfo['sql_where_cate']	= " gid='{$gid_safe}' "; 	
	// - ','로 시작하고, case '???' : continue 2; 해야함
	$dbinfo['sql_set']		= ", gid='{$gid_safe}' ";
	$dbinfo['sql_set_cate']	= ", gid='{$gid_safe}' "; // ','로 시작해야함
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($mode_req) {
	case 'catewrite' :
		$qs	= array(
				'cateuid'	 =>	"post,trim",
				'title'	 =>	"post,trim,notnull=" . urlencode("카테고리 제목을 입력하십시요")
			);
		$cateuid = cateWriteOK($dbinfo,$qs);
		if(!$cateuid) back("처리되지 않았습니다.");
		go_url($goto_req ? $goto_req : "{$prefixurl}cate.php?".href_qs("cateuid={$cateuid}",$qs_basic));
		break;
	case 'catemodify' :
		$qs	= array(
				'cateuid'	 =>	"post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다"),
				'title'	 =>	"post,trim,notnull",
			);	
		cateModifyOK($dbinfo,$qs,'uid');
		go_url($goto_req ? $goto_req : "{$prefixurl}cate.php?{$qs_basic}");
		break;
	case 'catedelete' :
		$goto = $goto_req ? $goto_req : "{$prefixurl}cate.php?{$qs_basic}";
		cateDeleteOK($dbinfo,'uid',$goto);
		go_url($goto);
		break;
	case 'catesort' :
		$qs	= array(
				'srcuid'	 =>	"post,trim,notnull=" . urlencode("있어야할 값이 넘어오지 않았습니다"),
				'dstuid'	 =>	"post,trim,notnull=" . urlencode("있어야할 값이 넘어오지 않았습니다")
			);	
		cateSortOk($dbinfo,$qs);		
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
	default :
		back("잘못된 웹페이지에 접근하였습니다.");
}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function cateSortOk($dbinfo,$qs) {
	global $conn;
	$qs=check_value($qs);
	
	$srcuid_safe = db_escape($qs['srcuid']);
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='{$srcuid_safe}' AND {$dbinfo['sql_where_cate']}";
	$result = db_query($sql);
	$src = $result ? db_array($result) : null;
	if(!$src) back("해당 카테고리가 존재하지 않습니다");
	
	// 변경할 카테고리 uid 구해서 where절 uid in (..) 만듬
	$rs_srcuids = db_query("SELECT uid FROM {$dbinfo['table_cate']} WHERE num='{$src['num']}' AND re LIKE '{$src['re']}%' AND {$dbinfo['sql_where_cate']}");
	$srcuids = [];
	while( $row = db_array($rs_srcuids) )
		$srcuids[] = $row['uid'];
	if(empty($srcuids)) return; // 대상이 없으면 종료
	$sql_where_srcuid_in = " uid IN (" . implode(",", $srcuids) . ") ";
	
	if(strlen($src['re'])) {
		$dstuid_safe = db_escape($qs['dstuid']);
		if($qs['dstuid']=="first") { // 처음으로 이동
			$re_prefix = substr($src['re'],0,-1);
			$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} AND num='{$src['num']}' AND re LIKE '{$re_prefix}_' ORDER BY re LIMIT 1";
			$result = db_query($sql);
			$dst = $result ? db_array($result) : null;
			if(!$dst) back("옮기고자 하는 카테고리 선택이 잘못되었습니다. err110");			
		
			$src_length = strlen($src['re']);
			if($dst['re'] == $re_prefix."1" ) {
				db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), CHAR(ORD(SUBSTRING(re,{$src_length},1))+1 ), SUBSTRING(re,{$src_length}+1) ) WHERE num='{$src['num']}' AND re > '{$re_prefix}' AND re < '{$src['re']}' AND {$dbinfo['sql_where_cate']}");
			}
			db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), '1', SUBSTRING(re,{$src_length}+1)) WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
		}
		else {
			$re_prefix = substr($src['re'],0,-1);
			$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='{$dstuid_safe}' AND num='{$src['num']}' AND re LIKE '{$re_prefix}_' AND {$dbinfo['sql_where_cate']}";
			$result = db_query($sql);
			$dst = $result ? db_array($result) : null;
			if(!$dst) back("옮기고자 하는 카테고리 선택이 잘못되었습니다. err118");

			if( strlen($src['re'])!=strlen($dst['re']) ) back("카테고리 선택이 잘못되었습니다.");

			$src_length = strlen($src['re']);
			if( strcmp($src['re'],$dst['re']) > 0 ){ // 상위로 이동
				$dst_re_next = substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
				db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), CHAR(ORD(SUBSTRING(re,{$src_length},1))+1 ), SUBSTRING(re,{$src_length}+1) ) WHERE num='{$src['num']}' AND re>='{$dst_re_next}' AND re<'{$src['re']}' AND {$dbinfo['sql_where_cate']}");
				db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), RIGHT('{$dst_re_next}',1), SUBSTRING(re,{$src_length}+1)) WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
			}
			elseif(strcmp($src['re'],$dst['re']) < 0) { // 하위로 이동
				$src_re_next = substr($src['re'],0,-1) . chr(ord(substr($src['re'],-1))+1);
				$dst_re_next = substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
				db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), CHAR(ORD(SUBSTRING(re,{$src_length},1))-1 ), SUBSTRING(re,{$src_length}+1) ) WHERE num='{$src['num']}' AND re>='{$src_re_next}' AND re<'{$dst_re_next}' AND {$dbinfo['sql_where_cate']}");
				db_query("UPDATE {$dbinfo['table_cate']} SET re=CONCAT( SUBSTRING(re,1,{$src_length}-1), RIGHT('{$dst['re']}',1) , SUBSTRING(re,{$src_length}+1) ) WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
			}
		}
	}
	else { // re값이 없고 num값을 변경해야될 경우임
		$dstuid_safe = db_escape($qs['dstuid']);
		if($qs['dstuid']=="first") { // 최상위로 이동
			$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} ORDER BY num LIMIT 1";
			$result = db_query($sql);
			$dst = $result ? db_array($result) : null;
			if(!$dst) back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 4");

			if($dst['num']==1) db_query("UPDATE {$dbinfo['table_cate']} SET num=num+1 WHERE num < {$src['num']} AND {$dbinfo['sql_where_cate']}");
			db_query("UPDATE {$dbinfo['table_cate']} SET num=1 WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
		}
		else {
			$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='{$dstuid_safe}' AND re='' AND {$dbinfo['sql_where_cate']}";
			$result = db_query($sql);
			$dst = $result ? db_array($result) : null;
			if(!$dst) back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 6");
		
			if($src['num'] > $dst['num']){
				db_query("UPDATE {$dbinfo['table_cate']} SET num=num+1 WHERE num > {$dst['num']} AND num < {$src['num']} AND {$dbinfo['sql_where_cate']}");
				db_query("UPDATE {$dbinfo['table_cate']} SET num={$dst['num']}+1 WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
			}
			elseif($src['num'] < $dst['num']){
				db_query("UPDATE {$dbinfo['table_cate']} SET num=num-1 WHERE num > {$src['num']} AND num <= {$dst['num']} AND {$dbinfo['sql_where_cate']}");
				db_query("UPDATE {$dbinfo['table_cate']} SET num={$dst['num']} WHERE {$sql_where_srcuid_in} AND {$dbinfo['sql_where_cate']}");
			}
		}
	}
} // end func.


// 카테고리 추가 부분($sql_set_cate 가져오는 것 필히 확인)
function cateWriteOK($dbinfo, $qs) {
	global $conn;
	$qs=check_value($qs);
	
	// num, re 값 결정
	if(isset($qs['cateuid'])){ // 서브카테고리 추가인경우
		$cateuid_safe = db_escape($qs['cateuid']);
		$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='{$cateuid_safe}' AND {$dbinfo['sql_where_cate']} ";
		$result = db_query($sql);
		$list = $result ? db_array($result) : null;
		if(!$list) back("해당 부모 카테고리가 없습니다.");
		
		$qs['num']=$list['num'];
		$qs['re'] =getCateRe($dbinfo['table_cate'],$dbinfo['sql_where_cate'],$list['num'],$list['re']);
		if(isset($dbinfo['cate_depth']) && $dbinfo['cate_depth'] < strlen($qs['re'])) back("더 하부의 서브카테고리를 만드실 수 없습니다");
	}
	else { // 탑카테고리 추가인경우
		$sql = "SELECT MAX(num) as max_num FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']}";
		$result = db_query($sql);
		$row = $result ? db_array($result) : null;
		$qs['num'] = ($row['max_num'] ?? 0) + 1;
		$qs['re']	= '';
	}
	
	////////////////////////////////////////////
	// 추가되어 있는 테이블 필드 포함($sql_set)
	$set_parts = [];
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_cate'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'gid' : continue 2; // 다음 foreach 로...
				case 'ip' :	$qs['ip'] = remote_addr(); break;
				case 'bid' :$qs['bid'] = (int)($_SESSION['seUid'] ?? 0); break;
				case 'zip' :
					if(!isset($_POST['zip'])) $qs['zip'] = ($_POST['zip1'] ?? '') . "-" . ($_POST['zip2'] ?? '');
					break;
				case 'userid' : // email과 함께 처리
				case 'email' :
					if(isset($_SESSION['seUid'])) {
						switch($dbinfo['enable_userid'] ?? 'userid') {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
						$qs['email'] = $_SESSION['seEmail'];
					} else {
						$qs['email'] = check_email($qs['email'] ?? '');
					}
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
		} // end foreach
	} // end if
	////////////////////////////////////////////

	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table_cate']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . ($dbinfo['sql_set_cate'] ?? '');
	db_query($sql);
	
	return db_insert_id();
}

// 카테고리 수정 부분
function cateModifyOK($dbinfo,$qs,$field) {
	global $conn;
	$qs=check_value($qs);
	$qs[$field] = $qs['cateuid'];
	
	$field_val_safe = db_escape($qs[$field]);
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE `{$field}`='{$field_val_safe}' AND {$dbinfo['sql_where_cate']}";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back('수정하실 카테고리가 없습니다');

	////////////////////////////////////////////
	// 추가되어 있는 테이블 필드 포함($sql_set)
	$set_parts = [];
	$skip_fields = array('bid','num','re','uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_cate'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'gid': case 'bid': case 'num': case 're':
					continue 2; // 다음 foreach 로...
			
				case 'ip' :	$qs['ip'] = remote_addr(); break;
				case 'zip' :
					if(!isset($_POST['zip'])) $qs['zip'] = ($_POST['zip1'] ?? '') . "-" . ($_POST['zip2'] ?? '');
					break;
				case 'userid' : // email과 함께 처리
				case 'email' :
					if(isset($_SESSION['seUid']) && $list['bid']==$_SESSION['seUid']) {
						switch($dbinfo['enable_userid'] ?? 'userid') {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
						$qs['email'] = $_SESSION['seEmail'];
					} else {
						$qs['email'] = check_email($qs['email'] ?? '');
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		} // end foreach
	} // end if
	////////////////////////////////////////////
	$sql_set = implode(', ', $set_parts);
	$sql="UPDATE {$dbinfo['table_cate']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . ($dbinfo['sql_set_cate'] ?? '') . " WHERE `$field`='$field_val_safe'";
	db_query($sql);
	
	return true;
}

// 카테고리 삭제부분
function cateDeleteOK($dbinfo,$field,$goto) {
	global $conn;
	$qs=array(
			'passwd'		 =>	"request,trim",
			'cateuid'		 =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다."),
		);
	$qs=check_value($qs);
	$qs[$field] = $qs['cateuid'];
	
	$field_val_safe = db_escape($qs[$field]);
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE `{$field}`='{$field_val_safe}' AND {$dbinfo['sql_where_cate']}";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back('삭제하실 카테고리가 없습니다');

	// 자신과 하위 카테고리 uid 구함($subcate_uid)
	$subcate_uid = []; // init
	$sql="SELECT uid FROM {$dbinfo['table_cate']} WHERE num={$list['num']} AND re LIKE '{$list['re']}%' AND {$dbinfo['sql_where_cate']}";
	$rs2 = db_query($sql);
	while($row = db_array($rs2)) {
		$subcate_uid[] = $row['uid'];
	}
	if(empty($subcate_uid)) return false;
	
	// SQL문 where부분 만들기
	$sql_cate_where = " ( uid IN (" . implode(",", $subcate_uid) . ") ) ";
	$sql_where = " ( cateuid IN (" . implode(",", $subcate_uid) . ") ) ";

	// 해당 카테고리의 DB 데이터가 있다면 삭제못함
	$sql="SELECT count(*) as count FROM {$dbinfo['table']} WHERE $sql_where AND {$dbinfo['sql_where']}";
	$result = db_query($sql);
	$count = $result ? (int)db_array($result)['count'] : 0;
	if($count > 0) {
		back("해당 카테고리와 관련된 DB 데이터가 있습니다.\\n해당 데이터를 먼저 삭제하시기 바랍니다.");
	}

	// 해당 카테고리 삭제
	$sql="DELETE FROM {$dbinfo['table_cate']} WHERE {$sql_cate_where} AND {$dbinfo['sql_where_cate']}";
	db_query($sql);
	
	// 카테고리값 시프트
	if(strlen($list['re']))
		$sql="UPDATE {$dbinfo['table_cate']} SET	
						re=CONCAT( SUBSTRING(re,1,LENGTH('{$list['re']}')-1),
						CHAR(ORD(SUBSTRING(re,LENGTH('{$list['re']}'),1))-1 ),
						SUBSTRING(re,LENGTH('{$list['re']}')+1) )
				WHERE
						num='{$list['num']}'
				AND
						re LIKE '" . substr($list['re'],0,-1) . "%'
				AND
						re > '{$list['re']}'
				AND {$dbinfo['sql_where_cate']}";
	else
		$sql="UPDATE {$dbinfo['table_cate']} SET
					num=num-1
				WHERE
					num > {$list['num']}
				AND {$dbinfo['sql_where_cate']}
			";
	db_query($sql);
	
	return true;
}

function getCateRe($table_cate, $sql_where_cate, $num, $re) {
	global $conn;
	if(trim($sql_where_cate)=="") $sql_where_cate=" 1 ";

	$sql="SELECT re, RIGHT(re,1) as last_char FROM `{$table_cate}` WHERE {$sql_where_cate} AND num='{$num}' AND LENGTH(re)=LENGTH('{$re}')+1 AND LOCATE('{$re}', re)=1 ORDER BY re DESC LIMIT 1";

	$result = db_query($sql);
	$row = $result ? db_array($result) : null;
	if($row) {
		$ord_head = substr($row['re'],0,-1);
		$ord_foot = chr(ord($row['last_char']) + 1);
		$re = $ord_head . $ord_foot;
	}
	else {
		$re .= "1";
	}
	return $re;
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