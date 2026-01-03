<?php
//=======================================================
// 설	명 : 인터넷결제창 뛰우기(KCP 기준)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 비회원로그인이더라도 로그인된 이후에
	if(!trim($_SESSION['seUid']) || !trim($_SESSION['seUserid'])){
		back("로그인을 먼저 하여주시기 바랍니다");
		exit;
	}

	$table				= $SITE['th'] . "payment";
	$table_ncash		= $SITE['th'] . "payment_ncash";
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";
	$table_logon		= $SITE['th'] . "logon";

	// PHP 7에서 $_POST 대신 $_REQUEST를 사용하여 호환성 유지
	if(!isset($_POST['payment']) || !sizeof($_POST['payment']) || !isset($_POST['bank']))
		back("결제 종류과 결제할 내역을 각각 선택하시기 바랍니다.");
	if(!isset($_SESSION['seUid']) || !isset($_SESSION['seUserid']))
		back("회원 정보가 이상합니다 . 로그아웃후 다시 로그인후에 시도하시기 바랍니다.");

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// PHP 7에서는 switch/case 문에서 문자열 비교가 엄격해짐
switch($_POST['bank']){
	case "신용카드" :
		kcp_card(); // mulkang, coolhair
		//telec_card();
		break;
	case "계좌이체" :
		kcp_bank("Personal"); // mulkang, coolhair
		//telec_bank();
		break;
	case "계좌이체-법인" :
		kcp_bank("Company");
		break;
	//case "휴대폰" :
	//	telec_hp();
	//	break;
	case "무통장" :
		remit();
		back("감사합니다 . 무통장 입금 안내입니다.\\n\\n이전페이지 위에서 안내하는 은행계좌로 입금하여 주시면 됩니다.\\n\\n무통장입금예정을 신청하셨지만, 언제든지 신용카드 등 다른 결제방법으로 결제하셔도 됩니다.","inquiry.php");
	default:
		back("죄송합니다.\\n현재 이용할 수 없는 결제 방법입니다.\\n 다른 지불 방법을 선택하여주시기 바랍니다.");
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// KCP-telecmcash 뱅크결제
function telec_bank()
{
	global $table, $table_ncash, $table_logon;

	// 회원세부정보(logon) 가져오기
	// 변수명 직접 사용 대신 $_SESSION 배열을 명시적으로 사용
	$sql = "SELECT * from {$table_logon} where uid = '" . db_escape($_SESSION['seUid']) . "'";
	$logon = db_arrayone($sql);

	$payment_uid = array();
	$payment_total = 0;
	$contentcategorycode = "0";
	$contentcategoryname = "기본";
	$re_address = "";
	$title = "";

	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$rs_payment = db_query("SELECT * from {$table} where bid='" . db_escape($_SESSION['seUid']) . "' and rdate='{$key}' and status='입금필요'");
			while($row = db_array($rs_payment)){
				$payment_uid[]	= $row['uid'];
				$payment_total	+= $row['price'];

				// 주소
				if($row['re_address']) $re_address = $row['re_address'];
				if($row['title']) $title .= ($title) ? "." : "{$row['title']} 등";
			} // end while
		} // end if
	} // end foreach

	if(!is_array($payment_uid) || empty($payment_uid))
		back("결제 내역이 없습니다 . 결제 내역을 선택바랍니다.");
	$payment_uids=join(":",$payment_uid);

	// PHP 7에서 UNIX_TIMESTAMP() 함수를 사용하여 시간을 가져옴
	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
				VALUES ('" . db_escape($_SESSION['seUid']) . "', '" . db_escape($_SESSION['seUserid']) . "', '" . db_escape($payment_uids) . "', '" . db_escape($contentcategorycode) . "', '" . db_escape($contentcategoryname) . "', '" . db_escape($payment_total) . "', '" . db_escape($payment_total) . "', '', UNIX_TIMESTAMP(), '" . db_escape($_SERVER['REMOTE_ADDR']) . "')");
	if(!$contentcode=db_insert_id())
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

	// URL Link..
	$href['returnurl'] = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['onload'] = "javascript:OpenWin('https://www.telec.co.kr/order/telecbank.jsp','CE??', '{$contentcode}', '{$payment_total}','{$_SESSION['seUserid']}','{$href['returnurl']}')";
