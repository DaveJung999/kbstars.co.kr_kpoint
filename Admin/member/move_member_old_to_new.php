<?php
//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정 
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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

$table_logon = "{$SITE['th']}logon";
$table_userinfo = "{$SITE['th']}userinfo";
$table_member = "members";

$sql = "SELECT * from {$table_member} where sequence <> 9 order by sequence";
$rs_list = db_query($sql);

$cnt = db_count($rs_list);

for ($i = 0; $i < $cnt ; $i++){
	
	$list = db_array($rs_list);
	$uid = $i+3;
	if ($list['level'] == 2){
		$list['class'] = 'researcher';
		$list['level'] = 10;
	}
	else{
		$list['class'] = 'person';
		$list['level'] = 1;
	}
	
	$sql_insert = "INSERT INTO 
							`new21_logon` 
						VALUES 
							({$uid}, 
							'{$list['memberid']}', 
							md5('{$list['password']}'), 
							'{$list['name']}', 
							'{$list['name']}', 
							'{$list['email']}', 
							1, 
							'{$list['class']}', 
							{$list['level']}, 
							0) ";
	echo "<br>";
	db_query($sql_insert);

	echo $sql_insert_userinfo = "INSERT INTO 
							`$table_userinfo` 
								(`bid`, 
								`idnum`, 
								`job`, 
								`depart`, 
								`position`, 
								`tel`, 
								`hp`, 
								`c_tel`, 
								`c_fax`, 
								`zip`, 
								`address`, 
								`c_zip`, 
								`c_address`, 
								`homepage`, 
								`rdate`) 
							VALUES 
								({$uid}, 
								$list['ssn1']-{$list['ssn2']}', 
								'{$list['job']}', 
								'{$list['depart']}', 
								'{$list['part']}', 
								'{$list['tel']}', 
								'{$list['phone']}', 
								'{$list['job_tel']}', 
								'{$list['job_fax']}', 
								'{$list['home_post']}', 
								'{$list['home_addr1']} {$list['home_addr2']}', 
								'{$list['job_post']}', 
								'{$list['job_addr1']} {$list['job_addr2']}', 
								'{$list['homepage']}', 
								unix_timestamp('{$list['signdate']}'))";

	echo "<br>";
	db_query($sql_insert_userinfo);

} 

?>
