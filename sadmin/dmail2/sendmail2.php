<?php
set_time_limit(0);
//=======================================================
// 설  명 : 샘플 메일 발송 처리 - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/30
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, mysqli->db_* 함수 변경
// 03/08/30	박선민		마지막 수정
// 25/11/10	Gemini AI	DB 함수 통일 (db_* 만 사용) 및 탭 들여쓰기 적용
//=======================================================
$HEADER = array (
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php"); // 'sin' -> 'sinc' 오타 수정
//page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================

/*
// 03/06/11
	// status
	NULL : 발송 가능
	FAIL : 발송 실패
	SEND : 발송됨

	// emailcheck
	9 : 발송 준비
	41 : 메일 가짜
	42 : 메일 유저 없음
	43 : 도메인미등록
	44 : 메일 서버 거부
	45 : 접속실패
*/
require ("./class_sendmail.php");

global $SITE; // mysqli 사용하지 않음

$db_name = $_GET['db'] ?? null;
if (!$db_name) {
	back("db값이 넘어오지 않았습니다");
}

$table_dmailinfo = ($SITE['th'] ?? '') . "dmailinfo";
$table_dmail	 = ($SITE['th'] ?? '') . "dmail_" . $db_name;

// [!] FIX: mysqli Prepared Statement 대신 db_arrayone 사용
$safe_db_name = db_escape($db_name);
$sql_info = "SELECT * FROM {$table_dmailinfo} WHERE db='{$safe_db_name}' LIMIT 1";
$dmailinfo = db_arrayone($sql_info);

if (!$dmailinfo) {
	back("메일 정보를 찾을 수 없습니다.");
}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$mail = new mime_mail;
$mail->from		= $dmailinfo['s_mail'] ?? '';
$mail->name		= $dmailinfo['s_name'] ?? '';
$mail_tpl		= $dmailinfo['tpl_yesno'] ?? 'N';
$mail_html		= $dmailinfo['h_yesno'] ?? 'N';
$mail->html	= 1;
$mail->subject	= $dmailinfo['title'] ?? '';
$mail_body 		= replace_string($dmailinfo['comment'] ?? '', $mail_html === 'Y' ? 'HTML' : 'TEXT');

##	전체 메일 발송
$sql_where = " status is null and emailcheck=0 ";
$sql = "SELECT count(*) as count FROM {$table_dmail} WHERE $sql_where ";
// [!] FIX: db_query 및 db_arrayone 사용
$row = db_arrayone($sql); 
$total_remaining = $row['count'] ?? 0;

echo "{$total_remaining} 명 남았습니다.\n<br>";
echo str_pad(" ", 256);
flush();

$sql = "SELECT * FROM {$table_dmail} WHERE $sql_where LIMIT 0, 500 ";
$rs_dmail = db_query($sql);

if (!$rs_dmail) {
	back('데이터베이스 조회에 문제가 발생하였습니다.');
}

$total = db_count($rs_dmail);
$rdate = time();


// 필드 목록을 루프 밖에서 미리 가져옴
$fields_to_replace = array();
$fields_result = db_query("SHOW COLUMNS FROM {$table_dmail}");

if($fields_result){
	while ($field_data = db_array($fields_result)) {
		$a_fields = $field_data['Field'];
		if( !in_array($a_fields, array('uid','status','emailcheck','readtime')) ){
			$fields_to_replace[] = $a_fields;
		}
	}
	db_free($fields_result);
}


for($i=0; $i<$total; $i++){
	$send = db_array($rs_dmail);
	if (!$send) break; // 레코드 끝 확인

	$mail->to = $send['email'] ?? '';
	
	$rpl_body = $mail_body;
	//본문 만들기
	if($mail_tpl === 'Y'){
		foreach ($fields_to_replace as $a_fields) {
			// preg_replace()로 변경
			$rpl_body = preg_replace("/\{{$a_fields}\}/i", ($send[$a_fields] ?? ''), $rpl_body);
		}
	}

	// 읽기 확인 루틴
	$check_read = "<img src='http://" . ($_SERVER['HTTP_HOST'] ?? '') . "/sjoin/dmail/check.php?db=". urlencode($db_name) ."&uid=". urlencode($send['uid'] ?? '') ."&email=". urlencode($send['email'] ?? '') ."' width=0 height=0 border=0> ";
	$mail->body = $check_read . $rpl_body;
	
	if($mail->send()) {
		$status = 'SEND';
	} else {
		$status = 'FAIL';
		sleep(2); // db server 부하를 줄이기 위해
	}

	// [!] FIX: UPDATE를 일반 db_query 사용으로 대체
	$sql_update = "UPDATE {$table_dmail} SET status='" . db_escape($status) . "' WHERE uid='{$send['uid']}' LIMIT 1";
	db_query($sql_update);


	if($i % 100 == 0){
		echo str_pad("\n<br>",256);
		flush();
	}
	$mail->parts = array();

	echo ".";
	//echo ($send['userid'] ?? '') . "$i 번째 : (" . ($send['email'] ?? '') . ")님에게 메일을 발송하였습니다.<br>";
}
db_free($rs_dmail);

if($total === 0){
	echo "메일 발송이 완료되었습니다. <font color='red'>3초후</font>에 이동합니다.";
	echo "<meta http-equiv='Refresh' content='3; URL=list.php'>";
	exit;
}
else {
	echo "<meta http-equiv='Refresh' content='0; URL=". ($_SERVER['PHP_SELF'] ?? '') ."?db=". urlencode($db_name) ."'>";
}
?>