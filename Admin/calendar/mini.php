<?php
//=======================================================
// 설 명 : 일정관리(index.php)
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
include_once("{$thisPath}/userfuntions.php");
include_once("{$thisPath}/function_lunartosol.php");// 음력,양력 변환 함수
$thisUrl			= "/Admin/calendar"; // 마지막 "/"이 빠져야함

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

// 넘어온 mode값 체크
$mode = $_GET['mode'] ?? 'month';

// 넘오온 date값 체크
$req_date = $_GET['date'] ?? date("Y-m-d");
if( !preg_match("/^[0-9]{4}-[01]?[0-9]-[0-3]?[0-9]$/", $req_date) ) {
	back("잘못된 날짜입니다");
}
$req_date = date("Y-m-d",strtotime($req_date));

// 각종 날짜변수 - 현재 날짜
$NowThisYear	= date("Y");
$NowThisMonth	= date("m");
$NowThisDay		= date("d");

// 각종 날짜 변수 - 넘오온 날짜
$intThisTimestamp	= strtotime($req_date);
$intThisYear	= date("Y",$intThisTimestamp);
$intThisMonth	= date("m",$intThisTimestamp);
$intThisDay		= date("d",$intThisTimestamp);
$intThisWeekday	= date("w",$intThisTimestamp);
$weekdays = ["일", "월", "화", "수", "목", "금", "토"];
$varThisWeekday = $weekdays[$intThisWeekday];

// 각종 날짜변수 - 이전달,다음달
$prevMonthDate = strtotime("-1 month", $intThisTimestamp);
$intPrevYear = date("Y", $prevMonthDate);
$intPrevMonth = date("m", $prevMonthDate);

$nextMonthDate = strtotime("+1 month", $intThisTimestamp);
$intNextYear = date("Y", $nextMonthDate);
$intNextMonth = date("m", $nextMonthDate);

// 각종 날짜변수 - 월말일
$intLastDay		= date('t', $intThisTimestamp);	//이번달
$intPrevLastDay = date('t', $prevMonthDate);	//지난달
$intNextLastDay = date('t', $nextMonthDate);	//다음달

// 각종 날짜변수 - 월 1일의 요일(숫자로)
$intFirstWeekday = date('w', strtotime("{$intThisYear}-{$intThisMonth}-01"));

// 각종 날짜 변수 - ex)2003년 9월 1일, 월요일 (음력 8월 5일)
$thisFullDate	= date("Y년 n월 j일",$intThisTimestamp) . " {$varThisWeekday}요일";
$sol2lun = sol2lun(date("Ymd",$intThisTimestamp));
$sol2lun_parts = explode("-", $sol2lun);
$thisFullDate	.= " (음력 {$sol2lun_parts['1']}월 {$sol2lun_parts['2']}일)";


// URL Link
$href['today']	= "{$thisUrl}/index.php?" . href_qs("mode=day&date=".date("Y-m-d"),$qs_basic);
$href['day']	= "{$thisUrl}/index.php?" . href_qs("mode=day&date=".$req_date,$qs_basic);
$href['week']	= "{$thisUrl}/index.php?" . href_qs("mode=week&date=".$req_date,$qs_basic);
$href['month']	= "{$thisUrl}/index.php?" . href_qs("mode=month&date=".$req_date,$qs_basic);

$outCal = [];
////////////////////////////
// 반복되지 않은 일정 구하기
// $outCal[YYYY-MM-DD]
$searchDateFrom = "{$intThisYear}-{$intThisMonth}-01";
$searchDateTo	= "{$intThisYear}-{$intThisMonth}-{$intLastDay}";

