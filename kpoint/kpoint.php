<?php
//=======================================================
// 설  명 : 관리자 페이지 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환, Short Tag)
//=======================================================
$HEADER=array(
//	'private'	=>1, // 브라우저 캐쉬
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

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
	// 기본 URL QueryString
	$qs_basic = "db={$db}".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid={$cateuid}".		//cateuid
				"&pern={$pern}" .	// 페이지당 표시될 게시물 수
				"&sc_column={$sc_column}".	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
				"&bid={$bid}".
				"&s_id={$s_id}".
				"&cur_sid={$cur_sid}".
				"&page={$page}".
				"&sdate={$sdate}".
				"&edate={$edate}".
				"&search={$search}".
				"&pay_cate={$pay_cate}".
				"&term_id={$term_id}"
				;				//현재 페이지
				
	include_once("$thisPath/dbinfo.php");	// $dbinfo 가져오기
	

	//시즌정보
	// [!] FIX: NBSP 제거
	$sql = " SELECT *, sid as s_id 
				FROM savers_secret.season 
				WHERE pnt_race = 1
				ORDER BY s_start DESC ";
	$rs = db_query($sql);
	$cnt = db_count($rs);
	
	if($cnt)	{
		for($i = 0 ; $i < $cnt ; $i++)	{
			$list = db_array($rs);
			//최신 시즌
			if ($i == 0 && !($_GET['s_id'] ?? null)) {
				$_GET['s_id'] = $list['s_id'];
				$s_name = $list['s_name'];
			}
			
			if(($_GET['s_id'] ?? null) == $list['s_id']){
				$sselect .= "<option value={$list['s_id']} selected>{$list['s_name']}</option>";
				$s_name = $list['s_name'];
			}else
				$sselect .= "<option value={$list['s_id']}>{$list['s_name']}</option>";
		}
		db_free($rs);
	}
	
	if (($_GET['mode'] ?? null) == 'allreg' ){
		
		$form_default = " method='post' action='kok.php'>";
		// [!] FIX: {$_GET} 변수 중괄호 {} 적용
		$form_default .= substr(href_qs("mode=kpoint_allreg&s_id={$_GET['s_id']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['subtitle'] = "일괄 등록";
		$kpoint['rdate_date'] = date("Y-m-d");;
		// [!] FIX: {$_GET} 변수 중괄호 {} 적용
		$href['inquiry'] = "klist.php?". href_qs("mode=inquiry&s_id={$_GET['s_id']}&cur_sid={$_GET['s_id']}",$qs_basic);
		
	}else if (($_GET['mode'] ?? null) == 'modify' ){
		
		// [!] FIX: {$dbinfo['table_kpoint']}, {$_GET['pid']} 변수 중괄호 {} 적용
		$sql = "select * from {$dbinfo['table_kpoint']} 
				 where pid={$_GET['pid']} LIMIT 1";
		$kpoint = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		
		$form_default = " method='post' action='kok.php'>";
		// [!] FIX: {$_GET}, {$kpoint} 변수 중괄호 {} 적용
		$form_default .= substr(href_qs("mode=kpointmodify&pid={$kpoint['pid']}&bid={$_GET['bid']}&accountno={$kpoint['accountno']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['subtitle'] = "수정";
		// [!] FIX: {$_GET}, {$qs_basic} 변수 중괄호 {} 적용
		$href['inquiry'] = "kread.php?". href_qs('mode=inquiry&bid='.$_GET['bid']."&s_id=".$_GET['s_id']."&cur_sid=".$_GET['s_id']."&accountno=".$_GET['accountno'],$qs_basic);
		
	}else{
		// 템플릿 마무리 할당
		$kpoint = userGetDefaultFromTable($dbinfo['table']);
		
		if (isset($_GET['accountno'])) $kpoint['accountno'] = $_GET['accountno'];
		$kpoint['rdate_date'] = date("Y-m-d");
		
		userEnumSetFieldsToOptionTag($dbinfo['table'],$kpoint); // $list['필드_option']에 enum,set필드 <option>..</option>생성
	
		$form_default = " method='post' action='kok.php'>";
		// [!] FIX: {$_GET}, {$kpoint} 변수 중괄호 {} 적용
		$form_default .= substr(href_qs("mode=kpointadd&bid={$_GET['bid']}&accountno={$kpoint['accountno']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['type'] = "홈경기(주중)";
		$kpoint['remark'] = "홈경기(주중) 포인트적립";
		$kpoint['deposit'] = "200";
		$kpoint['subtitle'] = "등록";
		// [!] FIX: {$_GET}, {$qs_basic} 변수 중괄호 {} 적용
		$href['inquiry'] = "kread.php?". href_qs('mode=inquiry&bid='.$_GET['bid']."&s_id=".$_GET['s_id']."&cur_sid=".$_GET['s_id']."&accountno=".$_GET['accountno'],$qs_basic);
	}
	
	if(($_GET['bid'] ?? null)){
		$sql_where_logon = " uid = {$_GET['bid']} ";
		$sql_logon = "SELECT * FROM {$dbinfo['table_logon']}	
						WHERE $sql_where_logon ";
		$list_logon=db_arrayone($sql_logon);
		
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->tie_var('kpoint'		,$kpoint);
$tpl->set_var('form_default',$form_default);
$tpl->set_var("type_checked_{$kpoint['type']}", "checked");
$tpl->set_var('sselect',($sselect ?? ''));
$tpl->set_var('href'		,($href ?? []));
$tpl->set_var('list_logon',($list_logon ?? []));
$tpl->set_var('s_name',($s_name ?? ''));

// 블럭: 일괄 등록
if (($_GET['mode'] ?? null) == 'allreg' ){
	$tpl->process('WIN','allreg');
}

// 마무리
$val='\\1'.$thisUrl.'skin/'.($dbinfo['skin'] ?? 'basic').'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// enum,set필드라면, $list['필드_option'] 만들어줌
function  userEnumSetFieldsToOptionTag($table,&$list) { // 05/02/03 박선민
	// [!] FIX: mysql_* 함수들을 db_query/db_count/db_array/db_free 함수로 대체
	$table_def = db_query('SHOW FIELDS FROM ' . db_escape($table));
	if(!$table_def) return;

	$fields_cnt	 = db_count($table_def);
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($table_def);
		$field			= $row_table_def['Field'];

		//$len			 = @mysql_field_len($result, $i);

		$row_table_def['True_Type'] = preg_replace('~\\(.*~', '', $row_table_def['Type']);
		if($row_table_def['True_Type']=='enum')
			$aFieldValue = array($list[$field] ?? null);
		elseif($row_table_def['True_Type']=='set')
			$aFieldValue = explode(',', $list[$field] ?? '');
		else continue;
		
		$return	= '';

		// The value column (depends on type)
		// ----------------
		$enum		= str_replace($row_table_def['True_Type'].'(', '', $row_table_def['Type']);
		$enum		= preg_replace('/\\)$/', '', $enum);
		$enum		= explode('\',\'', substr($enum, 1, -1));
		$enum_cnt	= count($enum);

		// show dropdown or radio depend on length
		for ($j = 0; $j < $enum_cnt; $j++) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
			$return .= '<option value="' . htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			if ((isset($list[$field]) && in_array($enum_atom,$aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
								&& $enum_atom==$row_table_def['Default'])) {
				$return .=  ' selected="selected"';
			}
			$return .=  '>' . htmlspecialchars($enum_atom) . "</option>\n";
		} // end for
		
		$list[$field.'_option'] = $return;
	} // end for
	db_free($table_def); // [!] FIX: 리소스 해제
} // end function

// 테이블에서 기본값들을 가져오는 함수
// $field값이 있을 경우, 해당 필드 기본값을 string으로 return
// $field값이 없을 경우, 모든 필드의 기본값을 array로 return
function  userGetDefaultFromTable($table,$field='') { // 05/02/03 박선민
	// [!] FIX: mysql_* 함수들을 db_query/db_count/db_array/db_free 함수로 대체
	$safe_table = db_escape($table);
	if($field) $sql_like = ' LIKE "'.db_escape($field).'" ';
	else $sql_like = '';
	
	// [!] FIX: SHOW COLUMNS 쿼리 시 백틱(`) 적용
	$table_def = db_query('SHOW COLUMNS FROM `'.$safe_table.'` '.$sql_like);
	if(!$table_def) return;
	
	$list = array();

	$fields_cnt	 = db_count($table_def);
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($table_def);
		$list[$row_table_def['Field']] = $row_table_def['Default'];
	} // end for
	db_free($table_def); // [!] FIX: 리소스 해제
	
	if($field) return $list[$field] ?? null;
	else return $list;
} // end function