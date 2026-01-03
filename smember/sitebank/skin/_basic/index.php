<!--
<?php
exit; ?>
-->
<style type="text/css">
<!--
.style1 {color: #009933}
.style2 {color: #009900; }
.style3 {color: #006633; }
-->
</style>
 
<table width="720" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<td height="15">&nbsp; <img src="/images/top_hade_tip_icon.gif" width="85" height="28" align="absmiddle">&gt; <a href="/">홈 </a>&gt; <a href="/mypage/mypage.html">마이페이지</a> &gt; 포인트조회 </td>
	</tr>
	<tr>
	<td height="1" bgcolor="#EEEEEE"></td>
	</tr>
	<tr>
	<td height="7"></td>
	</tr>
</table>
<table width="720" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td><img src="/mypage/images/point_top_title.gif" width="720" height="92"></td>
	</tr>
	<tr>
	<td height="10"> </td>
	</tr>
	<tr>
	<td align="center"><table width="630" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td><img src="/qna/images/box_top.gif" width="630" height="10"></td>
	</tr>
	<tr>
		<td align="center" background="/qna/images/box_bg.gif"><table width="600" border="0" align="center" cellpadding="0" cellspacing="0" bordercolor="CDCDCD">
			<tr>
			<td bgcolor="#FFFFFF"><p class="base2"><span class="base_gray_bold">포인트 조회를 은행 인터넷 뱅킹과 비슷하게 꾸몄습니다 . <br>
				적립포인트는 상품구매나 이벤트를 통해서 적립하여 드립니다.</span></p></td>
			</tr>
		</table></td>
	</tr>
	<tr>
		<td><img src="/qna/images/box_tip.gif" width="630" height="10"></td>
	</tr>
	</table>
	<br>
	<table width="630" border="0" cellpadding="4" cellspacing="1" bordercolor="#FFFFFF" bgcolor="#CCCCCC">
	<tr bgcolor="#CCF2DE">
		<td width="100"><div align="center" class="style3"><font size="2">포인트종류</font></div></td>
		<td width="140"><div align="center" class="style3"><font size="2">계좌번호</font></div></td>
		<td width="130"><div align="center" class="style3"><font size="2">현재잔액</font></div></td>
		<td width="70"><div align="center" class="style3"><font size="2">상태</font></div></td>
		<td width="130" bgcolor="#CCF2DE"><div align="center"><font size="2"><span class="style1"><span class="style2"><span class="style3"></span></span></span></font></div></td>
	</tr>
<?php echo $html_accountinfo ; ?>
	</table>
	</td>
	</tr>
</table>
<p>&nbsp;</p>
<?php
/*
계좌 내역 조회
*/

if($mode == "inquiry"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계속 발생한다면 사이트 종합질문페이지에 문의 바랍니다.");
	}

	$form_inquiry = " action='$_SERVER['PHP_SELF']' method='post'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='inquiry'
		";

	if($from_year){
		// 기간 조회에 따른 where절 만들기
		$sql_where = "rdate > " . mktime(0,0,0,$from_month, $from_day,$from_year);
		$sql_where .= " and rdate < " . mktime(23,59,59,$to_month, $to_day,$to_year);
	} else {
		$sql_where = "rdate > " . mktime(0, 0, 0, date(m)-1, date(d), date(Y));
		$sql_where .= " and rdate < " . time();
	}

	$rs_account=db_query("SELECT * from {$table_account} where bid={$bid} and accountno={$accountno} and $sql_where order by uid");
	while($list=db_array($rs_account)){
		$list['rdate']=date("Y-m-d",$list['rdate']);
		
		// 숫자에 콤모(,) 붙이기
		$list['deposit']=number_format($list['deposit'],0,"",",");
		$list['withdrawal']=number_format($list['withdrawal'],0,"",",");
		$list['balance']=number_format($list['balance'],0,"",",");
		$html_inquiry .= "
			<tr bgcolor='#F4FAF0'> 
				<td width='80' nowrap align=center> 
				<font size='2'>{$list['rdate']}</font></td>
				<td width='60' nowrap> 
				<div align='center'><font size='2'>{$list['type']}</font></div></td>
				<td width='70' nowrap> 
				<div align='right'><font size='2'>{$list['deposit']}</font></div></td>
				<td width='70' nowrap> 
				<div align='right'><font size='2'>{$list['withdrawal']}</font></div></td>
				<td width='135' nowrap> 
				<div align='left'><font size='2'>{$list['remark']}</font></div></td>
				<td width='90' nowrap> 
				<div align='right'><font size='2'>{$list['balance']}</font></div></td>
				<td width='50' nowrap> 
				<div align='center'><font size='2'>{$list['branch']}</font></div></td>
			</tr>
			";
	}
	if(!$html_inquiry){
		$html_inquiry = "
			<tr bgcolor='#F4FAF0'> 
				<td colspan='7'> 
				<div align='center'><font size='2'>해당 기간동안의 거래 내역이 없습니다.</font></div></td>
			</tr>
			";
	} 

?>
<table width="570" border="0" cellspacing="2" cellpadding="0">
	<tr> 
	<td bgcolor="#000000" height=1><img src="../../common/spacer.gif" height=1></td>
	</tr>
</table>
<table width="570" border="0" cellspacing="0" cellpadding="0">
	<tr> 
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">예금주명 :</font></div>
	</td>
	<td width="185"><font size="2" color="#004B2C"> 
<?php echo $accountinfo['name'] ; ?>
	</font></td>
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">현재잔액&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</font></div>
	</td>
	<td width="185"><font color="#004B2C" size=2> 
<?php echo $accountinfo['balance'] ; ?>
	</font></td>
	</tr>
	<tr> 
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">예금종류 :</font></div>
	</td>
	<td width="185"><font size="2" color="#004B2C"> 
<?php echo $accountinfo['accounttype'] ; ?>
	</font></td>
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">지급가능금액 :</font></div>
	</td>
	<td width="185"><font color="#004B2C" size=2> 
<?php echo $accountinfo['banktransferbalance'] ; ?>
	</font></td>
	</tr>
	<tr> 
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">계좌번호 :</font></div>
	</td>
	<td width="185"><font color="#004B2C" size=2> 
<?php echo $accountinfo['account'] ; ?>
	</font></td>
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">상태&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; :</font></div>
	</td>
	<td width="185"><font size="2" color="#004B2C"> 
<?php echo ($accountinfo['state'] == "정상") ? $accountinfo['state'] : $accountinfo['state'] . "({$accountinfo['errornotice']})" ; ?>
	</font></td>
	</tr>
	<tr> 
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">신규일자 :</font></div>
	</td>
	<td width="185"><font size="2" color="#004B2C"> 
<?php echo $accountinfo['rdate'] ; ?>
	</font></td>
	<td width="100"><font color="#004B2C">&nbsp;</font> </td>
	<td width="185"><font color="#004B2C">&nbsp;</font></td>
	</tr>
	<tr> 
	<td width="100"> 
	<div align="center"><font size="2" color="#004B2C">특기사항 :</font></div>
	</td>
	<td colspan="3"> 
	<div align="left"> <font size="2" color="#004B2C"> 
<?php
echo nl2br($accountinfo['comment']) ; ?>
		</font></div>
	</td>
	</tr>
</table>
<br>
<form	style='margin : 0px'<?php echo $form_inquiry ; ?>>
<table width="570" border="0" cellspacing="2" cellpadding="0">
	<tr> 
	<td bgcolor="#000000" height=1><img src="../../common/spacer.gif" height=1></td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5"><font size="2" color="#004B2C">조회하고자 하는 기간을 선택하여 주십시요</font></td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5"> 

		<font color="#004B2C" size="2">
		<input type="text" name="from_year" size="4" value="<?php echo ($from_year ? $from_year : date(Y)) ; ?>">
		년 
		<input type="text" name="from_month" size="2" value="<?php echo ($from_month ? $from_month : date(m)-1) ; ?>">
		월 
		<input type="text" name="from_day" size="2" value="<?php echo ($from_day ? $from_day : date(d)) ; ?>">
		일 부터 
		<input type="text" name="to_year" size="4" value="<?php echo ($to_year ? $to_year : date(Y)) ; ?>">
		년 
		<input type="text" name="to_month" size="2" value="<?php echo ($to_month ? $to_month : date(m)) ; ?>">
		월 
		<input type="text" name="to_day" size="2" value="<?php echo ($to_day ? $to_day : date(d)) ; ?>">
		일 부터 </font> 

	</td>
	</tr>
	<tr> 
	<td bgcolor="#000000" height=1><img src="../../common/spacer.gif" height=1></td>
	</tr>
	<tr> 
	<td> 
	<div align="right">
		<input type="image" border="0" name="imageField" src="images/inquiry.gif">
	</div>
	</td>
	</tr>
</table>
</form>
<table width="570" border="0" cellpadding="4" cellspacing="1" bordercolor="#FFFFFF" bgcolor="#CCCCCC">
	<tr bgcolor="#CCF2DE"> 
	<td width="75"> 
	<div align="center" class="style3"><font size="2">거래일자</font></div>
	</td>
	<td width="60"> 
	<div align="center" class="style3"><font size="2">적요</font></div>
	</td>
	<td width="70"> 
	<div align="center" class="style3"><font size="2">입급</font></div>
	</td>
	<td width="70"> 
	<div align="center" class="style3"><font size="2">지급</font></div>
	</td>
	<td width="135"> 
	<div align="center" class="style3"><font size="2">내용</font></div>
	</td>
	<td width="90"> 
	<div align="center" class="style3"><font size="2">잔액</font></div>
	</td>
	<td width="50"> 
	<div align="center" class="style3"><font size="2">거래점</font></div>
	</td>
	</tr>
<?php echo $html_inquiry ; ?>
</table>
<?php
} // end if($mode == "inquiry")

/////////////////////////////////////////////////////////////////
//	계좌 이체
if($mode == "deposit"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	$form_deposit = " action='./bankok.php' method='post'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='deposit'
		"; 
?>
	<form	style='margin : 0px'<?php echo $form_deposit ; ?>>
	<table width="570" border="0" cellspacing="2" cellpadding="0">
		<tr> 
		
	<td bgcolor="#000000" height=1 colspan="2"><img src="../../common/spacer.gif" height=1></td>
		</tr>
		<tr> 
		<td bgcolor="#F8FFE5" nowrap> 
			<div align="right"><font size="2" color="#004B2C">입금계좌번호</font></div>
		</td>
		<td bgcolor="#F8FFE5" nowrap> 
			<div align="left"><font size="2" color="#004B2C">사이트가상계좌-<?php echo "{$accountinfo['accounttype']}(계좌번호:{$accountinfo['account']})" ; ?></font></div>
		</td>
		</tr>
		<tr> 
		<td bgcolor="#F8FFE5" nowrap> 
			<div align="right"><font size="2" color="#004B2C">입금(충전) 금액</font></div>
		</td>
		<td bgcolor="#F8FFE5" nowrap> 
			<select name="money">
				<option value=1000>	1,000원</option>
				<option value=2000>	2,000원</option>
				<option value=3000>	3,000원</option>
				<option value=5000>	5,000원</option>
				<option value=10000> 10,000원</option>
				<option value=20000> 20,000원</option>
				<option value=30000> 30,000원</option>
				<option value=50000> 50,000원</option>
				<option value=1000000>100,000원</option>
			</select>
		</td>
		</tr>
		<tr> 
		<td bgcolor="#F8FFE5" nowrap> 
			<div align="right"><font color="#004B2C" size="2"><font size="2"><font size="2"><font color="#004B2C"></font></font></font> 
			</font> </div>
		</td>
		<td bgcolor="#F8FFE5" nowrap> 
			<div align="left"> <font size="2" color="#004B2C"> 
			<input type="submit" value="인터넷 요금 결제페이지로" name="submit">
			</font></div>
		</td>
		</tr>
	</table>
	</form>
<?php
} // end if($mode == "deposit")

/////////////////////////////////////////////////////////////////
//	계좌 이체
if($mode == "transfer"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	elseif( $accountinfo['transfertype'] == "모든이체불가" ){
		back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.");
	}
	$form_transfer = " action='$_SERVER['PHP_SELF']' method='post'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='transferconfirm'
		"; 
?>
<form	style='margin : 0px'<?php echo $form_transfer ; ?>>
	<table width="570" border="0" cellspacing="2" cellpadding="0">
	<tr> 
	<td bgcolor="#000000" height=1 colspan="2"><img src="../../common/spacer.gif" height=1></td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">출금계좌번호</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"><font size="2" color="#004B2C">사이트가상계좌-<?php echo "{$accountinfo['accounttype']}(계좌번호:{$accountinfo['account']})" ; ?></font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">입금은행</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<select name="to_bank">
			<option value="사이트" selected>사이트가상계좌-적립포인트계좌</option>
			<option value="농협">농협,축협(11)</option>
			<option value="국민">국민(04)</option>
			<option value="한빛">한빛(20)</option>
			<option value="조흥">조흥(21)</option>
			<option value="주택">주택(06)</option>
			<option value="기업">기업(03)</option>
			<option value="신한">신한(26)</option>
			<option value="서울">서울(25)</option>
			<option value="제일">제일(23)</option>
			<option value="한미">한미(27)</option>
			<option value="우체국">우체국(71)</option>
		</select>
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">입금계좌번호</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<input type="text" name="to_accountno" size="30">
			(숫자만 입력)
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">이체금액</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<input type="text" name="to_money" size="20">
			(숫자만 입력)
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font color="#004B2C" size="2"><font size="2"><font size="2"><font color="#004B2C"></font></font></font> 
		</font> </div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<input type="submit" value="이체 확인" name="submit">
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#000000" height=1 colspan="2"><img src="../../common/spacer.gif" height=1></td>
	</tr>
	</table>
	<table width="570" border="0" cellspacing="0" cellpadding="0" align="left">
	<tr>
	<td width="50">&nbsp;</td>
	<td>
		<ul>
		<li><font size="2">캐쉬포인트에서 적립포인트로 이체비용없이 실시간 이체할 수 있습니다.</font></li>
		<li><font size="2">적립포인트는 환금성을 지니고 있지 않기에, 한번 이체를 하시면 취소 혹은 환급되지 않습니다 . 
			이점 유념바랍니다.</font></li>
		<li><font size="2">실제 은행으로 이체(환급신청)는 10,000원 단위로 가능하며, 신청후 2영업일 이내에 
			당사 정산 담당자가 확인하여 이체하여 드립니다.</font></li>
		<li><font size="2">실제 은행으로 이체(환급신청)는 이체 수수료 최소 500원이 청구됨을 유념바랍니다.</font></li>
		<li><font size="2">만일 캐쉬포인트라도 그 적립 발생이 신용 카드 적립 등 현금으로 환급될 수 없는 방법으로 
			적립된 경우에는 이체 신청이 자동 취소될 수 있으며, 만일 확인상의 실수로 이체되었다면 다시 되돌려 주어야 합니다 . (회원님의 
			신용카드, 휴대폰 등을 통한 적립은 승인 취소의 방법으로 관련법률 및 계약상 취소되어야 합니다)</font></li>
		</ul>
	</td>
	</tr>
	</table>
	<p>&nbsp;</p>
</form>
<?php
} // end if($mode == "transfer")

/*
	계좌 이체
*/

if($mode == "transferconfirm"){
	// 넘어온 값 체크
	$qs=array(	to_bank =>  "post,trim,notnull=" . urlencode("이체 은행을 입력하시기 바랍니다."),
				to_accountno =>  "post,trim,notnull=" . urlencode("계좌번호를 입력하시기 바랍니다."),
				to_money =>  "post,trim,notnull=" . urlencode("이체 금액을 입력하시기바랍니다."),
		);
	$qs=check_value($qs);
	$qs['to_accountno']=eregi_replace("[^0-9]","",$qs['to_accountno']);

	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	elseif( $accountinfo['transfertype'] == "모든이체불가" ){
		back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.");
	}

	if($qs['to_bank'] == "사이트"){
		if(!db_count(db_query("SELECT * from {$table_accountinfo} where bid='{$bid}' and accountno='{$qs['to_accountno']}'"))){
			back("계좌번호가 틀립니다.\\n계좌번호를 확인하시고 숫자로만 입력바랍니다.");
		}
		$qs['commission']	= 0;
	} else {
		if($qs['to_money'] < 10000)
			back("실제 은행으로 이체(환급)은 1만원단위 만원 이상입니다.");
		$qs['to_money']	= (int)($qs['to_money']/10000)* 10000;
		$qs['commission']	= 500;

	}

	$transferconfirm = " action='bankok.php' method='post'>
					<input type='hidden' name='mode' value='transferok'>
					<input type='hidden' name='accountno' value='{$accountno}'>
					<input type='hidden' name='to_bank' value='{$qs['to_bank']}'>
					<input type='hidden' name='to_accountno' value='{$qs['to_accountno']}'>
					<input type='hidden' name='to_money' value='{$qs['to_money']}'
		"; 
?>
<form	style='margin : 0px'<?php echo $transferconfirm ; ?>>
	<table width="570" border="0" cellspacing="2" cellpadding="0">
	<tr> 
	<td bgcolor="#000000" height=1 colspan="2"><img src="../../common/spacer.gif" height=1></td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">입금은행</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C">
<?php echo $qs['to_bank'] ; ?>
(계좌번호:
<?php echo $qs['to_accountno'] ; ?>
)</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">이체금액</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> \
<?php echo $qs['to_money'] ; ?></font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">출금계좌번호</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"><font size="2" color="#004B2C">유캐리계좌- 
<?php
echo "{$accountinfo['accounttype']}({$accountinfo['account']})" ; ?>
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">이체 수수료</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> \
<?php echo $qs['commission'] ; ?>
원</font></div>
	</td>
	</tr>

	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font size="2" color="#004B2C">회원님의 웹로그인비밀번호</font></div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<input type="password" name="passwd">
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="right"><font color="#004B2C" size="2"><font size="2"><font size="2"><font color="#004B2C"></font></font></font> 
		</font> </div>
	</td>
	<td bgcolor="#F8FFE5" nowrap> 
		<div align="left"> <font size="2" color="#004B2C"> 
		<input type="submit" value="이체 신청" name="submit">
		</font></div>
	</td>
	</tr>
	<tr> 
	<td bgcolor="#000000" height=1 colspan="2"><img src="../../common/spacer.gif" height=1></td>
	</tr>
	</table>
	<table width="570" border="0" cellspacing="0" cellpadding="0" align="left">
	<tr> 
	<td width="50">&nbsp;</td>
	<td> 
		<ul>
		<li><font size="2"> 실제 은행으로 이체(환급신청)는 10,000원 단위로 가능하며, 신청후 2영업일 이내에 
			당사 정산 담당자가 확인하여 이체하여 드립니다.</font></li>
		<li><font size="2">출금 통장의 이용 내역 확인에서 이체 상황을 확인할 수 있습니다.</font></li>
		<li><font size="2">실제 은행으로 이체(환급신청)시 발생되는 이체 수수료는 최소 500원이 청구되며, 해당 
			은행의 이체 비용이 그 이상의 경우 추가로 해당 계좌에서 출금될 수 있습니다.</font></li>
		</ul>
	</td>
	</tr>
	</table>
	<p>&nbsp;</p>
</form>
<?php
} // end if($mode == "transferconfirm"); 
 echo $SITE['tail']; ?>
