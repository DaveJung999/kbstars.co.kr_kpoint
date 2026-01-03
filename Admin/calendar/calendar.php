<?php
//=======================================================
// 설	명 : 메인 첫 페이지 샘플(/index_basic.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
// 25/11/10 Gemini   PHP 7 마이그레이션 (mysql_* 함수 db_*로 대체, 배열 변수 접근 수정)
//=======================================================
$HEADER=array(
	'priv'		 => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1 // DB 커넥션 사용 (0:미사용, 1:사용)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");

$thisPath			= dirname(__FILE__);
include_once("{$thisPath}/userfuntions.php");
include_once("{$thisPath}/function_lunartosol.php");
$thisUrl			= "/Admin/calendar"; // 마지막 "/"이 빠져야함

$G = &$_GET;
$P = &$_POST;

$_GET['db'] = "schedule ";
	switch($G['mode']) {
		case "view":
			switch($G['submode']) {
				case "next":
					$stime = $G['etime'] + 7 * 86400;
					break;
				case "prev":
					$stime = $G['stime'] - 13 * 86400;
					break;
				default:
					$nowtime = time();
					$stime = $nowtime;
			}
			$date = explode(" ", date("Y m j w", $stime));
			$year = $date['0'];
			$month = $date['1'];
			$day = $date['2'];
			$week = $date['3'];

			$settime = mktime(0,0,0,$month,$day,$year);

			$stime = $week > 0 ? $settime - $week * 86400 : $settime;
			$etime = $stime + 14 * 86400 - 1;
			
			if(date("n", $stime) == date("n"))
				$tdate = explode(" ", date("Y n j w", $stime));
			else
				$tdate = explode(" ", date("Y n j w", $etime));
			$tyear = $tdate['0'];
			$tmonth = $tdate['1'];
		
			$where = "where startdate > '".date("Y-m-d", $stime) . "' and startdate < '".date("Y-m-d", $etime) . "'";
/*
			// mysql_query() -> db_query(), mysql_fetch_array() -> db_array()로 대체
			$res = db_query("select * from new21_calendar_schedule2 ".$where." order by startdate, starthour, startmin");
			while($row = db_array($res)) {
				$calendar[$row['startdate']]['kind'] = $row['kind']; // 배열 키에 따옴표 사용
				$calendar[$row['startdate']]['uid'] = $row['uid']; // 배열 키에 따옴표 사용
			}
			db_free($res);
*/
			// mysql_query() -> db_query(), mysql_fetch_array() -> db_array()로 대체
			$res = db_query("select * from new21_calendar_schedule ".$where." order by startdate, starthour, startmin");
			while($row = db_array($res)) {
				$calendar[$row['startdate']]['kind'] = $row['kind']; // 배열 키에 따옴표 사용
				$calendar[$row['startdate']]['uid'] = $row['uid']; // 배열 키에 따옴표 사용
			}
			db_free($res);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>::: 광주/전남지방병무청에 오신걸 환영합니다 :::</title>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style type="text/css">
</style>
<script language='javascript'>
	function Loading() {
		// 변수 접근 시 중괄호 사용
		parent.window.frames['iframesubject'].document.location = '<?=$thisUrl?>/calendar.php?mode=miniSubject&stime=<?=$stime?>&etime=<?=$etime?>';
	}
</script>
</head>

<body leftmargin=0 topmargin=0 onload='Loading()'>
<table border=0 cellpadding=0 cellspacing=0 width=225>
		<tr>
		<td><table width="100%"	border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td width="82"><img src="/images/main/calendar_top.gif" width="82" height="36" border="0"></td>
			<td align="center" background="/images/main/calendar_title_bg.gif"><a href="?mode=<?=$G['mode']?>&stime=<?=$stime?>&submode=prev"><img src="/images/main/a_p.gif" width="13" height="13" border="0" align="absmiddle"></a><strong><font color="7378B8"> <?=$tyear?>년 <?=$tmonth?>월 </font></strong><a href="?mode=<?=$G['mode']?>&etime=<?=$etime?>&submode=next"><img src="/images/main/a_n.gif" width="13" height="13" border="0" align="absmiddle"></a></td>
			<td width="5"><img src="/images/main/calendar_r.gif" width="5" height="35" border="0"></td>
			</tr>
		</table></td>
		</tr>
		<tr>
		<td align="center" background="/images/main/calendar_bg.gif"><table width="202"	border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td height="21" valign="top"><img src="/images/main/date_icon.gif" width="202" height="20" border="0"></td>
			</tr>
			<tr>
			<td><table width="100%"	border="0" cellspacing="0" cellpadding="0">
				<tr>
				<td><table width="100%"	border="0" cellspacing="0" cellpadding="0">
					<tr>
<?php
			$calendar = $calendar ?? []; // PHP 7: 변수가 정의되지 않았을 경우 대비 (배열 사용)
			for($i = $stime; $i < $etime + 1; $i += 86400) {
				// 배열 접근 시 중괄호 사용 및 isset 체크
				if(isset($calendar[date("Y-m-d", $i)]['kind']) && $calendar[date("Y-m-d", $i)]['kind'] == "지방청")//	지방청
					echo "<td width='28' height='20' align='center' background='/images/main/date_color_bg01.gif'><a href='/scalendar/index.php?db=schedule&mode=view&bmode=month&uid={$calendar[date(\"Y-m-d\", $i)]['uid']}' class='bw02'>".date('j', $i) . "</a></td>";
				else if(isset($calendar[date("Y-m-d", $i)]['kind']) && $calendar[date("Y-m-d", $i)]['kind'] == "지역행사")//	문화행사
					echo "<td width='28' height='20' align='center' background='/images/main/date_color_bg02.gif'><a href='/scalendar/index.php?db=schedule2&mode=view&bmode=month&uid={$calendar[date(\"Y-m-d\", $i)]['uid']}' class='bw02'>".date('j', $i) . "</a></td>";
				else
					echo "<td width='28' height='20' align='center' background='/images/main/date_bg.gif'><span class='bmainnotice'>".date('j', $i) . "</span></td>";
				
				if(date("w", $i) < "6")
					echo "<td width='1'><img src='/images/tr_px.gif' width='1' height='1' border='0'></td>";
				if(date("w", $i) == "6") {
?>
					</tr>
				</table></td>
				</tr>
				<tr>
				<td height="1"><img src="/images/tr_px.gif" width="1" height="1" border="0"></td>
				</tr>
				<tr>
				<td><table width="100%"	border="0" cellspacing="0" cellpadding="0">
					<tr>
<?php
				}
			}
?>
					</tr>
				</table></td>
				</tr>
				<tr>
				<td height="30" align="right"><img src="/images/main/color_box.gif" width="145" height="15" border="0"></td>
				</tr>
			</table></td>
			</tr>
			<tr>
			<td height="1" background="/images/main/dott_line.gif"><img src="/images/tr_px.gif" width="1" height="1" border="0"></td>
			</tr>
		</table></td>
		</tr>
</table>
</body>
</html>
<?php
			break;
		case "miniSubject":
			$calendar = array();
			$where = "where startdate > '".date("Y-m-d", $G['stime']) . "' and startdate < '".date("Y-m-d", $G['etime']) . "'";
/*
			// mysql_query() -> db_query(), mysql_fetch_array() -> db_array()로 대체
			$res = db_query("select * from new21_calendar_schedule2 ".$where." order by startdate, starthour, startmin");
			while($row = db_array($res)) {
				$calendar[$row['startdate']] = array_merge($row, array("db" => "schedule2"));
			}
			db_free($res);
*/
			// mysql_query() -> db_query(), mysql_fetch_array() -> db_array()로 대체
			$res = db_query("select * from new21_calendar_schedule ".$where." order by startdate, starthour, startmin");
			while($row = db_array($res)) {
				$calendar[$row['startdate']] = array_merge($row, array("db" => "schedule"));
			}
			db_free($res);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>::: 광주/전남지방병무청에 오신걸 환영합니다 :::</title>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style type="text/css">
</style>
<script language='javascript' src='/scalendar/Scrolling.js'></script>
</head>
<body leftmargin=0 topmargin=0>
<?php
			$i = 1;
			foreach($calendar as $date => $row) {
				if ($row['db'] == 'schedule') $pre_title = '<img src="/images/main/color_box_c1.gif" align="absmiddle">';
				else	$pre_title = '<img src="/images/main/color_box_c2.gif" align="absmiddle">';
?>
				<div style='display:none;' id='Mem<?=$i?>'>
				<table width="202" border="0" cellspacing="0" cellpadding="0">
				<tr>
				<td><a href="/scalendar/index.php?db=<?=$row['db']?>&mode=view&bmode=month&uid=<?=$row['uid']?>" target="_parent" class="b01"><strong><?=$pre_title;?><?=htmlspecialchars($row['title'])?></strong></a></td>
				</tr>
				<tr>
				<td align="right" class="b02">[<?=$date?>]</td>
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
				MEMBER1 = new HanaScl();
				MEMBER1.name = 'MEMBER1';
				MEMBER1.height = 40;
				MEMBER1.width	= 202;
				MEMBER1.scrollspeed	= 10;
				MEMBER1.pausedelay	= 3000;
				MEMBER1.pausemouseover = true;
				$Script_Commend
				MEMBER1.start();
			</script>
				";
?>
</body>
</html>
<?php
			break;
	}
?>