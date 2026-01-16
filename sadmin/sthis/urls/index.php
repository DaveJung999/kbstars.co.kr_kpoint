<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/09
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/09 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?= → <?php echo 변환
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

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
	// 관리자페이지 환경파일 읽어드림
	$rs_pageinfo= db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo	= db_count() ? db_array($rs_pageinfo) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

	$table		= "urls";
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 트리메뉴 클래스 읽기
include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_treemenu.php");
$icon = 'folder.gif';
$menu	= new HTML_TreeMenu("menuLayer", '/scommon/treemenu/images',"urls");

// 기본 카테고리 넣음
$menu->addItem(new HTML_TreeNode("카테고리수정","cate.php",$icon));

// DB에 있는 카테고리 넣음
$result2 = db_query("SELECT * from {$table} order by num, re");
while($rows=db_array($result2)) {
	$relen=strlen($rows['re']);
	if($relen==0) unset($node);
	
	if(!isset($node)) {
		$node[$relen]=&$menu->addItem(new HTML_TreeNode($rows['title'],$rows['url'],$icon));
	}
	else {
		$node[$relen]=&$node[($relen-1)]->addItem(new HTML_TreeNode($rows['title'],$rows['url'],$icon));
	}
} // end while
?>
<html>
<head>
<?php echo $pageinfo['html_header']
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<script src="/scommon/treemenu/sniffer.js" language="JavaScript" type="text/javascript"></script>
<script src="/scommon/treemenu/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
<div id="menuLayer"></div>

<?php$menu->printMenu()
?>
</body>
</html>