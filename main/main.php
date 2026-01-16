<?php
//=======================================================
// 설  명 : 리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/11
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/08/11 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php 변환
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'header'	=>1, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'html_echo'	=>1,
	'html_skin' =>"main" // html header 파일(/stpl/basic/index_$HEADER['html'].php 파일을 읽음)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	// PHP 8+에서 더 안전한 방식: $_REQUEST[$param] 값이 없으면 null을 할당.
	// 기존 변수 ($$param)에 값이 이미 있다면 그 값을 유지 (원본의 이중 널 병합 의도를 최대한 유지하며 개선)
	$$param = $_REQUEST[$param] ?? (isset($$param) ? $$param : null);
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================


function main_content($db, $skin, $cut_length=40, $rows=1){
		$oldGET = $_GET;
		// 배열 키에 따옴표를 추가 (PHP 8+ 호환)
		$_GET = array( 'db' => "$db", // 게시물 db
					'cut_length' => "$cut_length", // 게시물 제목 길이
					'limitno'		 => 0,
					'limitrows'		=> $rows,
					'sql_where'		=> " 1 ",
					'enable_listreply' => "no",
					'skin' => "$skin", // 게시판 스킨
					'html_headpattern'=>"no" // Site 해더 넣지 않음
					);
		include("{$_SERVER['DOCUMENT_ROOT']}/sboard2/list.php");
		$_GET = $oldGET;
}

// 게시판 게시물 가져오기............................
function board($db, $skin, $cut_length=40, $rows=5)
{
		$oldGET = $_GET;
		// 배열 키에 따옴표를 추가 (PHP 8+ 호환)
		$_GET = array( 'db' => "$db", // 게시물 db
					'cut_length' => "$cut_length", // 게시물 제목 길이
					'limitno'		 => 0,
					'limitrows'		=> $rows,
					'sql_where'		=> " 1 ",
					'sql_order'		=> " num desc, re ",
					'enable_listreply' => "no",
					'skin' => "$skin", // 게시판 스킨
					'html_headpattern'=>"no" // Site 해더 넣지 않음
					);
		include("{$_SERVER['DOCUMENT_ROOT']}/sboard2/list.php");
		$_GET = $oldGET;
}

function photo($db, $skin, $limitrows=1, $row_pern=1)
{
		$oldGET = $_GET;
		// 배열 키에 따옴표를 추가 (PHP 8+ 호환)
		$_GET = array( 'db' => "$db", // 게시물 db
					'cut_length' => 25, // 게시물 제목 길이
					'limitno'		 => 0,
					'limitrows'		=> "$limitrows",
					'row_pern'		=> "$row_pern",
					'sm_code'		=> $oldGET['sm_code'],
					'sql_where'		=> " 1 ",
					'enable_listreply' => "no",
					'skin' => "$skin", // 게시판 스킨
					'html_headpattern'=>"no" // Site 해더 넣지 않음
					);
		include("{$_SERVER['DOCUMENT_ROOT']}/sboard2/list.php");
		$_GET = $oldGET;
}

function player_selectbox($skin="fanletter", $cateuid=0){
		$oldGET = $_GET;
		// 배열 키에 따옴표를 추가 (PHP 8+ 호환)
		$_GET = array( 'db' => "basket_player", // 게시물 db
					'sql_where'		=> " tid = 6 and p_gubun = '현역' ",
					'sql_order'		=> " p_name ",
					'skin' => "$skin", // 게시판 스킨
					'pid'		=> "$cateuid",
					'html_headpattern'=>"no" // Site 해더 넣지 않음
					);
		include("{$_SERVER['DOCUMENT_ROOT']}/basketball/player_selectbox/list.php");
		$_GET = $oldGET;
}
// 게시판 게시물 가져오기............................
function event($db, $skin, $cut_length=40, $rows=5)
{
		$oldGET = $_GET;
		// 배열 키에 따옴표를 추가 (PHP 8+ 호환)
		$_GET = array( 'db' => "$db", // 게시물 db
					'cut_length' => "$cut_length", // 게시물 제목 길이
					'cateuid'		 => 1,
					'limitno'		 => 0,
					'limitrows'		=> $rows,
//					'sql_where'		=> " data1 = '1' ",
					'sql_order'		=> " num, re DESC ",
					'enable_listreply' => "no",
					'skin' => "$skin", // 게시판 스킨
					'html_headpattern'=>"no" // Site 해더 넣지 않음
					);
		include("{$_SERVER['DOCUMENT_ROOT']}/sboard2/list.php");
		$_GET = $oldGET;
}

