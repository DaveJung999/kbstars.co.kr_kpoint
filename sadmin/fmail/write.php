<?php
//=======================================================
// 설 명 : 글쓰기/수정(write.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/04/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	 수정인				수정 내용
// -------- ------ --------------------------------------
// 04/04/14 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'private' => 1,
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	);
// time값이 3시간 지났으면, 다시 불러들임
if(isset($_GET['time']) && $_GET['time'] < time()-10800) header("Location: {$_SERVER['REQUEST_URI']}&time=".time());
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath		= dirname(__FILE__);
$thisUrl		= '.'; // 마지막 "/"이 빠져야함

	// 기본 URL QueryString
	$qs_basic		= href_qs();

	include_once("{$thisPath}/config.php"); // $dbinfo 정의

	//===================
	// SQL문 where절 정리
	//===================
	if(!isset($sql_where) || !$sql_where) $sql_where= " 1 ";

	// 글 수정하기/ 글 답변하기라면...
	if(isset($_GET['mode']) && $_GET['mode'] == 'modify'){
		$sql = "SELECT *, password(rdate) as private_key FROM {$dbinfo['table']} WHERE uid='{$_GET['uid']}' and $sql_where LIMIT 1";
		$list = db_arrayone($sql) or back("게시물의 정보가 없습니다");

		$list['title'] = htmlspecialchars($list['title'],ENT_QUOTES);
		$list['content'] = htmlspecialchars($list['content'],ENT_QUOTES);
		/////////////////////////////////
		// 추가되어 있는 테이블 필드 포함
		$skip_fields = array('passwd', 'db', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
		if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
			foreach($fieldlist as $value){
				$list[$value]	= htmlspecialchars($list[$value],ENT_QUOTES);
			}
		}
		////////////////////////////////
		
		// 인증 체크
		if( (isset($list['bid']) && $list['bid'] == 0) or (isset($list['bid']) && $list['bid'] == $_SESSION['seUid']) or boardAuth($dbinfo, "priv_delete", 1) ){
			// nothing...
		}
		else back("글쓴이가 아니면 수정을 하실수 없습니다.");
	} else {
		// 인증 체크
		if(!boardAuth($dbinfo, "priv_write",1)) back("글쓰기 권한이 없습니다(레벨부족)");
		if(!isset($_GET['mode'])) $_GET['mode']="write";
	}

	$form_default = " method='post' action='{$thisUrl}/ok.php' ENCTYPE='multipart/form-data'>";
	$private_key = isset($list['private_key']) ? $list['private_key'] : '';
	$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
	$form_default .= substr(href_qs("mode={$mode}&private_key={$private_key}",$qs_basic,1),0,-1);

	// URL Link...
	$href['list'] = './list.php?' . href_qs('',$qs_basic);

	// 넘어온 값에 따라 $dbinfo값 변경
	if(isset($dbinfo['enable_getinfo']) && $dbinfo['enable_getinfo'] == 'Y'){
		if(isset($_GET['cut_length']))	$dbinfo['cut_length']	= $_GET['cut_length'];
		if(isset($_GET['pern']))			$dbinfo['pern']		= $_GET['pern']; 

		// skin관련
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['html_skin']) 
			and is_file(isset($SITE['html_path']) ? $SITE['html_path'] : '' .'index_'.$_GET['html_skin'].'.php') )	
			$dbinfo['html_skin'] = $_GET['html_skin'];
		if( isset($_GET['skin']) and preg_match("/^[_a-z0-9]+$/",$_GET['skin']) 
			and is_dir($thisPath.'/skin/'.$_GET['skin']) )
			$dbinfo['skin']	= $_GET['skin'];
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.(isset($dbinfo['skin']) ? $dbinfo['skin'] : '').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.(isset($dbinfo['skin']) ? $dbinfo['skin'] : '')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
if( !((isset($_GET['mode']) && $_GET['mode'] == "modify") and (isset($list['bid']) && $list['bid'] != (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : null))) ){
	switch(isset($dbinfo['enable_userid']) ? $dbinfo['enable_userid'] : null){
		case 'name'		: {$list['userid']} = $_SESSION['seName']; break;
		case 'nickname'	: {$list['userid']} = $_SESSION['seNickname']; break;
		default			: {$list['userid']} = $_SESSION['seUserid']; break;
	}
	$list['email']	= isset($_SESSION['seEmail']) ? $_SESSION['seEmail'] : (isset($email) ? $email : null);
}

$tpl->set_var('list',(isset($list) ? $list : null));
$tpl->set_var('dbinfo',(isset($dbinfo) ? $dbinfo : null));
$tpl->set_var('href',(isset($href) ? $href : null));
$tpl->set_var('form_default',(isset($form_default) ? $form_default : null));

// 블럭 : 사용자 정보
if(isset($_SESSION['seUid'])) $tpl->process('USERINFO','userinfo');
else $tpl->process('USERINFO','nouserinfo');

// 블럭 : 파일 업로드
if((isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'Y') or (isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'multi'))	
	$tpl->process('UPLOAD','upload',TPL_OPTIONAL);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>
