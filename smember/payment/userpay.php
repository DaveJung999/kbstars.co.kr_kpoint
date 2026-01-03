<?php
//=======================================================
// 설	명 : 고객입력 요금 청구
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/19 박선민 처음
// 25/08/13 Gemini	PHP7 및 mariadb 11 버전 업그레이드 대응
//=======================================================
	$HEADER=array(
		'private' => 1,
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useSkin' =>  1, // 템플릿 사용
		'useBoard2' => 1, // 보드관련 함수 포함
		'useCheck' => 1,
		'useApp' => 1,
	);
	require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 비회원로그인이더라도 로그인된 이후에
	if(!isset($_SESSION['seUid']) || !isset($_SESSION['seUserid']) || !trim($_SESSION['seUid']) || !trim($_SESSION['seUserid'])){
		$seREQUEST_URI = $_SERVER['REQUEST_URI'];
		session_start();
		$_SESSION['seREQUEST_URI'] = $seREQUEST_URI;
		go_url("/sjoin/login.php");
		exit;
	}
	
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "/smember/payment"; // 마지막 "/"이 빠져야함

	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_userinfo		= $SITE['th'] . "userinfo";
	
	$dbinfo	= array(
					'skin' =>  "basic",
					'enable_getinfo' =>  "Y",
					'html_type' =>  "no",
					'html_skin' =>  "basic"
				);
	// 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo'] == 'Y'){
		// skin관련
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) and preg_match('/^[_a-z0-9]+$/i',$_GET['html_skin'])
			and is_file($SITE['html_path'].'index_'.$_GET['html_skin'].'.php') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
		if( isset($_GET['skin']) and preg_match("/^[_a-z0-9]+$/i",$_GET['skin'])
			and is_dir($thisPath.'/skin/'.$_GET['skin']) )
			$dbinfo['skin']	= $_GET['skin'];
	}

	
	$form_default	= "method=post action='ok.php'>
						<input type=hidden name=mode value=userpay		
						";
	
	// 회원 휴대폰 번호
	$sql = "select hp from {$table_userinfo} where bid='{$_SESSION['seUid']}'";
	$userinfo = db_arrayone($sql);
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
	$tpl->set_var('form_default', $form_default);
	$tpl->set_var('list.name'	, $_SESSION['seName']);
	$tpl->set_var('list.tel'	, $userinfo['hp']);

// 마무리
	$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
