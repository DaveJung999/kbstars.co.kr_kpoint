<?php
//=======================================================
// 설	명 : 특정 그룹의 회원 가입 리스트(memberlist.php.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/10/01 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		html => "" // html header 파일(skln/index_$HEADER['html'].php 파일을 읽음)
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_logon	= $SITE['th'] . "logon";
	$table_groupinfo= $SITE['th'] . "groupinfo";
	$table_joininfo	= $SITE['th'] . "joininfo";

	// 해당 그룹에 가입되어 있지 않다면 볼 수 없슴
	if($_GET['gid']){
		$rs_groupinfo	= db_query("SELECT * from {$table_groupinfo} where uid='{$_GET['gid']}'");
		$groupinfo	= db_count() ? db_array($rs_groupinfo) : back("해당 그룹은 존재하지 않습니다.","###");

		if(!$_SESSION['seGroup'][$_GET['gid']] and $groupinfo['bid'] != $_SESSION['seUid']){
			echo "해당 그룹의 회원리스트를 보실 수 없습니다.";
			exit;
		}
	} else {
		echo "그룹을 선택바랍니다.";
		exit;
	}

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 회원 리스트 구하기
$rs_joininfo = db_query("SELECT * from {$table_joininfo} WHERE gid='{$groupinfo['uid']}' order by rdate");
if(db_count()){
	while($rows=db_array($rs_joininfo)){
		$rows['logon']=db_array(db_query("SELECT * from {$table_logon} where uid='{$rows['bid']}'"));
		echo "{$rows['bid']} - {$rows['logon']['name']}<br>";
	} // end while
}
else {
	echo "리스트가 없습니다.";
} 

?>
