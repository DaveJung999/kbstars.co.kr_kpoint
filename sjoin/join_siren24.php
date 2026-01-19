<?php
//=======================================================
// 설  명 : 회원 가입 폼(join.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?→ <?php 변환
//=======================================================
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 실명확인 후 넘어왔다면
	switch((int)$_POST['result']) {
		case 1:
			break;
		case 2:
			back('이름과 주민번호가 잘못되었습니다.','/sjoin/');
			break;
		case 3:
			back('입력된 주민번화와 성명은 실명확인을 할 수 없습니다.','/sjoin/');
			break;
		default : 
			back('이름과 주민번호를 정확히 입력하여주세요','/sjoin/');
	}
	
	$_SESSION['join_result']	= $_POST['result'];
	$_SESSION['join_jumin1']	= $_POST['jumin1'];
	$_SESSION['join_jumin2']	= $_POST['jumin2'];
	$_SESSION['join_name']	= $_POST['name'];
	
	go_url('join.php');
?>