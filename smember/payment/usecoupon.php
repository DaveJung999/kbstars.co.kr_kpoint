<?php
set_time_limit(0); // 회선때문에 중단되지 않도록..

//=======================================================
// 설	명 : 인터넷요금결제 - 포인트 사용 금액 입력(/smember/payment/usepoint.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/13
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/13 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' => 1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useCheck' => 1,
	'useApp' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 비회원로그인이더라도 로그인된 이후에
	if(!trim($_SESSION['seUid']) || !trim($_SESSION['seUserid'])){
		$seREQUEST_URI = $_SERVER['REQUEST_URI'];
		session_register('seREQUEST_URI');
		go_url("/sjoin/login.php");
		exit;
	}


	$thisPath	= dirname(__FILE__);
	//$thisUrl	= "/sthis/slist"; // 마지막 "/"이 빠져야함

	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_coupon		= $SITE['th'] . "shopcoupon";

	$dbinfo['skin']	= "basic";
	$form_default	= "method=post action='{$_SERVER['PHP_SELF']}'>
						<input type=hidden name=mode value=usepoint
						";

//===================
// $_GET['mode']값에 따른 처리
//===================
if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "usepoint"){
	$go_url=useCoupon_ok();
	back_close("", $go_url);
} // end if
//===================//

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 쿠폰 가져옴
$rs_coupon = db_query("SELECT * from {$table_coupon} WHERE bid='" . db_escape($_SESSION['seUid']) . "' and usedate=0 ORDER BY uid");
$html_option_coupon = "";
while($row=db_array($rs_coupon)){
	// 쿠폰 사용 기간이 지났으면
	if($row['todate'] < time()){
		$sql = "delete from {$table_coupon} where uid = '" . db_escape($row['uid']) . "'";
		db_query($sql); // 해당 쿠폰 삭제하여 버림
		continue;
	}

	$row['price'] = trim($row['price']);
	$row['point'] = trim($row['point']);
	if($row['price']){
		if( strval(intval($row['price'])) == $row['price'] ) { // 숫자이면
			$row['price'].="원";
		}
	}
	else $row['price'] = "0원";
	if($row['point']){
		if( strval(intval($row['point'])) == $row['point'] ) { // 숫자이면
			$row['point'].="원";
		}
	}
	else $row['point'] = "0원";

	$html_option_coupon.= "\n<option value='" . htmlspecialchars($row['uid']) . "'>" . htmlspecialchars($row['title']) . "(할인:{$row['price']},적립:{$row['point']})</option>";
} // end while
if(empty($html_option_coupon)) back_close("사용가능한 쿠폰이 없습니다");

$html_option_coupon = "<select name=coupon_uid>" .
							$html_option_coupon .
						"</select>";

// 포인트로 일부 혹은 전체 지불할 리스트
$result=db_query("SELECT * from {$table_payment} WHERE bid='" . db_escape($_SESSION['seUid']) . "' and status='입금필요' and price>0 and coupon_uid=0 ORDER BY idate, ordertable DESC");
$html_option_payment = "";
while($row=db_array($result)){
	// 쇼핑몰 상품인지 체크
	if(!preg_match("/^shop\_/", $row['ordertable'])) continue;

	$row['rdate']	= date("Y-m-d",$row['rdate']);
	$row['price']	= number_format($row['price']);

	$html_option_payment .= "\n<option value='" . htmlspecialchars($row['uid']) . "'>" . htmlspecialchars($row['rdate']) . " : " . htmlspecialchars($row['title']) . " : 금액 {$row['price']}원</option>";
} // end while
$html_option_payment = "<select name=payment_uid>" .
							$html_option_payment .
						"</select>";

// 템플릿 마무리 할당
$tpl->set_var('html_option_coupon', $html_option_coupon);
$tpl->set_var('html_option_payment', $html_option_payment);
$tpl->set_Var('form_default', $form_default);

