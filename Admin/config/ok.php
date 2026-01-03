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
// 25/08/11 Gemini	PHP 7 마이그레이션
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=array(
	'priv' => 99, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useImage' => 1, // thumbnail()
	'useClassSendmail' =>	1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
// page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//=================================================== // 이 함수가 정의되지 않아 주석 처리함.

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
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
			"&sc_string=" . urlencode(stripslashes($sc_string ?? '')) . //search string
			"&page=" . ($_REQUEST['page'] ?? '');
			
include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$thisPath	= dirname(__FILE__);
$thisUrl	= "/Admin/config"; // 마지막 "/"이 빠져야함
$prefix		= "board2"; // board? album? 등의 접두사

if( isset($_POST['db']) ) $dbinfo['db'] = $_POST['db'];

$dbinfo['upload_dir'] = trim($dbinfo['upload_dir'] ?? '') ? trim($dbinfo['upload_dir']) : dirname(__FILE__) . "/../../sboard2/upload/{$SITE['th']}{$prefix}_{$dbinfo['db']}" ;
$dbinfo['upload_dir'] = str_replace("\\", "/", $dbinfo['upload_dir']);

// 넘어온값 기본 처리
$sql_set="";

// $sql_where, $sql_set
if(($dbinfo['enable_type'] ?? 'N') == 'Y'){
	$sql_where	= (isset($mode, $writeinfo) && $mode == "write" && $writeinfo == "info") ? " type='info' " : " type='docu' ";
	$sql_set	= (isset($mode, $writeinfo) && $mode == "write" && $writeinfo == "info") ? ", type='info' " : ", type='docu' ";
}

$qs=array(
			"title" =>	"post,trim",
			"content" =>	"post,trim",
			"skin" =>	"post,trim",
			"data0" =>	"post,trim",
			"data1" =>	"post,trim",
			"data2" =>	"post,trim",
			"data3" =>	"post,trim",
			"data4" =>	"post,trim",
			"data5" =>	"post,trim",
			"data6" =>	"post,trim",
			"data7" =>	"post,trim",
			"data8" =>	"post,trim",
			"data9" =>	"post,trim",
			"data10" =>	"post,trim",
			"mode" =>	"post,trim",
			"new_images" =>	"post,trim",
			"check_default" =>	"post,trim"
	);
	
if(!isset($sql_where)) $sql_where = " 1 ";
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// info 테이블 정보 가져와서 $dbinfo로 저장

if($mode != ""){
	switch($mode){
		case 'write':
			if(!privAuth($dbinfo, "priv_write")) back("이용이 제한되었습니다(레벨부족) . 확인바랍니다.");
			$uid_new = write_ok();
			go_url("{$goto}?" . href_qs("uid={$uid_new}&goto={$goto}",$qs_basic));
			break;
		case 'modify':
			modify_ok();
			back("컨텐츠가 수정되었습니다.");
			break;
		case 'delete':
			delete_ok();
			go_url("{$goto}?" . href_qs("goto={$goto}",$qs_basic));
			break;
		case 'mailmessage':
			mailmessage_ok();
			go_url(($_REQUEST['goto'] ?? "{$thisUrl}/mailmessage.php?") . href_qs("uid={$uid}",$qs_basic),0,"설정이 완료되었습니다.");
			break;
		case 'modify_sitedesign':
			modify_sitedesign_ok();
			go_url("{$goto}?" . href_qs("uid={$uid}&goto={$goto}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'modify_logo':
			modify_logo_ok();
			go_url("logo.php?" . href_qs("uid={$uid}&goto={$goto}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'write_banner':
			if(!privAuth($dbinfo, "priv_write")) back("이용이 제한되었습니다(레벨부족) . 확인바랍니다.");
			$uid_new = write_banner_ok();
			go_url("{$goto}?" . href_qs("uid={$uid_new}&goto={$goto}",$qs_basic));
			break;
		case 'modify_banner':
			modify_banner_ok();
			go_url("{$goto}?" . href_qs("uid={$uid}&goto={$goto}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'delete_banner':
			delete_banner_ok();
			go_url("{$goto}?" . href_qs("goto={$goto}",$qs_basic));
			break;
		case 'write_bank':
			if(!privAuth($dbinfo, "priv_write")) back("이용이 제한되었습니다(레벨부족) . 확인바랍니다.");
			write_bank_ok();
			go_url("./bank.php?" . href_qs($qs_basic));
			break;
		case 'modify_bank':
			modify_bank_ok();
			go_url("./bank.php?" . href_qs($qs_basic), 0, "수정되었습니다.");
			break;
		case 'delete_bank':
			delete_bank_ok();
			go_url("./bank.php?" . href_qs($qs_basic));
			break;
		case 'sysconfig':
			sysconfig_ok();
			go_url("./index.php?" . href_qs($qs_basic), 0, "수정되었습니다.");
			break;
		case 'popWrite':
			$uid = popWrite_ok();
			go_url("./popup.php?" . href_qs("modify_uid={$uid}", $qs_basic), 0, "등록되었습니다.\\n\\n아래에서 내용을 입력하시기 바랍니다.");
			break;
		case 'popModify':
			popModify_ok();
			go_url("./popup.php?" . href_qs("modify_uid={$uid}", $qs_basic), 0, "수정되었습니다.");
			break;
		case 'popDelete':
			$uid_new = popDelete_ok();
			go_url("./popup.php?" . href_qs($qs_basic), 0, "삭제되었습니다.");
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다");
	}
}
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok(){
	global $qs, $dbinfo, $table, $sql_where, $sql_set;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	if(isset($qs['data4'])) check_url($qs['data4']);

	// 값 추가
	$qs['db']		= $dbinfo['db'] ?? '';
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);

	$sql_set=", `data1`		='1',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "'
			";
	
	//===========
	// 파일 업로드
	//===========
	$sql_set_file = '';
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		$updir = ($dbinfo['upload_dir'] ?? '') . "/";

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
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if . . esle ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//===========//

	if ( ($qs['content'] ?? '') != 'banner'){
		//사용함 값 초기화
		$sql_data = "UPDATE {$table} SET data1 = '' where content = '{$qs['content']}' ";
		db_query($sql_data);
	}
	$max=db_resultone("SELECT max(num) FROM {$table} WHERE  $sql_where ", 0, "max(num)") + 1;

	$sql="INSERT
			INTO
				{$table}
			SET
				db		='{$qs['db']}',
				num		='{$max}',
				bid		='{$qs['bid']}',
				userid	='{$qs['userid']}',
				passwd	=PASSWORD('" . ($qs['passwd'] ?? '') . "'),
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='" . ($qs['content'] ?? '') . "',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	return db_insert_id();
} // end func.

function modify_ok(){
	global $qs, $dbinfo, $table, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();
	
	// 수정 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and passwd=PASSWORD('" . ($qs['passwd'] ?? '') . "')");
	} // end if
	$list = db_count() ? db_array($rs_modify) : back("게시물이 없거나 수정할 권한이 없습니다");
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	
	$sql_set=", `data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "',
				`data8`		='" . ($qs['data8'] ?? '') . "',
				`data9`		='" . ($qs['data9'] ?? '') . "',
				`data10`	='" . ($qs['data10'] ?? '') . "'
			";
	
	//==========================
	// 파일 업로드 - 변경(modify)
	//==========================
	$sql_set_file = '';
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		// 기존 업로드 파일 정보 읽어오기
		$upfiles = unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일 업로드
		$updir = ($dbinfo['upload_dir'] ?? '') . "/" . (int)$list['bid'];
		
		if(($dbinfo['enable_upload'] ?? 'N') == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles_tmp=file_upload("upfile",$updir);

				// 기존 업로드 파일이 있다면 삭제
				if( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);
				elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);

				$upfiles_totalsize = $upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0) + $upfiles_tmp['size'];
				$upfiles['upfile']=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					$upfiles_tmp=file_upload($key,$updir);

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);

					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//========================== //
	$content = $qs['content'] ?? '';
	if ( $content != 'banner'){
		//사용함 값 초기화
		$sql_data = "UPDATE {$table} SET data1 = '' where content = '{$content}' ";
		db_query($sql_data);
	}
	$sql = "UPDATE
				{$table}
			SET
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='{$content}',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}',
				priv_level	='" . ($qs['priv_level'] ?? 0) . "'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$qs['uid']}
		";
	db_query($sql);

	return true;
} // end func.
// 삭제
function delete_ok(){
	global $dbinfo, $table, $qs_basic, $sql_where;
	$qs=array(
			'uid' =>	"request,tirm,notnull=" . urlencode("삭제할 게시물의 고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	$list = array();

	// 삭제 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면
		if(isset($qs['passwd']) && $qs['passwd'])
			$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}' and passwd=PASSWORD('{$qs['passwd']}')");
		else
			go_url("./delete.php?" . href_qs("uid={$qs['uid']}",$qs_basic),0,"비밀번호를 입력하십시오"); // 비밀번호 입력 페이지로 이동
	} // end if

	$list = db_count() ? db_array($rs_delete) : back("게시물이 이미 삭제되었거나 권한이 없습니다.");

	if(isset($list['upfiles'])){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name'])){
				if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			} // end if
		} // end foreach
	} // end if

	$rs_subre = db_query("SELECT * FROM {$table} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	while($row=db_array($rs_subre)){
		if(isset($row['upfiles'])){
			$upfiles=unserialize($row['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name'] = $row['upfiles'] ?? '';
				$upfiles['upfile']['size'] = (int)($row['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name'])){
					if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
					elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				} // end if
			} // end foreach
		} // end if
	} // end while
	
	db_query("DELETE FROM {$table} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	db_query("DELETE FROM {$table} where $sql_where and uid='{$list['uid']}'");
	
	if(isset($del_uploadfile) && is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value) @unlink($value);
	} // end if
	
	return true;
} // end func delete_ok()

