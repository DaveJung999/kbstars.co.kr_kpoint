<?php
//=======================================================
// 설	명 : 관리자 페이지 : 그룹정보 서치
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/01 박선민 처음
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'useSkin'	 =>	1, // 템플릿 사용
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 => 1,
	'useBoard'	 => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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
	
	global $conn, $SITE;

$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	include_once("{$thisPath}/config.php"); // $dbinfo 정의

	// table
	$table_logon	= $SITE['th'] . 'logon';
	$table_userinfo	= $SITE['th'] . 'userinfo';
	$table_groupinfo= $SITE['th'] . 'groupinfo';
	$table_joininfo	= $SITE['th'] . 'joininfo';
	$table_joininfo_cate=$SITE['th'].'joininfo_cate';
	$table_payment	= $SITE['th'] . 'payment';
	$table_service	= $SITE['th'] . 'service';
	$table_loguser	= $SITE['th'] . 'log_userinfo';
	$table_log_wtmp	= $SITE['th'] . 'log_wtmp';
	$table_log_lastlog=$SITE['th'] . 'log_lastlog';
	$table_boardinfo= $SITE['th'] . "boardinfo";
	
	// GET 파라미터 안전하게 받기
	$gid_get = $_GET['gid'] ?? '';
	$groupid_get = $_GET['groupid'] ?? '';
	$gsc_column_get = $_GET['gsc_column'] ?? '';
	$gsc_string_get = $_GET['gsc_string'] ?? '';
	$mode_get = $_GET['mode'] ?? '';
	$bid_get = $_GET['bid'] ?? '';

	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($gid_get) { $gsc_column_get='groupinfo.uid'; $gsc_string_get=$gid_get; }
	elseif($groupid_get) { $gsc_column_get='groupinfo.groupid'; $gsc_string_get=$groupid_get; }
	else {
		if(!$gsc_column_get) $gsc_column_get='groupinfo.uid';
		if(!$gsc_string_get) $gsc_string_get='%%';
	}
	
	// 기본 URL QueryString
	$qs_basic = "gsc_column=".urlencode($gsc_column_get) . "&gsc_string=".urlencode($gsc_string_get);

	
	/////////////////////////////////
	// $sql문 결정 (Limit ?,? 부분 제외)
	$sql_table= explode(".", $gsc_column_get);
	if(count($sql_table)!=2) go_url($_SERVER['PHP_SELF']);

	$gsc_string_safe = db_escape($gsc_string_get);
	if( strpos($gsc_string_get, "%") !== false ) {
		if($gsc_string_get=="%") $gsc_string_safe = "%%";
		$sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` LIKE '{$gsc_string_safe}') ";
	}
	else $sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` = '{$gsc_string_safe}') ";

	$sql = '';
	switch ($sql_table['0']) {
		case "groupinfo" :
			$sql="SELECT *, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as sc_column FROM {$table_groupinfo} WHERE  $sql_where ";
			break;
		case "logon" :
			$sql="SELECT {$table_groupinfo}.*, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as sc_column FROM {$table_logon}, {$table_groupinfo} WHERE $sql_where AND {$table_groupinfo}.uid=`{$SITE['th']}{$sql_table['0']}`.uid";
			break;
		default :
			back("지원하지 않는 서치 옵션을 선택하였습니다. 관리자에게 문의 바랍니다");
	} // end switch
	$rs_gsearch = db_query($sql);
	$count_gsearch = db_count($rs_gsearch);

	// - 결과값이 한명이 아니라면, 서치 페이지로 이동시킴
	if($count_gsearch!=1) go_url("gsearch.php?mode=gjoininfo&gsc_column=".urlencode($gsc_column_get) . "&gsc_string=".urlencode($gsc_string_get));
	
	$groupinfo	= db_array($rs_gsearch);
	$bid_safe = (int)($groupinfo['bid'] ?? 0);
	$sql_logon = "SELECT * FROM {$table_logon} WHERE uid='{$bid_safe}'";
	$result_logon = db_query($sql_logon);
	$groupinfo['logon'] = $result_logon ? db_array($result_logon) : null;
	/////////////////////////////////

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$list = [];
if($mode_get=='modify') {
	$gid_safe = db_escape($gid_get);
	$bid_safe = db_escape($bid_get);
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE gid='{$gid_safe}' AND bid='{$bid_safe}' LIMIT 1";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back("데이터가 없습니다.");
	
	// 그룹정보 가져옮
	$sql_group = "SELECT * FROM {$table_groupinfo} WHERE uid='{$gid_safe}'";
	$result_group = db_query($sql_group);
	$list['groupinfo'] = $result_group ? db_array($result_group) : null;

	$dbinfo['table'] = $table_joininfo;
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			if(isset($list[$value])) {
				$list[$value] = htmlspecialchars($list[$value],ENT_QUOTES);
			}
		}
	}
	////////////////////////////////
	
	$form_mode = "gjoininfomodify";
}
else {
	$form_mode = "gjoininfoadd";
}
$tpl->set_var('list',$list);

$form_write = " method='post' action='groupok.php'>";
$form_write .= substr(href_qs("groupid=" . ($groupinfo['groupid'] ?? '') . "&mode=$form_mode&bid=$bid_get&gid=$gid_get",$qs_basic,1),0,-1);
$tpl->set_var('form_write',$form_write);

// 템플릿 마무리 할당
// - 회원전체 서치 부분
$tpl->set_var('count_gsearch',$count_gsearch);
$tpl->set_var('gsc_column',$gsc_column_get);
$tpl->set_var('gsc_string',htmlspecialchars(stripslashes($gsc_string_get),ENT_QUOTES));
$tpl->set_var('groupinfo',$groupinfo);
$form_gsearch = " method=get action='{$_SERVER['PHP_SELF']}' ";
$tpl->set_var('form_gsearch',$form_gsearch);


// 마무리
$replacement = '$1' . $thisUrl . '/skin/' . ($dbinfo['skin'] ?? 'basic') . '/images/';
$pattern = '/([\'"])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html', TPL_OPTIONAL));

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