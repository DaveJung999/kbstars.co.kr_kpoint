<?php
//=======================================================
// 설 명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/11/26 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'auth' => 2, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
	'useApp' => 1, // file_upload()
	'useClassSendmail' =>	1 // mime_mail
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $_SERVER['HTTP_HOST']);

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
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? ($table ?? '')) .			//table 이름
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

	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont")
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$skip_fields = array('uid'); // uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
	if($fieldlist = userGetAppendFields(isset($dbinfo['table']) ? {$dbinfo['table']} : '', $skip_fields)) {
		foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
	}		
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once("{$thisPath}/config.php");
	
	$dbinfo['upload_dir'] = trim(isset($dbinfo['upload_dir']) ? $dbinfo['upload_dir'] : '') ? trim($dbinfo['upload_dir']) . "/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '') : dirname(__FILE__) . "/upload/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '');

	// 넘어온값 기본 처리
	$qs=array(
				'title' =>	"post,trim,notnull=" . urlencode("제목을 입력하시기 바랍니다."),
				'content' =>	"post,trim"
		);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch(isset($_REQUEST['mode']) ? $_REQUEST['mode'] : null){
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		back("신규 대량메일발송이 준비되었습니다.","./list.php");
		break;
	case 'modify':
		modify_ok($dbinfo,$qs,'uid');
		back("성공적으로 수정되었습니다","./list.php");
		break;
	case 'delete':
		delete_ok($dbinfo);
		back("성공적으로 삭제되었습니다","./list.php");	
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================

function write_ok(&$dbinfo, $qs){
	global $SITE;
	
	// 권한 검사
	if(!siteAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields(isset($dbinfo['table']) ? {$dbinfo['table']} : '', $skip_fields)){
		foreach($fieldlist as $value){
			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", `{$value}` = '" . db_escape($qs[$value]) . "' ";
			} elseif(isset($_POST[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('" . db_escape($_POST[$value]) . "') ";
				else $sql_set .= ", `{$value}` = '" . db_escape($_POST[$value]) . "' ";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(03/10/20)
	/////////////////////////////////
	$sql_set_file = '';
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = (isset($dbinfo['upload_dir']) ? $dbinfo['upload_dir'] : '') . "/" . (int)(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0);

		// 사용변수 초기화
		$upfiles=array();
		$upfiles_totalsize=0;
		if($dbinfo['enable_upload'] == 'Y'){
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']) { // 특정 확장자만 사용가능하면
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION)); //확장자
					if(in_array($ext,$allow_extension)){
						$upfiles['upfile']=file_upload("upfile",$updir);
						$upfiles_totalsize = $upfiles['upfile']['size'];
					}
				} else {
					$upfiles['upfile']=file_upload("upfile",$updir);
					$upfiles_totalsize = $upfiles['upfile']['size'];
				}
			}
		} else {
			foreach($_FILES as $key =>	$value){
				if(isset($value['name']) && $value['name']) { // 파일이 업로드 되었다면
					if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
						$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
						$ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION)); //확장자
						if(!in_array($ext,$allow_extension)) continue;
					}
					if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
						AND !is_array(@getimagesize($_FILES[$key]['tmp_name'])) )
						continue;
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if .. esle ..
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and count($upfiles) == 0){
			if( isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension'])
				back("다음의 파일 확장자만 업로드 가능합니다.\\n{$dbinfo['enable_uploadextension']}");
			if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		if($upfiles) $sql_set_file = ", upfiles='".db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	}
	/////////////////////////////////

	// 해당 메일 리스트 테이블 만들기
	$db_name = preg_replace("/[^a-zA-Z0-9_]/", "", ($_REQUEST['db'] ?? ''));
	if (empty($db_name)) {
		back("잘못된 DB 이름입니다.");
	}
	$table_dmail = ($SITE['th'] ?? '') . "dmail_" . $db_name;

	if( !db_istable($table_dmail) ){
		$tpl_field = explode(':', ($qs['fields'] ?? ''));
		
		$sql = "CREATE TABLE `$table_dmail` (
			`uid` int(10) unsigned NOT NULL auto_increment,
			`email` varchar(255) NOT NULL default '',";
		for($i=0; $i<count($tpl_field); $i++){
			$field_name = trim($tpl_field[$i]);
			if(!$field_name) continue;
			// 필드명에 허용된 문자(영문, 숫자, -, _)만 사용하도록 제한
			if(!preg_match("/^[a-zA-Z0-9\-_]+$/", $field_name)) continue;
			
			if( !in_array($field_name, array('uid','email','status','emailcheck','readtime')) ){
				$sql .= "`" . $field_name . "` varchar(255) not null, ";
			}
		}
		$sql .= "
			`status` enum('SEND','READ','FAIL') default NULL,
			`emailcheck` tinyint(4) NOT NULL default '0',
			`readtime` int(10) unsigned NOT NULL default '0',
			PRIMARY KEY (`uid`),
			UNIQUE KEY `email` (`email`),
			KEY `status` (`status`,`emailcheck`)
			) ENGINE=MyISAM COMMENT='sitePHPbasic 대량메일발송 1.0 - " . date('Y/m/d') . "' ;";
		db_query($sql);
	}
	else {
		back("이미 존재하는 테이블 입니다.");
	}	
	
	$sql="INSERT INTO {$dbinfo['table']} SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs,$field){
	// $qs 추가, 체크후 값 가져오기
	$qs[$field]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	$uid = (int)$qs[$field];

	// 수정 권한 체크와 해당 게시물 읽어오기
	$list = false;
	$sql = "";
	if(siteAuth($dbinfo,"priv_delete")) { // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE `{$field}`='{$uid}'";
	} elseif(isset($_SESSION['seUid'])) { // 회원의 글이라면,
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE `{$field}`='{$uid}' and bid='" . (int)$_SESSION['seUid'] . "'";
	} else { // 비회원의 글
		if(isset($_POST['passwd'])){
			$passwd = db_escape($_POST['passwd']);
			$sql = "SELECT * FROM {$dbinfo['table']} WHERE `{$field}`='{$uid}' and passwd=password('{$passwd}')";
		}
	} // end if
	$list=db_arrayone($sql) or back("게시물이 없거나 수정할 권한이 없습니다");

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate', 'fdate');
	if($fieldlist = userGetAppendFields(isset($dbinfo['table']) ? {$dbinfo['table']} : '', $skip_fields)){
		foreach($fieldlist as $value){
			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", `{$value}` ='".db_escape($qs[$value]) . "'";
			elseif(isset($_POST[$value])) $sql_set .= ", `{$value}` ='".db_escape($_POST[$value]) . "'";
		} // end foreach
	} // end if

	$sql = "UPDATE {$dbinfo['table']} SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set}
			WHERE
				uid='{$uid}'
		";
	db_query($sql);

	return true;
} // end func.
// 삭제
function delete_ok(&$dbinfo){
	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid' =>	'request,trim,notnull='	. urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd' =>	'request,trim'
		);
	$qs=check_value($qs);
	$uid = (int)$qs['uid'];
	$passwd = $qs['passwd'] ?? '';

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('".db_escape($passwd) . "') as pass FROM {$dbinfo['table']} WHERE uid='{$uid}' LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');

	// 삭제 권한 체크
	if(!privAuth(($dbinfo ?? null),'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if( 'nobid' == (isset($dbinfo['priv_delete']) ? substr($dbinfo['priv_delete'],0,5) : '') )
			back('삭제하실 수 없습니다.');
		elseif(isset($list['bid']) && $list['bid']>0) { // 회원이면
			if(($list['bid'] ?? 0) != ($_SESSION['seUid'] ?? 0))
				back('삭제하실 수 없습니다.');
		} else { // 비회원이면 passwd 검사
			if(($list['passwd'] ?? '') != ($list['pass'] ?? '')){
				if(isset($_SERVER['QUERY_STRING']))
					back('비밀번호를 입력하여 주십시오',$thisUrl.'delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	userDeleteBoard2DB(($list['db'] ?? null)); // 해당 게시판 테이블 삭제

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$uid}'");
	return true;
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

// 해당 게시판 테이블 삭제
// $forceDelete - 1값이면 무조건 삭제 - 하지만 구현안됨
function userDeleteBoard2DB($db,$forceDelete=0) { // 05/01/24 박선민
	global $SITE;
	// 넘어온값 체크
	if(!$db) return false;
	
	// 테이블 이름에 허용되지 않는 문자가 있는지 확인
	$db_sanitized = preg_replace("/[^a-zA-Z0-9_]/", "", $db);
	if ($db !== $db_sanitized) {
		return false; // 허용되지 않는 문자가 포함된 경우 중단
	}

	$prefix = 'board2';
	$table = ($SITE['th'] ?? '') .$prefix.'_'.$db_sanitized;
	
	// 게시물이 있으면 삭제불가
	if (db_istable($table)) {
		$sql = "select count(*) as count from {$table}";
		$count_db = db_resultone($sql,0,'count');
		if($forceDelete == 0 and $count_db) back('해당 게시판에 데이터가 있어서 삭제를 취소합니다.');
		
		// 테이블 삭제
		db_query('DROP TABLE IF EXISTS '.$table.'');
		db_query('DROP TABLE IF EXISTS '.$table.'_cate');
		db_query('DROP TABLE IF EXISTS '.$table.'_memo');
	}
	// 업로드된 것도 삭제 - 하지만 구현 안됨
}

?>
