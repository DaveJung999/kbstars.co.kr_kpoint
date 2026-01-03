<?php
//=======================================================
// 설	명 : 게시판 종합관리(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/30
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 02/09/30 박선민 마지막 수정
// 03/08/25 박선민 마지막 수정
// 24/08/11 Gemini-AI php 7 및 최신 문법으로 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useSkin' =>	1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================
// 2 . $dbinfo 가져오기
include_once($thisPath.'config.php');

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$table=$SITE['th'] . "board2info";	//게시판 관리 테이블
$thisPath	= dirname(__FILE__);

// 관리자페이지 환경파일 읽어드림
$rs=db_query("select * from {$SITE['th']}admininfo where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
$pageinfo=db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

// URL Link
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// board2info를 읽어드림
$in_mode = $_GET['in_mode'] ?? null; // php 7에서 undefined index 경고 방지
switch($in_mode){
	case "basic":
		$skin_include = "setup.htm";		
		break;
	default:
		$skin_include = "setup.htm"; // default 값 설정
		$list['html_head_select1'] = "checked";
}
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/basic_skin/{$skin_include}") ) $dbinfo['skin']="basic_skin";
$tpl->set_file('html',"{$thisPath}/stpl/basic_skin/{$skin_include}",TPL_BLOCK);
$sql = "SELECT * from {$table} where db = '{$_GET['db']}'";

$rs = db_query($sql);

$list = db_array($rs);

sw_list("enable_type");
sw_list("enable_hidelevel");
sw_list("enable_cate");
sw_list("enable_upload");
sw_list("enable_getinfo");
sw_list("enable_memo");
sw_list("enable_adm_mail");
sw_list("enable_rec_mail");

// TODO
// 스킨 리스트 가져오기
$list['skin_option'] = userGetSkinList($list['skin'],$_SERVER['DOCUMENT_ROOT'].'/sboard2/skin');
$list['html_skin_option'] = userGetSiteSkin($list['html_skin']); // 사이트 스킨
// 템플릿 마무리 할당
userEnumSetFieldsToOptionTag($dbinfo['table'],$list); // $list['필드_option']에 enum,set필드 <option>..</option>생성

$tpl->set_var('list'			,$list);
	
// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function sw_list($slist){
	global $list;
	switch($list[$slist] ?? null){ // 변수가 없을 경우를 대비
		case "Y":
			$list[$slist."1"] = "checked";
			break;
		case "N":
			$list[$slist."2"] = "checked";
			break;
		case "multi":
			$list[$slist."3"] = "checked";
			break;
		default:
			$list[$slist."2"] = "checked";
	}
}

/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
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
	return !empty($fieldlist) ? $fieldlist : false;
}

// enum,set필드라면, $list['필드_option'] 만들어줌
function userEnumSetFieldsToOptionTag($table, &$list) { 
	$table_def = db_query('SHOW FIELDS FROM '. $table);
	if(!$table_def) return;

	$fields_cnt	= db_count($table_def);
	for ($i = 0; $i < $fields_cnt; $i++){
		$row_table_def = db_array($table_def);
		$field = $row_table_def['Field'];

		$row_table_def['True_Type'] = preg_replace('/\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field]);
		} elseif($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field]);
		} else {
			continue;
		}
		
		$return	= '';
		$enum = str_replace($row_table_def['True_Type'].'(', '', $row_table_def['Type']);
		$enum = preg_replace('/\)$/', '', $enum);
		$enum = explode('\',\'', substr($enum, 1, -1));
		$enum_cnt = count($enum);

		for ($j = 0; $j < $enum_cnt; $j++){
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
			$return .= '<option value="'	. htmlspecialchars($enum_atom, ENT_QUOTES) . '"';
			
			$is_selected = isset($list[$field]) && in_array($enum_atom, $aFieldValue);
			$is_default = !isset($list[$field]) && $row_table_def['Null'] != 'YES' && $enum_atom == $row_table_def['Default'];

			if ($is_selected || $is_default) {
				$return .=	' selected="selected"';
			}
			$return .=	'>'	. htmlspecialchars($enum_atom) . "</option>\n";
		}
		
		$list[$field.'_option'] = $return;
	}
	db_free($table_def); // 결과 집합 해제
}

// 스킨 리스트 <option ..>..</option>으로 가져오기 (scandir로 코드 현대화)
function userGetSkinList($skin, $dir) {
	if(!$dir || !is_dir($dir)) return false;

	$rt_str = '';
	$files = scandir($dir);

	foreach ($files as $file){
		// . 과 .. 은 제외하고 디렉토리만 목록에 추가
		if ($file !== "." && $file !== ".." && is_dir($dir . '/' . $file)) {
			$selected = ($skin == $file) ? ' selected' : '';
			$rt_str .= "<option value='{$file}'{$selected}>{$file}</option>";
		}
	}
	return $rt_str;
}

// 사이트 스킨 리스트 <option ..>..</optin>으로 가져오기 (scandir로 코드 현대화)
function userGetSiteSkin($skin) {
	global $SITE;
	$path = $SITE['html_path'];
	if(!is_dir($path)) return false;
	
	$files = scandir($path);
	$rt_str = '';

	foreach ($files as $entry){
		if(preg_match('/^index\_[a-z0-9_-]+\.php/', $entry)){
			$file = substr($entry, 6, -4);
			$selected = ($skin == $file) ? ' selected' : '';
			$rt_str .= "<option value='{$file}'{$selected}>{$file}</option>";
		}
	}
	return $rt_str;
}

// 테이블에서 기본값들을 가져오는 함수
function userGetDefaultFromTable($table, $field='') { 
	$sql_like = $field ? " LIKE '{$field}'" : '';
	$table_def = db_query('SHOW COLUMNS FROM '. $table . $sql_like);
	if(!$table_def) return null; // 실패 시 null 반환
	
	$list = array();
	while($row_table_def = db_array($table_def)) {
		$list[$row_table_def['Field']] = $row_table_def['Default'];
	}
	db_free($table_def); // 결과 집합 해제
	
	if($field) {
		return $list[$field] ?? null; // 특정 필드 값 반환 (없으면 null)
	} else {
		return $list; // 전체 기본값 배열 반환
	}
}
?>