$val="\\1skin/" . htmlspecialchars($dbinfo['skin']) . "/images/";
echo preg_replace("/([\"|'])images\//", "{$val}", $tpl->process('', 'html', 1)); // 1 mean loop

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function useCoupon_ok()
{
	global $SITE, $table_payment, $table_coupon;
	$qs=array(
				"coupon_uid" =>	"post,trim,checkNumber=" . urlencode("쿠폰을 선택바랍니다."),
				"payment_uid" => "post,trim,checkNumber=" . urlencode("지불할 내역을 선택바랍니다.")
		);
	$qs=check_value($qs);

	// 해당 쿠폰이 있는지 체크
	$sql = "SELECT * from {$table_coupon} where uid='" . db_escape($qs['coupon_uid']) . "' and usedate=0";
	$coupon = db_arrayone($sql) or back("해당 쿠폰이 없거나 이미 사용하였습니다.");
	if($coupon['todate'] < time()) back("쿠폰 사용 기간이 만료되어 사용하실 수 없습니다");

	// 해당 지불정보 체크
	$sql = "SELECT * from {$table_payment} where uid='" . db_escape($qs['payment_uid']) . "' and price>0 and coupon_uid=0";
	$payment = db_arrayone($sql) or back("해당 상품이 없거나 이미 다른 쿠폰 적용을 받았습니다");

	// 해당 상품 정보 가져오기
	if(!preg_match("/^shop\_/", $payment['ordertable']))
		back("해당 쿠폰은 선택하신 상품에서 사용하실 수 없습니다");
	$table_shop = $SITE['th'] . "shop_" . substr($payment['ordertable'],5);
	$sql = "SELECT * from {$table_shop} where uid='" . db_escape($payment['orderuid']) . "'";
	$payment['shop'] = db_arrayone($sql) or back("해당 쿠폰은 선택하신 상품에서 사용하실 수 없습니다");

	// 해당 쿠폰을 선택한 상품에서 사용하실 수 있는지 체크
	// 1 . 최저 사용 금액 설정된 경우
	if($coupon['minprice'] > 0 and $coupon['minprice'] > $payment['price'])
		back("해당 쿠폰은 {$coupon['minprice']}원 이상에서 사용가능합니다");
	// 2 . 특정 상품에서만 사용 가능한 상품인 경우
	if($coupon['couponserial'] > 0 and $coupon['couponserial'] != $payment['shop']['couponserial'])
		back("해당 쿠폰은 특정 상품에서만 적용됩니다 . \\n선택하신 상품에는 적용되지 않습니다.");

	// 할인 금액 계산
	$coupon['price'] = trim($coupon['price']);
	$coupon['point'] = trim($coupon['point']);
	if($coupon['price']){
		if( strval(intval($coupon['price'])) != $coupon['price'] ) { // %(백분율)이 붙었으면
			$coupon['price'] = intval($coupon['price']);
			$coupon['price'] = intval($payment['price'] * $coupon['price'] / 100); // 할인금액구함

			// 할인 금액이 maxprice보다 크다면 maxprice
			if($coupon['maxprice'] > 0 and $coupon['maxprice'] < $coupon['price']){
				$coupon['price'] = $coupon['maxprice'];
			}
		}

		// 쿠폰 할인 금액이 물건 가격보다 높거나 같다면
		if($coupon['price'] >= $payment['price']) back("할인쿠폰은 {$payment['price']}보다 큰 상품에서 사용가능합니다");

		$coupon['price'] = 0 - intval($coupon['price']); // 할인이니 마이너스로

	}
	else $coupon['price'] = 0;
	// 적립 금액 계산
	if($coupon['point']){
		if( strval(intval($coupon['point'])) == $coupon['point'] ) { // 숫자이면
			$coupon['point'] = intval($coupon['point']);
		} else { // %(백분율)이 붙었으면
			$coupon['point'] = intval($coupon['point']);
			$coupon['point'] = intval($payment['point'] * $coupon['point'] / 100); // 할인금액구함

			// 할인 금액이 maxpoint보다 크다면 maxpoint
			if($coupon['maxpoint'] > 0 and $coupon['maxpoint'] < $coupon['point']){
				$coupon['point'] = $coupon['maxpoint'];
			}
		}
	}
	else $coupon['point'] = 0;

	// payment에 삽입
	$sql="INSERT INTO `{$table_payment}` SET
			`bid`		='" . db_escape($_SESSION['seUid']) . "',
			`userid`	='" . db_escape($_SESSION['seUserid']) . "',
			`ordertable`='shopcoupon',
			`orderuid`	='" . db_escape($coupon['uid']) . "',
			`title`		='쿠폰(" . db_escape($coupon['uid']) . ")사용:" . db_escape($coupon['title']) . "',
			`price`		='" . db_escape($coupon['price']) . "',
			`year`		='" . db_escape($payment['year']) . "',
			`month`		='" . db_escape($payment['month']) . "',
			`rdate`		='" . db_escape($payment['rdate']) . "',
			`status`	='입금필요'
		";
	db_query($sql);

	// 쿠폰 사용 등록 - coupon 테이블(해당상품 uid)
	$sql = "update {$table_coupon} set usedate=UNIX_TIMESTAMP(), payment_uid='" . db_escape($payment['uid']) . "' where uid='" . db_escape($coupon['uid']) . "'";
	db_query($sql);

	// 쿠폰 사용 등록 - payment 테이블(쿠폰고유번호 넣음)
	$sql = "update {$table_payment} set coupon_uid='" . db_escape($coupon['uid']) . "' where uid='" . db_escape($payment['uid']) . "'";
	db_query($sql);

	return "/smember/payment";
}

?>