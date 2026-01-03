<?php
//=======================================================
// 설  명 : 관리자 페이지 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 25/11/10 Gemini   PHP 7 마이그레이션 (mysql_* 함수 db_*로 대체, 배열 변수 접근 수정)
//=======================================================
$HEADER=array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 기본 URL QueryString
	$qs_basic = "db=$db".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid=$cateuid".		//cateuid
				"&pern=$pern" .	// 페이지당 표시될 게시물 수
				"&sc_column=$sc_column".	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
				"&bid=$bid".
				"&s_id=$s_id".
				"&cur_sid=$cur_sid".
				"&page=$page".
				"&sdate=$sdate".
				"&edate=$edate".
				"&search=$search".
				"&pay_cate=$pay_cate".
				"&term_id=$term_id"
				;				//현재 페이지
				
	include_once("$thisPath/dbinfo.php");	// $dbinfo 가져오기
	

	if ($_GET['mode'] == 'p_modify' ){
		
		// SQL 내 배열 변수 접근 시 중괄호 사용
		$sql = "select * 
				  FROM {$dbinfo['table_kpresent']} 
				  WHERE uid={$_GET['uid']} LIMIT 1";
		$kpresent_list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		$kpresent_list['pdate'] = date("Y-m-d", $kpresent_list['pdate']);
		
		$form_default = " method='post' action='kok.php'>";
		// href_qs 내 배열 변수 접근 시 중괄호 사용
		$form_default .= substr(href_qs("mode=p_modify&uid={$kpresent_list['uid']}&bid={$_GET['bid']}&s_id={$_GET['s_id']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
	}else{		
		// 템플릿 마무리 할당
		$kpresent_list = userGetDefaultFromTable($dbinfo['table']);
		
		if (isset($_GET['point']) && $_GET['point']>=0) $kpresent_list['point'] = $_GET['point']; // isset 체크 추가
		if (!isset($kpresent_list['pdate'])) $kpresent_list['pdate'] = date("Y-m-d"); // isset 체크 추가
		$kpresent_list['present'] = "구단로고발목양말";
		$kpresent_list['memo'] = "현장지급";
		
		userEnumSetFieldsToOptionTag($dbinfo['table'],$kpresent_list); // $list['필드_option']에 enum,set필드 <option>..</option>생성
	
		$form_default = " method='post' action='kok.php'>";
		// href_qs 내 배열 변수 접근 시 중괄호 사용
		$form_default .= substr(href_qs("mode=p_write&bid={$_GET['bid']}&s_id={$_GET['s_id']}&cur_sid={$_GET['cur_sid']}&point=",'mode=',1),0,-1);
	}

	if(isset($_GET['bid'])){ // isset 체크 추가
		// SQL 내 배열 변수 접근 시 중괄호 사용
		$sql = "select * 
				  from {$dbinfo['table_logon']} 
				  where uid={$_GET['bid']} LIMIT 1";
		$klogon_list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		if(!isset($klogon_list['balance']) || !$klogon_list['balance']) $klogon_list['balance'] = 0; // isset 체크 추가
	
	}
	if(isset($_GET['s_id'])){ // isset 체크 추가
		//시즌정보
		// SQL 내 배열 변수 접근 시 중괄호 사용
		$sql = " SELECT *
					FROM savers_secret.season 
					WHERE sid = {$_GET['s_id']}  LIMIT 1";
		$kseason_list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
	
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->tie_var('kpresent_list'		,$kpresent_list);
$tpl->tie_var('klogon_list'		,$klogon_list ?? []); // PHP 7에서 $klogon_list가 정의되지 않았을 경우를 대비 (null coalescing으로 빈 배열 할당)
$tpl->tie_var('kseason_list'		,$kseason_list ?? []); // PHP 7에서 $kseason_list가 정의되지 않았을 경우를 대비
$tpl->set_var('form_default',$form_default);
// $kpresent_list가 정의되지 않은 경우를 대비해 변수 자체를 isset으로 체크하지 않고, 배열 키 접근만 허용
$tpl->set_var("present_checked_{$kpresent_list['present']}", "checked"); 
$tpl->set_var("memo_checked_{$kpresent_list['memo']}", "checked");


// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// enum,set필드라면, $list['필드_option'] 만들어줌
function	userEnumSetFieldsToOptionTag($table,&$list) { // 05/02/03 박선민
	$table_def = db_query('SHOW FIELDS FROM '.db_escape($table)); // mysql_query() -> db_query() 및 db_escape() 추가
	if(!$table_def) return;

	$fields_cnt	 = (int)db_count($table_def); // @mysql_num_rows() -> db_count()
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($table_def); // mysql_fetch_assoc() -> db_array()
		$field			= $row_table_def['Field'];

		//$len			 = @mysql_field_len($result, $i);

		$row_table_def['True_Type'] = preg_replace('~\\(.*~', '', $row_table_def['Type']);
		if($row_table_def['True_Type']=='enum')
			$aFieldValue = array($list[$field]);
		elseif($row_table_def['True_Type']=='set')
			$aFieldValue = explode(',',$list[$field]);
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
				or (!isset($list[$field]) && $row_table_def['Null']!='YES'
								&& $enum_atom==$row_table_def['Default'])) {
				$return .=	' selected="selected"';
			}
			$return .=	'>' . htmlspecialchars($enum_atom) . "</option>\n";
		} // end for
		
		$list[$field.'_option'] = $return;
	} // end for
	db_free($table_def); // 결과 셋 해제 추가
} // end function

// 테이블에서 기본값들을 가져오는 함수
// $field값이 있을 경우, 해당 필드 기본값을 string으로 return
// $field값이 없을 경우, 모든 필드의 기본값을 array로 return
function	userGetDefaultFromTable($table,$field='') { // 05/02/03 박선민
	$sql_like = '';
	if($field) $sql_like = ' LIKE "'.db_escape($field).'" '; // db_escape() 적용
	
	// @mysql_query('SHOW COLUMNS FROM `'.$table.'` '.$sql_like) -> db_query()로 변경 및 db_escape() 적용
	$table_def = db_query('SHOW COLUMNS FROM `'.db_escape($table).'` '.$sql_like);
	if(!$table_def) return;
	
	$list = array();

	$fields_cnt	 = (int)db_count($table_def); // @mysql_num_rows() -> db_count()
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($table_def); // mysql_fetch_assoc() -> db_array()
		$list[$row_table_def['Field']] = $row_table_def['Default'];
	} // end for
	
	db_free($table_def); // 결과 셋 해제 추가

	if($field) return $list[$field];
	else return $list;
} // end function
?>