function mailmessage_ok(){
	global $dbinfo, $table_mail_message, $sql_set;

	$qs=array(
				'uid' =>	"post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
				'content' =>	"post,trim,notnull=" . urlencode("내용을 입력하시기 바랍니다."),
				'docu_type' =>	"post,trim",
				'data1' =>	"post.trim",
				'data2' =>	"post.trim",
				'data3' =>	"post.trim",
				'data4' =>	"post.trim",
				'data5' =>	"post.trim",
				'data6' =>	"post.trim",
				'data7' =>	"post.trim"
		);
		
	// 넘어온값 체크
	$qs=check_value($qs);
	$list = array();

	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";

	// 값 추가
	if(isset($list['bid']) && $list['bid'] == $_SESSION['seUid']){
		switch($dbinfo['enable_userid'] ?? ''){
			case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
			case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
			default			: $qs['userid'] = $_SESSION['seUserid']; break;
		}
		$qs['email']	= $_SESSION['seEmail'];
	} else {
		$qs['userid']	= $list['userid'] ?? '';
		$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	}
	$qs['ip']		= remote_addr();
	$qs['cateuid']= (isset($qs['catelist'], $list['re']) && strlen($list['re']) == 0 ) ? $qs['catelist'] : ($list['cateuid'] ?? ''); // 답변이 아닌 경우에만 카테고리 수정 가능

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
	if($fieldlist = userGetAppendFields($table_mail_message, $skip_fields)){
		foreach($fieldlist as $value){
			if(isset($_POST[$value])) $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
		}
	}
	////////////////////////////////
	$sql = "UPDATE
				{$table_mail_message}
			SET
				content	='{$qs['content']}',
				docu_type='html',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$qs['uid']}
		";
	db_query($sql);

	return true;
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

