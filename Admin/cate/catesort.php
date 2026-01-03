<?php
//=======================================================
// 설	명 : 쇼핑몰 카테고리 소트(catesort.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/05/31 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv' => 2, // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // privAuth
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
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
$prefix	= 'board2';

$db			= $_REQUEST['db'] ?? '';
$cateuid	= (int)($_REQUEST['cateuid'] ?? 0);

$table_dbinfo = "{$SITE['th']}{$prefix}info";

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$sql = "SELECT * FROM {$table_dbinfo} WHERE `db`='" . db_escape($db) . "'";
$dbinfo = db_arrayone($sql) or back("사용하지 않은 DB입니다.");
if(($dbinfo['enable_cate'] ?? '') != 'Y') back("카테고리를 지원하지 않습니다.");
// 인증 체크
if(!privAuth($dbinfo, "priv_catemanage")) back("이용이 제한되었습니다.(레벨부족)");

// table	
$dbinfo['table'] = "{$SITE['th']}{$prefix}_{$dbinfo['db']}"; // 테이블이름 가져오기
$dbinfo['table_cate'] = {$dbinfo['table']} . '_cate';

$sql_where_cate = ' 1 '; // init

$sql		= "SELECT * FROM {$dbinfo['table_cate']} WHERE `uid`={$cateuid} and {$sql_where_cate}";
$cateinfo	= db_arrayone($sql) or back_close("카테고리가 선택되지 않았습니다.");
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/{$dbinfo['skin']}/catesort.html") ) $dbinfo['skin']="basic";
$tpl->set_file('html',"{$thisPath}/stpl/{$dbinfo['skin']}/catesort.html",TPL_BLOCK);

/////////////////////////
// $dstuid_options 구하기
if(strlen($cateinfo['re'] ?? ''))
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and	`num`='" . (int)$cateinfo['num'] . "' and length(`re`) = length('" . db_escape($cateinfo['re']) . "') and locate('" . db_escape(substr($cateinfo['re'],0,-1)) . "',`re`)=1 order by `re`";
else
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and `re`='' order by `num`";
$rs_menus = db_query($sql);
$count=db_count($rs_menus);
if($count <=1) back_close("순서변경이 필요없습니다.");

// 처음으로, ??다음으로 출력
$dstuid_options = "";
$html_option="<option value='first'>처음으로</option>";
for($i=0; $i<$count; $i++){
	$list_menus=db_array($rs_menus);
	if($list_menus['uid'] == $cateinfo['uid'])
		$html_option="";
	elseif($i == $count-1) { // 마지막이면
		$dstuid_options .= $html_option;
		$dstuid_options .= "<option value='" . htmlspecialchars($list_menus['uid'], ENT_QUOTES, 'UTF-8') . "'>마지막으로</option>";
	} else {
		$dstuid_options .= $html_option;
		$html_option="<option value='" . htmlspecialchars($list_menus['uid'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($list_menus['title'], ENT_QUOTES, 'UTF-8') . " 다음으로</option>";
	}
} // end for
/////////////////////////
$tpl->set_var('dstuid_options',$dstuid_options);

$form_default = " method='post' action='cateok.php'>
	<input type='hidden' name='db' value='" . htmlspecialchars($db, ENT_QUOTES, 'UTF-8') . "'>
	<input type='hidden' name='mode' value='catesort'>
	<input type='hidden' name='srcuid' value='" . htmlspecialchars($cateinfo['uid'], ENT_QUOTES, 'UTF-8') . "' ";
$tpl->set_var('form_default',$form_default);
	
// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('cateinfo'		,$cateinfo);

// 오픈창으로 뜨니깐, 사이트 헤더테일 넣지 않고 바로
echo $tpl->process('', 'html',TPL_OPTIONAL);
?>