function main_match(){
	include("{$_SERVER['DOCUMENT_ROOT']}/basketball/game/main_match.php");
}

// hot 뉴스 ...................................
$sql = "select * from new21_board2_contents where uid = 19 ";
$list = db_arrayone($sql);

?>

<script language="javascript" src="/scommon/js/Scrolling.js"></script>


<?php

//=======================================================
//관련기관 링크 배너 가져오기..................davej...................
//=======================================================
function banner_link($content){
	
	$sql_link = "select * from new21_board2_banner where content = '$content' and data1 = '1' order by cateuid, num";
	$rs_list_link = db_query($sql_link);
	$cnt_link = db_count($rs_list_link);

	for ($r = 0 ; $r < $cnt_link ; $r++){
		$list_link = db_array($rs_list_link);
		$a = $r+1;
		
		// ${"div_{$content}"} 대신 $GLOBALS["div_{$content}"] 사용 (PHP 8+ 호환)
		$GLOBALS["div_{$content}"] .= "\n\t\t\t<div style='display:none;' id='link_{$content}_$a'><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td align='center'><a href='{$list_link['data4']}' target='_blank'><img src='/sbanner/download.php?db=banner&html_headpattern=no&skin=no&uid={$list_link['uid']}' width='{$list_link['data2']}' height='{$list_link['data3']}' border='0' align='absmiddle'></a></td></tr></table></div>";
		
		// ${"div_{$content}_scr"} 대신 $GLOBALS["div_{$content}_scr"] 사용 (PHP 8+ 호환)
		$GLOBALS["div_{$content}_scr"] .= "Link_{$content}.add(link_{$content}_$a.innerHTML);\n";
	}
	// $GLOBALS로 동적 변수에 접근
	echo "document.writeln(\"".($GLOBALS["div_{$content}"] ?? '')."\");\n";
	
	if ($content == 'familysite'){
		// $GLOBALS로 동적 변수에 접근
		$div_familysite_src = $GLOBALS["div_{$content}_scr"] ?? '';
		return $div_familysite_src;
	}else if ($content == 'banner'){
		// $GLOBALS로 동적 변수에 접근
		$div_banner_src = $GLOBALS["div_{$content}_scr"] ?? '';
		return $div_banner_src;
	}

}

?>

<SCRIPT language=javascript src="/js/slider.js"></SCRIPT>

<table style="margin-right:0; margin-left:0;" cellpadding="0" cellspacing="0" border="0" width="900" bordercolordark="black" bordercolorlight="black">
  <tr>
	<td width="20">&nbsp;</td>
	<td width="230"><img src="/images/index_ranking.gif" width="230" height="40" border="0" /></td>
	<td width="440" colspan="2" valign="top"><table cellpadding="0" cellspacing="0" border="0" width="440">
	  <tr>
		<td width="100"><img src="/images/index_hotn.gif" width="100" height="40" border="0" /></td>
		<td width="305" align="center" background="/images/index_hotn_bg.gif"><table width="90%" height="28" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
	<td align="center"><MARQUEE onmouseover=stop(); onmouseout=start(); scrollAmount=3 direction=left loop=ture>
<font color='#00FFFF'><? board("hotnews", "gonggi_hotnews", 120, 1)?></font></MARQUEE></td>
  </tr>
