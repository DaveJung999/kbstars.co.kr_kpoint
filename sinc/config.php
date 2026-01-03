<?php
## 기본 환경 변수 (사이트 변경시 수정 필요)
// 04/07/19 박선민 마지막 수정
// 24/08/10 Gemini PHP 7+ 호환성 수정
// config.php 파일

// 해더 해킹 차단 동작
$SECURITY = array(
	'server_ip' =>  "117.52.31.195", // 기입할 경우 해더 해킹 동작
	'domain' =>  "new.kbstars.co.kr", // 기입할 경우 해더 해킹 동작
	'header_version' =>  "1" // 기입할 경우 해더 해킹 동작
);
//header_security();

// $HEADER 변수가 다른 곳에서 선언되었다고 가정합니다.
if(isset($HEADER) && ($HEADER['usedb2'] || $HEADER['usedb2'] || $HEADER['priv'] || $HEADER['log'])) { // DB를 사용한다면...

	$SECURITY['db_server']	= "localhost";
	$SECURITY['db_user']	= "root";
	$SECURITY['db_pass']	= "dnflsp1004!";
	$SECURITY['db_name']	= "kbstars";
}

// 사이트 기본 환경 변수
$SITE = array(
	'th' =>  "new21_",	// MySQL Table prefix
	'name' =>  "KB세이버스",
	'version' =>  "2.0.0",
	'webmaster' =>  "sendonly@kbstars.co.kr",
	'hp' =>  "0196959505",
	'company' =>  "KB국민은행",
	'debug' =>  1,
	'database' =>  $SECURITY['db_name'] ?? 'kbstars',
	'database2' =>  "savers_secret",
	'season' =>  '10',
	'tid' =>  '13'
);

$GAMEINFO = array(
	'season' =>  '10',
	'tid' =>  '13'
);

// 선수 카테고리를 위해서.....
$PlayerCateBoard = array(
	"cmletter",
	"cmmemo",
	"player",
	"roomgallery"
);

if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '59.3.40.149'){
	$DEBUG = true;
} else {
	$DEBUG = false;
}
?>
