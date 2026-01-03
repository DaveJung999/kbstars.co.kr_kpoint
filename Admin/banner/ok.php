<?php
//=======================================================
// 설 명 : 게시판 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/13
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인 수정 내용
// -------- ------ --------------------------------------
// 03/03/06 박선민 delete_ok() 버그 수정
// 03/10/13 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션, 보안 강화 및 버그 수정
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=[
	'priv'	 => 10, // 인증유무 (0:모두에게 허용)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard' => 1, // 보드관련 함수 포함
	'useApp'	 => 1,
	'useImage' => 1, // thumbnail()
	'useClassSendmail' => 1,
	];
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
// page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 기본 URL QueryString
$qs_basic = "db=" . urlencode($_REQUEST['db'] ?? '') .			//table 이름
			"&mode=" . urlencode($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
			"&cateuid=" . urlencode($_REQUEST['cateuid'] ?? '') .		//cateuid
			"&pern=" . urlencode($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
			"&sc_column=" . urlencode($_REQUEST['sc_column'] ?? '') .	//search column
			"&sc_string=" . urlencode(stripslashes($_REQUEST['sc_string'] ?? '')). //search string
			"&goto=" . urlencode($_REQUEST['goto'] ?? '') .	// 페이지당 표시될 게시물 수
			"&page=" . urlencode($_REQUEST['page'] ?? '');				//현재 페이지

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$upload_path = !empty(trim($dbinfo['upload_dir'])) ? trim($dbinfo['upload_dir']) : dirname(__FILE__);
$dbinfo['upload_dir'] = $upload_path ;

// 넘어온값 기본 처리
$sql_set="";
$sql_where = '1';

// $sql_where, $sql_set
if(($dbinfo['enable_type'] ?? '') =='Y') {
	$writeinfo = $_REQUEST['writeinfo'] ?? '';
	$mode = $_REQUEST['mode'] ?? '';
	$sql_where 	= ($mode=="write" && $writeinfo=="info") ? " `type`='info' " : " `type`='docu' ";
	$sql_set 	= ($mode=="write" && $writeinfo=="info") ? ", `type`='info' " : ", `type`='docu' ";
}

$qs=[
			"title"		 =>	"post,trim",
			"db"	 =>	"post,trim",
			"content"	 =>	"post,trim",
			"data1"	 =>	"post,trim",
			"data2"	 =>	"post,trim",
			"data3"	 =>	"post,trim",
			"data4"	 =>	"post,trim",
			"data5"	 =>	"post,trim",
			"mode"		 =>	"post,trim",
			"new_images"		 =>	"post,trim",
			"check_default"	 =>	"post,trim"
	];
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
	// info 테이블 정보 가져와서 $dbinfo로 저장
$mode = $_REQUEST['mode'] ?? '';
$goto = $_REQUEST['goto'] ?? '';
$uid = $_REQUEST['uid'] ?? '';
$check_default = $_REQUEST['check_default'] ?? '';

if(isset($mode)){	
	switch($mode) {
		case 'write':
			if(!privAuth($dbinfo, "priv_write")) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
			$uid = write_ok();
			go_url("{$goto}?" . href_qs("uid={$uid}&goto={$goto}",$qs_basic), 0, "저장되었습니다.");
			break;
		case 'modify':
			modify_ok();
			go_url("{$goto}?" . href_qs("uid={$uid}&goto={$goto}",$qs_basic), 0, "수정되었습니다.");
			break;
		case 'delete':
			delete_ok();
			go_url("{$goto}?" . href_qs("goto={$goto}",$qs_basic));
			break;
		case "top_banner":
			if($check_default == "0") top_banner();
			else if($check_default == "1") top_set_ini_file();
			back("상단 배너 이미지를 등록 하였습니다.");
			break;
		case "main_banner":
			if($check_default == "0") main_banner();
			else if($check_default == "1") main_set_ini_file();
			back("메인 배너 이미지를 등록 하였습니다.");
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다");
	}
}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function write_ok()
{
	global $qs, $dbinfo, $table, $sql_where, $sql_set;
	// $qs 추가,변경
	$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	// check_url($qs['data4']); // check_url 함수가 정의되지 않아 주석 처리

	// 값 추가
	$qs['db']		= $dbinfo['db'];
	$qs['bid']	= $_SESSION['seUid'] ?? 0;
	$qs['userid']	= $_SESSION['seUserid'] ?? ($qs['userid'] ?? '');
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']): ($_SESSION['seEmail'] ?? '');
	$qs['ip']		= remote_addr();
	if(isset($qs['catelist'])) $qs['cateuid'] = $qs['catelist'];
	if(isset($qs['docu_type']) && strtolower($qs['docu_type'])!="html") $qs['docu_type']="text";
	$qs['priv_level']=(int)($qs['priv_level'] ?? 0);

	$sql_set.=", `data1`		='1',
				`data2`		='" . db_escape($qs['data2']) . "',
				`data3`		='" . db_escape($qs['data3']) . "',
				`data4`		='" . db_escape($qs['data4']) . "',
				`data5`		='" . db_escape($qs['data5']) . "'
			";
	
	// ===========
	// 파일 업로드
	// ===========
	$sql_set_file = '';
	if(($dbinfo['enable_upload'] ?? 'N') !='N' && isset($_FILES)) {
		$updir = $dbinfo['upload_dir'] . "/";

		// 사용변수 초기화
		$upfiles=[];
		$upfiles_totalsize=0;
		if(($dbinfo['enable_upload'] ?? '') =='Y') {
			if(isset($_FILES['upfile']['name'])) { // 파일이 업로드 되었다면
				$upfiles['upfile']=file_upload("upfile",$updir);
				$upfiles_totalsize = $upfiles['upfile']['size'] ?? 0;
			}
		}
		else {
			foreach($_FILES as $key => $value) {
				if(isset($value['name'])) { // 파일이 업로드 되었다면
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += ($upfiles[$key]['size'] ?? 0); // BUG FIX: 문자열 결합(.)을 덧셈(+=)으로 수정
				}
			} // end foreach
		} // end if .. esle ..
		$sql_set_file = ", upfiles='".db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	// ===========//

	if ( ($qs['content'] ?? '') == 'main'){
		//사용함 값 초기화
		$sql_data = "UPDATE {$table} SET `data1` = '' where `content` = '" . db_escape($qs['content']) . "' ";
		db_query($sql_data);
	}

	$max_num_result = db_arrayone("SELECT max(num) as max_num FROM {$table} WHERE  $sql_where ");
	$max = ($max_num_result['max_num'] ?? 0) + 1;

	$sql="INSERT
			INTO
				{$table}
			SET
				`num`		='{$max}',
				`bid`		='" . (int)$qs['bid'] . "',
				`userid`	='" . db_escape($qs['userid']) . "',
				`passwd`	=password('" . db_escape($qs['passwd'] ?? '') . "'),
				`email`	='" . db_escape($qs['email']) . "',
				`title`	='" . db_escape($qs['title']) . "',
				`content`	='" . db_escape($qs['content']) . "',
				`rdate`	= UNIX_TIMESTAMP(),
				`ip`		='" . db_escape($qs['ip']) . "'
				{$sql_set_file}
				{$sql_set}
		";

	db_query($sql);
	return db_insert_id();
} // end func.

function modify_ok()
{
	global $qs, $dbinfo, $table, $sql_set;

	// $qs 추가,변경
	$qs['uid']	="post,trim,notnull=" . urlencode("수정할 게시물의 고유넘버가 넘어오지 않았습니다.");
	$qs=check_value($qs);

	// 수정 권한 체크와 해당 게시물 읽어오기
	$list = null;
	if(privAuth($dbinfo,"priv_delete")) {
		$rs_modify=db_query("SELECT * FROM {$table} WHERE `uid`='" . (int)$qs['uid'] . "'");
	} elseif(isset($_SESSION['seUid'])) {
		$rs_modify=db_query("SELECT * FROM {$table} WHERE `uid`='" . (int)$qs['uid'] . "' and `bid`='" . (int)$_SESSION['seUid'] . "'");
	} else {
		$rs_modify=db_query("SELECT * FROM {$table} WHERE `uid`='" . (int)$qs['uid'] . "' and `passwd`=password('" . db_escape($qs['passwd'] ?? '') . "')");
	}

	if(db_count($rs_modify) > 0) {
		$list = db_array($rs_modify);
	} else {
		back("게시물이 없거나 수정할 권한이 없습니다");
	}
		
	// 값 추가
	$qs['ip']		= remote_addr();
	$qs['email']	= isset($qs['email']) ? check_email($qs['email']): ($list['email'] ?? '');
	
	$sql_set.=", `data1`	='" . db_escape($qs['data1']) . "',
				`data2`		='" . db_escape($qs['data2']) . "',
				`data3`		='" . db_escape($qs['data3']) . "',
				`data4`		='" . db_escape($qs['data4']) . "',
				`data5`		='" . db_escape($qs['data5']) . "'
			";
	
	// ==========================
	// 파일 업로드 - 변경(modify)
	// ==========================
	$sql_set_file = '';
	if(($dbinfo['enable_upload'] ?? 'N') !='N' && isset($_FILES)) {
		$upfiles=@unserialize($list['upfiles'] ?? '');
		if(!is_array($upfiles)) {
			$upfiles = [];
			if(isset($list['upfiles'])) {
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
			}
		}
		$upfiles_totalsize=(int)($list['upfiles_totalsize'] ?? 0);

		$updir = $dbinfo['upload_dir'] . "/";
		if(($dbinfo['enable_upload'] ?? '') =='Y') {
			if(isset($_FILES['upfile']['name'])) {
				$upfiles_tmp=file_upload("upfile",$updir);
				if(isset($upfiles['upfile']['name']) && is_file($updir . $upfiles['upfile']['name'])) {
					@unlink($updir . $upfiles['upfile']['name']);
				}
				$upfiles_totalsize = ($upfiles_totalsize - ($upfiles['upfile']['size'] ?? 0)) + ($upfiles_tmp['size'] ?? 0);
				$upfiles['upfile']=$upfiles_tmp;
			}
		}
		else {
			foreach($_FILES as $key => $value) {
				if(isset($value['name'])) {
					$upfiles_tmp=file_upload($key,$updir);
					if(isset($upfiles[$key]['name']) && is_file($updir . $upfiles[$key]['name'])) {
						@unlink($updir . $upfiles[$key]['name']);
					}
					$upfiles_totalsize = ($upfiles_totalsize - ($upfiles[$key]['size'] ?? 0)) + ($upfiles_tmp['size'] ?? 0);
					$upfiles[$key]=$upfiles_tmp;
				}
			}
		}
		$sql_set_file = ", upfiles='".db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	}
	// ==========================//

	if ( ($qs['content'] ?? '') == 'main' && ($qs['data1'] ?? '') == '1'){
		$sql_data = "UPDATE {$table} SET `data1` = '' where `content` = '" . db_escape($qs['content']) . "' ";
		db_query($sql_data);
	}

	$sql = "UPDATE
				{$table}
			SET
				`email`	='" . db_escape($qs['email']) . "',
				`title`	='" . db_escape($qs['title']) . "',
				`content`	='" . db_escape($qs['content']) . "',
				`rdate`	=UNIX_TIMESTAMP(),
				`ip`		='" . db_escape($qs['ip']) . "',
				`priv_level`	='" . (int)($qs['priv_level'] ?? 0) . "'
				{$sql_set_file}
				{$sql_set}
			WHERE
				`uid`=" . (int)$qs['uid'] . "
		";
	db_query($sql);

	return true;
} // end func.


// 삭제
function delete_ok()
{
	global $dbinfo, $table, $qs_basic, $sql_where;
	$qs=[
			'uid'			 =>	"request,trim,notnull=" . urlencode("삭제할 게시물의 고유넘버가 넘어오지 않았습니다."),
			'passwd'		 =>	"request,trim"
		];
	$qs=check_value($qs);

	// 삭제 권한 체크와 해당 게시물 읽어오기
	$list = null;
	if(privAuth($dbinfo,"priv_delete")) {
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and `uid`='" . (int)$qs['uid'] . "'");
	} elseif(isset($_SESSION['seUid'])) {
		$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and `uid`='" . (int)$qs['uid'] . "' and `bid`='" . (int)$_SESSION['seUid'] . "'");
	} else {
		if(isset($qs['passwd'])) {
			$rs_delete=db_query("SELECT * FROM {$table} WHERE $sql_where and `uid`='" . (int)$qs['uid'] . "' and `passwd`=password('" . db_escape($qs['passwd']) . "')");
		} else {
			go_url("./delete.php?" . href_qs("uid=" . (int)$qs['uid'],$qs_basic),0,"비밀번호를 입력하십시오");
		}
	}

	if (db_count($rs_delete) > 0) {
		$list = db_array($rs_delete);
	} else {
		back("게시물이 이미 삭제되었거나 권한이 없습니다.");
	}

	$del_uploadfile = [];
	if(isset($list['upfiles'])) {
		$upfiles=@unserialize($list['upfiles']);
		if(!is_array($upfiles)) {
			$upfiles = ['upfile' => ['name' => $list['upfiles']]];
		}
		foreach($upfiles as $key => $value) {
			if(isset($value['name']) && is_file($dbinfo['upload_dir'] . "/" . $value['name'])) {
				$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			}
		}
	}

	$rs_subre = db_query("SELECT * FROM {$table} WHERE $sql_where and `num`='" . (int)$list['num'] . "' AND length(`re`) > length('" . db_escape($list['re']) . "') AND locate('" . db_escape($list['re']) . "',`re`) = 1");
	while($row=db_array($rs_subre)) {
		if(isset($row['upfiles'])) {
			$upfiles=@unserialize($row['upfiles']);
			if(!is_array($upfiles)) {
				$upfiles = ['upfile' => ['name' => $row['upfiles']]];
			}
			foreach($upfiles as $key => $value) {
				if(isset($value['name']) && is_file($dbinfo['upload_dir'] . "/" . $value['name'])) {
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
				}
			}
		}
	}
	
	db_query("DELETE FROM {$table} WHERE $sql_where and `num`='" . (int)$list['num'] . "' AND length(`re`) > length('" . db_escape($list['re']) . "') AND locate('" . db_escape($list['re']) . "',`re`) = 1");
	db_query("DELETE FROM {$table} where $sql_where and `uid`='" . (int)$list['uid'] . "'");
	
	foreach ($del_uploadfile as $value) {
		@unlink($value);
	}
	
	return true;
} // end func delete_ok()



function top_banner(){
	global $qs, $dbinfo;
	$qs=check_value($qs);
	
	$updir = $dbinfo['upload_dir'] . "/";
	if(empty($_FILES['upfile']['name']) || $_FILES['upfile']['error'] > 0) {
		back("업로드할 이미지 혹은 파일을 선택하여 주시기 바랍니다");
	}else{
		$upfiles = [];
		foreach($_FILES as $key => $value) {
			if(isset($value['name'])) {
				if(!is_array(@getimagesize($value['tmp_name']))) continue;
				$upfiles[$key]=file_upload($key,$updir);
			}
		}
		$top_banner_name['top_banner'] = "/h_images/" . ($upfiles['upfile']['name'] ?? '');
		set_ini_file("{$_SERVER['DOCUMENT_ROOT']}/scommon/ini/banner.ini", $top_banner_name);
	}
}

function top_set_ini_file()
{
	global $qs;
	$qs=check_value($qs);
	
	$top_banner_name['top_banner'] = $qs['new_images'];
	set_ini_file("{$_SERVER['DOCUMENT_ROOT']}/scommon/ini/banner.ini", $top_banner_name);
}

function main_banner(){
	global $qs, $dbinfo;
	$qs=check_value($qs);
	
	$updir = $dbinfo['upload_dir'] . "/";
	if(empty($_FILES['upfile']['name']) || $_FILES['upfile']['error'] > 0) {
		back("업로드할 이미지 혹은 파일을 선택하여 주시기 바랍니다");
	}else{
		$upfiles = [];
		foreach($_FILES as $key => $value) {
			if(isset($value['name'])) {
				if(!is_array(@getimagesize($value['tmp_name']))) continue;
				$upfiles[$key]=file_upload($key,$updir);
			}
		}
		
		$main_banner_name['main_banner'] = "/h_images/" . ($upfiles['upfile']['name'] ?? '');
		set_ini_file("{$_SERVER['DOCUMENT_ROOT']}/scommon/ini/banner.ini", $main_banner_name);
	}
}

function main_set_ini_file()
{
	global $qs;
	$qs=check_value($qs);
	
	$main_banner_name['main_banner'] = $qs['new_images'];
	set_ini_file("{$_SERVER['DOCUMENT_ROOT']}/scommon/ini/banner.ini", $main_banner_name);
}


function set_ini_file($fileLocation, $configurationValue) {
	$keyArray = array_keys($configurationValue);
	$remainSearch = count($keyArray);
	$replacedKey = [];
	$content = file_get_contents($fileLocation);
	$fileDescriptor = fopen($fileLocation, "w");
	$divisionContent = explode("\n", $content);
	$divisionContentSize = count($divisionContent);
	
	for($i = 0; $i < $divisionContentSize; $i++) {
		$tag = ($i + 1 != $divisionContentSize) ? "\n" : "";
			
		if($remainSearch && preg_match("/^[^;].*=.*$/", $divisionContent[$i])){
			$divisionLine = explode("=", $divisionContent[$i], 2);
			$foundKey = $divisionLine['0'];
			$foundValue = $divisionLine['1'];
			$realKey = preg_replace("/[\t ]/", "", $foundKey);
			
			if(in_array($realKey, $keyArray) ) {
				$foundValue = $configurationValue[$realKey];
				$replacedKey[] = $realKey;
				$remainSearch--;
			}
			
			fwrite($fileDescriptor, $foundKey . "=" . $foundValue . $tag);
		} else {
			fwrite($fileDescriptor, $divisionContent[$i] . $tag);
		}
	}

	if($remainSearch) {
		$newKeys = array_diff($keyArray, $replacedKey);
		foreach ($newKeys as $newKey) {
			fwrite($fileDescriptor, "\n" . $newKey . "=" . $configurationValue[$newKey]);
		}
	}

	fclose($fileDescriptor);
}
?>