</table></td>
		<td width="35"><img src="/images/index_hotn_rt.gif" width="35" height="40" border="0" /></td>
	  </tr>
	</table></td>
	<td width="210"><img src="/images/index_event.gif" width="210" height="40" border="0" /></td>
  </tr>
  <tr>
	<td width="20">&nbsp;</td>
	<td width="230"><table cellpadding="0" cellspacing="0" border="0" width="230">
	  <tr>
		<td width="107"><img src="/images/index_ranking_bgtop.gif" width="205" height="10" border="0" /></td>
		<td width="25" rowspan="3"><img src="/images/index_ranking_rt.gif" width="25" height="195" border="0" /></td>
	  </tr>
	  <tr>
		<td width="205" height="140" background="/images/index_ranking_bg.gif" valign="top" align="center"><script src="/basketball/stat/6_main_js.php"></script></td>
	  </tr>
	  <tr>
		<td width="107"><img src="/images/index_ranking_bgtail.gif" width="205" height="45" border="0" /></td>
	  </tr>
	</table></td>
	<td width="220"><table cellpadding="0" cellspacing="0" border="0" width="220" height="195">
	  <tr>
		<td width="210" height="25"><img src="/images/index_news.gif" width="220" height="25" border="0" /></td>
	  </tr>
	  <tr>
		<td width="210"><table cellpadding="0" cellspacing="0" border="0" width="220" height="150">
		  <tr>
			<td width="210" background="/images/index_news_bg.gif"><table align="center" cellpadding="0" cellspacing="0" border="0" width="200" height="22">
			  <tr>
				<td width="310" height="130" align="center" id="t0"><table width="100%" height="110" border="0" cellpadding="0" cellspacing="0">
				  <tr>
					<td align="center" valign="top"><? main_content("news", "main_content_2", "12", "1");?></td>
				  </tr>
				</table>
				  </td>
			  </tr>
			  <tr>
				<td height="11" align="right"><a href="/sboard2/list.php?db=news"><img src="/images/icon_more.gif" width="35" height="11" border="0" /></a></td>
			  </tr>
			</table></td>
			<td width="10"><img src="/images/index_news_rt.gif" width="10" height="150" border="0" /></td>
		  </tr>
		</table></td>
	  </tr>
	  <tr>
		<td width="210" height="20"><img src="/images/index_news_tail.gif" width="220" height="20" border="0" /></td>
	  </tr>
	</table></td>
	<td width="220"><table cellpadding="0" cellspacing="0" border="0" width="220" height="195">
	  <tr>
		<td width="210" height="25"><img src="/images/index_notice.gif" width="220" height="25" border="0" /></td>
	  </tr>
	  <tr>
		<td width="210"><table cellpadding="0" cellspacing="0" border="0" width="220" height="150">
		  <tr>
			<td width="10" background="/images/index_news_bg.gif"><img src="/images/index_notice_lt.gif" width="10" height="150" border="0" /></td>
			<td width="200" background="/images/index_news_bg.gif"><table align="center" cellpadding="0" cellspacing="0" border="0" width="200" height="22">
			  <tr>
				<td width="310" height="130" align="center" id="t0"><table width="100%" height="110" border="0" cellpadding="0" cellspacing="0">
				  <tr>
					<td align="center" valign="top"><? board("notice", "gonggi_basic", "20", "5");?></td>
				  </tr>
				</table>
				  </td>
			  </tr>
			  <tr>
				<td height="11" align="right"><a href="/sboard2/list.php?db=notice"><img src="/images/icon_more.gif" width="35" height="11" border="0" /></a></td>
			  </tr>
			</table></td>
			<td width="10"><img src="/images/index_notice_rt.gif" width="10" height="150" border="0" /></td>
		  </tr>
		</table></td>
	  </tr>
	  <tr>
		<td width="210" height="20"><img src="/images/index_notice_tail.gif" width="220" height="20" border="0" /></td>
	  </tr>
	</table></td>
	<td width="210" valign="top"><table cellpadding="0" cellspacing="0" border="0" width="210">
	  <tr>
		<td width="210" height="10" valign="top"><img src="/images/index_event_top.gif" width="210" height="10" border="0" /></td>
	  </tr>
	  <tr>
		<td width="210" height="140" background="/images/index_event_bg.gif" valign="top"> <table align="center" cellpadding="0" cellspacing="0" border="0" width="100%" height="140">
		  <tr>
			<td width="5" align="center">&nbsp;</td>
			<td align="center"><? event("event", "gonggi_event", "45", "3");?></td>
		  </tr>
		  
		</table></td>
	  </tr>
	  <tr>
		<td width="210" height="45"><img src="/images/index_event_tail.gif" width="210" height="45" border="0" /></td>
	  </tr>
	</table></td>
  </tr>
