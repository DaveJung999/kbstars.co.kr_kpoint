<?php
//=======================================================
// 설	명 : 인클루드 파일 - inc_month.php
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/10
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/10/10 박선민 마지막 수정
//=======================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 인쿨루드인 경우에만 허용
	if (realpath($_SERVER["PATH_TRANSLATED"]) == realpath(__FILE__)){
		echo "직접 호출되어서 거부함";
		exit;
	}

	////////////////////////////
	// 반복되지 않은 일정 구하기
	// $outCal[YYYY-MM-DD]
	$searchDateFrom = "{$intThisYear}-{$intThisMonth}-01";
	$searchDateTo	= "{$intThisYear}-{$intThisMonth}-{$intLastDay}";

	$sql = "SELECT * from {$table_calendar} WHERE {$sql_where_cal} AND retimes=0 ";
	$sql .= "AND (startdate>='{$searchDateFrom}' AND startdate<='{$searchDateTo}') ";
	$sql .= " AND (dtype = 'hour' OR dtype = 'day') ";
	$sql .= " ORDER BY startdate, starthour";
	$rs	= db_query($sql);
	while( $list=db_array($rs) ){
		if($list['dtype'] == "day" )
			$lhour= "[ 하루 종일 ]";
		else{
			$list['starthour'] = str_pad($list['starthour'],2,"0",STR_PAD_LEFT); 
			$list['startmin'] = str_pad($list['startmin'],2,"0",STR_PAD_LEFT); 
			$list['endhour'] = str_pad($list['endhour'],2,"0",STR_PAD_LEFT); 
			$list['endmin'] = str_pad($list['endmin'],2,"0",STR_PAD_LEFT); 

			$lhour="[{$list['starthour']}:{$list['startmin']}~{$list['endhour']}:{$list['endmin']}]";
		}

		// 권한체크
		if(!privAuth($list,"priv_level")){
			$list['title']	= "비공개 일정";
			$list['content']	= "비공개 일정";

			// URL Link
			$href['view'] = "javascript: return false;";
		} else {
			$list['cut_title'] = cut_string($list['title'], 9);
			$list['cut_title'] = htmlspecialchars($list['cut_title'],ENT_QUOTES);
			$list['title'] = htmlspecialchars($list['title'],ENT_QUOTES);
			$list['content'] = cut_string($list['content'], 150);
			$list['content'] = htmlspecialchars($list['content'],ENT_QUOTES);
			$list['content'] = replace_string($list['content'], 'text');	// 문서 형식에 맞추어서 내용 변경

			// URL Link
			$href['view'] = "./index.php?".href_qs("mode=view&bmode={$_GET['mode']}&uid={$list['uid']}",$qs_basic);

		} // end if. . else
		
		// 일정 구분 아이콘
		switch ($list['kind']){
			Case "훈련":		$kind_icon = "<img src='/images/icon_training.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "전지훈련":	$kind_icon = "<img src='/images/icon_ct.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "경기":		$kind_icon = "<img src='/images/icon_game.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "연습경기":	$kind_icon = "<img src='/images/icon_pg.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "생일":		$kind_icon = "<img src='/images/icon_birth.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "휴식":		$kind_icon = "<img src='/images/icon_rest.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "휴가":		$kind_icon = "<img src='/images/icon_vac.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
		}

		$outCal[$list['startdate']] .= "$kind_icon <a href='{$href['view']}' onMouseOver=\"view('{$list['title']}', '{$lhour}','{$list['content']}');\" onMouseOut=\"noview();\"><span style='font-size:8pt'>{$list['cut_title']}</span></a><br> \n"	;
	} // end while
	////////////////////////////

	////////////////////////////
	// 반복 일정 구하기
	// $outCal['day']
	$sql = "SELECT * from {$table_calendar} WHERE {$sql_where_cal} AND retimes>0 ";
	$sql .= " AND (startdate<='{$searchDateTo}' AND enddate >='{$searchDateFrom}') ";
	$sql .= " AND (dtype = 'hour' or dtype = 'day') ";
	$sql .="	ORDER BY starthour";
	$rs	= db_query($sql);
	while( $list=db_array($rs) ){
		// 반복되는 첫 $tmp_time 구함
		if(strcmp($list['startdate'],$searchDateFrom)<0){
			$tmp_time = strtotime($searchDateFrom);
			
			switch($list['retype']){
				case "day"://일일단위 반복설정
					// - 레코드 저장일과 출력셀의 날짜와의 날짜차이
					$cday	= userDateDiff("d",$list['startdate'],$searchDateFrom)-1;
					
					if($cday%$list['retimes']>0)
						$tmp_time += ($list['retimes']-$cday%$list['retimes']) * 86400;
					break;
				case "week"://주단위 반복설정
					// - 레코드 저장일과 출력셀의 날짜와의 날짜차이
					$cday	= userDateDiff("d",$list['startdate'],$searchDateFrom)-1;

					// 주단위기에 retimes에서 7을 곱함
					if($cday%($list['retimes']*7)>0)
						$tmp_time += ($list['retimes']*7-$cday%($list['retimes']*7)) * 86400;
					break;
				case "month"://월단위 반복설정
					// 월단위기에 startdate의 일(Day)임
					$tmp_time = strtotime(substr($searchDateFrom,0,8).substr($list['startdate'],-2));
					break;
				case "year"://년단위 반복설정
					// 년단위기에 startdate의 일(Day)임
					$tmp_time = strtotime(substr($searchDateFrom,0,5).substr($list['startdate'],-5));
					break;
			} // end switch
		} else {
			// 기간안에 startdate가 있기에 그것이 첫날임
			$tmp_time = strtotime($list['startdate']);
		}

		if($list['dtype'] == "day" )
			$lhour= "[ 하루 종일 ]";
		else{
			$list['starthour'] = str_pad($list['starthour'],2,"0",STR_PAD_LEFT); 
			$list['startmin'] = str_pad($list['startmin'],2,"0",STR_PAD_LEFT); 
			$list['endhour'] = str_pad($list['endhour'],2,"0",STR_PAD_LEFT); 
			$list['endmin'] = str_pad($list['endmin'],2,"0",STR_PAD_LEFT); 

			$lhour="[{$list['starthour']}:{$list['startmin']}~{$list['endhour']}:{$list['endmin']}]";
		}

		// 권한체크
		// 권한체크
		if(!privAuth($list,"priv_level")){
			$list['title']	= "비공개 일정";
			$list['content']	= "비공개 일정";

			// URL Link
			$href['view'] = "javascript: return false;";
		} else {
			$list['cut_title'] = cut_string($list['title'], 8);
			$list['cut_title'] = htmlspecialchars($list['cut_title'],ENT_QUOTES);
			$list['title'] = htmlspecialchars($list['title'],ENT_QUOTES);
			$list['content'] = cut_string($list['content'], 150);
			$list['content'] = htmlspecialchars($list['content'],ENT_QUOTES);
			$list['content'] = replace_string($list['content'], 'text');	// 문서 형식에 맞추어서 내용 변경

			// URL Link
			$href['view'] = "./index.php?".href_qs("mode=view&bmode={$_GET['mode']}&uid={$list['uid']}",$qs_basic);

		} // end if. . else

		
		// 일정 구분 아이콘
		switch ($list['kind']){
			Case "훈련":		$kind_icon = "<img src='/images/icon_training.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "전지훈련":	$kind_icon = "<img src='/images/icon_ct.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "경기":		$kind_icon = "<img src='/images/icon_game.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "연습경기":	$kind_icon = "<img src='/images/icon_pg.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "생일":		$kind_icon = "<img src='/images/icon_birth.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "휴식":		$kind_icon = "<img src='/images/icon_rest.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
			Case "휴가":		$kind_icon = "<img src='/images/icon_vac.gif' 'width='15' height='20' border='1' align='absmiddle'>"; break;
		}

		// 일정 변수에 저장
		$tmp_enddate = (strcmp($searchDateTo,$list['enddate'])<0) ? $searchDateTo : $list['enddate'];
		$tmp_time_enddate = strtotime($tmp_enddate);
		while($tmp_time<=$tmp_time_enddate) {// 말일을 지나기 전까지
			$tmp = date("Y-m-d",$tmp_time);
			$outCal[$tmp] .= "$kind_icon <font ><span style='font-size:9pt'><a href='{$href['view']}' onMouseOver=\"view('{$list['title']}', '{$lhour}','{$list['content']}');\" onMouseOut=\"noview();\">{$list['cut_title']}</a></span></font><br> \n"	;

			switch($list['retype']){
				case "day":
					$tmp_time	+= $list['retimes'] * 86400;
					break;
				case "week":
					$tmp_time	+= $list['retimes'] * 7*86400;
					break;
				case "month": 
					$tmp_time	+= $list['retimes'] * 30*86400;
					break;
				case "year": 
					$tmp_time	+= $list['retimes'] * 365*86400;
					break;
			} // end switch
		} // end while
	} // end while
	////////////////////////////
	// 쓰기 권한이 있는지 확인
	if(privAuth($dbinfo, "priv_write"))	$enable_write = true;
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
?>
<div ID="overDiv" STYLE="position:absolute;top=50;substr=100; visibility:hide; z-index:2;"></div>
<script LANGUAGE="JavaScript" src="cal_div.js" type="Text/JavaScript"></script>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<div align="center">
			<table border="0" width="100%" cellspacing="1" cellpadding="5" bordercolor="#F8F8EA" bgcolor="#cccccc">
				<tr >
					<td height=30 width="14%" bgcolor="#8DCEDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">일(日)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">월(月)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">화(火)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">수(水)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">목(木)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">금(金)</span></font></span></td>
					<td width="14%" bgcolor="#8D9FDC" align="center"><span class="style1"><font face="굴림"><span
					style="font-size: 9pt">토(土)</span></font></span></td>
			</tr>
