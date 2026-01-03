<?php
//=======================================================
// 설	명 : 카테고리 관리리스트(cate.php)
// 책임자 : 박선민 , 검수:05/01/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/27 박선민 소스 개선
// 25/09/08 Gemini PHP 7+ 호환성 업데이트, 보안 강화, 함수 교체
//=======================================================
$HEADER=array(
	'usedb2'	=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix 	= 'board2';
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// GET 변수 보안 처리 및 초기화
$db_name = $_GET['db'] ?? '';
$cateuid = (int)($_GET['cateuid'] ?? 0);
$mode = $_GET['mode'] ?? 'catewrite';

// table
$table_dbinfo = $SITE['th'] . $prefix . 'info';

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장 (SQL 인젝션 방지)
$db_name_safe = db_escape($db_name);
$sql = "SELECT * FROM {$table_dbinfo} WHERE db='{$db_name_safe}' LIMIT 1";

$dbinfo = db_arrayone($sql) or back('사용하지 않는 카테고리입니다.');
if($dbinfo['enable_cate'] != 'Y') back('카테고리 기능을 지원하지 않습니다.');

// 인증 체크
if(!privAuth($dbinfo, 'priv_catemanage')) back('이용이 제한되었습니다.(레벨부족)');

// table
$dbinfo['table'] = $SITE['th'].$prefix.'_'.$dbinfo['db']; // 테이블이름 가져오기
$dbinfo['table_cate'] = $dbinfo['table'].'_cate';

$sql_where_cate = ' 1 '; // init
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate('','remove_nonjs');
$tpl->set_file('html', $thisPath.'skin/basic/cate.html', TPL_BLOCK);

//=====================================================================
// 카테고리 역순...........davej.......2013-11-07
// (cheer20082009away, groupview20082009)
//=====================================================================
$sql_orderby = ' num, re ';
if (substr($dbinfo['db'], 0, 5) == 'cheer' || substr($dbinfo['db'], 0, 5) == 'grvie' || substr($dbinfo['db'], 0, 5) == 'volun') {
	$sql_orderby = ' num DESC, re ';
}

$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} ORDER BY {$sql_orderby}";

$rs_catelist = db_query($sql);
$total = db_count($rs_catelist);
if($total == 0) $tpl->process('LIST','nolist', TPL_OPTIONAL | TPL_APPEND);

for($i=0; $i<$total; $i++){
	$list = db_array($rs_catelist);
	
	$list['rede'] = strlen($list['re']);
	if($list['rede']) {
		$list['title'] = str_repeat('&nbsp;&nbsp;&nbsp;', $list['rede']) . ' ↘ ' . $list['title'];
	}

	// 해당 카테고리수의 db수 구하기
	if($dbinfo['table']) {
		$list['dbcount'] = db_resultone("SELECT count(*) as count FROM {$dbinfo['table']} WHERE cateuid='{$list['uid']}'", 0, 'count');
	}

	// URL Link..
	$href['catewrite']	= $_SERVER['PHP_SELF'].'?db='.$dbinfo['db'];
	$href['catereply']	= $_SERVER['PHP_SELF']."?db={$dbinfo['db']}&cateuid={$list['uid']}";
	$href['catemodify']	= $_SERVER['PHP_SELF']."?db={$dbinfo['db']}&mode=catemodify&cateuid={$list['uid']}";
	$href['catesort']	= $thisUrl."catesort.php?db={$dbinfo['db']}&cateuid={$list['uid']}";
	$href['catedelete']	= $thisUrl."cateok.php?db={$dbinfo['db']}&mode=catedelete&cateuid={$list['uid']}";
	$href['list']		= $thisUrl."list.php?db={$dbinfo['db']}&cateuid={$list['uid']}";

	// 템플릿 입력
	$tpl->set_var('href', $href);
	$tpl->set_var('list', $list);
	
	$tpl->process('LIST','list', TPL_OPTIONAL | TPL_APPEND);
	$tpl->set_var('blockloop',true);
}
$tpl->drop_var('list');

