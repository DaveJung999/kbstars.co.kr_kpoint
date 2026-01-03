<?php
//=======================================================
// 설 명 : 일정관리(mini calendar)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/10
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/10 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv'		 => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard' => 1,
	'useApp'	 => 1,
	'html_echo'	 => 0, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
$thisPath			= dirname(__FILE__);
// userfuntions.php 와 function_lunartosol.php 는 header.php 에서 이미 로드되었다고 가정합니다.
// include_once("{$thisPath}/userfuntions.php");
// include_once("{$thisPath}/function_lunartosol.php");
$thisUrl			= "/scalendar"; // 마지막 "/"이 빠져야함

$db = $_GET['db'] ?? '';
// 기본 URL QueryString
$qs_basic = "db=".urlencode($db);

$table_calendarinfo	= "{$SITE['th']}calendarinfo";

if(isset($db)) {
	$sql = "SELECT * FROM {$table_calendarinfo} WHERE `db`='" . db_escape($db) . "'";
	if( !$dbinfo=db_arrayone($sql) )
		back("사용하지 않은 DB입니다.","infoadd.php?mode=user");

	$table_calendar	= "{$SITE['th']}calendar_" . $dbinfo['table_name']; // 게시판 테이블
	$sql_where_cal = " `infouid`='" . (int)$dbinfo['uid'] . "' ";
}
else back("DB 값이 없습니다");

// 넘오온 date값 체크
$req_date = date("Y-m-d");
if (isset($_GET['F_Year']) && isset($_GET['F_Month']) && isset($_GET['F_Day'])) {
	$req_date = $_GET['F_Year']."-".$_GET['F_Month']."-".$_GET['F_Day'];
} elseif (isset($_GET['date'])) {
	$req_date = $_GET['date'];
}

if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0-3]?[0-9]$/", $req_date) ) {
	$req_date = date("Y-m-d"); // 잘못된 형식이면 오늘 날짜로
}
$req_date = date("Y-m-d",strtotime($req_date));

// 각종 날짜 변수
$intThisTimestamp	= strtotime($req_date);
$gyear	= date("Y",$intThisTimestamp);
$gmonth	= date("m",$intThisTimestamp);
$gday		= date("d",$intThisTimestamp);

$prevMonthDate = strtotime("-1 month", $intThisTimestamp);
$intPrevYear = date("Y", $prevMonthDate);
$intPrevMonth = date("m", $prevMonthDate);

$nextMonthDate = strtotime("+1 month", $intThisTimestamp);
$intNextYear = date("Y", $nextMonthDate);
$intNextMonth = date("m", $nextMonthDate);

$sd = date("w", mktime(0,0,0,(int)$gmonth,1,(int)$gyear)); //요일 구하기 (num)
$ed = date("t", $intThisTimestamp); //마지막날 구하기
$jucnt = ceil(($sd+$ed)/7);

$outCal = [];
////////////////////////////
// 일정 구하기
$searchDateFrom = "{$gyear}-{$gmonth}-01";
$searchDateTo	= "{$gyear}-{$gmonth}-{$ed}";

$sql = "SELECT * FROM {$table_calendar} WHERE {$sql_where_cal} ";
$sql .= "AND `startdate` <= '{$searchDateTo}' AND `enddate` >= '{$searchDateFrom}' ";
$sql .= "AND (`dtype` = 'hour' OR `dtype` = 'day') ";
$sql .= "ORDER BY `startdate`, `starthour`";
$result	= db_query($sql);

$div_content = [];
while( $list=db_array($result) ) {
	if(!privAuth($list,"priv_level")) {
		// 비공개 일정은 미니 달력에 표시하지 않음
		continue;
	}
	
	$list['title'] = htmlspecialchars($list['title'],ENT_QUOTES, 'UTF-8');
	$div_content[$list['startdate']] = ($div_content[$list['startdate']] ?? '') . "- ".$list['title']."[".substr($list['startdate'], 8, 2) . "일]<br>";
}
if(is_array($div_content)) {
	foreach($div_content as $key => $list_content) {
		$js_list_content = addslashes($list_content);
		$outCal[$key] = " onMouseOver=\"parent.view_min('{$js_list_content}');\" onMouseOut=\"parent.noview();\"";
	}
}
////////////////////////////
?>
<script language='javascript' src='/scalendar/Scrolling.js'></script>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<table width="225" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr>
		<td><table width="99%" border='0' align='center' cellpadding='0' cellspacing='0'>
			<tr>
				<td height="36" width="82" valign="middle"><a href="/scalendar/index.php?db=<?= htmlspecialchars($db, ENT_QUOTES, 'UTF-8') ?>" target="_parent"><img src="/images/main/calendar_top.gif" width="80" height="34" border="0" /></a></td>
				<td align="center" valign="middle"><a href="?db=<?= htmlspecialchars($db, ENT_QUOTES, 'UTF-8') ?>&F_Year=<?= $intPrevYear ?>&F_Month=<?= $intPrevMonth ?>&F_Day=1"><img src="/images/main/a_p.gif" width="13" height="13" border="0" align="absmiddle" /></a><font color="7378B8"><strong> <?= $gyear ?>년</strong></font> <strong><font color="7378B8">
				<?= $gmonth ?>월 </font></strong><a href="?db=<?= htmlspecialchars($db, ENT_QUOTES, 'UTF-8') ?>&F_Year=<?= $intNextYear ?>&F_Month=<?= $intNextMonth ?>&F_Day=1"><img src="/images/main/a_n.gif" width="13" height="13" border="0" align="absmiddle" /></a></td>
			</tr>
		</table></td>
	</tr>
	<tr>
		<td><table width="202" border='0' align='center' cellpadding='0' cellspacing='0' bgcolor='#f3f3f3'>
			<tr valign="middle">
				<td height="23" align='center'><img src="/images/main/date_icon.gif" width="202" height="20" border="0" /></td>
			</tr></table></td>
	</tr>
	<tr>
		<td><table width="202" border='0' align='center' cellpadding='0' cellspacing='1' bgcolor='#e6e6e6'>