function modify_sitedesign_ok(){
	global $qs, $dbinfo, $table_sitedesign, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();

	// 수정 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$rs_modify=db_query("SELECT * FROM {$table_sitedesign} WHERE uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_modify=db_query("SELECT * FROM {$table_sitedesign} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		$rs_modify=db_query("SELECT * FROM {$table_sitedesign} WHERE uid='{$qs['uid']}' and passwd=PASSWORD('" . ($qs['passwd'] ?? '') . "')");
	} // end if
	$list = db_count() ? db_array($rs_modify) : back("게시물이 없거나 수정할 권한이 없습니다");
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	
	$sql_set_file = '';
	//==========================
	// 파일 업로드 - 변경(modify)
	//==========================
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		// 기존 업로드 파일 정보 읽어오기
		$upfiles=unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)){ // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일 업로드
		$updir = ($dbinfo['upload_dir'] ?? '') . "/{$list['bid']}/";
		if(($dbinfo['enable_upload'] ?? 'N') == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles_tmp=file_upload("upfile",$updir);

				// 기존 업로드 파일이 있다면 삭제
				if( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']);
				elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);

				$upfiles_totalsize = $upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0) + $upfiles_tmp['size'];
				$upfiles['upfile']=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					$upfiles_tmp=file_upload($key,$updir);

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);

					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//========================== //
	$content = $qs['content'] ?? '';
	$title = $qs['title'] ?? '';
	$priv_level = $qs['priv_level'] ?? 0;
	$email = $qs['email'] ?? '';
	$ip = $qs['ip'] ?? '';
	$uid_sitedesign = $qs['uid'] ?? 0;
	$cateuid = $qs['cateuid'] ?? '';
	
	$sql = "UPDATE
				{$table_sitedesign}
			SET
				email	='{$email}',
				title	='{$title}',
				content	='{$content}',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$ip}',
				priv_level	='{$priv_level}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$uid_sitedesign}
		";
	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	if( $cateuid <> $list['cateuid'] ){
		$type = $list['type'] ?? '';
		$num = $list['num'] ?? 0;
		db_query("update {$table_sitedesign} set cateuid='{$cateuid}' where	type='{$type}' and num='{$num}'");
	} // end if
	
	return true;
} // end func.