</table>
<table style="margin-right:0; margin-left:0;" cellpadding="0" cellspacing="0" border="0" width="900">
  <tr>
	<td width="1570"><img src="/images/index_middle.gif" width="900" height="20" border="0" /></td>
  </tr>
</table>
<table style="margin-right:0; margin-left:0;" cellpadding="0" cellspacing="0" border="0" width="900">
  <tr>
	<td width="20">&nbsp;</td>
	<td width="205" valign="top" background="/images/index_poll_bg2.gif"><iframe width="205" height="300" scrolling="No" marginwidth="0" marginheight="0" frameborder="0" vspace="0"
		src="/spoll/index.php"></iframe></td>
	<td width="675"><table cellpadding="0" cellspacing="0" border="0" width="675" height="320">
	  <tr>
		<td width="675" height="20"><img src="/images/index_main_top.gif" width="675" height="20" border="0" /></td>
	  </tr>
	  <tr>
		<td width="675" height="130"><table cellpadding="0" cellspacing="0" border="0" width="675" height="130">
		  <tr>
			<td width="25" height="25"><img src="/images/index_match_lt.gif" width="25" height="130" border="0" /></td>
			<td width="195"><table cellpadding="0" cellspacing="0" border="0" width="195" height="130">
			  <tr>
				<td width="195" height="20"><img src="/images/index_match.gif" width="195" height="20" border="0" /></td>
			  </tr>
			  <tr>
				<td align="center" background="/images/index_match_bg.gif"><? main_match(); ?></td>
			  </tr>
			  
			  
			  <tr>
				<td width="195" height="5"><img src="/images/index_match_tail.gif" width="195" height="5" border="0" /></td>
			  </tr>
			</table></td>
			<td width="30"><img src="/images/index_match_rt.gif" width="30" height="130" border="0" /></td>
			<td width="390"><table cellpadding="0" cellspacing="0" border="0" width="390" height="130">
			  <tr>
				<td width="390" height="15" colspan="3"><img src="/images/index_letter_top.gif" width="390" height="15" border="0" /></td>
			  </tr>
			  <tr>
				<td width="70"><img src="/images/index_letter.gif" width="140" height="70" border="0" /></td>
				<td width="230" height="100" rowspan="2" align="center" background="/images/index_letter_bg1.gif"><?php board("fanletter", "gonggi_fanletter_main", 30, 5)?></td>
				<td width="20" height="100" rowspan="2"><img src="/images/index_letter_rt1.gif" width="20" height="100" border="0" /></td>
			  </tr>
			  <tr>
				<td width="140" height="30" align="center" background="/images/index_letter_bg.gif">
				<?php player_selectbox() ;?></td>
			  </tr>
			  <tr>
				<td width="390" height="15" colspan="3"><img src="/images/index_letter_tail.gif" width="390" height="15" border="0" /></td>
			  </tr>
			</table></td>
			<td width="35"><img src="/images/index_letter_rt.gif" width="35" height="130" border="0" /></td>
		  </tr>
		</table></td>
	  </tr>
	  <tr>
		<td width="675" height="20"><img src="/images/index_main_mid.gif" width="675" height="20" border="0" /></td>
	  </tr>
	  <tr>
		<td width="675" height="130"><table cellpadding="0" cellspacing="0" border="0" width="675" height="130">
		  <tr>
			<td width="25"><img src="/images/index_sch_lt.gif" width="25" height="130" border="0" /></td>
			<td width="195"><a href="/d05/01.php"><img src="/images/index_sch.gif" width="195" height="65" border="0" /></a><a href="/d05/06.php"><img src="/images/index_word.gif" width="195" height="65" border="0" /></a></td>
			<td width="30"><img src="/images/index_multi_mid.gif" width="30" height="130" border="0" /></td>
			<td width="390"><table cellpadding="0" cellspacing="0" border="0" width="390" height="130">
			  <tr>
				<td height="15" colspan="3"><img src="/images/index_multi_top.gif" width="390" height="15" border="0" /></td>
			  </tr>
			  <tr>
				<td width="15" height="100"><img src="/images/index_multi_lt.gif" width="15" height="100" border="0" /></td>
				<td width="356" height="100"><table border="0" cellpadding="0" cellspacing="0">
				  <tr>
					<td width="110" height="100"><table cellpadding="0" cellspacing="0" border="0" width="110" height="100">
						<tr>
						  <td width="110" height="20"><p><span style="font-size:9pt;"><img src="/images/index_photo.gif" width="110" height="20" border="0" /></span></p></td>
						</tr>
						<tr>
						  <td width="110" height="80" background="/images/index_photo_bg.gif"><p align="center">
							<?php photo("photo","gonggi_photo", "1"); ?>
						  </p></td>
						</tr>
					</table></td>
					<td width="10" height="100"><p><span style="font-size:9pt;"><img src="/images/index_multi_line.gif" width="10" height="100" border="0" /></span></p></td>
					<td width="115" height="100"><table cellpadding="0" cellspacing="0" border="0" width="115" height="100">
						<tr>
						  <td width="115" height="20"><p><span style="font-size:9pt;"><img src="/images/index_wall.gif" width="115" height="20" border="0" /></span></p></td>
						</tr>
						<tr>
						  <td width="110" height="80" background="/images/index_wall_bg.gif"><p align="center">
							<?php photo("wallpaper","gonggi_wallpaper", "1"); ?>
						  </p></td>
						</tr>
					</table></td>
					<td width="10" height="100"><p><span style="font-size:9pt;"><img src="/images/index_multi_line1.gif" width="10" height="100" border="0" /></span></p></td>
					<td width="110" height="100"><table cellpadding="0" cellspacing="0" border="0" width="110" height="100">
						<tr>
						  <td width="110" height="20"><p><span style="font-size:9pt;"><img src="/images/index_carnd.gif" width="110" height="20" border="0" /></span></p></td>
						</tr>
						<tr>
						  <td width="110" height="80" valign="middle" background="/images/index_carnd_bg.gif"><p align="center">
							<?php photo("movie","gonggi_photo", "1"); ?>
						  </p></td>
						</tr>
					</table></td>
				  </tr>
				</table>
				</td>
				<td width="20" height="100"><img src="/images/index_multi_rt.gif" width="20" height="100" border="0" /></td>
			  </tr>
			  <tr>
				<td height="15" colspan="3"><img src="/images/index_multi_tail.gif" width="390" height="15" border="0" /></td>
			  </tr>
			</table></td>
			<td width="35"><img src="/images/index_multi_rt1.gif" width="35" height="130" border="0" /></td>
		  </tr>
		</table></td>
	  </tr>
	  <tr>
		<td width="675" height="20"><img src="/images/index_main_mid1.gif" width="675" height="20" border="0" /></td>
	  </tr>
	</table></td>
  </tr>
  <tr>
	<td width="20">&nbsp;</td>
	<td width="205" valign="top" background="/images/index_poll_bg2.gif"><img src="/images/index_poll_tail2.gif" width="205" height="50" border="0" /></td>
	<td width="675"><img src="/images/index_main_tail.gif" width="675" height="50" border="0" /></td>
  </tr>
