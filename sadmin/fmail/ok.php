<?php
//=======================================================
// 설 명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/24
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/04/16 박선민 마지막 수정
// 04/05/24 박선민 일부 수정
// 24/05/18 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useClassSendmail' =>	1,
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
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

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

	if(isset($_REQUEST['getinfo']) && $_REQUEST['getinfo'] == "cont")
		$qs_basic .= "&html_type=". (isset($_REQUEST['html_type']) ? $_REQUEST['html_type'] : '') ."&html_skin=". (isset($_REQUEST['html_skin']) ? $_REQUEST['html_skin'] : '') ."&skin=". (isset($_REQUEST['skin']) ? $_REQUEST['skin'] : '');

	include_once("{$thisPath}/config.php"); // $dbinfo 정의
	
	$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) . "/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '') : dirname(__FILE__) . "/upload/". (isset($SITE['th']) ? $SITE['th'] : '') . (isset($dbinfo['db']) ? $dbinfo['db'] : '');

	//===================
	// SQL문 where절 정리
	//===================
	if(!isset($sql_where) || !$sql_where) $sql_where=" 1 "; // $sql_where 사용 마무리

	// 넘어온값 기본 처리
	$sql_set="";
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
		$goto = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : (isset($dbinfo['goto_write']) ? $dbinfo['goto_write'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		go_url($goto);
		break;
	case 'modify':
		modify_ok($dbinfo,$qs,'uid');
		$goto = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : (isset($dbinfo['goto_modify']) ? $dbinfo['goto_modify'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		go_url($goto);
		break;
	case 'delete':
		$goto = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : (isset($dbinfo['goto_delete']) ? $dbinfo['goto_delete'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		delete_ok($dbinfo,'uid',$goto);
		go_url($goto);
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function write_ok($dbinfo, $qs){
	if(!boardAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");

	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);

	$sql_where = " 1 ";
	if(isset($dbinfo['table_name']) && isset($dbinfo['db']) && $dbinfo['table_name'] != $dbinfo['db']){
		$sql_where = " db='{$dbinfo['db']}'";
	}

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} WHERE  $sql_where ";
					$qs['num'] = db_resultone($sql,0,"max(num)");	
					if (!isset($qs['num'])){
						$qs['num'] = 1;
					} else {
						$qs['num']++;
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", {$value} = '" . db_escape($qs[$value]) . "' ";
			} elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . "' ";
		}
	}
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(03/10/20)
	/////////////////////////////////
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = $dbinfo['upload_dir'] . "/" . (int)(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0);

		// 사용변수 초기화
		$upfiles=array();
		$upfiles_totalsize=0;
		if($dbinfo['enable_upload'] == 'Y'){
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles['upfile']=file_upload("upfile",$updir);
				$upfiles_totalsize = $upfiles['upfile']['size'];
			}
		} else {
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
						AND !is_array(getimagesize($_FILES[$key]['tmp_name'])) )
						continue;
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if .. esle ..
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and sizeof($upfiles) == 0){
			if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	} else {
		$sql_set_file = "";
	}
	/////////////////////////////////

	$sql="INSERT
			INTO
				{$dbinfo['table']}
			SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	$uid = db_insert_id();

	// E-Mail 전송
	/*
	if( isset($dbinfo['enable_adm_mail']) && $dbinfo['enable_adm_mail'] == 'Y'){
		$mail = new mime_mail;

		$mail->from		= $dbinfo['email'];
		$mail->name		= "게시판 자동메일";
		$mail->to		= $list['email'];
		$mail->subject	= "[답변] {$qs['title']}";
		if(isset($qs['docu_type']) && $qs['docu_type'] == "html"){
			$mail->body	= "[" . (isset($list['userid']) ? $list['userid'] : '') ."]님께서 다음과 같은 답변을 주었습니다.]<br><hr>" . (isset($list['content']) ? $list['content'] : '');
			$mail->html	= 1;
		} else {
			$mail->body	= "[" . (isset($list['userid']) ? $list['userid'] : '') . "]님께서 다음과 같은 답변을 주었습니다.]\n--------------------------------------------\n" . (isset($list['content']) ? $list['content'] : '');
			$mail->html	= 0;
		}
		$mail->send();
	}
	*/

	return $uid;
} // end func.

function modify_ok($dbinfo,$qs,$field){
	// $qs 추가,변경
	$qs[$field]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	$sql_where = " 1 ";
	if(isset($dbinfo['table_name']) && isset($dbinfo['db']) && $dbinfo['table_name'] != $dbinfo['db']){
		$sql_where = " db='{$dbinfo['db']}'";
	}

	// 수정 권한 체크와 해당 게시물 읽어오기
	$list = false;
	$sql = "";
	if(boardAuth($dbinfo,"priv_delete")) { // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}'";
	} elseif(isset($_SESSION['seUid'])) { // 회원의 글이라면,
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'";
	} else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		if(isset($qs['passwd'])){
			$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and passwd=password('{$qs['passwd']}')";
		}
	}
	if($sql){
		$list=db_arrayone($sql) or back("게시물이 없거나 수정할 권한이 없습니다");
	} else {
		back("게시물이 없거나 수정할 권한이 없습니다");
	}

	$sql_set = "";
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} WHERE  $sql_where ";
					$qs['num'] = db_resultone($sql,0,"max(num)");
					if (!isset($qs['num'])){
						$qs['num'] = 1;
					} else {
						$qs['num']++;
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", {$value} = '" . db_escape($qs[$value]) . "' ";
			} elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . "' ";
		}
	}
	////////////////////////////////

	///////////////////////////////
	// 파일 업로드 - 변경(03/10/20)
	///////////////////////////////
	$sql_set_file = '';
	if( (isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N') and isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = $dbinfo['upload_dir'] . "/" . (int)(isset($list['bid']) ? $list['bid'] : 0);

		// 기존 업로드 파일 정보 읽어오기
		$upfiles=unserialize(isset($list['upfiles']) ? $list['upfiles'] : '');
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles = [];
			$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
			$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
		}
		$upfiles_totalsize=isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0;

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>	$value){
				if(isset($_REQUEST["del_{$key}"])) {
						// 해당 파일 삭제
						if( is_file($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '')) ){
							@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : ''));
							@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
						}
						elseif( is_file($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '')) ){
							@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : ''));
							@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
						}
						if(isset($upfiles[$key]['size'])){
							$upfiles_totalsize -= $upfiles[$key]['size'];
						}
						unset($upfiles[$key]);
				}
			}
		}

		// 업로드 파일 처리
		if($dbinfo['enable_upload'] == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				// 기존 업로드 파일이 있다면 삭제
				if( is_file($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '')) ){
					@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : ''));
					@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
				}
				elseif( is_file($dbinfo['upload_dir'] . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '')) ){
					@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : ''));
					@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles['upfile']['name']) ? $upfiles['upfile']['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
				}

				// 업로드
				$upfiles_tmp=file_upload("upfile",$updir);
				$upfiles_totalsize	= $upfiles_tmp['size'];
				$upfiles['upfile']	= $upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
						AND !is_array(@getimagesize($_FILES[$key]['tmp_name'])) )
						continue;

					// 기존 업로드 파일이 있다면 삭제
					if( is_file($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '')) ){
						@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : ''));
						@unlink($dbinfo['upload_dir'] . "/" . (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
					}
					elseif( is_file($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '')) ){
						@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : ''));
						@unlink($dbinfo['upload_dir'] . "/" . (isset($upfiles[$key]['name']) ? $upfiles[$key]['name'] : '') . ".thumb.jpg"); // thumbnail 삭제
					}
					// 업로드
					$upfiles_tmp=file_upload($key,$updir);
					$upfiles_totalsize = $upfiles_totalsize - (isset($upfiles[$key]['size']) ? $upfiles[$key]['size'] : 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if .. else ..
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and sizeof($upfiles) == 0){
			if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} else {
		$sql_set_file = '';
	}
	///////////////////////////////

	$sql = "UPDATE
				{$dbinfo['table']}
			SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid='{$qs['uid']}'
		";
	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	if( (isset($qs['cateuid']) ? $qs['cateuid'] : null) <> (isset($list['cateuid']) ? $list['cateuid'] : null) ){
		db_query("update {$dbinfo['table']} set cateuid='{$qs['cateuid']}' where db='{$list['db']}' and type='{$list['type']}' and num='{$list['num']}'");
	} // end if
	
	return true;
} // end func.

