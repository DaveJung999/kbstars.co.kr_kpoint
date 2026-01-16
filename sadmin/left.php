<?php
//=======================================================
// 설  명 : 관리페이지 - menu 프레임
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1,
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// table
	$table_dbinfo = $SITE['th'].'admininfo';

	// dbinfo
	$sql_dbinfo = "select * from $table_dbinfo where uid={$_SESSION['seUid']}";
	$dbinfo = db_arrayone($sql_dbinfo) or exit('관리자 설정을 가져오지 못하였습니다.');
	
	$table_admin_menu = $SITE['th'] . 'admin_menu';
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 트리메뉴 클래스 읽기
include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_treemenu.php");
$icon = 'folder.gif';
$menu  = new HTML_TreeMenu("menuLayer", '/scommon/treemenu/images',"sadminmain");

// 기본 카테고리 넣음
$menu->addItem(new HTML_TreeNode("로그아웃","/sjoin/logout.php",$icon));
$menu->addItem(new HTML_TreeNode("사이트홈","/",$icon));
$node= new HTML_TreeNode("sitePHPbasic","./setup.php",$icon);
$node->addItem(new HTML_TreeNode("회사정보", "companyinfo/write.php?mode=modify&uid=1", $icon));
$node->addItem(new HTML_TreeNode("운영자검색", "listadmin/list.php", $icon));
$node->addItem(new HTML_TreeNode("관리메뉴관리", "./menu/cate.php", $icon));
if(is_dir('../sboard') and is_dir("./board")) $node->addItem(new HTML_TreeNode("게시판종합관리", "./board/list.php?db=boardinfo", $icon));
if(is_dir('../sboard2') and is_dir("./board2")) $node->addItem(new HTML_TreeNode("게시판2종합관리", "./board2/list.php", $icon));
if(is_dir('../spoll') and is_dir("./poll")) $node->addItem(new HTML_TreeNode("설문종합관리", "./poll/list.php?db=boardinfo", $icon));
if(is_dir('../sfmail') and is_dir("./fmail")) $node->addItem(new HTML_TreeNode("폼메일종합관리", "./fmail/list.php", $icon));
if(is_dir('../salbum') and is_dir("./album")) $node->addItem(new HTML_TreeNode("앨범종합관리", "./album/list.php?db=boardinfo", $icon));
if(is_dir('../sshop') and is_dir("./shop")) $node->addItem(new HTML_TreeNode("쇼핑몰종합관리", "./shop/", $icon));
if(is_dir('../sshop2') and is_dir("./shop2")) $node->addItem(new HTML_TreeNode("쇼핑몰2종합관리", "./shop2/list.php", $icon));
if(is_dir('../sauction') and is_dir("./auction")) $node->addItem(new HTML_TreeNode("공구종합관리", "./auction/", $icon));
$node->addItem(new HTML_TreeNode("MySQL직접관리", "./myadmin260/", $icon));
$menu->addItem($node);

// DB에 있는 카테고리 넣음
$result2 = db_query("SELECT * FROM $table_admin_menu order by num, re");
while($rows=db_array($result2)) {
	$rows['url']=addslashes($rows['url']);
	$varRe=$rows['num'];
	for($iRe=0;$iRe<strlen($rows['re']);$iRe++) {
		$varRe.= "_" . ord(substr($rows['re'],$iRe,1));
	}
	$subNode{$varRe}=new HTML_TreeNode($rows['title'],$rows['url'],$icon);
	
	if(isset($rowsPrev)) {
		if($rows['num']!=$rowsPrev['num']) {
			$menu->addItem($subNode{$rowsPrev['num']});
			unset($subNode);
			$subNode{$varRe}=new HTML_TreeNode($rows['title'],$rows['url'],$icon);
			$varRePrev="";
		}
		else {
			$varReTemp=preg_replace("/_[^_]*$/i", "", $varRe);
			if(isset($subNode{$varReTemp}))
				$subNode{$varReTemp}->addItem($subNode{$varRe});
		}
	} // end if
	
	$rowsPrev=$rows;
	$varRePrev=$varRe;	
} // end while
if(isset($subNode{$rowsPrev['num']}))
	$menu->addItem($subNode{$rowsPrev['num']});
	unset($subNode);
?>
<html>
<head>
<?php echo $pageinfo['html_header'];?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body bgcolor="<?php echo $pageinfo['left_bgcolor'];?>" background="<?php echo $pageinfo['left_background'];?>"  topmargin='', leftmargin=0>
<?php echo $pageinfo['left_header'];?>
<script src="/scommon/treemenu/sniffer.js" language="JavaScript" type="text/javascript"></script>
<script src="/scommon/treemenu/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
<div id="menuLayer"></div>

<?php $menu->printMenu();?>
</body>
</html>