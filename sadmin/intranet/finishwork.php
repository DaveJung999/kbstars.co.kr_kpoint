<?php
//=======================================================
// 설	명 : 인트라넷 - 출근(finishwork.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/02
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/02 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
	'useApp'	 => 1, // remote_addr()
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
	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo = db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");


	$table = $SITE['th'] . "intranet_attendance";

	// 출근 여부 확인
	$rs_attend=db_query("SELECT * from {$table} where workday='".date("Ymd") . "' and bid={$_SESSION['seUid']}");
	if(!db_count($rs_attend)) {
		back("아직 출근하지 않았습니다. 출근부터 하여주시기 바랍니다.","beginwork.php");
	}
	$list_attend=db_array($rs_attend);
	if($list_attend['type']!="출근" and $list_attend['type']!="퇴근") back("{$list_attend['type']} 중이시군요!\\n 퇴근 체크하실 필요 없습니다.","list.php");
	
	// 최대 신청 가능 업무시간 구함
	$maxworkhours= (int)( ( time()-$list_attend['begintime'] ) / 3600 + 1);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
if($mode=="finishwork") {
	$qs=array(
				"dayhours"	 =>	"post,trim",
				"overhours"	 =>	"post,trim",
				"nighthours" =>	"post,trim",
				"memo"		 =>	"post,trim"
		);
	$qs=check_value($qs);
	if($maxworkhours < $qs['dayhours']+$qs['overhours']+$qs['nighthours']) back("^_^;;\\n출근후 지난시간({$maxworkhours}시간)보다 더 적으셨습니다.");

	$qs['ip']			= remote_addr();
	$qs['finishtime'] = strtotime($_POST['finishtime']);
	
	
	$sql="UPDATE
				`$table` 
			SET
				`type`			='퇴근',
				`finishtime`	=UNIX_TIMESTAMP() ,
				`finishtimeip`	={$qs['ip']} ,					 
				`dayhours`		={$qs['dayhours']} , 
				`overhours`		={$qs['overhours']} , 
				`nighthours`	={$qs['nighthours']} , 
				`memo`			={$qs['memo']}
			WHERE
				uid	= {$list_attend['uid']}
			AND
				bid = '{$_SESSION['seUid']}'
		";
	db_query($sql);

	go_url("attendance.php"); // 출근부 보기
	exit;
} // end if($mode)
?>
<html>
<?php echo $pageinfo['html_header'];?>
<body bgcolor="<?php echo $pageinfo['right_bgcolor'];?>" background="<?php echo $pageinfo['right_background'];?>">
<form method=post action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="mode"	value="finishwork" readonly>
<table width="500" border=0 cellpadding='<?php echo $pageinfo['table_cellpadding'];?>' cellspacing='<?php echo $pageinfo['table_cellspacing'];?>' bgcolor='<?php echo $pageinfo['table_linecolor'];?>'>
	<tr> 
		<td bgcolor='<?php echo $pageinfo['table_titlecolor'];?>'><b> 
		<?php echo $SITE['company'];?> 퇴근하기 (직원명: <?php echo $_SESSION['seName'];?>)</b></td>
	</tr>
	<tr> 
		<td bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'>
			<table width="400" border=0 align="center" cellpadding='<?php echo $pageinfo['table_cellpadding'];?>' cellspacing='<?php echo $pageinfo['table_cellspacing'];?>' bgcolor='<?php echo $pageinfo['table_linecolor'];?>'>
			<tr> 
			<td width="85" bgcolor='<?php echo $pageinfo['table_thcolor'];?>'><b>&nbsp;출근시간</b></td>
			<td colspan="3" align="center" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'><strong> 
				<?php echo date("Y-m-d H시 i분",$list_attend['begintime']);?>
				</strong></td>
			</tr>
			<tr> 
			<td bgcolor='<?php echo $pageinfo['table_thcolor'];?>'><b>&nbsp;퇴근시간</b></td>
			<td colspan="3" align="center" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'><b> 
				<?php echo date("Y-m-d H시 i분");?>
				</b></td>
			</tr>
			<tr> 
			<td bgcolor='<?php echo $pageinfo['table_thcolor'];?>'><strong>&nbsp;업무시간</strong></td>
			<td width="58" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 정규<br>
				시간외 <br>
				야근</td>
			<td width="107" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 
				<input name="dayhours" id="dayhours" 
					onChange="javascript: var	i=eval(this.form.dayhours.value)+eval(this.form.overhours.value)+eval(this.form.nighthours.value); if(i><?php echo $maxworkhours;?>){alert('^_^;;\n출근후 지난시간(<?php echo $maxworkhours;?>시간)보다 더 적으셨습니다.'); this.value=0;}" value=<?php echo $maxworkhours;?> size=3>시간<br>
					<input size=3 name="overhours" id="overhours" 
					onChange="javascript: var	i=eval(this.form.dayhours.value)+eval(this.form.overhours.value)+eval(this.form.nighthours.value); if(i><?php echo $maxworkhours;?>){alert('^_^;;\n출근후 지난시간(<?php echo $maxworkhours;?>시간)보다 더 적으셨습니다.'); this.value=0;}">시간<br>
					<input size=3 name="nighthours" id="nighthours" 
					onChange="javascript: var	i=eval(this.form.dayhours.value)+eval(this.form.overhours.value)+eval(this.form.nighthours.value); if(i><?php echo $maxworkhours;?>){alert('^_^;;\n출근후 지난시간(<?php echo $maxworkhours;?>시간)보다 더 적으셨습니다.'); this.value=0;}">시간</td>
			<td width="150" align="center" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'> 최대 <?php echo $maxworkhours;?>시간</td>
			</tr>
			<tr> 
			<td colspan="4" align="center" bgcolor='<?php echo $pageinfo['table_thcolor'];?>'><div align="left"><strong>&nbsp;메모</strong></div></td>
			</tr>
			<tr> 
			<td colspan="4" bgcolor='<?php echo $pageinfo['table_tdcolor'];?>'><textarea name="memo" cols="50" rows="6" id="memo"><?php echo $list_attend['memo'];?></textarea></td>
			</tr>
		</table>
		</td>
	</tr>
	<tr> 
		<td bgcolor='<?php echo $pageinfo['table_tdcolor'];?>' align="center">
		<input name="submit" type="submit" id="submit" value="퇴근합니다." 
				onMouseOver="javascript: var	i=eval(this.form.dayhours.value)+eval(this.form.overhours.value)+eval(this.form.nighthours.value); if(i><?php echo $maxworkhours;?>){alert('^_^;;\n출근후 지난시간(<?php echo $maxworkhours;?>시간)보다 더 적으셨습니다.'); return false;}"></td>
	</tr>
	</table>
</form>
</body>
</html>