function modify_logo_ok(){
	global $qs, $dbinfo, $table, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();

	// 수정 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and passwd=PASSWORD('" . ($qs['passwd'] ?? '') . "')");
	} // end if
	$list = db_count() ? db_array($rs_modify) : back("게시물이 없거나 수정할 권한이 없습니다");
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	
	$sql_set=", `data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	//==========================
	// 파일 업로드 - 변경(modify)
	//==========================
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		// 기존 업로드 파일 정보 읽어오기
		$upfiles = unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)){ // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일 업로드
		$updir = ($dbinfo['upload_dir'] ?? '') . "/" . ($list['bid'] ?? '') . "/";
		if(($dbinfo['enable_upload'] ?? 'N') == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles_tmp=file_upload("upfile",$updir);

				// 기존 업로드 파일이 있다면 삭제
				if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']);
				elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);

				$upfiles_totalsize = $upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0) + $upfiles_tmp['size'];
				$upfiles['upfile']=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					$upfiles_tmp=file_upload($key,$updir);

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);

					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//========================== //
	$content = $qs['content'] ?? '';
	$title = $qs['title'] ?? '';
	$priv_level = $qs['priv_level'] ?? 0;
	$email = $qs['email'] ?? '';
	$ip = $qs['ip'] ?? '';
	$uid_modify_logo = $qs['uid'] ?? 0;
	$cateuid = $qs['cateuid'] ?? '';

	$sql = "UPDATE
				{$table}
			SET
				email	='{$email}',
				title	='{$title}',
				content	='{$content}',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$ip}',
				priv_level	='{$priv_level}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$uid_modify_logo}
		";
	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	if( $cateuid <> ($list['cateuid'] ?? '') ){
		$type = $list['type'] ?? '';
		$num = $list['num'] ?? 0;
		db_query("update {$table} set cateuid='{$cateuid}' where	type='{$type}' and num='{$num}'");
	} // end if
	
	return true;
} // end func.

function write_banner_ok(){
	global $qs, $dbinfo, $table, $sql_where, $sql_set;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	if(isset($qs['data4'])) check_url($qs['data4']);

	// 값 추가
	$qs['db']		= $dbinfo['db'] ?? '';
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);

	$sql_set=", `data1`		='1',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	//===========
	// 파일 업로드
	//===========
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		$updir = ($dbinfo['upload_dir'] ?? '') . "/" . ($_SESSION['seUid'] ?? '') . "/";
		
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
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if . . esle ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//===========//

	if ( ($qs['content'] ?? '') != 'banner'){
		//사용함 값 초기화
		$sql_data = "UPDATE {$table} SET data1 = '' where content = '{$qs['content']}' ";
		db_query($sql_data);
	}
	$max=db_resultone("SELECT max(num) FROM {$table} WHERE  $sql_where ", 0, "max(num)") + 1;

	$sql="INSERT
			INTO
				{$table}
			SET
				num		='{$max}',
				bid		='{$qs['bid']}',
				userid	='{$qs['userid']}',
				passwd	=PASSWORD('" . ($qs['passwd'] ?? '') . "'),
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='" . ($qs['content'] ?? '') . "',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	return db_insert_id();
} // end func.