// 삭제
function delete_ok($dbinfo,$field,$goto){
	global $qs_basic, $thisUrl;
	$qs=array(
			"$field" =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);

	$sql_where = '1';
	if(isset($dbinfo['table_name']) && isset($dbinfo['db']) && $dbinfo['table_name'] != $dbinfo['db']){
		$sql_where = " db='{$dbinfo['db']}'";
	}

	// 삭제 권한 체크와 해당 게시물 읽어오기
	$list = false;
	$sql = "";
	if(boardAuth($dbinfo,"priv_delete")) {// 게시판 전체 삭제 권한을 가졌다면
		$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ";
	} else {
		if(isset($qs['passwd'])){
			$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ";
		} else {
			$sql = "SELECT *,null as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ";
		}
	}
	$list = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	if(!boardAuth($dbinfo,"priv_delete")) {// 게시판 전체 삭제 권한을 가졌다면
		if((isset($list['bid']) ? $list['bid'] : 0) == 0 && (isset($list['passwd']) ? $list['passwd'] : '') != (isset($list['pass']) ? $list['pass'] : '')){
			if(isset($_SERVER['QUERY_STRING']))
				back("비밀번호를 입력하여 주십시오","{$thisUrl}/delete.php?{$_SERVER['QUERY_STRING']}");
			else back("비밀번호를 정확히 입력하십시오");
		} elseif ((isset($list['bid']) ? $list['bid'] : 0) > 0 and (isset($list['bid']) ? $list['bid'] : 0) != (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0)) back("올린이가 아님니다.");
	}

	///////////////////////////////
	// 파일 업로드 - 삭제(03/10/20)
	///////////////////////////////
	if(isset($list['upfiles']) && $list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles = [];
			$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
			$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name']) && $value['name']){
				if( is_file($dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($value['name']) ? $value['name'] : '')) ){
					@unlink($dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($value['name']) ? $value['name'] : ''));
					@unlink($dbinfo['upload_dir'] . "/". (isset($list['bid']) ? $list['bid'] : '') . "/" . (isset($value['name']) ? $value['name'] : '') . ".thumb.jpg"); // thumbnail파일도
				}
				elseif( is_file($dbinfo['upload_dir'] . "/" . (isset($value['name']) ? $value['name'] : '')) ){
					@unlink($dbinfo['upload_dir'] . "/" . (isset($value['name']) ? $value['name'] : ''));
					@unlink($dbinfo['upload_dir'] . "/" . (isset($value['name']) ? $value['name'] : '') . ".thumb.jpg"); // thumbnail파일도
				}
			} // end if
		} // end foreach
	} // end if
	///////////////////////////////
	
	db_query("DELETE FROM {$dbinfo['table']} where uid='{$list['uid']}' and  $sql_where ");
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
