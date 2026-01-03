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
	'useClassSendmail' =>	1, // mime_mail
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
// Fix typo $_SERFVER to $_SERVER
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

	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	global $db_conn, $SITE;

	$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) . "/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '') : dirname(__FILE__) . "/upload/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '');

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
		$goto = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : (isset($dbinfo['goto_delete']) ? $dbinfo['goto_delete'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		delete_ok($dbinfo,'uid',$goto);
		back("성공적으로 삭제되었습니다","./list.php");	
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================

function write_ok(&$dbinfo, $qs){
	global $SITE, $db_conn;
	
	// 권한 검사
	if(!siteAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			} elseif(isset($_POST[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$_POST[$value]}') ";
				else $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(03/10/20)
	/////////////////////////////////
	$sql_set_file = '';
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = $dbinfo['upload_dir'] . "/" . (int)(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0);

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
		if($upfiles) $sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	}
	/////////////////////////////////

	// 해당 메일 리스트 테이블 만들기
	$table_dmail = (isset($SITE['th']) ? $SITE['th'] : '') . "dmail_" . (isset($_REQUEST['db']) ? $_REQUEST['db'] : '');

	if( !db_istable($table_dmail) ){
		// 업로드 - not test
		if( isset($_FILES['file']) ){
			$updir = realpath("./") . "/upload";
	
			// 사용변수 초기화
			$upfiles=array();
			$upfiles_totalsize=0;
			if(isset($_FILES['file']['name']) && $_FILES['file']['name']) { // 파일이 업로드 되었다면
				$upfiles['file']=file_upload("file",$updir);
				$upfiles_totalsize = $upfiles['file']['size'];
				
			}
			$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		}
		
		$tpl_field = explode(':', (isset($qs['fields']) ? $qs['fields'] : ''));
		
		$sql = "CREATE TABLE {$table_dmail} (
			uid int(10) unsigned NOT NULL auto_increment,
			`email` varchar(255) NOT NULL default '',"; 
		for($i=0; $i<count($tpl_field); $i++){
			$tpl_field[$i] = trim($tpl_field[$i]);
			if(!$tpl_field[$i]) continue;
			if(!preg_match("/^[a-z0-9\-\_]+$/i", $tpl_field[$i])) continue;
			
			if( !in_array($tpl_field[$i],array('uid','email','status','emailcheck','readtime')) ){
				$sql = $sql . "`" . $tpl_field[$i] . "` varchar(255) not null, ";
			}
		}
		$sql = $sql . "
				`status` enum('SEND','READ','FAIL') default NULL,
				`emailcheck` tinyint(4) NOT NULL default '0',
				`readtime` int(10) unsigned NOT NULL default '0',
				PRIMARY KEY	(`uid`),
				UNIQUE KEY `email` (`email`),				
				KEY `status` (`status`,`emailcheck`)
			) ENGINE=MyISAM COMMENT='sitePHPbasic 대량메일발송 1.0 - 2003/12/15' ;";
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
	global $db_conn, $SITE, $thisUrl, $urlprefix;
	// $qs 추가, 체크후 값 가져오기
	$qs[$field]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	// 수정 권한 체크와 해당 게시물 읽어오기
	$list = false;
	$sql = "";
	if(siteAuth($dbinfo,"priv_delete")) { // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'";
	} elseif(isset($_SESSION['seUid'])) { // 회원의 글이라면,
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and bid='{$_SESSION['seUid']}'";
	} else { // 비회원의 글이라면
		if(isset($_POST['passwd'])){
			$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and passwd=password('{$_POST['passwd']}')";
		}
	} // end if
	$list=db_arrayone($sql) or back("게시물이 없거나 수정할 권한이 없습니다");

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'h_yesno' : // checkbox 경우 체크되지 않으면 않 넘어옮
				case 'tpl_yesno' :
					if(!isset($_POST[$value])) $_POST[$value] = 0;
					break;
			}

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
		} // end foreach
	} // end if
	////////////////////////////////

	///////////////////////////////
	// 파일 업로드 - 변경(03/10/20)
	///////////////////////////////
	$sql_set_file = '';
	if( (isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N') and isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = $dbinfo['upload_dir'] . "/" . (int)(isset($list['bid']) ? $list['bid'] : 0);

		// 기존 업로드 파일 정보 읽어오기
		$upfiles = array();
		if(isset($list['upfiles']) && $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
				$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
			}
		}
		$upfiles_totalsize=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>	$value){
				if(isset($_REQUEST["del_{$key}"])) { 
						// 해당 파일 삭제
						$file_path = $dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '');
						$file_path2 = $dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '');

						if(is_file($file_path)){
							@unlink($file_path);
							@unlink($file_path.".thumb.jpg"); // thumbnail 삭제
						}
						elseif(is_file($file_path2)){
							@unlink($file_path2);
							@unlink($file_path2.".thumb.jpg"); // thumbnail 삭제
						}

						$upfiles_totalsize -= $upfiles[$key]['size'];
						unset($upfiles[$key]);
				}
			}
		}

		// 업로드 파일 처리
		if($dbinfo['enable_upload'] == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$ok_upload = 0;
				if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION)); //확장자 
					if(in_array($ext,$allow_extension)) $ok_upload = 1;
				}
				else $ok_upload = 1;

				if($ok_upload){
					// 기존 업로드 파일이 있다면 삭제
					$file_path = $dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '');
					$file_path2 = $dbinfo['upload_dir'] . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '');
					if(is_file($file_path)){
						@unlink($file_path);
						@unlink($file_path.".thumb.jpg"); // thumbnail 삭제
					}
					elseif(is_file($file_path2)){
						@unlink($file_path2);
						@unlink($file_path2.".thumb.jpg"); // thumbnail 삭제
					}

					// 업로드
					$upfiles_tmp=file_upload("upfile",$updir);
					$upfiles_totalsize	= $upfiles_tmp['size'];
					$upfiles['upfile']	= $upfiles_tmp;
					unset($upfiles_tmp);
				}
			}
		} else { // 복수 업로드라면,
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

					// 기존 업로드 파일이 있다면 삭제
					$file_path = $dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '');
					$file_path2 = $dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '');
					if(is_file($file_path)){
						@unlink($file_path);
						@unlink($file_path.".thumb.jpg"); // thumbnail 삭제
					}
					elseif(is_file($file_path2)){
						@unlink($file_path2);
						@unlink($file_path2.".thumb.jpg"); // thumbnail 삭제
					}

					// 업로드
					$upfiles_tmp=file_upload($key,$updir);
					$upfiles_totalsize = $upfiles_totalsize - (isset($upfiles[$key]['size']) ? $upfiles[$key]['size'] : 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if .. else ..
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and count($upfiles) == 0){
			if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']) 
				back("다음의 파일 확장자만 업로드 가능합니다.\\n{$dbinfo['enable_uploadextension']}");
			if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		if($upfiles) $sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	}
	///////////////////////////////

	$sql = "UPDATE {$dbinfo['table']} SET 
				rdate	=UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
			WHERE 
				{$field}='{$qs[$field]}'
		";
	db_query($sql);

	return true;
} // end func.

