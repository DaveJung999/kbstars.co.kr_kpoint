<?php
//=======================================================
// 설	명 : 관리자 페이지 : 지불정보, 회원로그정보 검색
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/02/07 박선민 처음
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
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	include_once($thisPath.'config.php');

	// 넘어온값 처리
	$mode_get = $_GET['mode'] ?? 'userinfo';
	$bid_get = $_GET['bid'] ?? '';
	$userid_get = $_GET['userid'] ?? '';
	$tel_get = $_GET['tel'] ?? '';
	$hp_get = $_GET['hp'] ?? '';
	$order_get = $_GET['order'] ?? '';
	$msc_column_get = $_GET['msc_column'] ?? '';
	$msc_string_get = $_GET['msc_string'] ?? '';

	// table
	$table_logon		= $SITE['th'].'logon';
	$table_groupinfo	= $SITE['th'].'groupinfo';
	$table_joininfo		= $SITE['th'].'joininfo';
	$table_payment		= $SITE['th'].'payment';
	$table_service		= $SITE['th'].'service';
	$table_loguser		= $SITE['th'].'log_userinfo';
	$table_log_wtmp		= $SITE['th'].'log_wtmp';
	$table_log_lastlog	= $SITE['th'].'log_lastlog';
	$table_account		= $SITE['th'].'account';
	$table_accountinfo	= $SITE['th'].'accountinfo';
	
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
	$sql_table= explode('.',$msc_column_get);
	if(count($sql_table)!=2 || empty($msc_string_get)) go_url('msearch.php');
	
	// - $sql_where
	$msc_string_safe = db_escape($msc_string_get);
	// ereg를 strpos로 변경
	if( strpos($msc_string_get, '%') !== false ) {
		if($msc_string_get=='%') $msc_string_safe = '%%';
		$sql_where	= " `({$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` LIKE '{$msc_string_safe}') ";
	}
	else $sql_where	= " `({$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` = '{$msc_string_safe}') ";
	
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
		go_url("msearch.php?mode={$mode_get}&msc_column={$msc_column_get}&msc_string=" . urlencode($msc_string_get));
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

// $logon
$zip = explode('-', $logon['zip'] ?? '');
$logon['zip1'] = $zip['0'] ?? '';
$logon['zip2'] = $zip['1'] ?? '';
$zip = explode('-', $logon['c_zip'] ?? '');
$logon['c_zip1'] = $zip['0'] ?? '';
$logon['c_zip2'] = $zip['1'] ?? '';
userEnumSetFieldsToOptionTag($dbinfo['table'],$logon); // $list['필드_option']에 enum,set필드 <option>..</option>생성

// form_default
$form_default = " method='post' action='ok.php'>";
$form_default .= substr(href_qs("mode=userinfomodify&bid=".($logon['uid'] ?? 0)."&msc_column={$msc_column_get}&msc_string=".urlencode($msc_string_get),'mode=',1),0,-1);
$tpl->set_var('form_default',$form_default);

// 템플릿 마무리 할당
$tpl->tie_var('dbinfo'			,$dbinfo);
$tpl->set_var('href'			,$href ?? []);
$tpl->tie_var('get'				,$_GET);
$tpl->set_var('logon'			,$logon);

// - 회원전체 서치 부분
$tpl->set_var('count_msearch', $count_msearch ?? 0);
$tpl->set_var('get.msc_string',htmlspecialchars(stripslashes($msc_string_get),ENT_QUOTES));
$form_msearch = " method=get action='{$_SERVER['PHP_SELF']}'> ";
$form_msearch .= substr(href_qs("mode={$mode_get}",'mode=',1),0,-1);
$tpl->set_var('form_msearch',$form_msearch);


// 마무리
// ereg_replace를 preg_replace로 변경
$replacement = '$1' . $thisUrl.'skin/'.($dbinfo['skin'] ?? 'basic').'/images/';
$pattern = '/([="\'])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html',TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================

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
		
		$list[$field . '_option'] = $return;
	} // end for
	db_free($table_def);
} // end function
?>