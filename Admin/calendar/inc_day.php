<?php
//=======================================================
// 설	명 : 인클루드 파일 - cal_day.php
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/08
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/08 박선민 마지막 수정
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
	$searchDateFrom = "{$intThisYear}-{$intThisMonth}-{$intThisDay}";
	$searchDateTo	= "{$intThisYear}-{$intThisMonth}-{$intThisDay}";

	$sql = "SELECT * from {$table_calendar} WHERE {$sql_where_cal} AND retimes=0 ";
	$sql .= "AND (startdate>='{$searchDateFrom}' AND startdate<='{$searchDateTo}') ";
	$sql .= " AND (dtype = 'hour' OR dtype = 'day') ";
	$sql .= " ORDER BY startdate, starthour";
	$rs	= db_query($sql);
	while( $list=db_array($rs) ){
		$tmp_hour	= intval($list['starthour']);

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
			//$list['title'] = cut_string($list['title'], 12);
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

		$outCal[$list['startdate']][$tmp_hour] .= "$kind_icon <font face=굴림><span style='font-size:9pt'><a href='{$href['view']}' onMouseOver=\"view('{$list['title']}', '{$lhour}','{$list['content']}');\" onMouseOut=\"noview();\">{$list['title']}</a></span><br> \n"	;
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
		$tmp_hour	= intval($list['starthour']);

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
			//$list['title'] = cut_string($list['title'], 12);
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
			$outCal[$tmp][$tmp_hour] .= "$kind_icon <font face=굴림><span style='font-size:9pt'><a href='{$href['view']}' onMouseOver=\"view('{$list['title']}', '{$lhour}','{$list['content']}');\" onMouseOut=\"noview();\">{$list['title']}</a></span><br> \n"	;

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
<style type="text/css">
<!--
.style1 {font-size: 9pt}
-->
</style>
<div ID="overDiv" STYLE="position:absolute;top=50;substr=100; visibility:hide; z-index:2;"></div>
<script LANGUAGE="JavaScript" src="cal_div.js" type="Text/JavaScript"></script>
<div align="center">

<table border='0' width='100%' cellspacing='1' cellpadding='2' bgcolor='cccccc'>
	<tr >
		<td height="30" align="center" bgcolor="#D2BF7E" colspan="2"><font face="굴림"><span class="style1">일별 일정</span></font></td>
	</tr>
<?php
//'8:00 부터 22:00까지 1시간 단위로 일정을 보여주기 위해 루프
for	($i=7 ;$i <= 20; $i++){
	// '짝수일때 테이블 행단위로 색깔 부여
	if(($i%2) == 0 ){
		echo "<tr> \n"; 
		echo "	<td align=center width=120 height=30 bgcolor=#F0EBD6> \n";
	} else {
		echo "<tr> \n";	
		echo "	<td align=center width=120 height=30 bgcolor=f1f1f1> \n"	;
	}

	// URL Link
	$href['goinput']	= "./index.php?" . href_qs("mode=input&date={$_GET['date']}&starthour={$i}",$qs_basic);

	if($enable_write) 
		echo "<span style='font-size:9pt'>{$i} : 00 </span>&nbsp;<a href='{$href['goinput']}'><img src=images/add_sq.gif border=0></a>\n";
	else 
		echo "<span style='font-size:9pt'>{$i} : 00 </span>\n";
	echo "	</td> \n";
	echo "	<td align=left	bgcolor=F8F8EA> \n";

	if ($i == 7 ) { //'8:00 이전이면 0시부터 9시 전까지의 일정을
		$isData = false;
		for($j=0;$j<=7;$j++){
			// 일정 내용 출력
			if($outCal[$_GET['date']][$j]){
				$isData = true;
				echo $outCal[$_GET['date']][$j]; 
			}
		}
		if($isData == false)
			echo "<span style='font-size:9pt'>&nbsp;</span> \n";
	} elseif ($i == 20 ) { //'20:00 이후이면 20:00 부터의 일정을
		$isData = false;
		for($j=20;$j<=24;$j++){
			// 일정 내용 출력
			if($outCal[$_GET['date']][$j]){
				$isData = true;
				echo $outCal[$_GET['date']][$j]; 
			}
		}
		if($isData == false)
			echo "<span style='font-size:9pt'>&nbsp;</span> \n";
	} else {//'그외의 경우는 1시간 단위로
		// 일정 내용 출력
		if($outCal[$_GET['date']][$i]) echo $outCal[$_GET['date']][$i]; 
		else echo "<span style='font-size:9pt'>&nbsp;</span> \n";
	}
	echo "	</td> \n";
	echo"</tr> \n";
} 

?>
</table>
</div>
<br />
<p align=right><span style='font-size:9pt'>
<?php
$intPrevDate = date("Y-m-d",strtotime($_GET['date'])-3600*24);
$intNextDate = date("Y-m-d",strtotime($_GET['date'])+3600*24);

// URL Link
$href['intPrevDate'] = "{$_SERVER['PHP_SELF']}?" . href_qs("mode=day&date={$intPrevDate}",$qs_basic);
$href['intNextDate'] = "{$_SERVER['PHP_SELF']}?" . href_qs("mode=day&date={$intNextDate}",$qs_basic);

	echo "<a href='{$href['intPrevDate']}'>";	
	echo "◀ [{$intPrevDate}] </a>&nbsp;&nbsp;";
	echo "<a href='{$href['intNextDate']}'>"	;
	echo "	[{$intNextDate}] ▶ </a>"; 
?>
</span></p>