$sql = "SELECT * FROM {$table_calendar} WHERE {$sql_where_cal} AND `retimes`=0 ";
$sql .= "AND (`startdate`>='{$searchDateFrom}' AND `startdate`<='{$searchDateTo}') ";
$sql .= " AND (`dtype` = 'hour' OR `dtype` = 'day') ";
$sql .= " ORDER BY `startdate`, `starthour`";
$result	= db_query($sql);
while( $list=db_array($result) ) {
	$lhour = ($list['dtype'] == "day") ? "[ 하루 종일 ]" : "[{$list['starthour']}:{$list['startmin']}~{$list['endhour']}:{$list['endmin']}]";

	if(!privAuth($list,"priv_level")) {
		$list['title']	= "비공개 일정";
		$list['content']	= "비공개 일정";
		$href['view'] = "javascript:return false;";
	} else {
		$list['title'] = cut_string($list['title'], 12);
		$list['content'] = cut_string($list['content'], 150);
		$list['content'] = replace_string($list['content'], 'text');
		$href['view'] = "{$thisUrl}/index.php?".href_qs("mode=view&bmode={$mode}&uid={$list['uid']}",$qs_basic);
	}

	$js_title = addslashes(htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'));
	$js_lhour = addslashes(htmlspecialchars($lhour, ENT_QUOTES, 'UTF-8'));
	$js_content = addslashes(htmlspecialchars($list['content'], ENT_QUOTES, 'UTF-8'));
	$safe_href_view = htmlspecialchars($href['view'], ENT_QUOTES, 'UTF-8');

	$outCal[$list['startdate']] = ($outCal[$list['startdate']] ?? '') . "<img src='{$thisUrl}/images/micon.gif' border=0><font face=굴림><span style='font-size:9pt'><a href='{$safe_href_view}' onMouseOver=\"view('{$js_title}', '{$js_lhour}','{$js_content}');\" onMouseOut=\"noview();\">" . htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') . "</a></span></font><br> \n";
}
////////////////////////////

////////////////////////////
// 반복 일정 구하기
// $outCal['day']
$sql = "SELECT * FROM {$table_calendar} WHERE {$sql_where_cal} AND `retimes`>0 ";
$sql .= " AND (`startdate`<='{$searchDateTo}' AND `enddate` >='{$searchDateFrom}') ";
$sql .= " AND (`dtype` = 'hour' or `dtype` = 'day') ";
$sql .="	ORDER BY `starthour`";
$result	= db_query($sql);
while( $list=db_array($result) ) {
	// 반복되는 첫 $tmp_time 구함
	if(strcmp($list['startdate'],$searchDateFrom)<0) {
		$tmp_time = strtotime($searchDateFrom);
		switch($list['retype']) {
			case "day":
				$cday	= userDateDiff("d",$list['startdate'],$searchDateFrom)-1;
				if($cday % $list['retimes'] > 0)
					$tmp_time += ($list['retimes'] - $cday % $list['retimes']) * 86400;
				break;
			case "week":
				$cday	= userDateDiff("d",$list['startdate'],$searchDateFrom)-1;
				if($cday % ($list['retimes']*7) > 0)
					$tmp_time += ($list['retimes']*7 - $cday % ($list['retimes']*7)) * 86400;
				break;
			case "month":
				$tmp_time = strtotime(substr($searchDateFrom,0,8) . substr($list['startdate'],-2));
				break;
		}
	} else {
		$tmp_time = strtotime($list['startdate']);
	}

	$lhour = ($list['dtype'] == "day") ? "[ 하루 종일 ]" : "[{$list['starthour']}:{$list['startmin']}~{$list['endhour']}:{$list['endmin']}]";

	if(!privAuth($list,"priv_level")) {
		$list['title']	= "비공개 일정";
		$list['content']	= "비공개 일정";
		$href['view'] = "javascript:return false;";
	} else {
		$list['title'] = cut_string($list['title'], 12);
		$list['content'] = cut_string($list['content'], 150);
		$list['content'] = replace_string($list['content'], 'text');
		$href['view'] = "{$thisUrl}/index.php?".href_qs("mode=view&bmode={$mode}&uid={$list['uid']}",$qs_basic);
	}

	$js_title = addslashes(htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'));
	$js_lhour = addslashes(htmlspecialchars($lhour, ENT_QUOTES, 'UTF-8'));
	$js_content = addslashes(htmlspecialchars($list['content'], ENT_QUOTES, 'UTF-8'));
	$safe_href_view = htmlspecialchars($href['view'], ENT_QUOTES, 'UTF-8');

	$tmp_enddate = (strcmp($searchDateTo,$list['enddate'])<0) ? $searchDateTo : $list['enddate'];
	$tmp_time_enddate = strtotime($tmp_enddate);
	while($tmp_time<=$tmp_time_enddate) {
		$tmp = date("Y-m-d",$tmp_time);
		$outCal[$tmp] = ($outCal[$tmp] ?? '') . "<img src='{$thisUrl}/images/micon.gif' border=0><font face=굴림><span style='font-size:9pt'><a href='{$safe_href_view}' onMouseOver=\"view('{$js_title}', '{$js_lhour}','{$js_content}');\" onMouseOut=\"noview();\">" . htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') . "</a></span></font><br> \n";

		switch($list['retype']) {
			case "day":
				$tmp_time	+= $list['retimes'] * 86400;
				break;
			case "week":
				$tmp_time	+= $list['retimes'] * 7 * 86400;
				break;
			case "month":
				$current_day = date('d', $tmp_time);
				$tmp_time = strtotime("+{$list['retimes']} month", $tmp_time);
				// 월이 변경될 때 날짜가 유효하지 않으면 말일로 조정 (예: 1/31 -> 2/28)
				if (date('d', $tmp_time) != $current_day) {
					$tmp_time = strtotime(date('Y-m-t', strtotime("-1 month", $tmp_time)));
				}
				break;
		}
	}
}
////////////////////////////

// 쓰기 권한이 있는지 확인
$enable_write = privAuth($dbinfo, "priv_write");
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<table align="center" cellpadding="0" cellspacing="0" width="179">
	<tr>
		<td width="10" height="15" rowspan="2">
		<p>&nbsp;</p>
		</td>
		<td width="159" height="5">
		</td>
		<td width="10" height="15" rowspan="2">
		<p>&nbsp;</p>
		</td>
	</tr>
	<tr>
		<td width="159">
		<p align="center"><a href="<?php echo  htmlspecialchars($href['month'], ENT_QUOTES, 'UTF-8') ?>"><img src="<?php echo  htmlspecialchars($thisUrl, ENT_QUOTES, 'UTF-8') ?>/images/month_<?php echo  htmlspecialchars($intThisMonth, ENT_QUOTES, 'UTF-8') ?>.gif" width="156" height="57" border="0"></a></p>
		</td>
	</tr>
	<tr>
		<td width="10">
		<p>&nbsp;</p>
		</td>
		<td width="159">				
<table width="100%" cellspacing="0" cellpadding="0" height="100%" align="center">
	<tr valign="top">
		<td height="22" colspan="7">
			<IMG height=25 src="<?php echo  htmlspecialchars($thisUrl, ENT_QUOTES, 'UTF-8') ?>/images/month_title.gif" width="156">
		</td>
	</tr>
<?php
// for문 초기값 정의
$intPrintDay	= 1;
$Stop_Flag		= 0;
for($intNextWeek=1; $intNextWeek < 7 ; $intNextWeek++) {
	echo "<tr> \n";
	for($intNextDay=1; $intNextDay < 8 ; $intNextDay++) {
		echo "<td height='20' align='center'>";
		
		if ($intPrintDay==1 && $intNextDay < $intFirstWeekday + 1) {
			echo "<font face=굴림 size=2 color=white>.</font> \n";
		}
		else {
			if ($intPrintDay > $intLastDay ) {
				echo "<font face=굴림 size=2 color=white>.</font> \n";
			}
			else {
				$intcday = sprintf("%s-%s-%02d", $intThisYear, $intThisMonth, $intPrintDay);
				$href['goday'] = "{$thisUrl}/index.php?" . href_qs("mode=day&date={$intcday}",$qs_basic);
				$safe_href_goday = htmlspecialchars($href['goday'], ENT_QUOTES, 'UTF-8');

				if( $intThisYear == $NowThisYear && $intThisMonth == $NowThisMonth && $intPrintDay == $NowThisDay ) {
					echo "<b><a href='{$safe_href_goday}'><font face=굴림 size=2 color=darkorange>{$intPrintDay}◈</font></a></b> ";
				}
				elseif( ($intNextDay-1) % 7 == 0 ) { // 일요일
					echo "<b><a href='{$safe_href_goday}'><font face=굴림 size=2 color='#5167DF'>{$intPrintDay}</font></a></b>\n";
				}
				else{
					echo "<b><font face=굴림 size=2 color=black><a href='{$safe_href_goday}'>{$intPrintDay}</a></font></b>\n";
				}
			}
			$intPrintDay++;
			if ($intPrintDay > $intLastDay ) $Stop_Flag=1;
		}
		echo "</td>";
	}
	echo "</tr>";
	if ($Stop_Flag==1 )	break;
}
?>
</table>
		</td>
		<td width="10">
		<p>&nbsp;</p>
		</td>
	</tr>
	<tr>
		<td width="179" height="5" colspan="3">
		</td>
	</tr>
	<tr>
		<td width="179" height="1" colspan="3" background="img/hbg.gif">
		</td>
	</tr>
</table>
