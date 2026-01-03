<?php
//=======================================================
// 설	명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/11/27 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
	'useApp' => 1, // file_upload()
	'useClassSendmail' =>	1, // mime_mail
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//=================================================== // $_SERFVER -> $_SERVER

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	
	// 1 . 넘어온값 체크

	// 2 . 기본 URL QueryString
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
				"&page=" . ($_REQUEST['page'] ?? '');
				
	if($_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");
	$table_logon = $SITE['th'] . "logon";
	$table_userinfo = $SITE['th'] . "userinfo";
	
	$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) . "/{$SITE['th']}{$dbinfo['db']}" : dirname(__FILE__) . "/upload/{$SITE['th']}{$dbinfo['db']}";

	// 넘어온값 기본 처리
	$qs=array();

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'write':
		write_ok($dbinfo, $qs);
		go_url("list.php?db={$_REQUEST['db']}");
		break;
	case 'modify':
		modify_ok($dbinfo,$qs,'uid');
		go_url("list.php?db={$_REQUEST['db']}");
		break;
	case 'delete':
		delete_ok($dbinfo,'uid',$goto);
		go_url("list.php?db={$_REQUEST['db']}");
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		if( $_REQUEST['mode'] and preg_match("/^[a-z0-9\-\_]+$/i",$_REQUEST['mode']) // eregi -> preg_match
			and function_exists("mode_{$_REQUEST['mode']}") ){
			$func = "mode_{$_REQUEST['mode']}";
			$func();			
		}
		else back('잘못된 요청입니다.');
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================