/////////////////////////
// 템플릿할당 - 쓰기 부분
$list = array(); // $list 변수 초기화
$cate_nevi = ''; // $cate_nevi 변수 초기화

if($cateuid) {
	$cate_nevi = "<a href='{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}'>Top</a> > ";
	// cateuid는 (int)로 캐스팅하여 SQL 인젝션 방지
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid={$cateuid} AND {$sql_where_cate} LIMIT 1";
	if($cateinfo = db_arrayone($sql)) {
		if(strlen($cateinfo['re'])) {
			$sql_where_cate_tmp = ' (re="" ';
			for($i=0; $i < strlen($cateinfo['re']); $i++) {
				$sql_where_cate_tmp .= ' or re="' . substr($cateinfo['re'],0,$i+1) .'" ';
			}
			$sql_where_cate_tmp .= ' ) ';

			$rs = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} AND num={$cateinfo['num']} AND {$sql_where_cate_tmp} ORDER BY re");
			while($row=db_array($rs)) {
				$cate_nevi .= $row['title'] . ' > ';
			}
		}
		if($mode == 'catemodify') {
			$list = $cateinfo;
		} else {
			$cate_nevi .= $cateinfo['title'].' > ';
		}
	}
}

$form_catewrite = " method='post' action='{$thisUrl}cateok.php'>";
$form_catewrite .= href_qs("mode={$mode}&db={$dbinfo['db']}&cateuid={$cateuid}",'mode=',1);
$form_catewrite = substr($form_catewrite, 0, -1);
$tpl->set_var('form_catewrite', $form_catewrite);

// - 추가되어 있는 테이블 필드 포함
$skip_fields = array('uid','bid','cateuid','passwd' , 'db' , 'cateuid' , 'num' , 're' , 'upfiles' , 'upfiles_totalsize' , 'docu_type' , 'type' , 'priv_level' , 'ip' , 'hit' , 'hitip' , 'hitdownload', 'vote' , 'voteip' , 'rdate');
if($fieldlist = userGetAppendFields($dbinfo['table_cate'], $skip_fields)) {
	foreach($fieldlist as $value) {
		$list[$value]	= htmlspecialchars($list[$value] ?? '', ENT_QUOTES);
	}
}

if ($mode == 'catewrite') $list['comment'] = $list['comment'] ?? '보이기';

// 카테고리 수정 시 기존 $dbinfo 값을 기본값으로 사용
$default_fields = [
	'skin', 'orderby', 'pern', 'row_pern', 'page_pern', 'cut_length',
	'imagesize_thumbnail', 'imagesize_read', 'enable_upload', 'enable_uploadmust',
	'enable_uploadextension', 'enable_memo', 'enable_vote', 'enable_level',
	'enable_listreply', 'enable_readlog', 'enable_readlist', 'enable_userid',
	'enable_adm_mail', 'enable_rec_mail', 'enable_getinfo', 'enable_getinfoskins',
	'include_listphp', 'priv_list', 'priv_write', 'priv_memowrite', 'priv_reply',
	'priv_read', 'priv_readlog', 'priv_modify', 'priv_download', 'priv_delete',
	'priv_writeinfo', 'priv_catemanage', 'redirect', 'html_type', 'html_skin',
	'html_head', 'html_tail'
];

foreach ($default_fields as $field) {
	if (empty($list[$field])) {
		$list[$field] = $dbinfo[$field];
	}
}

sw_list("enable_type");
sw_list("enable_sec");
sw_list("enable_cate");
sw_list("enable_upload");
sw_list("enable_getinfo");
sw_list("enable_memo");
sw_list("enable_adm_mail");
sw_list("enable_rec_mail");

// 스킨 리스트 가져오기
$list['skin_option'] = userGetSkinList($list['skin'] ?? '', $thisPath.'skin');
$list['html_skin_option'] = userGetSiteSkin($list['html_skin'] ?? ''); // 사이트 스킨
userEnumSetFieldsToOptionTag($dbinfo['table_cate'], $list); // $list['필드_option']에 enum,set필드 <option>..</option>생성