<?php
$day = -$sd + 1;
for ( $ju=0 ; $ju < $jucnt ; $ju++ ) {
?>
	<tr bgcolor="#ffffff">
<?php
		for ( $i=0 ; $i < 7 ; $i++, $day++ ) {
				$intcday = sprintf("%s-%s-%02d", $gyear, $gmonth, $day);
				
				$__tcolor_style = "";
				if ( $day == $gday ) $__tcolor_style="background-color:#FFD6AC;";
				
				$__content = $outCal[$intcday] ?? '';
				if($__content){
					$__tcolor_style="background-color:#E0D6FD;";
				}

				$__day = "";
				if ( $day > 0 && $day <= $ed ) {
					$day_href = htmlspecialchars("{$thisUrl}/index.php?db={$db}&mode=day&date={$gyear}-{$gmonth}-{$day}", ENT_QUOTES, 'UTF-8');
					$__day="<a href='{$day_href}' {$__content} target=_parent><font size='2' color='#333333'>{$day}</font></a>";
				}
?>
		<td width="14%" height="18" align="center" style="<?= $__tcolor_style ?>"><?= $__day ?></td>
<?php
		}
?>
	</tr>
<?php
}
?>
	</table></td>
	</tr>
	<tr>
		<td height="5"></td>
	</tr>
	<tr>
		<td height="1" background="/images/main/dott_line.gif"><img src="/images/tr_px.gif" width="1" height="1" border="0"></td>
	</tr>
	<tr>
		<td height="5"></td>
	</tr>
	<tr>
		<td align="center">
<?php
			$calendar = array();
			$where = "where `startdate` >= '". db_escape($searchDateFrom) . "' and `startdate` <= '". db_escape($searchDateTo) . "'";
			$sql_cal = "select * from {$table_calendar} ".$where." and {$sql_where_cal} order by `startdate`, `starthour`, `startmin`";
			$res = db_query($sql_cal);
			$i = 1;
			$Script_Commend = "";
			$pre_title = '<img src="/images/main/color_box_c1.gif" align="absmiddle">';
			while($row = db_array($res)) {
				$view_href = htmlspecialchars("/scalendar/index.php?db={$db}&mode=view&bmode=month&uid={$row['uid']}", ENT_QUOTES, 'UTF-8');
				$row_title = htmlspecialchars(cut_string($row['title'],20), ENT_QUOTES, 'UTF-8');
				$outCal_js = $outCal[$row['startdate']] ?? '';
?>
			<div style='display:none;' id='Mem<?=$i?>'>
			<table width="202" border="0" cellspacing="0" cellpadding="1">
			<tr>
				<td><a href="<?= $view_href ?>" target=_parent class="bmails00102" <?= $outCal_js ?>><?= $pre_title ?> <font color="#111111">[<?=substr($row['startdate'], 5)?>]</font> <?= $row_title ?></a></td>
			</tr>
			</table>
			</div>
<?php
					$Script_Commend .= "MEMBER1.add(Mem".$i.".innerHTML);\n";
					$i++;
			}
			if($i > 1)
				echo "
					<script language='javascript'>
					var MEMBER1 = new HanaScl();
					MEMBER1.name = 'MEMBER1';
					MEMBER1.height = 20;
					MEMBER1.width = 202;
					MEMBER1.scrollspeed = 10;
					MEMBER1.pausedelay = 3000;
					MEMBER1.pausemouseover = true;
					{$Script_Commend}
					MEMBER1.start();
					</script>
				";
?>
	</td>
	</tr>
</table>

<script>
	function iframexy(e) {
		var event = e || window.event;
		parent.x = event.clientX + 20;
		parent.y = event.clientY + 340;
	}
	
	document.onmousemove = iframexy;

	window.onload = function() {
		try {
			if ( <?= (int)$jucnt ?> > 5) {
				parent.document.getElementById('cal_min').height = 204;
			} else {
				parent.document.getElementById('cal_min').height = 184;
			}
		} catch(e) {
			// 부모 프레임 접근 오류는 무시
		}
	}
</script>
