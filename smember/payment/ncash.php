<?php
//=======================================================
// 설	명 : NCASH서버에서 상품정보와 가격을 조회하는 페이지
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 2002/02/09 박선민 마지막 수정
// 2025/09/11 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
	$HEADER=array(
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	);
	require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

## Ready.. . (변수 초기화 및 넘어온값 필터링)
	/* Ncash 왈....
	// Ncash에서 결제 성공할 경우 직접 접근하여 처리함

		{$contentCode} 에 따라 상품에 대한 정보를 출력해 주시면 됩니다.
		{$contnetCode} 는 필요에 따라 장바구니 아이디 등으로 활용하시어 사용하시면 됩니다.
		이 페이지는 저희 billCrux 시스템에서 해당 가맹점의 Web Server 와 직접 연결 되는 페이지입니다.
		따라서 쿠키나 세션을 이용하시는 방법은 불가능합니다.
		브라우져를 이용하여 사용자가 처음 접근하여 생성한 쿠키나 세션은 이페이지에서 전혀 소용이 없습니다.
		=================================================================
		ContentCategoryCode (32 Byte,	char)	:	상품 종목 코드
		ContentCategoryName (128 Byte, char)	:	상품 종목 이름
		ContentCode (32 Byte, char)		:	상품 코드
		ContentName (32 Byte, char)		:	상품 이름
		PrimCost (integer)			:	상품 정가
		ContentPrice (integer)		:	판매가격
		=================================================================
	*/

	// 기존 PHP 4 전역 변수를 $_REQUEST로 교체
	$contentCode = $_REQUEST['contentCode'] ?? '';

	// 기존 PHP 4 전역 변수를 $_SERVER로 교체
	$phpSelf = $_SERVER['PHP_SELF'] ?? '';
	$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

	$table = $SITE['th'] . "payment_ncash";

## Start.. . (DB 작업 및 display)
// 날아온 메시지를 관리자 메일로 보냄
	@mail("sponsor@new21.com", $phpSelf, $remoteAddr . join(":",array_keys($_REQUEST)) . "<br>\n" .join(":",$_REQUEST));

	$sql = "SELECT * FROM " . db_escape($table) . " WHERE uid='" . db_escape($contentCode) . "'";
	$list = @db_arrayone($sql);

	// 결과가 없을 경우를 대비하여 빈 배열로 초기화
	$list = $list ?: [];

	echo "1" . ($list['contentcategorycode'] ?? '') . "|" . ($list['contentcategoryname'] ?? '') . "|" . db_escape($contentCode) . "|UCARI.COM-" . ($list['bid'] ?? '') . "|" . ($list['primcost'] ?? '') . "|" . ($list['contentprice'] ?? '');

	db_free($rs);
?>
