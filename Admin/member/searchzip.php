<?php
//=======================================================
// 설	명 : 우편번호 찾기(searchzip.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/11
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/11 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
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
$thisPath		= dirname(__FILE__);
$thisUrl		= "/sjoin"; // 마지막 "/"이 빠져야함

$dbinfo	= [
		'skin' => "basic",
	'html_headpattern' => "no",
	'html_headtpl' => "basic"
	];

$table			= "{$SITE['th']}postcode";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/{$dbinfo['skin']}/searchzip.htm") ) $dbinfo['skin']="basic";
$tpl->set_file('html',"{$thisPath}/stpl/{$dbinfo['skin']}/searchzip.htm",TPL_BLOCK);

$mode = $_REQUEST['mode'] ?? '';
$region = $_REQUEST['region'] ?? '';
$which = $_REQUEST['which'] ?? '';

if($mode == "check" && isset($region)){
	$sql = "SELECT * FROM {$table} WHERE `region2` LIKE '%" . db_escape($region) . "%' ORDER BY `region1`";
	$result = db_query($sql);
	if($total = db_count($result)) {
		while($row = db_array($result)) {
			$zip = explode("-", $row["postcode"]);
			
			$tpl->set_var("ZIP1", htmlspecialchars($zip['0'] ?? '', ENT_QUOTES, 'UTF-8'));
			$tpl->set_var("ZIP2", htmlspecialchars($zip['1'] ?? '', ENT_QUOTES, 'UTF-8'));
			$tpl->set_var("ADD1", htmlspecialchars($row["region1"], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var("ADD2", htmlspecialchars($row["region2"], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var("ADD3", htmlspecialchars($row["region3"], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var("which", htmlspecialchars($which, ENT_QUOTES, 'UTF-8'));

			$tpl->process("result",'resultzip',TPL_APPEND);
		}
	} else {
		$tpl->process("result",'nozip');
	}
}

// 템플릿 마무리 할당
$tpl->set_var("site",	$SITE);

// 마무리
$val="\\1{$thisUrl}/stpl/{$dbinfo['skin']}/images/";
$processed_html = $tpl->process('', 'html', TPL_OPTIONAL);

// - 사이트 템플릿 읽어오기
if(preg_match("/^(ht|h|t)$/", $dbinfo['html_headpattern'])){
	if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
		@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
	else
		@include("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");
}
switch($dbinfo['html_headpattern']){
	case "ht":
		echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
		echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
		echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
		break;
	case "h":
		echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
		echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
		echo ($dbinfo['html_tail'] ?? '');
		break;
	case "t":
		echo ($dbinfo['html_head'] ?? '');
		echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
		echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
		break;
	case "no":
		echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
		break;
	default:
		echo ($dbinfo['html_head'] ?? '');
		echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
		echo ($dbinfo['html_tail'] ?? '');
}
?>