</table>
<table style="margin-right:0; margin-left:0;" cellpadding="0" cellspacing="0" border="0" width="900">
  <tr>
	<td width="225">&nbsp;</td>
	<td width="335"><table cellpadding="0" cellspacing="0" border="0" width="335">
	  <tr>
		<td width="80"><img src="/images/index_family.gif" width="80" height="50" border="0" /></td>
		<td width="235" background="/images/index_family_bg.gif" align="center">
							<script language="javascript">
							Link_familysite = new HanaScl();
							Link_familysite.name = "Link_familysite";
							Link_familysite.height = 40;
							Link_familysite.width  = 200;
							Link_familysite.scrollspeed  = 5;
							Link_familysite.type  = 1;
							Link_familysite.pausedelay	= 3000;
							Link_familysite.pausemouseover = true;
							<?php echo banner_link('familysite');?>
							Link_familysite.start();
							</script></td>
		<td width="20"><img src="/images/index_family_rt.gif" width="20" height="50" border="0" /></td>
	  </tr>
	</table></td>
	<td width="340"><table cellpadding="0" cellspacing="0" border="0" width="340">
	  <tr>
		<td width="80"><img src="/images/index_link.gif" width="80" height="50" border="0" /></td>
		<td width="235" background="/images/index_family_bg.gif" align="center">
							<script language="javascript">
							Link_banner = new HanaScl();
							Link_banner.name = "Link_banner";
							Link_banner.height = 40;
							Link_banner.width  = 200;
							Link_banner.scrollspeed  = 5;
							Link_banner.type  = 1;
							Link_banner.pausedelay	= 3000;
							Link_banner.pausemouseover = true;
							<?php echo 	banner_link('banner');?>
							Link_banner.start();
							</script></td>
		<td width="25"><img src="/images/index_link_rt.gif" width="25" height="50" border="0" /></td>
	  </tr>
	</table></td>
  </tr>
