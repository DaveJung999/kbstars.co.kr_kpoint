
<?php
##	Sunmin Park's know-how 1.2
##	Last Edit 2002 . 2 . 9 . by Sunmin Park(sponsor@new21.com)
##	
$HEADER=array(
			'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
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

##Ready.. . (변수 초기화 및 넘어온값 필터링)
$table = $SITE['th'] . "admin_config";
// 관리자페이지 환경파일 읽어드림
$rs=db_query("SELECT * from {$table} where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
if(!db_count()) back("관리자페이지 환경파일을 읽을 수가 없습니다");
else	$page=db_array($rs);

##Start.. . (DB 작업 및 display); ?>
<html>
<head>
<?php echo $page['html_header'] ; ?>
<SCRIPT language="javascript">
<!-- 2002.1.9 평수가 만듬	-->
<!--
	var Open = ""
	var Closed = ""

	function preload(){
		if(document.images){
			Open = new Image(16,13)	
			Closed = new Image(16,13)
			Open.src = "<?php echo $page['left_topmenuicon_open'] ; ?>"
			Closed.src = "<?php echo $page['left_topmenuicon_close'] ; ?>"
		}
	}
	function showhide(what,what2){
		if (what.style.display == 'none'){
			what.style.display='';
			what2.src=Open.src
		}
		else{
			what.style.display='none'
			what2.src=Closed.src
		}
	}
//-->
</SCRIPT>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body bgcolor="<?php echo $page['left_bgcolor'] ; ?>" background="<?php echo $page['left_background'] ; ?>"	onLoad="preload()" topmargin='', leftmargin=0>

<table width="180" cellspacing="1" cellpadding="1">
	<tr> 
	<td><a href="/logout.php">로그아웃</a></td>
	</tr>
<!-- 관리자 관리 -->
	<tr>
	<td>
	<span id="menu0" onClick="showhide(menu0outline,menu0sign)" style="cursor:hand; font-Family:Verdana;	font-weight:bold"><font style="text-decoration:none" size=2><img id="menu0sign" src="<?php echo $page['left_topmenuicon_close'] ; ?>" valign="bottom"></font> <a href="./setup.php" target="main">관리페이지설정</a></span><br> 
	<span id="menu0outline" style="display:'none'"> 
<?php
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='./setup.php' target='main'>환경설정</a><br>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='./myadmin224/read_dump.php?is_js_confirmed=0&lang=ko&server=1&pos=0&goto=db_details.php&zero_rows=SQL%B9%AE%C0%CC+%C1%A4%BB%F3%C0%FB%C0%B8%B7%CE+%BD%C7%C7%E0%B5%C7%BE%FA%BD%C0%B4%CF%B4%D9.&prev_sql_query=&sql_query=select+*+from+{$SITE['th']}logon+where+class%3D%27root%27&show_query=y&sql_file=&SQL=%BD%C7%C7%E0' target='main'>운영자 검색</a><br>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='./menu/cate.php' target='main'>관리메뉴관리</a><br>"; 
?>
	</span> 
	</td>
	</tr>
<!-- 관리자 관리 메뉴 끝 -->

<!--	관리 메뉴에 추가된 상위 메뉴 출력 -->
<?php
$result = db_query("SELECT * FROM {$SITE['th']}admin_menu WHERE re='' ORDER BY num");
$total = db_count();

for($i=0; $i<$total; $i++){
	$list = db_array($result); ?>
<tr>
	<td>
	
		<span id="menu
<?php echo $list['uid'] ; ?>" onClick="showhide(menu
<?php echo $list['uid'] ; ?>
outline,menu
<?php echo $list['uid'] ; ?>
sign)" style="cursor:hand; font-Family:Verdana; font-weight:bold"><font style="text-decoration:none" size=2><img id="menu
<?php echo $list['uid'] ; ?>
sign" src="<?php echo $page['left_topmenuicon_close'] ; ?>" valign="bottom"> </font>
<?php echo $list['title'] ; ?></span><br> 

		<span id="menu
<?php echo $list['uid']; ?>
outline" style="display:'none'"> 
<?php
			## 서브 메뉴를 뿌린당..
			$result2 = db_query("SELECT * FROM {$SITE['th']}admin_menu WHERE num={$list['num']} and re<>'' ORDER BY re");
			$total2 = db_count();
			for($j=0; $j<$total2; $j++){
				$list2 = db_array($result2); ?>
		
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='<?php echo $list2['url'] ; ?>' target='main'>
<?php echo $list2['title'] ; ?></a><br> 
<?php
} 
?>
						
		</span> 

	</td>
	</tr>
<?php
} 
?>
<!--	관리 메뉴에 추가된 상위 메뉴 출력 끝-->
</table>
</body>
</html>