?>
<html>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script language="JavaScript">
		function OpenWin(url, ShopID,	OrderID, Amount, Name, Ret_URL)
		{
		var R_URL=url+"?ShopID="+ShopID+"&OrderID="+OrderID+"&Amount="+Amount +"&Name="+Name +"&Ret_URL="+encodeURIComponent(Ret_URL);
		window.open(R_URL, "Window", "width=600,height=750,status=no,scrollbars");
		}
	</script>
	<body onLoad="<?php echo $href['onload'] ; ?>">
		<FORM id=form1 name=form1>
			<table width="500" height="280" border="0" cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td align="right" valign="top" background="/smember/payment/skin/mbasic/images/moneypay_img.gif"><table width="330" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td height="130">&nbsp;</td>
					</tr>
					<tr>
						<td align="center">오픈창이 뜨지 않았다면
						<input type=button onClick="<?php echo $href['onload'] ; ?>" value='오픈창띄우기'>
						를 <br>
						클릭하십시요.<br>
						<br>
						결제과정에서 문제가 발생하였다면, <br>
						종합질문페이지에 문의주시기 바랍니다 . <br>
						<br>
						<P align=center>고객선터	02-444-2945<BR>
							<A href="mailto:help@mulkang.com">help@mulkang.com</A></P>
		</FORM>
	</body>
</html>
<?php
} // end func.
// KCP-telecmcash 휴대폰결제
function telec_hp()
{
	global $table, $table_ncash, $table_logon;

	// 회원세부정보(logon) 가져오기
	$sql = "SELECT * from {$table_logon} where uid = '" . db_escape($_SESSION['seUid']) . "'";
	$logon = db_arrayone($sql);

	$payment_uid = array();
	$payment_total = 0;
	$contentcategorycode = "0";
	$contentcategoryname = "기본";
	$re_address = "";
	$title = "";
	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$rs_payment=db_query("SELECT * from {$table} where bid='" . db_escape($_SESSION['seUid']) . "' and rdate='{$key}' and status='입금필요'");
			while($row=db_array($rs_payment)){
				$payment_uid[]	= $row['uid'];
				$payment_total	+= $row['price'];

				// 주소
				if($row['re_address']) $re_address = $row['re_address'];
				if($row['title']) $title .= ($title) ? "." : "{$row['title']} 등";
			} // end while
		} // end if
	} // end foreach

	if(!is_array($payment_uid) || empty($payment_uid))
		back("결제 내역이 없습니다 . 결제 내역을 선택바랍니다.");
	$payment_uids=join(":",$payment_uid);
	$contentcategorycode="0";
	$contentcategoryname="기본";

	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
						VALUES ('" . db_escape($_SESSION['seUid']) . "', '" . db_escape($_SESSION['seUserid']) . "', '" . db_escape($payment_uids) . "', '" . db_escape($contentcategorycode) . "', '" . db_escape($contentcategoryname) . "', '" . db_escape($payment_total) . "', '" . db_escape($payment_total) . "', '', UNIX_TIMESTAMP(), '" . db_escape($_SERVER['REMOTE_ADDR']) . "')");
	if(!$contentcode=db_insert_id())
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

	// URL Link..
	$href['returnurl'] = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['onload'] = "javascript: document.TelecForm.submit();";
?>
<html>
	<body onLoad="<?php echo $href['onload'] ; ?>">
		<FORM name=TelecForm action='https://www.telec.co.kr/order/telecmcash.jsp' method=post >
			<INPUT type=hidden name='ShopID' value='CE??' >
			<INPUT type=hidden name='ServiceID' value="031217120032">
			<INPUT type=hidden name='OrderID' value='<?php echo $contentcode ; ?>'>
			<INPUT type=hidden name='Amount' value='<?php echo $payment_total; ?>' >
			<INPUT type=hidden name='Type' value='B'>
			<INPUT type=hidden name='Name' value='ID<?php echo $_SESSION['seUserid'] ; ?>' >
			<INPUT type=hidden name='Good' value='<?php echo $title ; ?>'>
			<INPUT type=hidden name='Good_CD' value='<?php echo $contentcode ; ?>'>
			<INPUT type=hidden name='SubShopID' value='' >
			<INPUT type=hidden name='CertFlag' value='Y' >
			<INPUT type=hidden name=Home_URL value='<?php echo $href['returnurl'] ; ?>' >
			<center>
			<b>잠시 후 결제 페이지가 나타납니다 . 감사합니다.</b><br>
			만일 결제페이지로 바로 바로 이동하지 않으면
			<input type=submit value='휴대폰 결제하기'>
			를 클릭하십시요.<br>
			<br>
			결제과정에서 문제가 발생하였다면, 종합질문페이지에 문의주시기 바랍니다.
		</FORM>
	</body>
