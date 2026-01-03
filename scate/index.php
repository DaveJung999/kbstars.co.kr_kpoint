<?php
//=======================================================
// 설 명 : 즐겨찾기 보기
// 책임자 : 박선민 (), 검수: 05/01/26
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/26 박선민 마지막 수정
//=======================================================
$HEADER = array(
	'usedb2' => 1, // DB 커넥션 사용
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'] . '/sinc/header.php');
$thisPath = dirname(__FILE__) . '/'; // 마지막이 '/'으로 끝나야함
$prefix = 'cate';
$thisUrl = './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// table
$table_dbinfo = $SITE['th'] . $prefix . 'info';

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$sql = "SELECT * FROM {$table_dbinfo} WHERE db='{$_GET['db']}'";
$dbinfo = db_arrayone($sql) or back("사용하지 않는 즐겨찾기입니다.");
if ($dbinfo['enable_cate'] != 'Y') {
	back("즐겨찾기를 지원하지 않습니다.");
}

// 인증 체크
if (!privAuth($dbinfo, "priv_list")) {
	back("즐겨찾기를 보실 수 없습니다.");
}

// table
$dbinfo['table_cate'] = $SITE['th'] . $prefix;

$sql_where_cate = " db='{$dbinfo['db']}' "; // init

////////////////////////
// 트리메뉴 클래스 읽기
include_once($thisPath . 'HTML_TreeMenu-1.1.9/TreeMenu.php');
$menu = new HTML_TreeMenu();

// DB에 있는 카테고리 넣음
$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} ORDER BY num, re";
$rs_cate = db_query($sql);
$hideVarRe = '';
$subNode = [];
$rowsPrev = null;
$varRePrev = '';

while ($rows = db_array($rs_cate)) {
	$rows['url'] = addslashes($rows['url']);
	$varRe = $rows['num'];
	for ($iRe = 0; $iRe < strlen($rows['re']); $iRe++) {
		$varRe .= "_" . ord(substr($rows['re'], $iRe, 1));
	}

	// hide 했거나, priv로 권한이 없으면 해당 카테고리 숨김
	if ($rows['hide'] || ($rows['priv'] && !privAuth($rows, 'priv'))) {
		$hideVarRe = $varRe;
		continue; // 숨김
	}
	if (strlen($hideVarRe) && strpos($varRe, $hideVarRe) === 0) {
		continue; //숨김
	}

	// 기존 코드 (PHP 4에서는 동작했으나, PHP 7에서는 자바스크립트 오류 유발 가능)
	// $subNode{$varRe}=new HTML_TreeNode(array('text' => $rows['title'],'link' => $rows['url'],'icon' => $rows['icon'],'expandedIcon' => $rows['expandedIcon'],'linkTarget' => $rows['target'],'expanded' => $rows['expanded']));

	// PHP 7 호환 및 자바스크립트 구문 오류 수정
	$subNode[$varRe] = new HTML_TreeNode(array(
		'text' => $rows['title'],
		'link' => $rows['url'],
		'icon' => $rows['icon'],
		'expandedIcon' => $rows['expandedIcon'],
		'linkTarget' => $rows['target'],
		// 'expanded'가 빈 값이면 false로 설정하여 자바스크립트 문법 오류 방지
		'expanded' => isset($rows['expanded']) && $rows['expanded'] ? true : false,
		'isDynamic' => true, // 또는 $rows['isDynamic'] 등으로 설정
	));

	if (isset($rowsPrev)) {
		if ($rows['num'] != $rowsPrev['num']) {
			$menu->addItem($subNode[$rowsPrev['num']]);
			$varRePrev = "";
			$hideVarRe = '';
		} else {
			$varReTemp = preg_replace("/_[^_]*$/", "", $varRe);
			if (isset($subNode[$varReTemp])) {
				$subNode[$varReTemp]->addItem($subNode[$varRe]);
			}
		}
	} // end if
	
	$rowsPrev = $rows;
	$varRePrev = $varRe;
} // end while

if (isset($rowsPrev) && isset($subNode[$rowsPrev['num']])) {
	$menu->addItem($subNode[$rowsPrev['num']]);
}
////////////////////////

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile = basename(__FILE__, '.php') . '.html';
if (!is_file($thisPath . 'skin/' . $dbinfo['skin'] . '/' . $skinfile)) {
	$dbinfo['skin'] = 'basic';
}
$tpl = new phemplate($thisPath . 'skin/' . $dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html', $skinfile, TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->tie_var('get', $_GET); // get값으로 넘어온것들
$tpl->tie_var('dbinfo', $dbinfo); // dbinfo 정보 변수<br>
$tpl->tie_var('thisUrl', $thisUrl);

$treeMenu = new HTML_TreeMenu_DHTML($menu, array('images' => $thisUrl . 'skin/' . $dbinfo['skin'] . '/images', 'defaultClass' => 'treeMenuDefault'));
$tpl->set_var('treeMenu.toHTML', $treeMenu->toHTML());

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>