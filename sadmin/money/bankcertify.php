<?php
//=======================================================
// 설 명 : 관리자페이지 - 무통장입금처러(money/bankinput.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/14
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/14 박선민 마지막 수정
// 25/08/13 Gemini	PHP7 및 mariadb 11 버전 업그레이드 대응
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환
//=======================================================	
$HEADER=array(
	'auth'		 => 10, // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'useCheck'	 => 1, // check_value()
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
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
	// 관리자페이지 환경파일 읽어드림
	$sql = "select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC";
	$pageinfo	= db_arrayone($sql) or back("관리자페이지 환경파일을 읽을 수가 없습니다");

	// table
	$table_logon	= $SITE['th'] . "logon";
	$table_userinfo	= $SITE['th'] . "userinfo";
	$table_payment	= $SITE['th'] . "payment";


	if(!isset($_POST['mode']) || $_POST['mode'] != "입금처리") back("잘못된 요청입니다");
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
	$qs=array(
			"userid"	 =>	"post,trim",
			"name"		 =>	"post,trim",
			"hp"		 =>	"post,trim",
			"receiptor"	 =>	"post,trim,notnull=" . urlencode("입금인(없으면 '없음'등)을 입력하시기 바랍니다"),
			"price"		 =>	"post,trim,checkNumber=" . urlencode("금액을 숫자로만 입력하시기 바랍니다"),
			"comment"	 =>	"post,trim",
			"bank"		 =>	"post,trim,notnull=" . urlencode("입금방법을 입력하시기 바랍니다"),
			"inputdate"	 =>	"post,trim,notnull=" . urlencode("입금날자를 입력하시기 바랍니다"),
			"mode"		 =>	"post,trim"
		);
	$qs=check_value($qs);

	if($qs['price'] < 1) back("금액을 1원 이상으로 입력하시기 바랍니다");

	$qs['idate']	= strtotime($qs['inputdate']);
	if($qs['idate'] < strtotime("2003-1-1")) back("입금날자가 잘못되었습니다"); // 11월 13일 이후에 개발되었는데? 그 이전이라고?

	// sql문 만들기
	if($qs['userid']) {
		$sc_column = "아이디";
		$sc_string = $qs['userid'];
		if( preg_match('/%/', $qs['userid']) ) {
			if($qs['userid']=="%") $qs['userid'] = "%%";
			$sql = "SELECT l.uid as bid, l.userid as userid, l.name as name, p.uid as uid, p.ordertable as ordertable, p.title as title, p.price as price, p.year as year, p.month as month, p.rdate as rdate FROM {$table_logon} as l, {$table_payment} as p WHERE p.status='입금필요' and l.uid=p.bid and l.userid like '{$qs['userid']}' order by bid, rdate DESC";
		}
		else
			$sql = "SELECT l.uid as bid, l.userid as userid, l.name as name, p.uid as uid, p.ordertable as ordertable, p.title as title, p.price as price, p.year as year, p.month as month, p.rdate as rdate FROM {$table_logon} as l, {$table_payment} as p WHERE p.status='입금필요' and l.uid=p.bid and l.userid = '{$qs['userid']}' order by bid, rdate DESC";
	}
	elseif($qs['name']) {
		$sc_column = "이름";
		$sc_string = $qs['name'];
		if( preg_match('/%/', $qs['name']) ) {
			if($qs['name']=="%") $qs['name'] = "%%";
			$sql = "SELECT l.uid as bid, l.userid as userid, l.name as name, p.uid as uid, p.ordertable as ordertable, p.title as title, p.price as price, p.year as year, p.month as month, p.rdate as rdate FROM {$table_logon} as l, {$table_payment} as p WHERE p.status='입금필요' and l.uid=p.bid and l.name like '{$qs['name']}' order by bid, rdate DESC";
		}
		else
			$sql = "SELECT l.uid as bid, l.userid as userid, l.name as name, p.uid as uid, p.ordertable as ordertable, p.title as title, p.price as price, p.year as year, p.month as month, p.rdate as rdate FROM {$table_logon} as l, {$table_payment} as p WHERE p.status='입금필요' and l.uid=p.bid and l.name = '{$qs['name']}' order by bid, rdate DESC";
	}
	elseif($qs['hp']) { // 휴대폰번호를 입력했다면
		$qs['hp'] = preg_replace("/[^0-9]/", "", $qs['hp']);

		$sc_column = "휴대폰";
		$sc_string = $qs['hp'];

		$sql = "select bid from {$table_userinfo} where hp='{$qs['hp']}'";
		$rs_tmp = db_query($sql);
		$tmp_bids = "";
		while($list_tmp = db_array($rs_tmp)) {
			$tmp_bids.="{$list_tmp['bid']} ,";
		}
		if($tmp_bids) {
			$tmp_bids = substr($tmp_bids,0,-1);
			$sql = "SELECT l.uid as bid, l.userid as userid, l.name as name, p.uid as uid, p.ordertable as ordertable, p.title as title, p.price as price, p.year as year, p.month as month, p.rdate as rdate FROM {$table_logon} as l, {$table_payment} as p WHERE p.status='입금필요' and l.uid=p.bid and l.uid in ($tmp_bids) order by bid, rdate DESC";
		}
		else $sql = "select * from {$table_payment} LIMIT 0"; // 아무 데이터도 리턴되지 않게
	}
	else back("아이디, 이름, 휴대폰번호중 하나는 입력하여주시기 바랍니다");
?>
	<form name="board" method="post" action="./bankok.php">
	<input type='hidden' name='mode' value='input_ok'>
	<input type='hidden' name='receiptor' value='<?php echo $qs['receiptor']?>'>
	<input type='hidden' name='price' value='<?php echo $qs['price']?>'>
	<input type='hidden' name='bank' value='<?php echo $qs['bank']?>'>
	<input type='hidden' name='inputdate' value='<?php echo $qs['inputdate']?>'>
	<table width="330" border="0" cellspacing="1" cellpadding="3" bgcolor="#2f4f4f">
	 <tr>
		<td bgcolor="#d2b48c" width="101"><b><font size="2" color="#333333">&nbsp;&nbsp;<?php echo $sc_column?></font></b></td>
		<td width="214" bgcolor="#faf0e6"> <font size="2"><?php echo $sc_string?></font></td>
	 </tr>
	 <tr>
		<td bgcolor="#d2b48c" width="101"><b><font size="2" color="#333333">&nbsp;&nbsp;입금인</font></b></td>
		<td width="214" bgcolor="#faf0e6"> <font size="2"><?php echo $qs['receiptor']?></font></td>
	 </tr>
	 <tr>
		<td bgcolor="#d2b48c" width="101"><b><font color="#333333">&nbsp;&nbsp;<font size="2">금액</font></font></b></td>
		<td width="214" bgcolor="#faf0e6"> <font size="2"><?php echo number_format($qs['price'])?> 원</font></td>
	 </tr>
	 <tr>
		<td bgcolor="#d2b48c" width="101"><b><font color="#333333">&nbsp;&nbsp;<font size="2">입금은행</font></font></b></td>
		<td width="214" bgcolor="#faf0e6"> <font size="2"><?php echo $qs['bank']?></font></td>
	 </tr>
	 <tr>
		<td bgcolor="#d2b48c" width="101"><b><font color="#333333">&nbsp;&nbsp;<font size="2">입금날짜</font></font></b></td>
		<td width="214" bgcolor="#faf0e6"> <font size="2"><?php echo $qs['inputdate']?></font></td>
	 </tr>
	</table>
	<br>
	<table border="0" cellspacing="1" cellpadding="3" bgcolor="#336666">
	 <tr bgcolor="#CCCC99" align="center">
		<td height="21"><b><font size="2"></font></b></td>
		<td height="21"><b><font size="2">아이디</font></b></td>
		<td height="21"><b><font size="2">이름</font></b></td>
		<td height="21"><b><font size="2">rdate</font></b></td>
		<td height="21"><b><font size="2">서비스</font></b></td>
		<td height="21"><b><font size="2">내용</font></b></td>
		<td height="21"><b><font size="2">요금</font></b></td>
		<td height="21"><b><font size="2">년/월</font></b></td>
		<td height="21"><b><font size="2">기간</font></b></td>
	 </tr>
<?php
	$result = db_query($sql);
	$total = db_count();
	for($i=0; $i<$total; $i++) 	{
		$list = db_array($result);

		echo ("
			 <tr bgcolor='#faf0e6'>
				<td><font size='2'><input type='checkbox' name='payment_uid[]' value='{$list['uid']}'></font></td>
				<td><font size='2'><a href='../member/search.php?mode=payment&sc_column=logon.userid&sc_string={$list['userid']}' target=search>{$list['userid']}</a></font></td>
				<td><font size='2'>{$list['name']}</font></td>
				<td><font size='2'>{$list['rdate']}</font></td>
				<td><font size='2'>{$list['ordertable']}</font></td>
				<td><font size='2'>{$list['title']}</font></td>
				<td><font size='2'>{$list['price']}</font></td>
				<td><font size='2'>{$list['year']}/{$list['month']}</font></td>
				<td><font size='2'><input type='text' name='period[{$list['uid']}]' size='3' value='{$list['period']}'></font></td>
			 </tr>
		");
	}
?>
	 <tr bgcolor="#faf0e6" align="center">
		 <td colspan="9"> <font size="2">
			<input type="submit" name="submit" value="입금처리">
			<br>
			<input type="text" name="comment" size="30">
			<input type="submit" name="submit" value="미확인처리">
			</font> </td>
	 </tr>
	</table>
	</form>