</html>
<?php
} // end func.

// KCP-telec 카드결제
function telec_card()
{
	global $table, $table_ncash, $table_logon;

	// 회원세부정보(logon) 가져오기
	$sql = "SELECT * from {$table_logon} where uid = '" . db_escape($_SESSION['seUid']) . "'";
	$logon = db_arrayone($sql);

	$payment_uid = array();
	$payment_total = 0;
	$contentcategorycode = "0";
	$contentcategoryname = "기본";
	$re_address = "";
	$title = "";
	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$rs_payment=db_query("SELECT * from {$table} where bid='" . db_escape($_SESSION['seUid']) . "' and rdate='{$key}' and status='입금필요'");
			while($row=db_array($rs_payment)){
				$payment_uid[]	= $row['uid'];
				$payment_total	+= $row['price'];

				// 주소
				if($row['re_address']) $re_address = $row['re_address'];
				if($row['title']) $title .= ($title) ? "." : "{$row['title']} 등";
			} // end while
		} // end if
	} // end foreach

	if(!is_array($payment_uid) || empty($payment_uid))
		back("결제 내역이 없습니다 . 결제 내역을 선택바랍니다.");
	$payment_uids=join(":",$payment_uid);
	$contentcategorycode="0";
	$contentcategoryname="기본";

	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
	VALUES ('" . db_escape($_SESSION['seUid']) . "', '" . db_escape($_SESSION['seUserid']) . "', '" . db_escape($payment_uids) . "', '" . db_escape($contentcategorycode) . "', '" . db_escape($contentcategoryname) . "', '" . db_escape($payment_total) . "', '" . db_escape($payment_total) . "', '', UNIX_TIMESTAMP(), '" . db_escape($_SERVER['REMOTE_ADDR']) . "')");
	if(!$contentcode=db_insert_id())
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

	// URL Link..
	$href['returnurl'] = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['onload'] = "javascript: OpenWindow();document.ecPayForm.submit();";
?>
<html>
	<script language="JavaScript">
		function OpenWindow()
		{
			var winopts = "width=550,height=450,toolbar=no,location=no,directories=no,status=yes,menubar=no,status=yes,menubar=no,scrollbars=no,resizable=yes";
			var popWindow = window.open('','POPWIN', winopts);
		}
	</script>
	<body onLoad="<?php echo $href['onload'] ; ?>">
		<FORM name=ecPayForm	method=post	target=POPWIN onSubmit="return OpenWindow()" action=https://www.telec.co.kr/order/telecpg.jsp>
			<input type=hidden name=ShopID	value='CE53'>
			<input type=hidden name=OrderID	value='<?php echo $contentcode ; ?>'>
			<input type=hidden name=Ret_URL	value='<?php echo $href['returnurl'] ; ?>' >
			<input type=hidden name=Amount	value='<?php echo $payment_total; ?>' >
			<input type=hidden name=Name	value='ID<?php echo $_SESSION['seUserid'] ; ?>' >
			<input type=hidden name=Phone	value='<?php echo $logon['hp'] ; ?>' >
			<input type=hidden name=E_mail	value='<?php echo $_SESSION['seEmail'] ; ?>' >
			<input type=hidden name=Addr	value='<?php echo $re_address ; ?>'>
			<input type=hidden name=Good	value='<?php echo $title ; ?>'>
			<input type=hidden name=Good_CD	value='<?php echo $contentcode ; ?>'>
			<center>
			<b>오픈 창으로 결제 페이지가 나타납니다 . 감사합니다.</b><br>
			오픈창이 뜨지 않았다면
			<input type=submit value='오픈창띄우기'>
			를 클릭하십시요.<br>
			<br>
			결제과정에서 문제가 발생하였다면, 종합질문페이지에 문의주시기 바랍니다.
		</FORM>
	</body>
</html>
<?php
} // end func.