function modify_banner_ok(){
	global $qs, $dbinfo, $table, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();

	// 수정 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		$rs_modify=db_query("SELECT * FROM {$table} WHERE uid='{$qs['uid']}' and passwd=PASSWORD('" . ($qs['passwd'] ?? '') . "')");
	} // end if
	$list = db_count() ? db_array($rs_modify) : back("게시물이 없거나 수정할 권한이 없습니다");
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	
	$sql_set=", `data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	//==========================
	// 파일 업로드 - 변경(modify)
	//==========================
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		// 기존 업로드 파일 정보 읽어오기
		$upfiles=unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)){ // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']= $list['upfiles'] ?? '';
			$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일 업로드
		$updir = ($dbinfo['upload_dir'] ?? '') . "/" . ($list['bid'] ?? '') . "/";
		if(($dbinfo['enable_upload'] ?? 'N') == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles_tmp=file_upload("upfile",$updir);

				// 기존 업로드 파일이 있다면 삭제
				if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']);
				elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);

				$upfiles_totalsize = $upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0) + $upfiles_tmp['size'];
				$upfiles['upfile']=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					$upfiles_tmp=file_upload($key,$updir);

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);

					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//========================== //
	$content = $qs['content'] ?? '';
	$title = $qs['title'] ?? '';
	$priv_level = $qs['priv_level'] ?? 0;
	$email = $qs['email'] ?? '';
	$ip = $qs['ip'] ?? '';
	$uid_banner = $qs['uid'] ?? 0;

	$sql = "UPDATE
				{$table}
			SET
				email	='{$email}',
				title	='{$title}',
				content	='{$content}',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$ip}',
				priv_level	='{$priv_level}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$uid_banner}
		";
	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	$cateuid = $qs['cateuid'] ?? '';
	if( $cateuid <> ($list['cateuid'] ?? '') ){
		$type = $list['type'] ?? '';
		$num = $list['num'] ?? 0;
		db_query("update {$table} set cateuid='{$cateuid}' where	type='{$type}' and num='{$num}'");
	} // end if
	
	return true;
} // end func.
// 삭제
function delete_banner_ok(){
	global $dbinfo, $table, $qs_basic, $sql_where;
	$qs=array(
			'uid' =>	"request,tirm,notnull=" . urlencode("삭제할 게시물의 고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	$list = array();

	// 삭제 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면
		if(isset($qs['passwd']) && $qs['passwd'])
			$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and uid='{$qs['uid']}' and passwd=PASSWORD('{$qs['passwd']}')");
		else
			go_url("./delete.php?" . href_qs("uid={$qs['uid']}",$qs_basic),0,"비밀번호를 입력하십시오"); // 비밀번호 입력 페이지로 이동
	} // end if

	$list = db_count() ? db_array($rs_delete) : back("게시물이 이미 삭제되었거나 권한이 없습니다.");
	$del_uploadfile = array();

	if(isset($list['upfiles'])){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name'])){
				if( isset($dbinfo['upload_dir'], $list['bid']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name'];
				elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			} // end if
		} // end foreach
	} // end if

	$rs_subre = db_query("SELECT * FROM {$table} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	while($row=db_array($rs_subre)){
		if(isset($row['upfiles'])){
			$upfiles=unserialize($row['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name'] = $row['upfiles'] ?? '';
				$upfiles['upfile']['size'] = (int)($row['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name'])){
					if( isset($dbinfo['upload_dir'], $row['bid']) && is_file($dbinfo['upload_dir'] . "/{$row['bid']}/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/{$row['bid']}/" . $value['name'];
					elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				} // end if
			} // end foreach
		} // end if
	} // end while
	
	db_query("DELETE FROM {$table} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	db_query("DELETE FROM {$table} where $sql_where and uid='{$list['uid']}'");
	
	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value) @unlink($value);
	} // end if
	
	return true;
} // end func delete_ok()

function write_bank_ok(){
	global $qs, $dbinfo, $table, $table_bank, $sql_where, $sql_set;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	if(isset($qs['data3'])) check_url($qs['data3']);
	// 값 추가
	$qs['db']		= $dbinfo['db'] ?? '';
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);

	$sql_set=", `data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	//===========
	// 파일 업로드
	//===========
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		$updir = ($dbinfo['upload_dir'] ?? '') . "/";

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
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if . . esle ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//===========//

	$max=db_resultone("SELECT max(num) FROM {$table_bank} WHERE  $sql_where ", 0, "max(num)") + 1;

	$sql="INSERT
			INTO
				{$table_bank}
			SET
				num		='{$max}',
				bid		='{$qs['bid']}',
				userid	='{$qs['userid']}',
				passwd	=PASSWORD('" . ($qs['passwd'] ?? '') . "'),
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='" . ($qs['content'] ?? '') . "',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	return db_insert_id();
} // end func.

