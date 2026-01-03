<?php
//=======================================================
// 설	명 : dmail의 email 체크(emailcheck.php) - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/06/12
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, db_* 함수 적용
// 03/06/12	박선민		마지막 수정
//=======================================================
$HEADER=array(
	'priv' => 30, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'html_echo' => '' // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST'] ?? '');

$debug = 1 ; // DEBUG
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	global $SITE; // $mysqli 전역변수 제거
	$table		= "{$SITE['th']}board2_regtelemail";//	. $_GET['db'];

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
	header ("Cache-Control: cache, must-revalidate");	
	header ("Pragma: no-cache");	
	header ('Content-type: application/vnd.ms-excel'); // 보다 표준적인 Excel MIME 타입으로 변경
	header ("Content-Disposition: attachment; filename=regtelemail.xls" );
	header ("Content-Description: PHP Generated Data" ); // INTERBASE 제거

	$fieldlist = array(
				'userid',
				'email',
				'data1',
				'data2',
				'data3',
				'data4',
				'content',
				'rdate'
			);
	
	$sql = " SELECT userid, email, data1, data2, data3, data4, content, rdate FROM {$table} ";
	$rs = db_query($sql); // db_query 함수 사용
	
	if (!$rs) {
		// db_error 함수를 사용하여 DB 쿼리 실패 처리
		db_error('쿼리 실패: 데이터 조회 오류', $sql);
	}
	$total = db_count($rs); // db_count 함수 사용

	xlsBOF();	// begin Excel stream
	xlsWriteLabel(0,0,"이름");
	xlsWriteLabel(0,1,"이메일");
	xlsWriteLabel(0,2,"주민등록번호1");
	xlsWriteLabel(0,3,"주민등록번호2");
	xlsWriteLabel(0,4,"구분-병역설계");
	xlsWriteLabel(0,5,"구분-병무정보서비스");
	xlsWriteLabel(0,6,"핸드폰번호");
	xlsWriteLabel(0,7,"등록일");

	for($i=0; $i<$total;$i++){
		$list = db_array($rs); // db_array 함수 사용
		
		// list['rdate']가 존재하고 0이 아닌 경우에만 date 포맷 적용
		if(isset($list['rdate']) && $list['rdate']){
			$list['rdate'] = date("Y/m/d", $list['rdate']);
		}

		for ($j=0; $j<count($fieldlist); $j++){
			$fieldName = $fieldlist[$j];
			$temp = $list[$fieldName] ?? ''; // 널 병합 연산자 사용 (PHP 7+)
			xlsWriteLabel($i+1, $j, $temp);
		}
	}
	db_free($rs); // db_free 함수 사용

	xlsEOF(); // close the stream
	exit; // 스크립트 종료

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// ----- begin of function library -----
// Excel begin of file header
function xlsBOF(){
	echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
	return;
}
// Excel end of file footer
function xlsEOF(){
	echo pack("ss", 0x0A, 0x00);
	return;
}
// Function to write a Number (double) into Row, Col
function xlsWriteNumber($Row, $Col, $Value){
	echo pack("sssss", 0x203, 14, $Row, $Col, 0x0);
	echo pack("d", $Value);
	return;
}
// Function to write a label (text) into Row, Col
function xlsWriteLabel($Row, $Col, $Value ){
	$L = strlen($Value);
	echo pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L);
	echo $Value;
	return;
}
// ----- end of function library -----
?>