// 무통장 입금 처리 부분
function remit()
{
	global $table, $table_ncash, $table_logon;

	// PHP 7에서 $_POST 대신 $_REQUEST를 사용하여 호환성 유지
	$qs=array(
		'remitdate' =>	"post,trim,notnull=" . urlencode("송금날자를 입력하시기 바랍니다."),
		'remitname' =>	"post,trim,notnull=" . urlencode("송금자 이름을 입력하시기 바랍니다."),
		'remitbank' =>	"post,trim,notnull=" . urlencode("송금은행을 선택 혹은 입력하시기 바랍니다."),
		'taxcash_check' => 	"post",
		'taxcash_name' => 	"post,trim",
		'taxcash_num' => 	"post,trim",
		'taxcash_hp' => 	"post,trim",
		'taxcash_email' => 	"post,trim",
	);
	$qs=check_value($qs);
	$qs['idate'] = strtotime($qs['remitdate']);
	if( $qs['idate'] < strtotime(date("Y-m-d")) ) back("송금예정날자가 잘못 입력되었습니다.");
	if( isset($qs['taxcash_check']) && $qs['taxcash_check'])	{
		$qs['taxcash_num'] = preg_replace('/[^0-9]/','',$qs['taxcash_num']);
		$qs['taxcash_hp']	= preg_replace('/[^0-9]/','',$qs['taxcash_hp']);
		if(empty($qs['taxcash_name']) || empty($qs['taxcash_num']) || empty($qs['taxcash_hp']) || empty($qs['taxcash_email']))
			back('현금 영수증 신청을 위해서 이름,주민(사업자)등록번호,휴대폰,메일주소를 모두 입력하셔야 합니다.');
		$update_sql = " , taxcash_name = '" . db_escape($qs['taxcash_name']) . "', taxcash_num = '" . db_escape($qs['taxcash_num']) . "' , taxcash_hp = '" . db_escape($qs['taxcash_hp']) . "', taxcash_email = '" . db_escape($qs['taxcash_email']) . "' , taxcash_status = '발행요청' ";
	} else {
		$update_sql = "";
	}

	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$sql = "update {$table} set bank='" . db_escape($qs['remitbank']) . "', receiptor='" . db_escape($qs['remitname']) . "', idate='" . db_escape($qs['idate']) . "' $update_sql where bid='" . db_escape($_SESSION['seUid']) . "' and num='{$key}' and status='입금필요'";
			db_query($sql);
		} // end if
	} // end foreach
} // end func.
// KCP 카드결제
function kcp_card()
{
	global $table, $table_ncash, $table_logon;

	// 회원세부정보(logon) 가져오기
	$sql = "SELECT * from {$table_logon} where uid = '" . db_escape($_SESSION['seUid']) . "'";
	$logon = db_arrayone($sql);

	$payment_uid = array();
	$payment_total = 0;
	$contentcategorycode = "0";
	$contentcategoryname = "기본";
	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$rs_payment=db_query("SELECT * from {$table} where bid='" . db_escape($_SESSION['seUid']) . "' and num='{$key}' and re='' and status='입금필요'");
			while($row=db_array($rs_payment)){
				$payment_uid[]	= $row['uid'];
				$payment_total	+= $row['totalprice'];
			} // end while
		} // end if
	} // end foreach

	if(!is_array($payment_uid) || empty($payment_uid))
		back("결제 내역이 없습니다 . 결제 내역을 선택바랍니다.");
	$payment_uids=join(":",$payment_uid);
	$contentcategorycode="0";
	$contentcategoryname="기본";

	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
						VALUES ('" . db_escape($_SESSION['seUid']) . "', '" . db_escape($_SESSION['seUserid']) . "', '" . db_escape($payment_uids) . "', '" . db_escape($contentcategorycode) . "', '" . db_escape($contentcategoryname) . "', '" . db_escape($payment_total) . "', '" . db_escape($payment_total) . "', '', UNIX_TIMESTAMP(), '" . db_escape($_SERVER['REMOTE_ADDR']) . "')");
	if(!$contentcode=db_insert_id())
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

	// URL Link..
	$href['returnurl'] = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['onload'] = "javascript: makeWin('{$contentcode}', '{$payment_total}', 'ID{$_SESSION['seUserid']}', '{$logon['hp']}', '{$_SESSION['seUid']}','{$_SESSION['seEmail']}','{$href['returnurl']}');";