function modify_bank_ok(){
	global $qs, $dbinfo, $table, $table_bank, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();

	// 수정 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면 수정 권한 무조건 부여
		$rs_modify=db_query("SELECT * FROM {$table_bank} WHERE uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_modify=db_query("SELECT * FROM {$table_bank} WHERE uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면 (비회원의 글에 패스워드가 없을 경우 누구든지 수정 가능, 실수로 안 입력했을 경우 수정가능하게)
		$rs_modify=db_query("SELECT * FROM {$table_bank} WHERE uid='{$qs['uid']}' and passwd=PASSWORD('" . ($qs['passwd'] ?? '') . "')");
	} // end if
	$list = db_count() ? db_array($rs_modify) : back("게시물이 없거나 수정할 권한이 없습니다");
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($list['email'] ?? ''); // email값이 넘어오면 수정하고 아니면 그대로 유지
	
	$sql_set=", `data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	//==========================
	// 파일 업로드 - 변경(modify)
	//==========================
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		// 기존 업로드 파일 정보 읽어오기
		$upfiles=unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)){ // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$list['upfiles'] ?? '';
			$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일 업로드
		$updir = ($dbinfo['upload_dir'] ?? '') . "/";
		if(($dbinfo['enable_upload'] ?? 'N') == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				$upfiles_tmp=file_upload("upfile",$updir);

				// 기존 업로드 파일이 있다면 삭제
				if( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);
				elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) )
					@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);

				$upfiles_totalsize = $upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0) + $upfiles_tmp['size'];
				$upfiles['upfile']=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if(isset($_FILES[$key]['name']) && $_FILES[$key]['name']) { // 파일이 업로드 되었다면
					$upfiles_tmp=file_upload($key,$updir);

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) )
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);

					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//========================== //
	$content = $qs['content'] ?? '';
	$title = $qs['title'] ?? '';
	$priv_level = $qs['priv_level'] ?? 0;
	$email = $qs['email'] ?? '';
	$ip = $qs['ip'] ?? '';
	$uid_bank = $qs['uid'] ?? 0;
	$cateuid = $qs['cateuid'] ?? '';

	$sql = "UPDATE
				{$table_bank}
			SET
				email	='{$email}',
				title	='{$title}',
				content	='{$content}',
				rdate	=UNIX_TIMESTAMP(),
				ip		='{$ip}',
				priv_level	='{$priv_level}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid={$uid_bank}
		";
	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	if( $cateuid <> ($list['cateuid'] ?? '') ){
		$type = $list['type'] ?? '';
		$num = $list['num'] ?? 0;
		db_query("update {$table_bank} set cateuid='{$cateuid}' where type='{$type}' and num='{$num}'");
	} // end if
	
	return true;
} // end func.
// 삭제
function delete_bank_ok(){
	global $dbinfo, $table, $table_bank, $qs_basic, $sql_where;
	$qs=array(
			'uid' =>	"request,tirm,notnull=" . urlencode("삭제할 게시물의 고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	$list = array();

	// 삭제 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면
		$rs_delete=db_query("SELECT * FROM {$table_bank} WHERE $sql_where and uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_delete=db_query("SELECT * FROM {$table_bank} WHERE $sql_where and uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면
		if(isset($qs['passwd']) && $qs['passwd'])
			$rs_delete=db_query("SELECT * FROM {$table_bank} WHERE $sql_where and uid='{$qs['uid']}' and passwd=PASSWORD('{$qs['passwd']}')");
		else
			go_url("./delete.php?" . href_qs("uid={$qs['uid']}",$qs_basic),0,"비밀번호를 입력하십시오"); // 비밀번호 입력 페이지로 이동
	} // end if

	$list = db_count() ? db_array($rs_delete) : back("게시물이 이미 삭제되었거나 권한이 없습니다.");
	$del_uploadfile = array();

	if(isset($list['upfiles'])){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name'])){
				if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			} // end if
		} // end foreach
	} // end if

	$rs_subre = db_query("SELECT * FROM {$table_bank} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	while($row=db_array($rs_subre)){
		if(isset($row['upfiles'])){
			$upfiles=unserialize($row['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name'] = $row['upfiles'] ?? '';
				$upfiles['upfile']['size'] = (int)($row['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name'])){
					if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
					elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				} // end if
			} // end foreach
		} // end if
	} // end while
	
	db_query("DELETE FROM {$table_bank} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	db_query("DELETE FROM {$table_bank} where $sql_where and uid='{$list['uid']}'");
	
	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value) @unlink($value);
	} // end if
	
	return true;
} // end func delete_ok()

function sysconfig_ok(){
	global $qs, $dbinfo, $table_sysconfig, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,tirm,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);
	$list = array();

	// 값 추가
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);
	
	$site_name = $_POST['site_name'] ?? '';
	$homepage = $_POST['homepage'] ?? '';
	$c_name = $_POST['c_name'] ?? '';
	$ceo_name = $_POST['ceo_name'] ?? '';
	$b_conditions = $_POST['b_conditions'] ?? '';
	$b_item = $_POST['b_item'] ?? '';
	$c_tel1 = $_POST['c_tel1'] ?? '';
	$c_tel2 = $_POST['c_tel2'] ?? '';
	$c_fax = $_POST['c_fax'] ?? '';
	$c_email = $_POST['c_email'] ?? '';
	$c_biznum = $_POST['c_biznum'] ?? '';
	$c_zipcode = $_POST['c_zipcode'] ?? '';
	$c_addr = $_POST['c_addr'] ?? '';

	$sql_set=", `site_name`		= '{$site_name}',
				`homepage`		= '{$homepage}',
				`c_name`		= '{$c_name}',
				`ceo_name`		= '{$ceo_name}',
				`b_conditions`	= '{$b_conditions}',
				`b_item`		= '{$b_item}',
				`c_tel1`		= '{$c_tel1}',
				`c_tel2`		= '{$c_tel2}',
				`c_fax`			= '{$c_fax}',
				`c_email`		= '{$c_email}',
				`c_biznum`		= '{$c_biznum}',
				`c_zipcode`		= '{$c_zipcode}',
				`c_addr`		= '{$c_addr}'
			";

	$sql_set_file = '';

	$sql = "UPDATE
				{$table_sysconfig}
			SET
				bid		='{$qs['bid']}',
				userid	='{$qs['userid']}',
				passwd	=PASSWORD('" . ($qs['passwd'] ?? '') . "'),
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='" . ($qs['content'] ?? '') . "',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid='{$qs['uid']}'
		";
	db_query($sql);

	return true;
} // end func.

