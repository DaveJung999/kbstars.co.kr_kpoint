<?php
//=======================================================
// 설	명 : 읽기(read.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/09/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/09/07 박선민 마지막 수정
// 25/08/13 Gemini	PHP7 및 mariadb 11 버전 업그레이드 대응
//=======================================================
	$HEADER=array(
		'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useSkin' =>  1, // 템플릿 사용
		'useBoard2' => 1, // 보드관련 함수 포함
		'useApp' => 1
	);
	require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= "taxbuy"; // ???list.php ???write.ephp ???ok.php
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// $dbinfo
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	$dbinfo['table'] = $SITE['th'] . "comptaxbuy";

	// 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont")
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	//=================
	// 해당 게시물 읽음
	//=================
	if (isset($_GET['uid']) && isset($_SESSION['seUid'])) {
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$_GET['uid']}' and bid='{$_SESSION['seUid']}' ";
		$list=db_arrayone($sql) or back("게시물이 존재하지 않습니다.");
	} else {
		back("필수 정보가 누락되었습니다.");
	}

	// 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo'] == 'Y'){
		// skin 변경
		if( isset($_GET['skin']) and preg_match("/^[_a-z0-9]+$/i",$_GET['skin'])
					and is_file("{$thisPath}/skin/{$_GET['skin']}/read.html") ){
			if(isset($dbinfo['enable_getinfoskins'])) { // 사용 스킨을 제안했다면,
				$aTmp = explode(',',$dbinfo['enable_getinfoskins']);
				foreach($aTmp as $v){
					if($v == $_GET['skin']) $dbinfo['skin']	= $_GET['skin'];
				}
			}
			else $dbinfo['skin']	= $_GET['skin'];
		}
		// 사이트 해더테일 변경
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) and preg_match("/^[_a-z0-9]+$/i",$_GET['html_skin'])
			and is_file($thisPath.'skin/'.$_GET['skin'].'/list.html') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
	}

	//=================
	// 해당 게시물 처리
	//=================
	$list['rdate_date'] = date("Y/m/d", $list['rdate']);

	// URL Link...
	$href['list']	= "{$thisUrl}/list.php?" . href_qs("uid=",$qs_basic);
	$href['listdb'] = "list.php?db={$dbinfo['db']}";
	$href['write']	= "{$thisUrl}/write.php?" . href_qs("mode=write&time=".time(),$qs_basic);
	$href['modify']	= "{$thisUrl}/write.php?" . href_qs("mode=modify&uid={$list['uid']}&num={$list['num']}&time=".time(),$qs_basic);
	$href['delete']	= "{$thisUrl}/ok.php?" . href_qs("mode=delete&uid={$list['uid']}",$qs_basic);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
	$tpl->set_var('dbinfo'			,$dbinfo);// shopinfo 정보 변수
	$tpl->set_var('get'				,$_GET);
	$tpl->set_var('href'			,$href);
	$tpl->set_var('list'			,$list);

// 블럭 : 글쓰기
	if(siteAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');

// 블럭 : 글답변
	if(siteAuth($dbinfo, "priv_reply")) $tpl->process('REPLY','reply');

// 블럭 : 글수정,삭제
	if(siteAuth($dbinfo, "priv_delete") or $list['bid'] == $_SESSION['seUid'] or $list['bid'] == 0){
		$tpl->process('MODIFY','modify');
		$tpl->process('DELETE','delete');
	}

// 마무리
	$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