<?php
// for문 초기값 정의
$intPrintDay	= 1;	//출력 초기일 값은 1부터
$Stop_Flag		= 0;
for($intNextWeek=1; $intNextWeek < 7 ; $intNextWeek++) {	//주단위 루프 시작, 최대 6주 
	echo "<tr> \n";
	for($intNextDay=1; $intNextDay < 8	; $intNextDay++) {	//요일단위 루프 시작, 일요일부터
		if( $intThisYear-$NowThisYear == 0 and $intThisMonth-$NowThisMonth == 0 and $intPrintDay-$NowThisDay == 0 ) 
			echo "<td bgcolor='#FFD6AC' height=80 valign=top align=left> ";
		else
			echo "<td bgcolor='#F8F8EA' height=80 valign=top align=left> ";
		
		if ($intPrintDay == 1 and $intNextDay<$intFirstWeekday+1) { //첫주시작일이 1보다 크면
			echo "<font size=2 color=#F8F8EA>.</font> \n";
			//$intFirstWeekday=$intFirstWeekday-1;
		} else {	//
			if ($intPrintDay > $intLastDay ) { //입력날짜가 월말보다 크다면
				echo "<font size=2 color=#F8F8EA>.</font> \n";
			} else { //입력날짜가 현재월에 해당되면
				$intcday=$intThisYear."-".$intThisMonth."-" . (($intPrintDay<10)?"0":"").$intPrintDay;

				// URL Link
				$href['goinput']	= "./index.php?" .	href_qs("mode=input&date={$intcday}",$qs_basic);
				$href['goday']	= "./index.php?" . href_qs("mode=day&date={$intcday}",$qs_basic);

				if( $intThisYear-$NowThisYear == 0 and $intThisMonth-$NowThisMonth == 0 and $intPrintDay-$NowThisDay == 0 ){
					//오늘 날짜이면은 글씨폰트를 다르게
					if($enable_write or $outCal[$intcday]) 
						echo "<b><a href='{$href['goday']}'><font size=2 color=darkorange>{$intPrintDay}◈</font></a></b> ";
					else 
						echo "<b><font size=2 color=darkorange>{$intPrintDay}◈</font></b> <br>\n";
					
				}
				elseif( $intNextDay == 1 ) { 
					//일요일이면 빨간 색으로
					if($enable_write or $outCal[$intcday]) 
						echo "<b><a href='{$href['goday']}'><font size=2 color=D23036>{$intPrintDay}</font></a></b>\n";
					else 
						echo "<b><font size=2 color=D23036>{$intPrintDay}</font></b>\n";
				}
				elseif( $intNextDay == 7 ) { 
					//토요일면 파란 색으로
					if($enable_write or $outCal[$intcday]) 
						echo "<b><a href='{$href['goday']}'><font size=2 color=4B73FA>{$intPrintDay}</font></a></b>\n";
					else 
						echo "<b><font size=2 color=4B73FA>{$intPrintDay}</font></b>\n";
				}
				else{	
					// 그외의 경우
					if($enable_write or $outCal[$intcday]) 
						echo "<b><font size=2><a href='{$href['goday']}'>{$intPrintDay}</a></font></b>\n";
					else 
						echo "<b><font size=2>{$intPrintDay}</font></b>\n";
				}

				// 일정 추가가 가능하면
				if($enable_write) 
					echo "<a href='{$href['goinput']}'><img src=images/add_sq.gif border=0></a><br>\n";
				else 
					echo "<br>";
				
				// 일정 내용 출력
				if($outCal[$intcday]) echo $outCal[$intcday];
				else echo "<span style='font-size:9pt'>&nbsp;</span> \n";
			} // end if. . else

			$intPrintDay	+= 1;	//날짜값을 1 증가
			if ($intPrintDay>$intLastDay )	//만약 날짜값이 월말값보다 크면 루프문 탈출
				$Stop_Flag=1;
		} // end if. . else
		echo "</td>";
	} // end for intNextDay
	echo "</tr>";
	if ($Stop_Flag == 1 )	break;
} // end for intNextWeek
?>
</table>
</td>
</tr>
</table>
<br />
<p align=right><span style='font-size:9pt'>
<?php
if ($intThisDay >= $intPrevLastDay )
	$intPrevDay=$intPrevLastDay;
else
	$intPrevDay=$intThisDay;

if ($intThisDay >= $intNextLastDay )
	$intNextDay=$intNextLastDay;
else
	$intNextDay=$intThisDay;

// URL Link
$href['PrevYear'] = "{$_SERVER['PHP_SELF']}?" . href_qs("mode=month&date={$intPrevYear}-{$intPrevMonth}-{$intPrevDay}",$qs_basic);
$href['NextYear'] = "{$_SERVER['PHP_SELF']}?" . href_qs("mode=month&date={$intNextYear}-{$intNextMonth}-{$intNextDay}",$qs_basic);

echo "<a href='{$href['PrevYear']}'>◀ [{$intPrevMonth}월 {$intPrevDay}일] </a>&nbsp;&nbsp;";
echo "<a href='{$href['NextYear']}'> [{$intNextMonth}월 {$intNextDay}일] ▶ </a>"; ?></span></p>
