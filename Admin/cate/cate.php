<?php
//=======================================================
// 설	명 : 카테고리 관리리스트(cate.php) - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/31
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, MySQLi 적용, 보안 강화
// 25/09/12	Gemini AI	사용자 정의 db_* 함수로 통일 및 보안 강화
// 04/05/31	박선민		소스 개선
//=======================================================
$HEADER=array(
	'priv' => 2, // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용		
	'useApp' => 1,
	'useBoard2' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= __DIR__;
$thisUrl	= "."; // 마지막 "/"이 빠져야함
$prefix	= 'board2';

global $db_conn, $SITE;

$db			= $_REQUEST['db'] ?? null;
$cateuid	= $_REQUEST['cateuid'] ?? null;
$mode		= $_REQUEST['mode'] ?? 'catewrite';

$table_dbinfo = "{$SITE['th']}{$prefix}info";

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$rs_dbinfo = db_query("SELECT * FROM {$table_dbinfo} WHERE db='".db_escape($db)."'");
$dbinfo = db_arrayone($rs_dbinfo);
db_free($rs_dbinfo);

if (!$dbinfo) {
	back("사용하지 않은 DB입니다.");
}
if(($dbinfo['enable_cate'] ?? 'N') !== 'Y') back("카테고리를 지원하지 않습니다.");
// 인증 체크
if(!privAuth($dbinfo, "priv_catemanage")) back("이용이 제한되었습니다.(레벨부족)");

// table	
$dbinfo['table'] = "{$SITE['th']}{$prefix}_{$dbinfo['db']}"; // 테이블이름 가져오기
$dbinfo['table_cate'] = {$dbinfo['table']} . '_cate';

$sql_where_cate = ' 1 '; // init

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/{$dbinfo['skin']}/cate.html") ) $dbinfo['skin']="basic";
$tpl->set_file('html',"{$thisPath}/stpl/{$dbinfo['skin']}/cate.html",TPL_BLOCK);

$rs_catelist = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} ORDER BY num, re");
$total = db_count($rs_catelist);

while($list = db_array($rs_catelist)){
	$list['rede'] = strlen($list['re']);
	if($list['rede']) {
		$list['title'] = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $list['rede']) . " <img src='/images/board_re.gif' width='12' height='12' border='0' align='absmiddle'> " . $list['title'];
	}

	// 해당 카테고리수의 db수 구하기
	$rs_count = db_query("select count(*) as count from {$dbinfo['table']} WHERE {$sql_where_cate} and cateuid='".db_escape($list['uid'])."'");
	$list['dbcount'] = db_resultone($rs_count);
	db_free($rs_count);

	// URL Link..
	$href['catewrite']	= "{$_SERVER['PHP_SELF']}?db={$db}";
	$href['catereply']	= "{$_SERVER['PHP_SELF']}?db={$db}&cateuid={$list['uid']}";
	$href['catemodify']	= "{$_SERVER['PHP_SELF']}?db={$db}&mode=catemodify&cateuid={$list['uid']}";
	$href['catesort']		= "./catesort.php?db={$db}&cateuid={$list['uid']}";
	$href['catedelete']	= "./cateok.php?db={$db}&mode=catedelete&cateuid={$list['uid']}";
	$href["list"]		= "/sboard2/list.php?db={$db}&cateuid={$list['uid']}";

	// 템플릿 YESRESULT 값들 입력
	$tpl->set_var('href'		, $href);
	$tpl->set_var('list'		, $list);
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	$tpl->set_var('blockloop',true);	
} // end for
if ($rs_catelist) db_free($rs_catelist);

/////////////////////////
// 템플릿할당 - 쓰기 부분
// - 해당 카테고리 네비케이션 구하기
$cate_nevi = '';
$list = []; // reset list for the form
if($cateuid){
	$cate_nevi = "<a href='{$_SERVER['PHP_SELF']}?db={$db}'>Top</a> > ";
	$rs_cateinfo = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE uid='".db_escape($cateuid)."' and {$sql_where_cate} LIMIT 1");
	
	if(	$cateinfo = db_arrayone($rs_cateinfo) ){
		if(strlen($cateinfo['re'] ?? '')){
			// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
			$re_parts = [];
			for($i=0;$i<strlen($cateinfo['re']);$i++){
				$re_parts[] = "'" . db_escape(substr($cateinfo['re'],0,$i+1)) ."'";
			}
			$sql_where_cate_tmp = " (re='' OR re IN (" . implode(',', $re_parts) . ")) ";
			
			//	카테고리 네비게이션 만들기
			$rs = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num={$cateinfo['num']} and {$sql_where_cate_tmp} order by re");
			while($row=db_array($rs)){
				$cate_nevi .= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . " > ";
			}
			db_free($rs);
		} // end if	
		if($mode === "catemodify") {
			$list=$cateinfo;
		} else {
			$cate_nevi .= htmlspecialchars($cateinfo['title'], ENT_QUOTES, 'UTF-8') . " > ";
		}
	} // end if
	db_free($rs_cateinfo);
} // end if($cateuid)

$form_catewrite = " method='post' action='cateok.php'>
	<input type='hidden' name='mode' value='{$mode}'>
	<input type='hidden' name='db' value='{$db}'>
	<input type='hidden' name='cateuid' value='{$cateuid}'>
	";
$tpl->set_var('form_catewrite',$form_catewrite);
// - 추가되어 있는 테이블 필드 포함
$skip_fields = array('passwd', 'db', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
if($fieldlist = userGetAppendFields($dbinfo['table_cate'], $skip_fields)){
	foreach($fieldlist as $value){
		if (isset($list[$value])) {
			$list[$value]	= htmlspecialchars($list[$value],ENT_QUOTES, 'UTF-8');
		}
	}
}

// 스킨 리스트 가져오기
$list['skinlist'] = userGetSkinList($list['skin'] ?? '',$_SERVER['DOCUMENT_ROOT'].'/sboard2/stpl');

$tpl->set_var('list',	$list);

$tpl->set_var('cate_nevi', $cate_nevi);
if($mode === 'catemodify') $tpl->set_var('is_modify', true);
/////////////////////////
// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수

// 마무리
$val="\\1{$thisUrl}/stpl/{$dbinfo['skin']}/images/";
echo preg_replace('/([\'"])images\//', $val, $tpl->process('', 'html', TPL_OPTIONAL));

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
 * 스킨 리스트 <option ..>..</option>으로 가져오기 (Modernized version)
 * @param string $skin
 * @param string $dir
 * @return string|false
 */
function userGetSkinList(string $skin, string $dir){
	if(empty($dir)) $dir = __DIR__ . "/stpl";
	if(!is_dir($dir)) return false;
	
	$rt_str = '';
	$files = scandir($dir);
	if ($files === false) return false;

	sort($files);
	foreach ($files as $entry) {
		if ($entry != "." && $entry != ".." && is_dir($dir."/".$entry)) {	
			$selected = ($entry == $skin) ? 'selected' : '';
			$rt_str .= "<option value='".htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') . "' {$selected}>".htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') . "</option>";
		}
	}
	
	return $rt_str;
}

?>