$comment_checked_var = "comment_".($list['comment'] ?? '')."_checked";
$$comment_checked_var = " checked";

// 템플릿 마무리 할당
$tpl->set_var('dbinfo', $dbinfo); // boardinfo 정보 변수
$tpl->set_var('list', $list);
$tpl->set_var($comment_checked_var, $$comment_checked_var ?? '');
$tpl->set_var('cate_nevi', $cate_nevi);
$tpl->set_var('mode_'.$mode, true);		// mode_write, mode_modify 값있게

// 마무리 (ereg_replace -> preg_replace)
$val = '\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('/([="\'\(])images\//', $val, $tpl->process('', 'html', TPL_OPTIONAL));
echo $SITE['tail'] ?? '';

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function sw_list($slist){
	global $list;
	switch($list[$slist] ?? null) {
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

// mysql_* 함수를 사용하지 않도록 현대적인 방식으로 재작성
function userGetAppendFields($table, $skip_fields = []) {
	if(empty($table)) return false;

	$result = db_query("SHOW COLUMNS FROM {$table}");
	if (!$result) return false;

	$fieldlist = [];
	while ($row = db_array($result)) {
		if (!in_array($row['Field'], $skip_fields)) {
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result);

	return !empty($fieldlist) ? $fieldlist : false;
}

// 스킨 리스트 <option ..>..</option>으로 가져오기 (scandir로 코드 현대화)
function userGetSkinList($skin, $dir) {
	if(!$dir || !is_dir($dir)) return false;

	$rt_str = '';
	$files = scandir($dir);

	foreach ($files as $file){
		if ($file !== "." && $file !== ".." && is_dir($dir . '/' . $file)) {
			$selected = ($skin == $file) ? ' selected' : '';
			$rt_str .= "<option value='{$file}'{$selected}>{$file}</option>";
		}
	}
	return $rt_str;
}

// 사이트 스킨 리스트 <option ..>..</optin>으로 가져오기 (scandir 및 preg_match로 현대화)
function userGetSiteSkin($skin) {
	global $SITE;
	$path = $SITE['html_path'];
	if(!is_dir($path)) return false;
	
	$files = scandir($path);
	$rt_str = '';

	foreach ($files as $entry){
		if(preg_match('/^index\_[a-z0-9_-]+\.php/', $entry)) {
			$file = substr($entry, 6, -4);
			$selected = ($skin == $file) ? ' selected' : '';
			$rt_str .= "<option value='{$file}'{$selected}>{$file}</option>";
		}
	}
	return $rt_str;
}

// enum,set필드라면, $list['필드_option'] 만들어줌 (mysql_* -> db_* 및 preg_*로 변경)
function userEnumSetFieldsToOptionTag($table, &$list) {
	$table_def = db_query('SHOW FIELDS FROM '. $table);
	if(!$table_def) return;

	while($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		$row_table_def['True_Type'] = preg_replace('/\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field] ?? null);
		} elseif($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field] ?? '');
		} else {
			continue;
		}
		
		$return	= '';
		$enum = str_replace($row_table_def['True_Type'].'(', '', $row_table_def['Type']);
		$enum = preg_replace('/\)$/', '', $enum);
		$enum = explode('\',\'', substr($enum, 1, -1));

		foreach ($enum as $enum_val) {
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum_val));
			$return .= '<option value="' . htmlspecialchars($enum_atom, ENT_QUOTES) . '"';
			
			$is_selected = !empty($list[$field]) && in_array($enum_atom, $aFieldValue);
			$is_default = empty($list[$field]) && $row_table_def['Null'] != 'YES' && $enum_atom == $row_table_def['Default'];
			
			if ($is_selected || $is_default) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom) . "</option>\n";
		}
		
		$list[$field.'_option'] = $return;
	}
	db_free($table_def);
}
?>