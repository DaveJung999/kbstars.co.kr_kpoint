<?php
//=======================================================
// 설 명 : 메인 첫 페이지 샘플(/index_basic.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv'	 => 99, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1, // 보드관련 함수 포함
	'html_echo'	 => 1, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'html_skin'	 => "admin_basic", // html header 파일(/stpl/basic/index_$HEADER['html'].php 파일을 읽음)
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
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$prefix		= "board4"; // board? album? 등의 접두사
$thisPath	= dirname(__FILE__);
$thisUrl	= "/s{$prefix}"; // 마지막 "/"이 빠져야함

// 기본 URL QueryString
$qs_basic		= href_qs();
$table_dbinfo	= "{$SITE['th']}{$prefix}info";

$_GET['db'] = 'sysconfig';
$db = $_GET['db'];
// info 테이블 정보 가져와서 $dbinfo로 저장
if(isset($db)) {
	$sql = "SELECT * FROM {$table_dbinfo} WHERE `db`='" . db_escape($db) . "'";
	$dbinfo=db_arrayone($sql) or back("사용하지 않은 DB입니다.","/");

	$dbinfo['table']	= "{$SITE['th']}{$prefix}_" . $dbinfo['db']; // 게시판 테이블
}
else back("DB 값이 없습니다");

// 넘어온 값에 따라 $dbinfo값 변경
if(($dbinfo['enable_getinfo'] ?? '')=='Y') {
	// skin 변경
	if( isset($_GET['skin']) && preg_match("/^[_a-z0-9]+$/i", $_GET['skin'])
		&& is_file("{$thisPath}/stpl/{$_GET['skin']}/search_real.htm") )
		$dbinfo['skin']	= $_GET['skin'];
	// 사이트 해더테일 변경
	if(isset($_GET['html_headpattern']))	$dbinfo['html_headpattern'] = $_GET['html_headpattern'];
	if( isset($_GET['html_headtpl']) && preg_match("/^[_a-z0-9]+$/i", $_GET['html_headtpl'])
		&& is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$_GET['html_headtpl']}.php") )	
		$dbinfo['html_headtpl'] = $_GET['html_headtpl'];
}

$sql_where = " 1 ";
$sql_where .= " and `uid` = '1' ";

$sql = "select * from {$dbinfo['table']} where  $sql_where ";
$list = db_arrayone($sql);

$dbinfo['color_main_title'] ="#9494C9";
$dbinfo['color_sub_title'] ="#BDBCE3";
$dbinfo['color_current_list'] ="#F1F1FF";
	
?>
<script>
//================================================================================================
// 버튼/아이콘 이미지 마우스 오버, 마우스 다운, 마우스아웃 관련 스크립트 davej...........2004-08-03
//================================================================================================
	function onOut(imgObj){
		var src = imgObj.src;
		var i = src.lastIndexOf("_");
		var str = src.substr(0, i);
		str = str+"_0.gif";
		imgObj.src = str;
	}
	function onOver(imgObj){
		var src = imgObj.src;
		var i = src.lastIndexOf("_");
		var str = src.substr(0, i);
		str = str+"_1.gif";
		imgObj.src = str;
	}
	
	function onDown(imgObj){
		var src = imgObj.src;
		var i = src.lastIndexOf("_");
		var str = src.substr(0, i);
		str = str+"_2.gif";
		imgObj.src = str;
	}
</script>
<link href="/admin/images/basic/basic.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.style11 {color: #0033FF}
-->
</style>
<table width="100%" border="0" align="center" cellpadding="1" cellspacing="1" bgcolor="#bbbbbb">
	<tr>
		<td height="23" bgcolor="<?= htmlspecialchars($dbinfo['color_main_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td><img src="/sboard2/stpl/a_class/images/company.gif" width="16" height="15" border="0" align="absmiddle"> <strong>환경설정</strong>&nbsp;&nbsp;&nbsp; <img src="/sboard2/stpl/a_class/images/bar_menu.gif" width="3" height="16" border="0" align="absmiddle">&nbsp; <a href="./index.php" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_system_0.gif" width="80" height="18" border="0" align="absmiddle" OnMouseDown = "onDown(this);" OnMouseOver = "onOver(this);" OnMouseOut = "onOut(this);"></a> <img src="/sboard2/stpl/a_class/images/bar.gif" width="1" height="16" border="0" align="absmiddle"> <a href="./popup.php" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_popupad_0.gif" width="70" height="18" border="0" align="absmiddle" OnMouseDown = "onDown(this);" OnMouseOver = "onOver(this);" OnMouseOut = "onOut(this);"></a> <img src="/sboard2/stpl/a_class/images/bar.gif" width="1" height="16" border="0" align="absmiddle"> <a href="./mailmessage.php" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_mailmsg_0.gif" width="100" height="18" border="0" align="absmiddle" OnMouseDown = "onDown(this);" OnMouseOver = "onOver(this);" OnMouseOut = "onOut(this);"></a> <img src="/sboard2/stpl/a_class/images/bar.gif" width="1" height="16" border="0" align="absmiddle"> <a href="./bank.php" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_bankadmin_0.gif" width="90" height="18" border="0" align="absmiddle" OnMouseDown = "onDown(this);" OnMouseOver = "onOver(this);" OnMouseOut = "onOut(this);"></a> <img src="/sboard2/stpl/a_class/images/bar.gif" width="1" height="16" border="0" align="absmiddle"> <a href="./logo.php" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_adjlogo_0.gif" width="90" height="18" border="0" align="absmiddle" OnMouseDown = "onDown(this);" OnMouseOver = "onOver(this);" OnMouseOut = "onOut(this);"></a></td>
				</tr>
		</table></td>
	</tr>
