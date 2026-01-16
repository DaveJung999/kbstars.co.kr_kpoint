<?php
//=======================================================
// 설	명 : 인트라넷 - 출근부(attendance.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/28
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/09/28 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
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
	$table = $SITE['th'] . "intranet_attendance";

	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo = db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

	// 출근여부
	$rs_tmp=db_query("SELECT * from {$table} where workday='".date("Ymd") . "' and bid={$_SESSION['seUid']}");
	if(!db_count($rs_tmp)) { // 오전 6시 이후에 출근부를 보고자할 경우 출근여부 확인함
		if(date("H")>6) back("출근시간에 출근 후에 조회바랍니다.","beginwork.php");
		else back("새벽 6시 이후 조회 바랍니다.");
	}

	// 넘어온값 필터링
	if(!$_GET['year'] or strlen($_GET['year'])!=4 or $_GET['year']<2000) $_GET['year']=date("Y");
	if((int)$_GET['month']<1 or (int)$_GET['month']>12) $_GET['month']=date("m");
	if(strlen($_GET['month'])==1) $_GET['month']="0".$_GET['month'];
	$workmonth=$_GET['year'] . $_GET['month'];

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 해당 월 데이터 변수에 일괄 저장
$rs_attend	= db_query("SELECT * from {$table} where bid={$_SESSION['seUid']} and workday>'{$workmonth}00' and workday<{$workmonth}00+100");
while($row=db_array($rs_attend)) {
	$total_worktime['dayhours']	+= $row['dayhours'];
	$total_worktime['overhours']	+= $row['overhours'];
	$total_worktime['nighthours']	+= $row['nighthours'];

	$data_attend[$row['workday']]=$row;
}
db_free($rs_attend);
$total_worktime['totalhours'] = $total_worktime['dayhours'] + $total_worktime['overhours'] + $total_worktime['nighthours'];
?>
<html>
<?php echo $pageinfo['html_header'];	 // 스타일시트
?>
<body bgcolor="<?php echo $pageinfo['right_bgcolor'];?>" background="<?php echo $pageinfo['right_background'];?>">
	
<table width="600" border=0 cellpadding='<?php echo $pageinfo['table_cellpadding'];?>' cellspacing='<?php echo $pageinfo['table_cellspacing'];?>' bgcolor='<?php echo $pageinfo['table_linecolor'];?>'>
	<tr> 
	<form method=get action="<?php echo $PHP_SELF;?>">
		<td align="center" bgcolor='<?php echo $pageinfo['table_titlecolor'];?>'><b> 
		<?php echo $SITE['company'];?>
		개인별 출근부 (직원명 : 
		<?php echo $_SESSION['seName'];?>
		) &nbsp; 
		<select name="year" id="year">
			<?php
				// 2002년부터 당해년도까지
				for($i=2002;$i<=date("Y");$i++) {
					if($_GET['year']) {
						if($_GET['year']==$i) echo "\n<option value={$i} selected>{$i}</option>";
						else echo "\n<option value={$i} selected>{$i}</option>";
					}
					elseif($i==date("Y")) echo "\n<option value={$i} selected>{$i}</option>";
					else echo "\n<option value={$i} selected>{$i}</option>";
				}
			
?>
		</select>
		년 
		<select name="month">
			<?php
				for($i=1;$i<=12;$i++) {
					if($_GET['month']) {
						if((int)$_GET['month']==$i) echo "\n<option value={$i} selected>{$i}</option>";
						else echo "\n<option value={$i}>{$i}</option>";
					}
					elseif($i==date("g")) echo "\n<option value={$i} selected>{$i}</option>";
					else echo "\n<option value={$i}>{$i}</option>";
				}
			
?>
		</select>
		<input type="submit" name="Submit" value="조회▶">
		</b></td>
	</form>
	</tr>
	<tr> 
	<td align="center" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'>총업무시간 <?php echo $total_worktime['totalhours'];?>시간 (정규 <?php echo $total_worktime['dayhours'];?>시간, 시간외 <?php echo $total_worktime['overhours'];?>시간, 야근 <?php echo $total_worktime['nighthours'];?>시간) </td>
	</tr>
	<tr> 
	<td bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> <table width="600" border=0 align="left" cellpadding='<?php echo $pageinfo['table_cellpadding'];?>' cellspacing='<?php echo $pageinfo['table_cellspacing'];?>' bgcolor='<?php echo $pageinfo['table_linecolor'];?>'>
		<tr bgcolor="<?php echo $pageinfo['table_thcolor'];?>"> 
			<td rowspan="2" align="center"><strong>날짜</strong></td>
			<td rowspan="2" align="center"><strong>구분</strong></td>
			<td rowspan="2" align="center"><strong>출근</strong></td>
			<td rowspan="2" align="center"><strong>퇴근</strong></td>
			<td colspan="3" align="center"><p><strong>업무시간</strong></p></td>
			<td width="200" rowspan="2" align="center"><strong>메모</strong></td>
		</tr>
		<tr align="center"	bgcolor="<?php echo $pageinfo['table_thcolor'];?>"> 
			<td width=30>정규</td>
			<td width=30><font size=1>시간외</font></td>
			<td width=30>야근</td>
		</tr>
<?php
if($workmonth == date("Y") . date("m")) $lastday=date("j"); // 1-31
else $lastday=date("j",mktime (0,0,0,$_GET['month']+1,0,$_GET['year']));
for($i=1;$i<=$lastday;$i++) {
	if(strlen($i)==1) $tmp_day="0".$i;
	else $tmp_day=$i;
?>
		<tr align="center" > 
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'	align="right"	width=60> 
<?php 

					switch( date("w",mktime(0,0,0,$_GET['month'],$i,$_GET['year'])) ) {
						case 0 :
							$tmp = "<font color=red>" . $i . "일 (" . "일" . ")</font>";
							break;
						case 1 :
							$tmp = $i . "일 (" . "월" . ")";
							break;		 
						case 2 :		 
							$tmp = $i . "일 (" . "화" . ")";
							break;		 
						case 3 :		 
							$tmp = $i . "일 (" . "수" . ")";
							break;		 
						case 4 :		 
							$tmp = $i . "일 (" . "목" . ")";
							break;		 
						case 5 :		 
							$tmp = $i . "일 (" . "금" . ")";
							break;		 
						case 6 :		 
							$tmp = "<font color=blue>" . $i . "일 (" . "토" . ")</font>";
							break;		 
					} // end switch

					echo $tmp;
					unset($tmp);
				
?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>' width=40> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][type];?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>' width=50> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][begintime] ? date("d H:i",$data_attend["{$workmonth}{$tmp_day}"][begintime]) : "-";?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>' width=50> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][finishtime] ? date("d H:i",$data_attend["{$workmonth}{$tmp_day}"][finishtime]) : "-";?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][dayhours];?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][overhours];?>
			</td>
			<td nowrap bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][nighthours];?>
			</td>
			<td width="200" align="left" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 
			<?php echo $data_attend["{$workmonth}{$tmp_day}"][memo];?>
			</td>
			<?php
} // end for
?>
		</tr>
		</table></td>
	</tr>
	<tr> 
	<td bgcolor='<?php echo $pageinfo['table_tdcolor'];?>' align="center"><input type="button"	value="퇴근함니다." onClick="javascript: window.location='./finishwork.php'"></td>
	</tr>
</table>
</body>
</html>