function write_ok(&$dbinfo, $qs){
	global $db_conn; // mysqli를 위해 추가

	// 권한 검사
	if(!siteAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족) . 확인바랍니다.");
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($_SESSION['seUid']) $qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			} elseif(isset($_POST[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$_POST['passwd']}') ";
				else $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	$sql_set = substr($sql_set,1);
	$sql="INSERT INTO {$dbinfo['table']} SET
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

/*================================ 
			일괄 메일 추가
================================ */
function mode_write_textarea(){
	global $SITE, $dbinfo;
	$count_email = 0;
	
	if($_POST['emaillist']){
		$aSql = array();
		$aEmail = explode("\n",$_POST['emaillist']);
		for($i=0;$i<count($aEmail);$i++){
			if($aEmail[$i]=check_email($aEmail[$i])) 
				$aSql[] = "insert ignore into {$dbinfo['table']} (email) values ('{$aEmail[$i]}')";			
		}

		// sql 실행
		foreach($aSql as $sql){
			db_query($sql); 
			if(db_count()) $count_email++;
		}
	}
	
	back("중복메일 제거하고 {$count_email}개 추가되었습니다","list.php?db={$_REQUEST['db']}");
}
function modify_ok(&$dbinfo,$qs,$field){
	// $qs 추가, 체크후 값 가져오기
	$qs["$field"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	// 수정 권한 체크와 해당 게시물 읽어오기
	if(!siteAuth($dbinfo,"priv_delete")) back("삭제할 권한이 없습니다.");

	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'";
	$list=db_arrayone($sql) or back("게시물이 없습니다.");

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value) { 
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid'] == $_SESSION['seUid']) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
		} // end foreach
	} // end if
	////////////////////////////////
	$sql_set = substr($sql_set,1);
	$sql = "UPDATE {$dbinfo['table']} SET 
				{$sql_set}
			WHERE 
				{$field}='{$qs[$field]}'
		";
	db_query($sql);

	return true;
} // end func.
// 삭제
function delete_ok(&$dbinfo,$field,$goto){
	global $qs_basic, $thisUrl,$urlprefix;
	$qs=array(
			"{$field}" =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	
	$sql_where = ' 1 ' ;

	// 삭제 권한 체크와 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ";
	$list = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");
	if(!siteAuth($dbinfo,"priv_delete")) back("삭제할 권한이 없습니다.");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'");

	return true;
} // end func delete_ok()
/*================================ 
				초기화
================================ */
function mode_reset(){
	global $dbinfo;

	$sql = "UPDATE {$dbinfo['table']} SET status=null, readtime=0, emailcheck=0";
	db_query($sql);
	
	echo "초기화 되었습니다";
	echo "<meta http-equiv='Refresh' Content='0; URL=../list.php'>";
}

/*================================ 
		선택된것 모두 삭제
================================ */
function mode_selectdel(){
	global $dbinfo;
	
	$tmp = explode("_",$_GET['uid']);
	for($i=0; $i<count($tmp); $i++){
		$rs = db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$tmp[$i]}'");
	}
	echo "삭제 되었습니다";
	echo "<meta http-equiv='Refresh' Content='0; URL=list.php?db={$_REQUEST['db']}'>";
}

/*================================ 
		선택된것 모두 미발송으로...
================================ */
function mode_change_sendno(){
	global $dbinfo;

	$tmp = explode("_",$_GET['uid']);
	for($i=0; $i<count($tmp); $i++){
		$rs = db_query("UPDATE {$dbinfo['table']} SET status=null WHERE uid='{$tmp[$i]}'");
	}

	echo "변경 되었습니다";
	echo "<meta http-equiv='Refresh' Content='0; URL=list.php?db={$_REQUEST['db']}'>";
}

/*================================ 
		선택된것 모두 발송으로...
================================ */
function mode_change_sendok(){
	global $dbinfo;

	$tmp = explode("_",$_GET['uid']);
	for($i=0; $i<count($tmp); $i++){
		$rs = db_query("UPDATE {$dbinfo['table']} SET status='SEND' WHERE uid='{$tmp[$i]}'");
	}

	echo "변경 되었습니다";
	echo "<meta http-equiv='Refresh' Content='0; URL=list.php?db={$_REQUEST['db']}'>";
}

function mode_writefromuserdb(){
	global $SITE, $table_logon, $table_userinfo;
	$table_dmailinfo = $SITE['th'] . "dmailinfo";
	
	$sql = "SELECT * from {$table_dmailinfo} WHERE db='{$_POST['db']}'";
	$dbinfo = db_arrayone($sql) or back("db값이 정확하지 않습니다 279L");
	$dbinfo['table'] = $SITE['th'] . "dmail_{$dbinfo['db']}";
	
	// 입력해야할 dmail 테이블 필드 결정
	$skip_fields = array ('uid','status','emailcheck','readtime');
	$dmail_fields = userGetAppendFields($dbinfo['table'], $skip_fields);
	$userinfo_fields = userGetAppendFields($table_userinfo,array('bid'));
	
	// dmail 입력해야할 필드 중에 userinfo 테이블 필드와 일치한 것이 있는지
	$useUserinfo = 0;
	$tmp = array_intersect($dmail_fields, $userinfo_fields);
	if(sizeof($tmp)) $useUserinfo = 1;
	if($_POST['fage'] and $_POST['fsex'])	$useUserinfo = 1;

	// $sql_where 결정
	$sql_where = "";
	if($_POST['fyesmail']) $sql_where = " yesmail=1 ";
	if($_POST['fage']){
		$_POST['agestart'] = (int)$_POST['agestart'];
		$_POST['ageend']	= (int)$_POST['ageend'];
		if(!$_POST['agestart'] or !$_POST['ageend'] and $_POST['agestart']>$_POST['ageend'])
			back('나이를 정확하게 입력하여주세요');
		if($sql_where) $sql_where .= " and ";
		$sql_where .= " (left(u.idnum,2)>='{$_POST['agestart']}' and left(u.idnum,2)<='{$_POST['ageend']}') ";
	}
	if($_POST['fsex']){
		if($sql_where) $sql_where .= " and ";
		if($_POST['sex']) 
			$sql_where .= " ( SUBSTRING(u.idnum,7,2)='-1' or SUBSTRING(u.idnum,7,2)='-3' ) ";
		else 
			$sql_where .= " ( SUBSTRING(u.idnum,7,2)='-2' or SUBSTRING(u.idnum,7,2)='-4' ) ";
	}
	if($_POST['class']){
		$sql_where = " l.class='{$_POST['class']}' ";
	}
	if($_POST['level']){
		$_POST['levelstart'] = (int)$_POST['levelstart'];
		$_POST['levelend']	= (int)$_POST['levelend'];
		if(!$_POST['levelstart'] or !$_POST['levelend'] and $_POST['levelstart']>$_POST['levelend'])
			back('나이를 정확하게 입력하여주세요');
		if($sql_where) $sql_where .= " and ";
		$sql_where .= " (l.level>='{$_POST['levelstart']}' and l.level<='{$_POST['levelend']}') ";	}
	if(!$sql_where) $sql_where = ' 1 ';
	
	
	if( $useUserinfo ) $sql = "SELECT * from {$table_logon} as l, {$table_userinfo} as u where l.uid=u.bid and $sql_where ";
	else $sql = "SELECT * from {$table_logon} as l where $sql_where ";
	
	$rs_email = db_query($sql);
	$count_email = db_count($rs_email);
	$count_insert = 0; // init
	for($i=0;$i<$count_email;$i++){
		$list = db_array($rs_email);

		// sql_set 만들기
		$sql_set = array();
		foreach($dmail_fields as $value){
			$sql_set[] = "$value='".$list[$value]."'";
		}
		$sql_set = implode(',',$sql_set);

		$sql = "insert ignore into {$dbinfo['table']} set {$sql_set} ";
		db_query($sql);
		if(db_count()){
			echo "$list['email'] 메일추가<br>\n";
			$count_insert++;
		}
		else echo "$list['email'] 메일추가실패<br>\n";
	}

	return back('등록완료하였습니다.',"list.php?db={$_POST['db']}");
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