function popWrite_ok(){
	global $qs, $dbinfo, $table_popup, $sql_where, $sql_set;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	// 값 추가
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);

	$sql_set=", `skin`		='" . ($qs['skin'] ?? '') . "',
				`data0`		='400',
				`data1`		='400',
				`data2`		='no',
				`data3`		='yes',
				`data4`		=UNIX_TIMESTAMP(),
				`data5`		=UNIX_TIMESTAMP(),
				`data6`		='0',
				`data7`		='0'
			";
	
	$sql_set_file = '';
	//===========
	// 파일 업로드
	//===========
	if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES)){
		$updir = ($dbinfo['upload_dir'] ?? '') . "/";

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
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if . . esle ..
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	//===========//

	$max=db_resultone("SELECT max(num) FROM {$table_popup} WHERE  $sql_where ", 0, "max(num)") + 1;

	$sql="INSERT
			INTO
				{$table_popup}
			SET
				num		='{$max}',
				bid		='{$qs['bid']}',
				userid	='{$qs['userid']}',
				passwd	=PASSWORD('" . ($qs['passwd'] ?? '') . "'),
				email	='{$qs['email']}',
				title	='" . ($qs['title'] ?? '') . "',
				content	='" . ($qs['content'] ?? '') . "',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$qs['ip']}'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	$uid = db_insert_id();
	return $uid;
} // end func.

