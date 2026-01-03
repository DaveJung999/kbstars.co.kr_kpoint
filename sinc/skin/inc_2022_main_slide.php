<?php
//=======================================================
// 설 명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//		DATE	수정인		수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
//=======================================================
$HEADER = array(
	'usedb2' =>  1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>  1, // 템플릿 사용
	'useBoard2' =>  1, // privAuth()
	'useApp' =>  1
);

if( isset($_GET['html_skin']) ){
$HEADER['html_skin'] = $_GET['html_skin'];
}

require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// $seHTTP_REFERER는 어디서 링크하여 왔는지 저장하고, 로그인하면서 로그에 남기고 삭제된다.
	if( !isset($_SESSION['seUserid']) && !isset($_SESSION['seHTTP_REFERER']) && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$_SERVER["HTTP_HOST"]) === false ){
		$_SESSION['seHTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
	}
	//=======================================================
	// Start... (DB 작업 및 display)
	//=======================================================
	function get_player(){
		global $SITE, $GAMEINFO, $PlayerCateBoard, $DEBUG; // global 변수 추
	
		$oldGET = $_GET;
		$_GET = array(
			'skin' =>  "main_slide",
			'row_pern' =>  1
		);
		include("{$_SERVER['DOCUMENT_ROOT']}/sthis/sthis_player/profile_list.php");
		$_GET = $oldGET;
	}
	
	get_player();


?>

