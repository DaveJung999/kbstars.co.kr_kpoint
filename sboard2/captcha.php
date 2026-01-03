<?php
	@session_start();

	// 6자리의 랜덤 문자열 생성
	$ranStr = md5(microtime());
	$ranStr = substr($ranStr, 0, 6);

	// 세션에 보안 문자 저장
	$_SESSION['cap_code'] = $ranStr;

	// 배경 이미지 로드
	$newImage = imagecreatefromjpeg("cap_bg.jpg");

	// 텍스트 색상 할당 (검정색)
	$txtColor = imagecolorallocate($newImage, 0, 0, 0);

	// 이미지에 문자열 그리기
	imagestring($newImage, 5, 8, 3, $ranStr, $txtColor);

	// Content-Type 헤더 설정
	header("Content-type: image/jpeg");

	// 이미지 출력 및 메모리 해제
	imagejpeg($newImage);
	imagedestroy($newImage);
?>