function popModify_ok(){
	global $qs, $dbinfo, $table_popup, $sql_where, $sql_set, $uid;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	$list = array();
	
	// 수정할 글 읽기
	$sql = "SELECT * FROM {$table_popup} WHERE uid='{$uid}'";
	$list=db_arrayone($sql) or back("게시물이 없거나 수정할 권한이 없습니다");

	// 값 추가
	$qs['bid']		= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']) : ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);
	
	$qs['data4'] = strtotime($qs['data4'] ?? '') ?: 0;
	$qs['data5'] = strtotime($qs['data5'] ?? '') ?: 0;
	
	$sql_set=", `skin`		='" . ($qs['skin'] ?? '') . "',
				`data0`		='" . ($qs['data0'] ?? '') . "',
				`data1`		='" . ($qs['data1'] ?? '') . "',
				`data2`		='" . ($qs['data2'] ?? '') . "',
				`data3`		='" . ($qs['data3'] ?? '') . "',
				`data4`		='" . ($qs['data4'] ?? '') . "',
				`data5`		='" . ($qs['data5'] ?? '') . "',
				`data6`		='" . ($qs['data6'] ?? '') . "',
				`data7`		='" . ($qs['data7'] ?? '') . "'
			";
	
	$sql_set_file = '';
	///////////////////////////////
	// 파일 업로드 - 변경(03/10/20)
	///////////////////////////////
	if( ($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = ($dbinfo['upload_dir'] ?? '') . "/" . (int)($list['bid'] ?? 0);

		// 기존 업로드 파일 정보 읽어오기
		$upfiles=unserialize($list['upfiles'] ?? '');
		
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>	$value){
				if(isset($_REQUEST["del_{$key}"])) {
						// 해당 파일 삭제
						if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) ){
							@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
						}
						elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) ){
							@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
						}

						$upfiles_totalsize -= ($upfiles[$key]['size'] ?? 0);
						unset($upfiles[$key]);
				}
			}
		}

		// 업로드 파일 처리
		if($dbinfo['enable_upload'] == 'Y') { // 파일 하나 업로드라면
			if(isset($_FILES['upfile']['name']) && $_FILES['upfile']['name']) {	// 파일이 업로드 되었다면
				$ok_upload =0;
				if(isset($dbinfo['enable_uploadextension'])){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($_FILES['upfile']['name'],"."), 1)); //확장자
					if(in_array($ext,$allow_extension)) $ok_upload = 1;
				}
				else $ok_upload = 1;

				if($ok_upload){
					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']) ){
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']);
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name'].".thumb.jpg"); // thumbnail 삭제
					}
					elseif( isset($dbinfo['upload_dir'], $upfiles['upfile']['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) ){
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name'].".thumb.jpg"); // thumbnail 삭제
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
					if(isset($dbinfo['enable_uploadextension'])){
						$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
						$ext = strtolower(substr(strrchr($value['name'],"."), 1)); //확장자
						if(!in_array($ext,$allow_extension)) continue;
					}
					if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
						&& !is_array(getimagesize($_FILES[$key]['tmp_name'])) )
						continue;

					// 기존 업로드 파일이 있다면 삭제
					if( isset($dbinfo['upload_dir'], $list['bid'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) ){
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
					}
					elseif( isset($dbinfo['upload_dir'], $upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) ){
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
					}

					// 업로드
					$upfiles_tmp=file_upload($key,$updir);
					$upfiles_totalsize = $upfiles_totalsize - ($upfiles[$key]['size'] ?? 0) + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' && sizeof($upfiles) == 0){
			if(isset($dbinfo['enable_uploadextension']))
				back("다음의 파일 확장자만 업로드 가능합니다.\\n{$dbinfo['enable_uploadextension']}");
			if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		$sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	///////////////////////////////
	$title = $qs['title'] ?? '';
	$content = $qs['content'] ?? '';
	$email = $qs['email'] ?? '';
	$ip = $qs['ip'] ?? '';
	$priv_level = $qs['priv_level'] ?? 0;
	$bid = {$qs['bid']} ?? 0;
	$userid = $qs['userid'] ?? '';
	$passwd = $qs['passwd'] ?? '';

	$sql="UPDATE
				{$table_popup}
			SET
				bid		='{$bid}',
				userid	='{$userid}',
				passwd	=PASSWORD('{$passwd}'),
				email	='{$email}',
				title	='{$title}',
				content	='{$content}',
				rdate	= UNIX_TIMESTAMP(),
				ip		='{$ip}'
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid ='{$uid}'
		";

	db_query($sql);

	return true;
} // end func.
// 삭제
function popDelete_ok(){
	global $dbinfo, $table_popup, $qs_basic, $sql_where, $uid;
	$qs=array(
			'uid' =>	"request,tirm,notnull=" . urlencode("삭제할 게시물의 고유넘버가 넘어오지 않았습니다."),
			'passwd' =>	"request,trim"
		);
	$qs=check_value($qs);
	$list = array();

	// 삭제 권한 체크와 해당 게시물 읽어오기
	if(privAuth($dbinfo,"priv_delete")) // 게시판 전체 삭제 권한을 가졌다면
		$rs_delete=db_query("SELECT * FROM {$table_popup} WHERE $sql_where and uid='{$qs['uid']}'");
	elseif(isset($_SESSION['seUid'])) // 회원의 글이라면,
		$rs_delete=db_query("SELECT * FROM {$table_popup} WHERE $sql_where and uid='{$qs['uid']}' and bid='{$_SESSION['seUid']}'");
	else { // 비회원의 글이라면
		if(isset($qs['passwd']) && $qs['passwd'])
			$rs_delete=db_query("SELECT * FROM {$table_popup} WHERE $sql_where and uid='{$qs['uid']}' and passwd=PASSWORD('{$qs['passwd']}')");
		else
			go_url("./delete.php?" . href_qs("uid={$qs['uid']}",$qs_basic),0,"비밀번호를 입력하십시오"); // 비밀번호 입력 페이지로 이동
	} // end if

	$list = db_count() ? db_array($rs_delete) : back("게시물이 이미 삭제되었거나 권한이 없습니다.");
	$del_uploadfile = array();

	if(isset($list['upfiles'])){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'] ?? '';
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		foreach($upfiles as $key =>	$value){
			if(isset($value['name'])){
				if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			} // end if
		} // end foreach
	} // end if

	$rs_subre = db_query("SELECT * FROM {$table_popup} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	while($row=db_array($rs_subre)){
		if(isset($row['upfiles'])){
			$upfiles=unserialize($row['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name'] = $row['upfiles'] ?? '';
				$upfiles['upfile']['size'] = (int)($row['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name'])){
					if( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
					elseif( isset($dbinfo['upload_dir']) && is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
						$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				} // end if
			} // end foreach
		} // end if
	} // end while
	
	db_query("DELETE FROM {$table_popup} WHERE $sql_where and num='{$list['num']}' AND LENGTH(re) > LENGTH('{$list['re']}') AND LOCATE('{$list['re']}',re) = 1");
	db_query("DELETE FROM {$table_popup} where $sql_where and uid='{$list['uid']}'");
	
	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value) @unlink($value);
	} // end if
	
	return $uid;
} // end func delete_ok();
?>
