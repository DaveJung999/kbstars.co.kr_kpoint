<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/02/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/02/26 박선민 처음제작
// 04/02/26 박선민 마지막수정
// 25/08/11 Gemini	PHP 7.x, MariaDB 호환성 업데이트
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useCheck' => 1, // 값 체크함수
	'useSkin' =>	1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================

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
				"&page=" . ($_REQUEST['page'] ?? '');

	$table		= ($SITE['th'] ?? '') . "board2info";

	$qs=array(
			"uid" => "post,trim",
			"skin" =>	"post,trim",
			"db" =>	"post,trim,notnull=" . urlencode('테이블 아이디를 입력하세요.'),
			"title" =>	"post,trim,notnull=" . urlencode('게시판 제목을 입력하세요.'),
			"bid" => "post,trim", "gid" => "post,trim", "cateuid" => "post,trim",
			"orderby" => "post,trim", "pern" => "post,trim", "row_pern" => "post,trim",
			"page_pern" => "post,trim", "cut_length" => "post,trim", "cate_depth" => "post,trim",
			"enable_type" => "post,trim", "enable_cate" => "post,trim", "enable_upload" => "post,trim",
			"enable_uploadmust" => "post,trim", "upload_dir" => "post,trim", "enable_download" => "post,trim",
			"enable_memo" => "post,trim", "enable_writeinfo" => "post,trim", "enable_vote" => "post,trim",
			"enable_level" => "post,trim", "enable_listreply" => "post,trim", "enable_readlog" => "post,trim",
			"enable_readlist" => "post,trim", "enable_userid" => "post,trim", "enable_adm_mail" => "post,trim",
			"enable_rec_mail" => "post,trim", "enable_getinfo" => "post,trim", "priv_list" => "post,trim",
			"priv_write" => "post,trim", "priv_read" => "post,trim", "priv_reply" => "post,trim",
			"priv_delete" => "post,trim", "priv_writeinfo" => "post,trim", "priv_catemanage" => "post,trim",
			"priv_level" => "post,trim", "html_type" => "post,trim", "html_headtpl" => "post,trim",
			"html_head" => "post,trim", "html_tail" => "post,trim", "rdate" => "post,trim", "mdate" => "post,trim"
		);
	
	// 쓰기 모드일 때만 중복 체크
	if(($_REQUEST['mode'] ?? '') == 'write') {
		$db_check = db_escape($_POST['db'] ?? '');
		$rs_logon = db_query("SELECT uid FROM {$table} WHERE db='{$db_check}'");
		if(db_count($rs_logon)) back("이미있는 테이블 아이디 입니다.");
	}

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? ''){
	case 'write':
		$uid = write_ok($table,$qs);
		go_url($_REQUEST['goto'] ?? "list.php");
		break;
	case 'modify':
		modify_ok($table,$qs,"uid");
		go_url($_REQUEST['goto'] ?? "read.php?" . href_qs("uid=" . ($_REQUEST['uid'] ?? ''),$qs_basic));
		break;
	case 'delete':
		delete_ok($table,"uid");
		go_url($_REQUEST['goto'] ?? "./list.php?" . href_qs("",$qs_basic));
		break;	
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok($table,$qs){
	global $dbinfo;
	// 권한체크
	if(!privAuth($dbinfo, "priv_write")) back("추가 권한이 없습니다");

	$qs=check_value($qs);

	// $sql 완성
	$sql_set	= ""; // $sql_set 시작
	$sql="INSERT
			INTO
				{$table}
			SET
				`skin`		='" . db_escape($qs['skin']) . "',
				`db`		='" . db_escape($qs['db']) . "',
				`title`		='" . db_escape($qs['title']) . "'
				{$sql_set}
		";
	db_query($sql);

	return db_insert_id();
} // end func write_ok

function modify_ok($table,$qs,$field){
	global $dbinfo;

	$qs[$field]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	// 넘어온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	$sql = "SELECT * FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!privAuth($dbinfo, "priv_delete")){
		if(($list['bid'] ?? '') != ($_SESSION['seUid'] ?? '')) back("수정 권한이 없습니다");
	}

	// $sql 완성
	$sql="UPDATE
				{$table}
			SET
				`bid`		='" . db_escape($qs['bid']) . "',
				`gid`		='" . db_escape($qs['gid']) . "',
				`cateuid`	='" . db_escape($qs['cateuid']) . "',
				`skin`		='" . db_escape($qs['skin']) . "',
				`db`		='" . db_escape($qs['db']) . "',
				`title`		='" . db_escape($qs['title']) . "',
				`orderby`	='" . db_escape($qs['orderby']) . "',
				`pern`		='" . db_escape($qs['pern']) . "',
				`row_pern`	='" . db_escape($qs['row_pern']) . "',
				`page_pern`	='" . db_escape($qs['page_pern']) . "',
				`cut_length`	='" . db_escape($qs['cut_length']) . "',
				`cate_depth`	='" . db_escape($qs['cate_depth']) . "',
				`enable_type`	='" . db_escape($qs['enable_type']) . "',
				`enable_cate`	='" . db_escape($qs['enable_cate']) . "',
				`enable_upload`	='" . db_escape($qs['enable_upload']) . "',
				`enable_uploadmust`	='" . db_escape($qs['enable_uploadmust']) . "',
				`upload_dir`	='" . db_escape($qs['upload_dir']) . "',
				`enable_download`	='" . db_escape($qs['enable_download']) . "',
				`enable_memo`	='" . db_escape($qs['enable_memo']) . "',
				`enable_writeinfo`	='" . db_escape($qs['enable_writeinfo']) . "',
				`enable_vote`	='" . db_escape($qs['enable_vote']) . "',
				`enable_level`	='" . db_escape($qs['enable_level']) . "',
				`enable_listreply`	='" . db_escape($qs['enable_listreply']) . "',
				`enable_readlog`	='" . db_escape($qs['enable_readlog']) . "',
				`enable_readlist`	='" . db_escape($qs['enable_readlist']) . "',
				`enable_userid`	='" . db_escape($qs['enable_userid']) . "',
				`enable_adm_mail`	='" . db_escape($qs['enable_adm_mail']) . "',
				`enable_rec_mail`	='" . db_escape($qs['enable_rec_mail']) . "',
				`enable_getinfo`	='" . db_escape($qs['enable_getinfo']) . "',
				`priv_list`	='" . db_escape($qs['priv_list']) . "',
				`priv_write`	='" . db_escape($qs['priv_write']) . "',
				`priv_read`	='" . db_escape($qs['priv_read']) . "',
				`priv_reply`	='" . db_escape($qs['priv_reply']) . "',
				`priv_delete`	='" . db_escape($qs['priv_delete']) . "',
				`priv_writeinfo`	='" . db_escape($qs['priv_writeinfo']) . "',
				`priv_catemanage`	='" . db_escape($qs['priv_catemanage']) . "',
				`priv_level`	='" . db_escape($qs['priv_level']) . "',
				`html_type`	='" . db_escape($qs['html_type']) . "',
				`html_headtpl`	='" . db_escape($qs['html_headtpl']) . "',
				`html_head`	='" . db_escape($qs['html_head']) . "',
				`html_tail`	='" . db_escape($qs['html_tail']) . "',
				`rdate`		='" . db_escape($qs['rdate']) . "',
				`mdate`		='" . db_escape($qs['mdate']) . "'
			WHERE
				`{$field}`='" . db_escape($qs[$field]) . "'
			AND
				 $sql_where 
		";
	db_query($sql);

	return db_count();
} // end func modify_ok

function delete_ok($table,$field){
	global $dbinfo;
	$qs=array(
			"$field" =>	"request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	$sql = "SELECT * FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!privAuth($dbinfo, "priv_delete")){
		if(($list['bid'] ?? '') != ($_SESSION['seUid'] ?? '')) back("삭제 권한이 없습니다");
	}

	db_query("DELETE FROM {$table} WHERE `{$field}`='" . db_escape($qs[$field]) . "' and  $sql_where ");

	return db_count();
} // end func delete_ok
?>
