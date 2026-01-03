<?php
//=======================================================
// 설	명 : 관리자 페이지 : 서비스 이용정보 검색
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'이 빠져야함
$Action_domain = '';
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	include_once($thisPath.'config.php');

	// 넘어온값 처리
	$mode_get = $_GET['mode'] ?? 'joininfo';
	$bid_get = $_GET['bid'] ?? '';
	$gid_get = $_GET['gid'] ?? '';
	$userid_get = $_GET['userid'] ?? '';
	$tel_get = $_GET['tel'] ?? '';
	$hp_get = $_GET['hp'] ?? '';
	$order_get = $_GET['order'] ?? '';
	$msc_column_get = $_GET['msc_column'] ?? '';
	$msc_string_get = $_GET['msc_string'] ?? '';

	// table
	$table_logon	= $SITE['th'].'logon';
	$table_groupinfo= $SITE['th'].'groupinfo';
	$table_joininfo	= $SITE['th'].'joininfo';
	$table_payment	= $SITE['th'].'payment';
	$table_service	= $SITE['th'].'service';
	$table_loguser	= $SITE['th'].'log_userinfo';
	$table_log_wtmp	= $SITE['th'].'log_wtmp';
	$table_log_lastlog=$SITE['th'].'log_lastlog';
	
	$dbinfo = array(
				'skin'	 =>	'basic',
				'table'	 =>	$table_logon				
			);

	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($bid_get) { $msc_column_get='logon.uid'; $msc_string_get=$bid_get;}
	elseif($userid_get) { $msc_column_get='logon.userid'; $msc_string_get=$userid_get;}
	elseif($tel_get) { $msc_column_get='logon.tel'; $msc_string_get=$tel_get;}
	elseif($hp_get) { $msc_column_get='logon.hp'; $msc_string_get=$hp_get;}
	elseif($order_get) { $msc_column_get='payment.num'; $msc_string_get=$order_get;}
	elseif(!$msc_column_get) { $msc_column_get='logon.userid'; $msc_string_get='%';}

	/////////////////////////////////
	// 회원 검색 및 회원정보 가져오기
	// - 넘어온값 체크
	$sql_table= explode('.', $msc_column_get);
	if(count($sql_table)!=2 || empty($msc_string_get)) go_url('msearch.php');
	
	// - $sql_where
	$msc_string_safe = db_escape($msc_string_get);
	if( strpos($msc_string_get, '%') !== false ) {
		if($msc_string_get=='%') $msc_string_safe = '%%';
		$sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` LIKE '{$msc_string_safe}') ";
	}
	else $sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` = '{$msc_string_safe}') ";
	
	// - $sql문 완성
	$sql = '';
	switch ($sql_table['0']) {
		case 'logon' :
			$sql="SELECT *, email as msc_column FROM `{$SITE['th']}{$sql_table['0']}` WHERE  $sql_where ";
			break;
		case 'payment':
			$sql="SELECT {$table_logon}.*, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as msc_column FROM {$table_logon}, `{$SITE['th']}{$sql_table['0']}` WHERE {$table_logon}.uid=`{$SITE['th']}{$sql_table['0']}`.bid AND  $sql_where ";
			break;
		default:
			$sql = ''; // 예외 처리
			break;
	} // end switch
	
	$rs_msearch = $sql ? db_query($sql) : false;
	$count_msearch = $rs_msearch ? db_count($rs_msearch) : 0;

	// 결과값이 한명이 아니라면, 서치 페이지로 이동시킴.
	if($count_msearch != 1)
		go_url("msearch.php?mode={$mode_get}&msc_column={$msc_column_get}&msc_string=".urlencode($msc_string_get));
	$logon = db_array($rs_msearch);
	db_free($rs_msearch);
	/////////////////////////////////

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$list = [];
if($mode_get=='modify') {
	$gid_safe = db_escape($gid_get);
	$bid_safe = db_escape($bid_get);
	$sql = "SELECT * FROM `{$table_joininfo}` WHERE gid='{$gid_safe}' and bid='{$bid_safe}' LIMIT 1";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back("데이터가 없습니다. 90L");
	
	// 그룹정보 가져옮
	$sql = "SELECT * FROM {$table_groupinfo} WHERE uid='{$gid_safe}'";
	$result_group = db_query($sql);
	$list['groupinfo'] = $result_group ? db_array($result_group) : null;
	db_free($result_group);

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
	
	$form_mode = "joininfomodify";
}
else {
	$form_mode = "joininfoadd";
}
$tpl->set_var('list',$list);

$form_default = " method='post' action='ok.php'>";
$form_default .= substr(href_qs("mode={$form_mode}&bid=".($logon['uid'] ?? '')."&gid={$gid_get}&userid=".($logon['userid'] ?? '')."&msc_column={$msc_column_get}&msc_string=".urlencode($msc_string_get),'mode=',1),0,-1);
$tpl->set_var('form_default',$form_default);

// 블럭 : 사용자 정보
$seUid = $_SESSION['seUid'] ?? null;
if(isset($seUid)) $tpl->process('USERINFO','userinfo');
else $tpl->process('USERINFO','nouserinfo');

// 템플릿 마무리 할당
$tpl->set_var('href',$href ?? []);
$tpl->set_var('msc_column',$msc_column_get);
$tpl->set_var('msc_string',htmlspecialchars(stripslashes($msc_string_get),ENT_QUOTES));
$tpl->set_var('logon',$logon);
$tpl->set_var('userinfo',$userinfo ?? []);

$form_msearch = " method=get action='msearch.php' ";
$tpl->set_var('form_msearch',$form_msearch);

// 마무리
$replacement = '$1' . $thisUrl.'skin/'.($dbinfo['skin'] ?? 'basic').'/images/';
$pattern = '/([="\'])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html',TPL_OPTIONAL));

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