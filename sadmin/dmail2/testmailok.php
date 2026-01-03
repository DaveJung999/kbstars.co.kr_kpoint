<?php
set_time_limit(0);
//=======================================================
// 설  명 : 샘플 메일 발송 처리 - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/12/01
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, mysqli->db_* 함수 변경
// 04/12/01	박선민		마지막 수정
// 25/11/10	Gemini AI	DB 함수 통일 및 탭 들여쓰기 적용
//=======================================================
$HEADER = array (
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	require ("./class_sendmail.php");

	global $SITE; // $mysqli 객체 사용 대신 $db_* 함수 사용
	
	$db_name = $_POST['db'] ?? ($_REQUEST['db'] ?? null);
	if (!$db_name) {
		back("db값이 넘어오지 않았습니다");
	}

	$table_dmailinfo = ($SITE['th'] ?? '') . "dmailinfo";
	$table_dmail	 = ($SITE['th'] ?? '') . "dmail_" . $db_name;

	// [!] FIX: mysqli Prepared Statement 대신 db_arrayone 사용
	$sql_info = "SELECT * FROM {$table_dmailinfo} WHERE db='" . db_escape($db_name) . "' LIMIT 1";
	$dmailinfo = db_arrayone($sql_info);

	if (!$dmailinfo) {
		back("메일 정보를 찾을 수 없습니다.");
	}
	
	// db_query 함수가 $db_conn 객체를 필요로 하므로, 여기서 $db_conn이 전역으로 존재한다고 가정합니다.
	// (header.php에서 db_connect 및 db_select가 실행되었다고 가정)


//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$mail = new mime_mail;
$mail->from		= $dmailinfo['s_mail'] ?? '';
$mail->name		= $dmailinfo['s_name'] ?? '';
$mail_tpl		= $dmailinfo['tpl_yesno'] ?? 'N';
$mail_html		= $dmailinfo['h_yesno'] ?? 'N';
$mail->html		= 1;
$mail->subject	= $dmailinfo['title'] ?? '';
// replace_string 함수가 외부에서 정의되었다고 가정
$mail_body 		= replace_string($dmailinfo['comment'] ?? '', $mail_html === 'Y' ? 'HTML' : 'TEXT');

$sendnum = $sendnum ?? 1;
// [!] FIX: db_query 사용
$sql = "SELECT * FROM {$table_dmail} LIMIT 0, " . (int)$sendnum;
$rs = db_query($sql);

if (!$rs) {
	back('데이터베이스 접근과정에서 에러가 발생하였습니다.');
}
// [!] FIX: db_count 사용
if (db_count($rs) === 0) {
	back("메일링 리스트에 데이터가 하나도 없습니다.");
}

$total = db_count($rs);
$rdate = time();

// SHOW COLUMNS 쿼리를 루프 밖에서 미리 실행
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
	// [!] FIX: db_array 사용
	$send = db_array($rs); 
	if (!$send) break; // 레코드 끝 확인

	// [!] FIX: $email 변수가 정의되지 않았으므로 $send['email']로 가정
	$mail->to = $send['email'] ?? ''; 
	
	$rpl_body = $mail_body;
	
	//본문 만들기
	if($mail_tpl === 'Y'){
		foreach ($fields_to_replace as $a_fields) {
			// [!] FIX: preg_replace()로 변경
			$rpl_body = preg_replace("/\{{$a_fields}\}/i", ($send[$a_fields] ?? ''), $rpl_body);
		}
	}
	
	// 읽기 확인 루틴
	$mail->body = $rpl_body;	
	
	$mail->send();

	$mail->parts = array();
}
// [!] FIX: db_free 사용
db_free($rs);

echo "<br>메일 발송이 완료되었습니다. <font color='red'>3초후</font>에 이동합니다.";
echo "<meta http-equiv='Refresh' content='3; URL=list.php'>";
exit;
?>