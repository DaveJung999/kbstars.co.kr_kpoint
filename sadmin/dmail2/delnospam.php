<?php
set_time_limit(0);
//=======================================================
// 설	명 : dmail의 email 체크(emailcheck.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/06/11 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/06/11 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$db_val = $_GET['db'] ?? '';
$table_dmailinfo	= $SITE['th'] . 'dmailinfo';
$table_dmailnospam	= $SITE['th'] . 'dmailnospam';
$table_dmail		= $SITE['th'] . 'dmail_' . db_escape($db_val);

$sql = "SELECT * FROM {$table_dmailinfo} WHERE db='".db_escape($db_val)."'";
$dmailinfo = db_arrayone($sql) or back("db값이 넘어오지 않았습니다");

//	header('Cache-Control: no-cache, must-revalidate');
//	header('Pragma: no-cache');
echo "수신거부 리스트 삭제 시작<br>\n";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$result = db_query("SELECT email FROM {$table_dmailnospam} WHERE yesmail=0");
$total = db_count($result);

$start_unixtime = time();
$start_date = date("Y년 m월 d일 H시 i분 s초", $start_unixtime);

echo "$start_date, {$total}개 스팸 메일 검사 시작합니다 . <br>";
for ($i = 0; $i < 300; $i++) {
	print (" ");
}
print ("\n");

$i = 0;
while ($row = db_array($result)) {
	$email = $row['email'];
	if(strlen($email) > 3) {
		echo "{$i} {$email} ->";
		$sql = "SELECT uid FROM {$table_dmail} WHERE email='".db_escape($email)."' LIMIT 1";
		$uid = db_resultone($sql, 0, "uid");
		if($uid) {
			echo "있음<br>";
			db_query("DELETE FROM {$table_dmail} WHERE email='".db_escape($email)."' LIMIT 1");
		} else {
			echo "x<br>";
		}
	}
	$i++;
} // end while

echo "\n<br><br>완료되었습니다.<br><br><br>\n";
echo "<a href='#' onClick='history.back();'>돌아가기</a>";

// db_close(); // PHP 7에서는 스크립트 종료 시 자동으로 연결이 닫힙니다.
?>