</table>
<table style="margin-right:0; margin-left:0;" cellpadding="0" cellspacing="0" border="0" width="900">
  <tr>
	<td width="1570" height="20">&nbsp;</td>
  </tr>
</table>

<?php

//=======================================================
// 팝업 관리 
//=======================================================
$today_pop = strtotime(date("Y-m-d"));
$sql_pop = "select * from {$SITE['th']}board2_popup Where data3 = 'yes' and data4 <= $today_pop and data5 >= $today_pop ";
$rs_list_pop = db_query($sql_pop);
$cnt = db_count($rs_list_pop); // db_count()에 $rs_list_pop 전달
if($cnt > 0){
	for ($k = 0 ; $k < $cnt;$k++){
		$list_pop = db_array($rs_list_pop);
		
		$width = $list_pop['data0'];
		if($list_pop['data2'] == "yes")	$width = $width + 16;
		
		echo "<script>
					// 문자열 연결을 사용하여 변수를 명확하게 분리 (PHP 8+ 호환)
					if(GetCookie('popup_".$list_pop['uid']."') != '".$list_pop['uid']."'){
						 void(window.open('/Admin/config/popupskin/".$list_pop['skin']."/index.php?uid=".$list_pop['uid']."','popup_".$list_pop['uid']."','height=".$list_pop['data1'].", width=".$width.", left=".$list_pop['data6'].", top=".$list_pop['data7'].",scrollbars=".$list_pop['data2'].",location=no,directories=no,status=no,menubar=no,toolbar=no,resizable=no'));
					 }
				</script>";
	}
}
?>



<?php
//=======================================================
echo $SITE['tail'];
?>