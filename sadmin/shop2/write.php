<?php
//=======================================================
// 설 명 : 심플리스트-추가/수정
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/11
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/11 박선민 마지막 수정
// 25/08/13 Gemini PHP 7+ 호환성 및 보안 강화
//=======================================================
$HEADER=array(
	'private'	 =>	1, // 브라우저 캐쉬
	'priv'		 =>	'', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 =>	1, // DB 커넥션 사용
	'useSkin'	 =>	1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 1. 넘어온값 체크
$mode = $_GET['mode'] ?? 'write';
$uid = $_GET['uid'] ?? null;
$getinfo = $_GET['getinfo'] ?? '';

// 2. $dbinfo 가져오기
include_once($thisPath.'config.php');

// 3. 기본 URL QueryString
$qs_basic	= 'mode=&limitno=&limitrows=&time=';
if($getinfo != 'cont')
	$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
// - uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
$skip_fields = array('uid');
$dbinfo_table = {$dbinfo['table']} ?? '';
if($fieldlist = userGetAppendFields($dbinfo_table, $skip_fields)) {
	foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
}
$qs_basic		= href_qs($qs_basic); // 해당값 초기화

// 4. SQL문 where절
$sql_where = ' 1 ';

// 5. 수정모드라면
if($mode == 'modify' && $uid) {
	// !! 중요: SQL 인젝션 방지를 위해 Prepared Statement 사용 !!
	$uid_safe = db_escape($uid);
	$sql = "SELECT * FROM {$dbinfo_table} WHERE uid='{$uid_safe}' AND $sql_where LIMIT 1";
	$result = db_query($sql);
	$list = db_array($result);
	db_free($result);

	if (!$list) {
		back('해당 데이터가 없습니다.');
	}

	// 수정 권한 체크
	if(!privAuth($dbinfo, 'priv_modify', (int)($list['bid'] ?? 0)) ) {
		if(($list['bid'] ?? 0) > 0 && (($list['bid'] ?? '') != ($_SESSION['seUid'] ?? null) || 'nobid' == substr(($dbinfo['priv_modify'] ?? ''), 0, 5)) )
			back('수정하실 권한이 없습니다.');
	}

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'bid', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo_table, $skip_fields)) {
		foreach($fieldlist as $value) {
			$list[$value] = htmlspecialchars($list[$value] ?? '', ENT_QUOTES);
		}
	}
	////////////////////////////////

	$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
	$form_default .= href_qs('mode=modify&uid='.$uid.'&rdate='.($list['rdate'] ?? ''), $qs_basic, 1);
	$form_default = substr($form_default, 0, -1);
}
else { // write 모드라면
	// 인증 체크
	if(!privAuth($dbinfo, 'priv_write', 1))
		back('글을 작성하실 권한이 없습니다.');

	// form_default
	$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
	$phpsess = session_id() ? substr(session_id(), 0, -5) : '';
	$form_default .= href_qs('mode=write&phpsess='.$phpsess, $qs_basic, 1);
	$form_default = substr($form_default, 0, -1);

	// 기본값 설정
	$list = userGetDefaultFromTable($dbinfo_table);
	$list['startdate'] = time();
	$list['mode'] = 'write';
	$list['docu_type'] = $dbinfo['default_docu_type'] ?? '';
	if(($dbinfo['default_title'] ?? null)) $list['title'] = $dbinfo['default_title'];
	if(($dbinfo['default_content'] ?? null)) $list['content'] = $dbinfo['default_content'];
}

// 6. 공통 할당
$list['docu_type_checked'] = (strtolower($list['docu_type'] ?? '') == 'html') ? ' checked ' : '';
if( !($mode == 'modify' && ($list['bid'] ?? 0) != ($_SESSION['seUid'] ?? null)) ) {
	switch($dbinfo['enable_userid'] ?? 'userid') {
		case 'name'		: {$list['userid']} = $_SESSION['seName'] ?? ''; break;
		case 'nickname'	: {$list['userid']} = $_SESSION['seNickname'] ?? ''; break;
		default			: {$list['userid']} = $_SESSION['seUserid'] ?? ''; break;
	}
	$list['email']	= ($_SESSION['seEmail'] ?? null) ? $_SESSION['seEmail'] : ($list['email'] ?? '');
}

if(isset($list['startdate'])) $list['startdate_date'] = date('Y-m-d H:i:s', (int)($list['startdate'] ?? time()));

// URL Link...
$href['list'] = $thisUrl.'list.php?'.$qs_basic;
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile = basename(__FILE__, '.php').'.html';
$skin = $dbinfo['skin'] ?? 'basic';
if( !is_file($thisPath.'skin/'.$skin.'/'.$skinfile) ) $dbinfo['skin'] = 'basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html', $skinfile, TPL_BLOCK);

// 스킨 리스트 가져오기
$list['skin_option'] = userGetSkinList($list['skin'] ?? '', $_SERVER['DOCUMENT_ROOT'].'/sshop2/skin');
$list['html_skin_option'] = userGetSiteSkin($list['html_skin'] ?? ''); // 사이트 스킨

// 템플릿 마무리 할당
userEnumSetFieldsToOptionTag($dbinfo_table, $list); // $list['필드_option']에 enum,set필드 <option>..</option>생성
$tpl->tie_var('list', $list);
$tpl->set_var('form_default', $form_default);

