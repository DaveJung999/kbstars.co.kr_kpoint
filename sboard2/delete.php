<?php
//=======================================================
// 설 명 : 게시판2 각종비밀번호 입력페이지(delete.php)
// 책임자 : 박선민 , 검수: 05/01/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	 수정인					수정 내용
// -------- ------ --------------------------------------
// 05/01/12 박선민 추가수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useBoard2' => 1, // board2CateInfo()
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic = href_qs();
	
	// table
	$table_dbinfo	= $SITE['th'].$prefix.'info';
	
	// 3. info 테이블 정보 가져와서 $dbinfo로 저장
	if(isset($_GET['db'])){
		$sql = "SELECT * FROM {$table_dbinfo} WHERE db='{$_GET['db']}' LIMIT 1";
		$dbinfo=db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');
				
		$dbinfo['table']	= $SITE['th'].$prefix.'_'.$dbinfo['db']; // 게시판 테이블
	}
	else back('DB 값이 없습니다');

	//====================== 
	// 4. 카테고리 정보 구함
	//====================== 
	if($dbinfo['enable_cate'] == 'Y' and $dbinfo['enable_cateinfo'] == 'Y'){
		$cateinfo=board2CateInfo($dbinfo, (isset($_REQUEST['cateuid']) ? $_REQUEST['cateuid'] : ''), CATEINFO_ONLY);
				
		// 카테고리 정보에 따른 dbinfo 변수 변경
		if( isset($cateinfo['skin']) and is_file($thisPath.'skin/'.$cateinfo['skin'].'/list.html') )
			$dbinfo['skin']		= $cateinfo['skin'];
		if(isset($cateinfo['html_type']))	{
			$dbinfo['html_type']	= $cateinfo['html_type'];
			if( isset($cateinfo['html_skin']) and is_file($SITE['html_path'].'index_'.$cateinfo['html_skin'].'.php') )
				$dbinfo['html_skin']	= $cateinfo['html_skin'];
			$dbinfo['html_head']		= $cateinfo['html_head'];
			$dbinfo['html_tail']		= $cateinfo['html_tail'];
		}
	} // end if
	//===================

	// 5. 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo'] == 'Y'){
		// skin 변경
		if( isset($_GET['skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['skin'])
					and is_file($thisPath.'skin/'.$_GET['skin'].'/list.html') ){
			if($dbinfo['enable_getinfoskins']) { // 특정 스킨만 get값으로 사용할 수 있도록 했다면
				$aTmp = explode(',',$dbinfo['enable_getinfoskins']);
				foreach($aTmp as $v){
					if($v == $_GET['skin']) $dbinfo['skin']	= $_GET['skin'];
				}
			}
			else $dbinfo['skin']	= $_GET['skin'];
		}
		// 사이트 해더테일 변경
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['html_skin'])
			and is_file($SITE['html_path'].'index_'.$_GET['html_skin'].'.php') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
	}

	// 6. $form_default 값 결정
	$action = isset($_GET['passwdgoto']) ? $_GET['passwdgoto'] : $thisUrl.'ok.php';
	$method = isset($_GET['method']) ? $_GET['method'] : 'POST';
	$form_default	= " ACTION='{$action}' method='{$method}'>";
	$form_default	.= substr(href_qs('passwdgoto=&method=',$qs_basic,1),0,-1);

	// 7. URL Link..
	$href['list'] = $thisUrl.'list.php?'.$qs_basic;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->tie_var('form_default'	,$form_default);
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('href'			,$href);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
