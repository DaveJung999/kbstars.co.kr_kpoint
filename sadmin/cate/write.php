<?php
//=======================================================
// 설	명 : 즐겨찾기관리 -추가/수정 - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/27
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, db_* 함수 적용, 보안 강화
// 05/01/27	박선민		마지막 수정
//=======================================================
$HEADER=array(
	'private' => 1, // 브라우저 캐쉬
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 1 . 넘어온값 체크
$mode = $_GET['mode'] ?? 'write';

// 2 . $dbinfo 가져오기
include_once($thisPath.'config.php');
global $SITE;

// 3 . 기본 URL QueryString
$qs_basic	= 'mode=&limitno=&limitrows=&time=';
if(($_GET['getinfo'] ?? '') != 'cont') {
	$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
}
// - uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
$skip_fields = array('uid');
if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
	foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
}		
$qs_basic		= href_qs($qs_basic);

// 4 . SQL문 where절
$sql_where = ' 1 ';

// 5 . 수정모드라면
if($mode === 'modify'){
	$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
	$result = db_query("SELECT * FROM {$dbinfo['table']} WHERE uid={$uid} AND $sql_where LIMIT 1");
	$list = db_array($result);
	db_free($result);

	if (!$list) {
		back('해당 데이터가 없습니다.');
	}

	// 수정 권한 체크
	if(!privAuth($dbinfo,'priv_modify',(int)($list['bid'] ?? 0)) ){
		if(($list['bid'] ?? 0) > 0 and (($list['bid'] != ($_SESSION['seUid'] ?? null)) or 'nobid' === substr($dbinfo['priv_modify'],0,5)) )
			back('수정하실 권한이 없습니다.');
	}

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'bid', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			if (isset($list[$value])) {
				$list[$value]	= htmlspecialchars($list[$value],ENT_QUOTES, 'UTF-8');
			}
		}
	}
	////////////////////////////////

	$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
	$form_default .= href_qs('mode=modify&rdate='.($_GET['rdate'] ?? ''),$qs_basic,1);
	$form_default = substr($form_default,0,-1);
} else {
	// write 모드라면
	if(!privAuth($dbinfo, 'priv_write',1))
		back('글을 작성하실 권한이 없습니다.');

	$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
	$form_default .= href_qs('mode=write&phpsess='.substr(session_id(),0,-5),$qs_basic,1);
	$form_default = substr($form_default,0,-1);

	$list = array (
		'skin' => 'basic',
		'gid' => 0,
		'cate_depth' => 0,
	'priv_list' => '',
	'priv_catemanage' => '운영자',
		'enable_priv' => 'Y',
		'enable_hide' => 'Y',
	'html_headpattern' => 'no',
	'html_headtpl' => 'basic'
	);

	$list['mode'] = 'write';
	$list['docu_type'] = $dbinfo['default_docu_type'] ?? '';
	if(isset($dbinfo['default_title'])) $list['title'] = $dbinfo['default_title'];
	if(isset($dbinfo['default_content'])) $list['content'] = $dbinfo['default_content'];
}

$list['docu_type_checked'] = (strtolower($list['docu_type'] ?? '') == 'html') ? ' checked ' : '';
if(!($mode === 'modify' and ($list['bid'] ?? null) != ($_SESSION['seUid'] ?? null))){
	switch($dbinfo['enable_userid'] ?? ''){
		case 'name':
			$list['userid'] = $_SESSION['seName'] ?? '';
			break;
		case 'nickname':
			$list['userid'] = $_SESSION['seNickname'] ?? '';
			break;
		default:
			$list['userid'] = $_SESSION['seUserid'] ?? '';
			break;
	}
	$list['email'] = ($_SESSION['seEmail'] ?? null) ? $_SESSION['seEmail'] : ($list['email'] ?? '');
}

$href['list'] = $thisUrl.'list.php?'.$qs_basic;

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if(!is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile)) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic'));
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// TODO
$list['skin_option'] = userGetSkinList($list['skin'] ?? '',$_SERVER['DOCUMENT_ROOT'].'/scate/skin');
$list['html_skin_option'] = userGetSiteSkin($list['html_skin'] ?? '');

userEnumSetFieldsToOptionTag($dbinfo['table'],$list);

$tpl->tie_var('list',$list);
$tpl->set_var('form_default',$form_default);

$tpl->set_var('session.seUserid',$_SESSION['seUserid'] ?? '');
$tpl->set_var('session.seName',$_SESSION['seName'] ?? '');
$tpl->set_var('session.seNickname',$_SESSION['seNickname'] ?? '');
$tpl->tie_var('get',$_GET);
$tpl->tie_var('dbinfo',$dbinfo);
$tpl->tie_var('href',$href);
$tpl->set_var('mode_'.$mode,true);

if(($dbinfo['enable_priv'] ?? 'N') === 'Y') $tpl->process('priv','priv');

$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function userGetAppendFields(string $table, array $skip_fields = []) {
	if (empty($table)) return false;
	$result = db_query("SHOW COLUMNS FROM {$table}");
	if (!$result) return false;

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result);

	return isset($fieldlist) ? $fieldlist : false;
}

function userEnumSetFieldsToOptionTag(string $table, array &$list) {
	$table_def = db_query("SHOW FIELDS FROM {$table}");
	if(!$table_def) return;

	while($row_table_def = db_array($table_def)){
		$field = $row_table_def['Field'];
		$row_table_def['True_Type'] = preg_replace('/\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type'] == 'enum')
			$aFieldValue = array($list[$field] ?? null);
		elseif($row_table_def['True_Type'] == 'set')
			$aFieldValue = explode(',', $list[$field] ?? '');
		else continue;
		
		$return	= '';
		$enum	= str_replace($row_table_def['True_Type'].'(', '', $row_table_def['Type']);
		$enum	= preg_replace('/\)$/', '', $enum);
		$enum	= explode('\',\'', substr($enum, 1, -1));
		foreach ($enum as $enum_atom){
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			if ((isset($list[$field]) && in_array($enum_atom,$aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
								 && $enum_atom == $row_table_def['Default'])){
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom) . "</option>\n";
		}
		$list[$field.'_option'] = $return;
	}
	db_free($table_def);
}

function userGetSkinList(string $skin, string $dir) {
	if(empty($dir)) $dir = dirname(__FILE__) . '/skin';
	if(!is_dir($dir)) return false;
	$files = scandir($dir);
	if ($files === false) return false;
	$rt_str = '';
	sort($files);
	foreach ($files as $file){
		if(($file != ".") && ($file != "..") && is_dir($dir . '/' . $file)){
			if($skin == $file)
				$rt_str .= "<option value='{$file}' selected>{$file}</option>";
			else
				$rt_str .= "<option value='{$file}'>{$file}</option>";
		}
	}
	return $rt_str;
}

function userGetSiteSkin(string $skin) {
	global $SITE;
	$path = $SITE['html_path'] ?? $_SERVER['DOCUMENT_ROOT'] . '/stpl/basic';
	if(!is_dir($path)) return false;
	$files = scandir($path);
	if ($files === false) return false;
	$rt_str = '';
	sort($files);
	foreach ($files as $entry){
		if(preg_match('/^index\_[a-z0-9_-]+\.php$/i',$entry)){
			$entry = substr($entry,6,-4);
			if($skin == $entry)
				$rt_str .= "<option value='{$entry}' selected>{$entry}</option>";
			else
				$rt_str .= "<option value='{$entry}'>{$entry}</option>";
		}
	}
	return $rt_str;
}
?>