// 삭제
function delete_ok(&$dbinfo,$field,$goto){
	global $SITE, $qs_basic, $thisUrl,$urlprefix, $db_conn;
	$qs=array(
			$field =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	
	$sql_where = '1';
	if(isset($dbinfo['table_name']) && isset($dbinfo['db']) && $dbinfo['table_name'] != $dbinfo['db']){
		$sql_where = " db='{$dbinfo['db']}'";
	}

	// 삭제 권한 체크와 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ";
	$list = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");
	if(!siteAuth($dbinfo,"priv_delete")) back("삭제할 권한이 없습니다.");

	// 업로드 파일 삭제 준비
	$del_uploadfile = array(); // init
	if(isset($list['upfiles']) && $list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles = [];
			$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
			$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name']) && $value['name']){
				$file_path = $dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($value['name']) ? $value['name'] : '');
				$file_path2 = $dbinfo['upload_dir'] . "/" . (isset($value['name']) ? $value['name'] : '');
				if(is_file($file_path)){
					$del_uploadfile[] = $file_path;
					$del_uploadfile[] = $file_path.".thumb.jpg";
				}
				elseif(is_file($file_path2)){
					$del_uploadfile[] = $file_path2;
					$del_uploadfile[] = $file_path2.".thumb.jpg";
				}
			} // end if
		} // end foreach
	} // end if

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'");

	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value){
			@unlink($value);
		}
	} // end if
	
	// 해당 메일리스트 테이블 삭제
	$table_dmail = (isset($SITE['th']) ? $SITE['th'] : '') . "dmail_" . (isset($list['db']) ? $list['db'] : '');
	db_query("DROP TABLE IF EXISTS {$table_dmail}");

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
?>