</table>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr>
		<td height="1"></td>
	</tr>
</table>

<table width="100%" border="0" align="center" cellpadding="1" cellspacing="1" bgcolor="#bbbbbb">
	<form name="sysconfig" method="post" action="./ok.php">
	<input type="hidden" name="mode" value="sysconfig">
	<input type="hidden" name="uid" value="<?= htmlspecialchars($list['uid'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
	<tr>
		<td height="23" colspan="2" bgcolor="<?= htmlspecialchars($dbinfo['color_sub_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><table width="95%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td><img src="/sboard2/stpl/a_class/images/title_blue.gif" width="13" height="14" border="0" align="absmiddle"> <strong><span class="style11">시스템 정보</span> &nbsp; <img src="/sboard2/stpl/a_class/images/bar_menu.gif" width="3" height="16" border="0" align="absmiddle">&nbsp;
							<a href="#" onClick="document.sysconfig.submit();" onFocus="this.blur();"><img src="/sboard2/stpl/a_class/images/btm_save_0.gif" width="50" height="18" border="0" align="absmiddle" onMouseDown = "onDown(this);" onMouseOver = "onOver(this);" onMouseOut = "onOut(this);"></a>
</strong> </td>
				</tr>
		</table></td>
	</tr>
	<tr>
		<td width="17%" bgcolor="#E6E6E6">&nbsp; 사이트명</td>
		<td width="83%" bgcolor="#FFFFFF"><input type="text" name="site_name" style="width:95%" class="styleinput" value="<?= htmlspecialchars($list['site_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 홈페이지</td>
		<td bgcolor="#FFFFFF"><input type="text" name="homepage" style="width:95%" class="styleinput" value="<?= htmlspecialchars($list['homepage'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 회사명</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_name" size="20" class="styleinput" value="<?= htmlspecialchars($list['c_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 대표</td>
		<td bgcolor="#FFFFFF"><input type="text" name="ceo_name" size="20" class="styleinput" value="<?= htmlspecialchars($list['ceo_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 업태</td>
		<td bgcolor="#FFFFFF"><input type="text" name="b_conditions" size="20" class="styleinput" value="<?= htmlspecialchars($list['b_conditions'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 종목</td>
		<td bgcolor="#FFFFFF"><input type="text" name="b_item" size="20" class="styleinput" value="<?= htmlspecialchars($list['b_item'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; Tel 1</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_tel1" size="20" class="styleinput" value="<?= htmlspecialchars($list['c_tel1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; Tel 2</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_tel2" size="20" class="styleinput" value="<?= htmlspecialchars($list['c_tel2'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; Fax</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_fax" size="20" class="styleinput" value="<?= htmlspecialchars($list['c_fax'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 대표 E-Mail</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_email" size="30" class="styleinput" value="<?= htmlspecialchars($list['c_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 사업자<br>&nbsp; 등록번호</td>
		<td bgcolor="#FFFFFF"><input type="text" name="c_biznum" size="30" class="styleinput" value="<?= htmlspecialchars($list['c_biznum'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	<tr>
		<td bgcolor="#E6E6E6">&nbsp; 센터 주소</td>
		<td bgcolor="#FFFFFF"><input name="c_zipcode" type="text" class="styleinput" id="c_zipcode" value="<?= htmlspecialchars($list['c_zipcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" size="7">
				<br>
				<input type="text" name="c_addr" size="60" class="styleinput" value="<?= htmlspecialchars($list['c_addr'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
	</tr>
	</form>
</table>
<?php
//=======================================================
echo $SITE['tail'] ?? '';
?>
