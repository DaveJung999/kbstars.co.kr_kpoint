<?php
//=======================================================
// 설	명 : 심플리스트-추가/수정
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/11/27 박선민 마지막 수정
// 24/08/11 Gemini-AI php 7 및 최신 문법으로 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 1 . 넘어온값 체크
	$mode = $_GET['mode'] ?? '';
	$uid = $_GET['uid'] ?? '';
	$db = $_GET['db'] ?? '';
	
	// 2 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	// 3 . 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont")
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$skip_fields = array('uid'); // uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
	}		
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화
	
	// 수정모드라면
	if(isset($mode) && $mode == "modify"){
		$uid_safe = db_escape($uid);
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$uid_safe}'";
		$list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
	
		/////////////////////////////////
		// 추가되어 있는 테이블 필드 포함
		$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
		if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
			foreach($fieldlist as $value){
				$list[$value] = htmlspecialchars($list[$value] ?? '', ENT_QUOTES);
			}
		}
		////////////////////////////////
		
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$rdate = $_GET['rdate'] ?? '';
		$form_default .= href_qs("mode=modify&rdate={$rdate}", $qs_basic, 1);
		$form_default = substr($form_default,0,-1);
	} else {
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$rdate = $_GET['rdate'] ?? '';
		$form_default .= href_qs("mode=write&rdate={$rdate}", $qs_basic, 1);
		$form_default = substr($form_default,0,-1);
	}
	
	// URL Link...
	$href['list'] = "list.php?{$qs_basic}";
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
$skin = $dbinfo['skin'] ?? 'basic';
if( !is_file($thisPath.'skin/'.$skin.'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
userEnumFieldsToOptionTag($dbinfo['table'], $list); // enum필드 <option>..</option>생성
$tpl->set_var('list'		, $list);
$tpl->set_var("form_default",	$form_default);

$tpl->set_var('get'				, $_GET);	// get값으로 넘어온것들
$tpl->set_var('dbinfo'			, $dbinfo);	// dbinfo 정보 변수
$tpl->set_var('href'			, $href);	// 게시판 각종 링크
$tpl->set_var("mode_{$mode}", 1); // mode_write, mode_modify 값있게

// 추가 필드이름 체크
$fieldlist = array();
$sql = "SHOW COLUMNS FROM `".db_escape($dbinfo['table']) . "`";
$fields = db_query($sql);
if($fields) {
	while($row = db_array($fields)) {
		$a_fields = $row['Field'];
		if( !in_array($a_fields, array('uid','email','readtime')) ){
			$fieldlist[] = $a_fields;
			$tpl->set_var('fieldname', $a_fields);
			$tpl->set_var('fieldvalue', $list[$a_fields] ?? '');
			$tpl->process('FIELD','field',TPL_OPTIONAL|TPL_APPEND);
		}
	}
}

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

// enum필드라면, $list[필드이름_options] 만들어줌
// 04/08/17 박선민
function	userEnumFieldsToOptionTag($table,&$list){
	global $db_conn;
	$table_def = db_query("SHOW FIELDS FROM `" . db_escape($table) . "`");
	/**
	* Displays the form
	*/
	if(!$table_def) return;

	$fields_cnt	= db_count($table_def);
	for ($i = 0; $i < $fields_cnt; $i++){
		$row_table_def	= db_array($table_def);
		$field			= $row_table_def['Field'];

		//$len			= @mysql_field_len($result, $i);

		$row_table_def['True_Type'] = preg_replace('/\\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type'] != 'enum') continue;
		
		$return	= '';

		// The value column (depends on type)
		// ----------------
		$enum		= str_replace('enum(', '', $row_table_def['Type']);
		$enum		= preg_replace('/\\)$/', '', $enum);
		$enum		= explode('\',\'', substr($enum, 1, -1));
		$enum_cnt	= count($enum);

		// show dropdown or radio depend on length
		for ($j = 0; $j < $enum_cnt; $j++){
			// Removes automatic MySQL escape format
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
			$return .= '<option value="'	. htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			if ((isset($list[$field]) && $list[$field] == $enum_atom)
				|| (!isset($list[$field]) && ($row_table_def['Null'] != 'YES')
					 && $enum_atom == $row_table_def['Default'])){
				$return .=	' selected="selected"';
			}
			$return .=	'>'	. htmlspecialchars($enum_atom) . '</option>'	. "\n";
		} // end for
		
		$list["{$field}_option"] = $return;
	} // end for
} // end function
?>