$tpl->set_var('session.seUserid', $_SESSION['seUserid'] ?? '');	// 로그인 userid
$tpl->set_var('session.seName', $_SESSION['seName'] ?? '');		// 로그인 이름
$tpl->set_var('session.seNickname', $_SESSION['seNickname'] ?? '');	// 로그인 별명
$tpl->tie_var('get', $_GET); 	// get값으로 넘어온것들
$tpl->tie_var('dbinfo', $dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('href', $href);	// 게시판 각종 링크
$tpl->set_var('mode_'.$mode, true);		// mode_write, mode_modify 값있게

// 블럭 : 권한 입력 부분
if(($dbinfo['enable_priv'] ?? 'N') == 'Y') $tpl->process('PRIV', 'priv');

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

/**
 * enum,set필드라면, $list['필드_option'] 만들어줌 (Modernized version)
 * @param string $table
 * @param array &$list
 */
function userEnumSetFieldsToOptionTag(string $table, array &$list){
	// SHOW FIELDS는 db_query를 사용하여 여러 행을 가져옵니다.
	$table_safe = db_escape($table);
	$table_def = db_query("SHOW FIELDS FROM {$table_safe}");
	if (!$table_def) {
		return;
	}

	while ($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		// preg_replace 수정: 괄호와 내부 내용만 제거하도록
		$row_table_def['True_Type'] = preg_replace('/\([^\)]*\)/', '', $row_table_def['Type']);

		if ($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field] ?? null);
		} elseif ($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field] ?? '');
		} else {
			continue;
		}

		$return = '';

		// The value column (depends on type)
		// ----------------
		$enum = substr($row_table_def['Type'], strpos($row_table_def['Type'], '(') + 1, -1);
		$enum = explode("','", $enum);

		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace("''", "'", str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . '"';
			if ((isset($list[$field]) && in_array($enum_atom, $aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
					&& $enum_atom == ($row_table_def['Default'] ?? ''))
			) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . "</option>\n";
		} // end for
		
		if(is_array($list)) {
			$list[$field . '_option'] = $return;
		}
	} // end for
	db_free($table_def);
} // end function

/**
 * 스킨 디렉토리 목록을 <option> 태그로 반환합니다.
 * @author 06/01/17 박선민
 */
function userGetSkinList($skin, $dir) {
	if (!$dir || !is_dir($dir)) return '';
	$rt_str = '';
	$d = dir($dir);
	while (false !== ($entry = $d->read())) {
		if ($entry['0'] != '.' && $entry['0'] != '_' && is_dir($d->path.'/'.$entry)) {
			$selected = ($entry == $skin) ? ' selected' : '';
			$rt_str .= "<option value=\"{$entry}\"{$selected}>{$entry}</option>";
		}
	}
	$d->close();
	return $rt_str;
}

/**
 * 사이트 스킨 목록을 <option> 태그로 반환합니다.
 * @author 06/01/17 박선민
 */
function userGetSiteSkin($skin) {
	global $SITE;
	$path = $SITE['html_path'] ?? '';
	if (!$path || !is_dir($path)) return '';
	$rt_str = '';
	$d = dir($path);
	while (false !== ($entry = $d->read())) {
		if (preg_match('/^index\_[a-z0-9_-]+\.php$/', $entry)) {
			$entry_name = substr($entry, 6, -4);
			$selected = ($entry_name == $skin) ? ' selected' : '';
			$rt_str .= "<option value=\"{$entry_name}\"{$selected}>{$entry_name}</option>";
		}
	}
	$d->close();
	return $rt_str;
}

/**
 * 테이블의 특정 필드 또는 전체 필드의 기본값을 가져옵니다.
 *
 * @param string $table 테이블 이름.
 * @param string $field (선택) 특정 필드의 이름을 지정하면 해당 필드의 기본값만 반환합니다.
 * @return mixed|array|null 필드가 지정된 경우 해당 필드의 기본값, 그렇지 않은 경우 [필드명 => 기본값] 형태의 배열을 반환합니다.
 */
function userGetDefaultFromTable($table, $field = '') {
	// 전역 DB 연결은 db_* 함수 내부에서 처리되므로 global 선언이 필요 없습니다.

	// 보안 참고: db_escape() 함수를 사용하여 SQL 인젝션을 방어합니다.
	$safe_table = db_escape($table);
	$sql_like = $field ? " LIKE '" . db_escape($field) . "'" : '';

	// 2025-08-19 Gemini: 
	// 보안 참고: SHOW COLUMNS 구문은 Prepared Statements를 지원하지 않으므로,
	// 이 함수를 호출하기 전에 $table 변수가 신뢰할 수 있는 값인지 확인하는 것이 좋습니다.
	$result = db_query("SHOW COLUMNS FROM {$safe_table} {$sql_like}");

	if (!$result) {
		return $field ? '' : [];
	}

	$list = [];
	// 2025-08-19 Gemini: 
	while ($row = db_array($result)) {
		$list[$row['Field']] = $row['Default'];
	}

	// 2025-08-19 Gemini: 
	db_free($result);

	return $field ? ($list[$field] ?? null) : $list;
}
?>