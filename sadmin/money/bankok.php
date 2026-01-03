<?php
//=======================================================
// 설 명 : 관리자페이지 - 무통장입금처러(money/bankinput.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/14
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/11/14 박선민 마지막 수정
// 25/08/13 Gemini	PHP7 및 mariadb 11 버전 업그레이드 대응
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
	// table
	$table_payment	= $SITE['th'] . "payment";
	$table_unconfirm= $SITE['th'] . "payment_unconfirm";

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
##	미확인 입금 리스트 처리 부분
##	
##	아이디를 체크한다.
##	unconfirm 테이블에서 입금 정보를 불러들인다.
##	입금 처리를 한 후 unconfirm 테이블에서 정보를 삭제한다.
	if(isset($_POST['submit']) && $_POST['submit']=="미확인처리") {
		$qs=array(	
			'price'		 =>	"post,trim,notnull=" . urlencode("요금이 0원일 수 없습니다."),
			'bank'		 =>	"post,trim,notnull=" . urlencode("입금방법이 입력되지 않았습니다."),
			'receiptor'	 =>	"post,trim",
			'comment'	 =>	"post,trim",
			'inputdate'	 =>	"post,trim,notnull=" . urlencode("입금 날자가 입력되지 않았습니다.")
		);
		$qs=check_value($qs);
		
		$qs['idate'] = strtotime($qs['inputdate']);
		$sql = "INSERT INTO {$table_unconfirm} set
					bid		= '{$_SESSION['bid']}',
					price	= '{$qs['price']}',
					idate	= '{$qs['idate']}',
					rdate	= UNIX_TIMESTAMP(),
					bank	= '{$qs['bank']}',
					receiptor= '{$qs['receiptor']}',
					comment	= '{$qs['comment']}'
				";
					
		db_query($sql);
		go_url("./bankinput.php?bank={$qs['bank']}&inputdate={$qs['inputdate']}");
	}

##	입금 처리 부분
	if(isset($_POST['mode']) && $_POST['mode']=="input_ok") {
		$qs=array(	
			'price'		 =>	"post,trim,notnull=" . urlencode("요금이 0원일 수 없습니다."),
			'bank'		 =>	"post,trim,notnull=" . urlencode("입금방법이 입력되지 않았습니다."),
			'receiptor'	 =>	"post,trim",
			'comment'		 =>	"post,trim",
			'inputdate'	 =>	"post,trim,notnull=" . urlencode("입금 날자가 입력되지 않았습니다.")
		);
		$qs=check_value($qs);

		$qs['idate']	= strtotime($qs['inputdate']);
		if($qs['idate'] < strtotime("2003-1-1")) back("입금날자가 잘못되었습니다"); // 11월 13일 이후에 개발되었는데? 그 이전이라고?


		$sqls = array(); // init
		$totalprice = 0;
		$this_bid	= -1;
		if(isset($_POST['payment_uid']) && is_array($_POST['payment_uid'])){
			foreach($_POST['payment_uid'] as $uid) {
				$sql	= "SELECT * FROM {$table_payment} WHERE uid={$uid}";
				$list	= db_arrayone($sql);

				if($this_bid==-1) {
					$this_bid = $list['bid'];
				}
				elseif($this_bid!=$list['bid']) back("다른 회원아이디를 함께 선택하셨습니다");

				$payment_period = (isset($_POST['period'][$uid]) && $_POST['period'][$uid]<1) ? 1 : $_POST['period'][$uid];
				$list['price'] = $list['price'] * $payment_period;

				$totalprice += $list['price'];

				// $status값 구함
				if(preg_match("/^shop\_/",$list['ordertable']) or $list['ordertable']=="배송료")
					$status="배송준비";
				else
					$status="입금완료";
				
				$sqls[] = "UPDATE {$table_payment} SET period={$payment_period}, price={$list['price']}, idate='{$qs['idate']}', bank='{$qs['bank']}', receiptor='{$qs['receiptor']}', status='{$status}' WHERE uid={$uid}";
			}
		}

		if($qs['price'] != $totalprice)
			back("입금한 금액과 선택하신 금액이 다름니다. 다시 선택해주십시요");

		foreach($sqls as $sql) db_query($sql);
		go_url("./bankinput.php?bank={$qs['bank']}&inputdate={$qs['inputdate']}");
	} // end if($mode=="input_ok")
?>
