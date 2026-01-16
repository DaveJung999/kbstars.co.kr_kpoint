<?php
//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin'=> 1, // 템플릿 사용
	'useBoard'=>1, // 보드관련 함수 포함
	'useApp'	=>1,
	'html_echo'	=>0	 // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

?>

<html>
<head></head>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 5px;
	margin-top: 15px;
	margin-right: 5px;
	margin-bottom: 5px;
	background-color:F8F8EA;
}
-->
</style>

<body>
<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
					<td background="/images/admin/tbox_bg.gif"><strong><?php echo $name?></strong>님의 포인트 계좌 내역</td>
					<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
				</tr>
				</table>
		</td>		
	</tr>
	<tr>
		<td>
			<br>
			<table width="100%" border="0" cellpadding="3" cellspacing="1" bordercolor="#F8F8EA" bgcolor="#999999">
				<tr align="center" bgcolor="#CECFCE">
					<td width="62%" height="26">공급사</td>
					<td>적립금</td>
				</tr>
		
<?php
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
//$tpl = new phemplate("stpl/basic/","remove_nonjs");
//$tpl->set_file('html',"point.htm",1); // here 1 mean extract blocks
//방금위의 $_GET['skin']값이 들어간 이유는 박선민(sponsor@new21.com)에게 물어보기바람

//공급사별 적립금 잔액 보여주기
$rs_list = db_query("SELECT account_supplier as sup, sum(balance) as bal, sup_bid as supid 
					FROM new21_accountinfo where bid=$bid
					GROUP BY account_supplier ");
$total = db_count();

if($total>0)	{
	for($i=0 ; $i<$total ; $i++){
		$list = db_array($rs_list);
		$list['bal'] = number_format($list['bal']);
?>
				<tr bgcolor="#F8F8EA">
					<td height="25" align="center">&nbsp;<a href="javascript:location.href('point_detail.php?uid=<?php echo $bid?>&name=<?php echo $name?>&supid=<?php echo $list['supid']?>&sup=<?php echo $list['sup']?>');"><?php echo $list['sup']?></a></td>
					<td align="center">&nbsp;<?php echo $list['bal']?></td>
				</tr>
			
<?php
 } 
}else{
?>				<tr><td height="25" colspan="2" align="center" bgcolor="#F8F8EA">포인트 거래내역이 없습니다.</td>
</tr>
<?php
 } 
?>
			</table>
			<br>
			<table width="100%" border="0" cellpadding="3" cellspacing="1" bordercolor="#F8F8EA" bgcolor="#999999">
				<tr align="center" bgcolor="#CECFCE">
					<td height="26" width="5%">No.</td>
					<td width="19%">날짜</td>
					<td width="19%">적립액</td>
					<td width="19%">사용액</td>
					<td width="19%">잔액</td>
					<td width="19%">비고(결제액)</td>
					</tr>
<?php
	$sup = $_GET['sup'];
	$supid = $_GET['supid'];
	echo "&nbsp;- <b>".$sup."</b> 포인트 거래 내역";
	$sql_de = "select rdate, deposit, withdrawal, balance, pay_price
				from new21_account 
				where bid = bid and bid=$bid ";
	if($supid) $sql_de .= " and sup_bid={$supid}	";
	$sql_de .= " order by rdate desc ";
	
	$rs_de = db_query($sql_de);
	$cnt = db_count();
	if($cnt>0){		
		for($k=0 ; $k<$cnt ; $k++){
			$list_de = db_array($rs_de);		
			$list_de['deposit'] = number_format($list_de['deposit']);
			$list_de['withdrawal'] = number_format($list_de['withdrawal']);
			$list_de['balance'] = number_format($list_de['balance']);
			$list_de['pay_price'] = number_format($list_de['pay_price']);	
			
?>				<tr bgcolor="#F8F8EA" align="center" onMouseOver="this.style.backgroundColor='#F5F5F5'" onMouseOut="this.style.backgroundColor=''">
					<td height="26"><?php echo $cnt-$k?></td>
					<td><?php echo date('Y-m-d H:m:s', $list_de['rdate'])?></td>
					<td><?php echo $list_de['deposit']?></td>
					<td><?php echo $list_de['withdrawal']?></td>
					<td><?php echo $list_de['balance']?></td>
					<td><?php echo $list_de['pay_price']?></td>
					</tr>	
<?php
 		} 
	}else{
?>				
				<tr><td height="26" colspan="6" align="center" bgcolor="#F8F8EA">&nbsp;</td>
				</tr>
<?php
 } 
?>				
			</table>
		</td>
	</tr>
</table>
</body>
</html>		