?>
<html>
	<script language="JavaScript">
		/********************************************************************************
		*			KCP Payplus Version 5.- Web version 용 자바스크립트				*
		*																				*
		*===============================================================================*
		*다음 스크립트중 termid,cgiurl 은 부여받은 값으로 대체시키고 스크립트의 나머지	*
		*부분들은 별도 협의 없이 변경을 삼가해주시기 바랍니다.							*
		*스크립트에 대한 문의는 KCP 기술팀:02-2108-1000로 해주시기 바랍니다.			*
		*********************************************************************************/
		function makeWin(orderid, amt, customerName, customerTel, userkey, customerEmail, returnurl)
		{
			cgiurl = "https://secure.kcp.co.kr/webpay/gen_webauthpage.asp"	//test시 url과 실사용시 url이 다릅니다.
			termid = "T06969"												//KCP로부터 부여받은 영업점코드(터미날 ID)를 정확히 입력하세요(6자리)
			//termid="T08118"
			midbykcp = "MK07"												//KCP로부터 부여받은 가맹점코드(가맹점 ID)를 정확히 입력하세요(4자리)
			//midbykcp="MKC1"
			//returnurl = "<?php echo $href['returnurl'] ; ?>";		//결제성공시 응답받을 URL
			cgiurl=cgiurl+"?orderid="+encodeURIComponent(orderid)+"&termid="+termid+"&midbykcp="+midbykcp+"&amt="+amt+"&customerName="+encodeURIComponent(customerName)+"&customerTel="+encodeURIComponent(customerTel)+"&userkey="+encodeURIComponent(userkey)+"&customerEmail="+encodeURIComponent(customerEmail)+"&returnurl="+encodeURIComponent(returnurl);
			if(navigator.appName == "Netscape"){
				newWin=window.open(cgiurl,"_new","width=530,height=530,scrollbars=0,scroll=0,resizable=0,status=1");
			} else {
				newWin=window.open("","_new","width=530,height=600,scrollbars=0,scroll=0,resizable=0,status=1");
				newWin.document.writeln("<body>");
				newWin.document.writeln("<form name=sendForm method=post action=" +cgiurl+">");
				newWin.document.writeln("<\/form>");
				newWin.document.writeln("<\/body>");
				newWin.document.sendForm.submit();
			}
		}
	</script>
	<body onLoad="<?php echo $href['onload'] ; ?>">
		<FORM id=form1 name=form1>
			<table width="500" height="280" border="0" cellpadding="0" cellspacing="0" align="center">
			<tr>
				<td align="right" valign="top" background="/smember/payment/skin/mbasic/images/moneypay_img.gif"><table width="330" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td height="130">&nbsp;</td>
					</tr>
					<tr>
						<td align="center">오픈창이 뜨지 않았다면
						<input type=button onClick="<?php echo $href['onload'] ; ?>" value='오픈창띄우기'>
						를 <br>
						클릭하십시요.<br>
						<br>
						결제과정에서 문제가 발생하였다면, <br>
						종합질문페이지에 문의주시기 바랍니다 . <br>
						<br>
						<P align=center>고객선터 02-444-2945 <BR>
							<A href="mailto:help@mulkang.com">help@mulkang.com</A></P>
		</FORM>
		</td>
		</tr>
		</table>
		</td>
		</tr>
		</table>
	</body>
</html>
<?php
} // end func.

