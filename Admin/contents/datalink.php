<?php
//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정 
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 기본 URL QueryString
$qs_basic = "db={$db}".					//table 이름
			"&mode=".					// mode값은 list.php에서는 당연히 빈값
			"&cateuid={$cateuid}".		//cateuid
			"&pern={$pern}" .	// 페이지당 표시될 게시물 수
			"&sc_column={$sc_column}".	//search column
			"&sc_string=" . urlencode(stripslashes($sc_string)) . //search string
			"&page={$page}";				//현재 페이지

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

// 인증 체크
//	if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

//===================
// SQL문 where절 정리
//===================
if (!$_GET['uid']) $_GET['uid']=0;
if(!$sql_where) $sql_where= " uid = {$_GET['uid']} ";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("stpl/{$dbinfo['skin']}/","remove_nonjs");
$tpl->set_file('html',"datalink.htm",1); // here 1 mean extract blocks
//방금위의 $_GET['skin']값이 들어간 이유는 박선민(sponsor@new21.com)에게 물어보기바람

// Limit로 필요한 게시물만 읽음.
$rs_list = db_query("SELECT * from {$table} WHERE  $sql_where ");

$list		= db_array($rs_list);
$list['no']	= $count['lastnum'];
$list['rede']	= strlen($list['re']);

$list['rdate']= $list['rdate'] ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환

//제목과 내용 자르기 :: 정대입
$list['cut_title'] = cut_string($list['title'], $dbinfo['cut_length']);
$list['cut_content'] = cut_string($list['content'],300);

if(!$list['title']) $list['title'] = "제목없음…";

// URL Link...
$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
$href['list']	= "list.php?db={$db}";

// 템플릿 YESRESULT 값들 입력
$tpl->set_var('href.read'		,$href['read']);
$tpl->set_var('href.list'		,$href['list']);
$tpl->set_var('list'			,$list);

$tpl->process('LIST','list',TPL_APPEND);

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('href'			,$href);	// 게시판 각종 링크

// 블럭 : 글쓰기
if(privAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$val="\\1stpl/{$dbinfo['skin']}/images/";
if($_GET['skin']){
	echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop		
}
else {
	switch($dbinfo['html_headpattern']){
		case "ht":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") ) 
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop		
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		case "h":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") ) 
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop		
			echo $dbinfo['html_tail'];
			break;
		case "t":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") ) 
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop		
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		default:
			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop		
			echo $dbinfo['html_tail'];
	} // end switch
} // end if 
?>