// KCP 계좌이체
function kcp_bank($bank="Personal")
{
	global $table, $table_ncash, $table_logon;

	// 회원세부정보(logon) 가져오기
	$sql = "SELECT * from {$table_logon} where uid = '" . db_escape($_SESSION['seUid']) . "'";
	$logon = db_arrayone($sql);

	$payment_uid = array();
	$payment_total = 0;
	$contentcategorycode = "0";
	$contentcategoryname = "기본";
	foreach($_POST['payment'] as $key => $value){
		if($value == 1){
			$key = db_escape($key);
			$rs_payment=db_query("SELECT * from {$table} where bid='" . db_escape($_SESSION['seUid']) . "' and num='{$key}' and re='' and status='입금필요'");
			while($row=db_array($rs_payment)){
				$payment_uid[]	= $row['uid'];
				$payment_total	+= $row['totalprice'];
			} // end while
		} // end if
	} // end foreach

	if(!is_array($payment_uid) || empty($payment_uid))
		back("결제 내역이 없습니다 . 결제 내역을 선택바랍니다.");
	$payment_uids=join(":",$payment_uid);
	$contentcategorycode="0";
	$contentcategoryname="기본";

	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
						VALUES ('" . db_escape($_SESSION['seUid']) . "', '" . db_escape($_SESSION['seUserid']) . "', '" . db_escape($payment_uids) . "', '" . db_escape($contentcategorycode) . "', '" . db_escape($contentcategoryname) . "', '" . db_escape($payment_total) . "', '" . db_escape($payment_total) . "', '', UNIX_TIMESTAMP(), '" . db_escape($_SERVER['REMOTE_ADDR']) . "')");
	if(!$contentcode=db_insert_id())
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

	// URL Link..
	$href['returnurl'] = "http://" . $_SERVER['HTTP_HOST'] . "/".dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['onload'] = "javascript: makeWin('{$contentcode}', '{$payment_total}', 'ID{$_SESSION['seUserid']}', '{$logon['hp']}', '{$_SESSION['seUid']}');";
?>
<html>
	<script language="JavaScript">
		/********************************************************************************
		*		KCP 계좌이체지불서비스.- Web version 용 자바스크립트				*
		*		작성일:2001/04
		*			수정함: 03/11/05 BY Sunmin Park
		*===============================================================================*
		*다음 스크립트중 termid,cgiurl 은 부여받은 값으로 대체시키고 스크립트의 나머지	*
		*부분들은 별도 협의 없이 변경을 삼가해주시기 바랍니다.							*
		*스크립트에 대한 문의는 KCP 기술팀:02-2108-1000으로 해주시기 바랍니다.			*
		*********************************************************************************/
		function makeWin(orderid, amount, customerName, customerTel, userkey)
		{
			var v="<?php echo $bank; ?>";

			if(v != "Company") v="Personal"; // by Sunmin Park
			//var radioObj=document.pay.PayMethod;
			//for(var i=0; i<radioObj.length;i++){
			//	if( radioObj[i].checked){
			//		v=radioObj[i].value;
			//	}
			//}

			cgiurl = "http://secure.kcp.co.kr/ibanking/authpage.asp";	//test시 url과 실사용시 url이 다릅니다.
			termid = "T06969";		//KCP로부터 부여받은 영업점코드(터미날 ID)를 정확히 입력하세요(6자리)
			//termid="T08118";
			MIDbyKCP = "MK07";		//kcp에서 부여한 머천트아이디 (test 시 MT31)
			//MIDbyKCP="MKC1" ;
			cgiurl=cgiurl+"?orderid="+encodeURIComponent(orderid)+"&termid="+encodeURIComponent(termid)+"&MIDbyKCP="+encodeURIComponent(MIDbyKCP)+"&amount="+encodeURIComponent(amount)+"&customerName="+encodeURIComponent(customerName)+"&customerTel="+encodeURIComponent(customerTel)+"&Userkey="+encodeURIComponent(userkey)+"&PayMethod="+encodeURIComponent(v);

			if(navigator.appName == "Netscape"){
				alert ("인터넷 익스플러스에서만 동작합니다.");
				return false;
			} else {
				newWin=window.open("","_new","width=430,height=200,scrollbars=0,scroll=0,resizable=0,status=1,top=200,left=300");
				newWin.document.writeln("<body>");
				newWin.document.writeln("<form name=sendForm method=post action=" +cgiurl+">");
				newWin.document.writeln("</form>");
				newWin.document.writeln("</body>");
				newWin.document.sendForm.submit();
			}
		}
	</script>
	<body onLoad="<?php echo $href['onload'] ; ?>">
		<FORM id=form1 name=form1>
			<table width="500" height="280" border="0" cellpadding="0" cellspacing="0" align="center">
			<tr>
				<td align="right" valign="top" background="/smember/payment/skin/mbasic/images/moneypay_img.gif"><table width="330" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td height="130">&nbsp;</td>
					</tr>
					<tr>
						<td align="center">오픈창이 뜨지 않았다면
						<input type=button onClick="<?php echo $href['onload'] ; ?>" value='오픈창띄우기'>
						를 <br>
						클릭하십시요.<br>
						<br>
						결제과정에서 문제가 발생하였다면, <br>
						종합질문페이지에 문의주시기 바랍니다 . <br>
						<br>
						<P align=center>고객선터 02-444-2945 <BR>
							<A href="mailto:help@mulkang.com">help@mulkang.com</A></P>
		</FORM>
		</td>
		</tr>
		</table>
		</td>
		</tr>
		</table>
	</body>
</html>
<?php
} // end func.
?>