<?php
//=======================================================
// 설  명 : 관리자 페이지 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 25/11/10 Gemini PHP7/MariaDB 환경 맞춤 및 DB 함수 변경
//=======================================================
$HEADER=array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'	=>1, // DB 커넥션 사용
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

	// table

	

	//시즌정보
	$sql = " SELECT *, sid as s_id
				FROM savers_secret.season
				WHERE pnt_race = 1
			  ORDER BY s_start DESC ";
	$rs = db_query($sql);
	$cnt = db_count($rs);
	
	$sselect = ''; // 변수 초기화
	$s_name = ''; // 변수 초기화
	
	if($cnt > 0)	{
		for($i = 0 ; $i < $cnt ; $i++)	{
			$list = db_array($rs);
			
			//최신 시즌
			if ($i == 0 && !isset($_GET['s_id'])) {
				$_GET['s_id'] = $list['s_id'];
				$s_name = $list['s_name'];
			}
			
			if(isset($_GET['s_id']) && $_GET['s_id'] == $list['s_id']){
				$sselect .= "<option value={$list['s_id']} selected>{$list['s_name']}</option>";
				$s_name = $list['s_name'];
			}else
				$sselect .= "<option value={$list['s_id']}>{$list['s_name']}</option>";
		}		
	}
	
	$kpoint = array(); // $kpoint 배열 초기화
	$href = array(); // $href 배열 초기화
	$form_default = ""; // $form_default 변수 초기화
	$list_logon = array(); // $list_logon 배열 초기화
	
	if (isset($_GET['mode']) && $_GET['mode'] == 'allreg' ){
		
		$form_default = " method='post' action='kok.php'>";
		// 중괄호 사용 시 변수명 명확히
		$form_default .= substr(href_qs("mode=kpoint_allreg&s_id={$_GET['s_id']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['subtitle'] = "일괄 등록";
		$kpoint['rdate_date'] = date("Y-m-d");
		$href['inquiry'] = "klist.php?". href_qs("mode=inquiry&s_id={$_GET['s_id']}&cur_sid={$_GET['s_id']}",$qs_basic);
		
	}else if (isset($_GET['mode']) && $_GET['mode'] == 'modify' ){
		
		$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0; // 정수형 캐스팅
		
		$sql = "select *
				  from {$dbinfo['table_kpoint']}
				 where pid={$pid} LIMIT 1";
		$kpoint = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		
		$form_default = " method='post' action='kok.php'>";
		// 중괄호 사용 시 변수명 명확히
		$form_default .= substr(href_qs("mode=kpointmodify&pid={$kpoint['pid']}&bid={$_GET['bid']}&accountno={$kpoint['accountno']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['subtitle'] = "수정";
		$href['inquiry'] = "kread.php?". href_qs('mode=inquiry&bid='.$_GET['bid']."&s_id=".$_GET['s_id']."&cur_sid=".$_GET['s_id']."&accountno=".$_GET['accountno'],$qs_basic);
		
	}else{
		// 템플릿 마무리 할당
		$kpoint = userGetDefaultFromTable($dbinfo['table']);
		
		if (isset($_GET['accountno']) && $_GET['accountno']) $kpoint['accountno'] = $_GET['accountno'];
		$kpoint['rdate_date'] = date("Y-m-d");
		
		userEnumSetFieldsToOptionTag($dbinfo['table'],$kpoint); // $list['필드_option']에 enum,set필드 <option>..</option>생성
	
		$form_default = " method='post' action='kok.php'>";
		// 중괄호 사용 시 변수명 명확히
		$form_default .= substr(href_qs("mode=kpointadd&bid={$_GET['bid']}&accountno={$kpoint['accountno']}&cur_sid={$_GET['cur_sid']}",'mode=',1),0,-1);
		$kpoint['type'] = "홈경기(주중)";
		$kpoint['remark'] = "홈경기(주중) 포인트적립";
		$kpoint['deposit'] = "200";
		$kpoint['subtitle'] = "등록";
		$href['inquiry'] = "kread.php?". href_qs('mode=inquiry&bid='.$_GET['bid']."&s_id=".$_GET['s_id']."&cur_sid=".$_GET['s_id']."&accountno=".$_GET['accountno'],$qs_basic);
	}
	
	if(isset($_GET['bid']) && $_GET['bid']){
		$bid = (int)$_GET['bid'];
		$sql_where_logon = " uid = {$bid} ";
		$sql_logon = "SELECT *
						 from {$dbinfo['table_logon']}	
						WHERE $sql_where_logon ";
		$list_logon=db_arrayone($sql_logon);
		
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->tie_var('kpoint'		,$kpoint);
$tpl->set_var('form_default',$form_default);
// 배열 키에 변수가 아닌 리터럴 사용 권장, 변수가 복잡할 경우 중괄호 사용
$tpl->set_var("type_checked_{$kpoint['type']}", "checked");
$tpl->set_var('sselect',$sselect);
$tpl->set_var('href'		,$href);
$tpl->set_var('list_logon',$list_logon);
$tpl->set_var('s_name',$s_name);

// 마무리
// PHP 7에서 preg_replace의 /e 수정자 사용은 권장되지 않지만, 현재 코드에서는 사용하지 않으므로 그대로 유지
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// enum,set필드라면, $list['필드_option'] 만들어줌
function  userEnumSetFieldsToOptionTag($table,&$list) { // 05/02/03 박선민
	
	$sql_def = 'SHOW FULL COLUMNS FROM `' . $table . '`'; // FULL COLUMNS 사용
	$rs_def = db_query($sql_def);
	if(!$rs_def) return;
	
	$fields_cnt	 = db_count($rs_def);
	
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($rs_def);
		$field			= $row_table_def['Field'];

		//$len			 = @mysql_field_len($result, $i); // 제거 대상
		
		// preg_replace 대신 strstr 사용 및 정규식 보완
		if(strstr($row_table_def['Type'], '(') !== false) {
			$row_table_def['True_Type'] = substr($row_table_def['Type'], 0, strpos($row_table_def['Type'], '('));
		} else {
			$row_table_def['True_Type'] = $row_table_def['Type'];
		}

		if($row_table_def['True_Type']=='enum')
			$aFieldValue = array(isset($list[$field]) ? $list[$field] : '');
		elseif($row_table_def['True_Type']=='set')
			$aFieldValue = isset($list[$field]) ? explode(',',$list[$field]) : array();
		else continue;
		
		$return	= '';

		// The value column (depends on type)
		// ----------------
		// Type에서 enum/set 정의 부분을 깔끔하게 추출
		$enum_str = substr($row_table_def['Type'], strlen($row_table_def['True_Type']) + 1, -1);
		$enum		= explode('\',\'', substr($enum_str, 1, -1));
		$enum_cnt	= count($enum);

		// show dropdown or radio depend on length
		for ($j = 0; $j < $enum_cnt; $j++) {
			// Removes automatic MySQL escape format - PHP 7에서는 필요 없을 수 있으나 기존 로직 최대한 유지
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
			$return .= '<option value="' . htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			
			// isset() 및 빈 문자열 처리 보완
			$defaultValue = ($row_table_def['Null'] != 'YES' && isset($row_table_def['Default'])) ? $row_table_def['Default'] : '';
			
			if ( (isset($list[$field]) && in_array($enum_atom,$aFieldValue)) 
				|| (!isset($list[$field]) && $defaultValue != '' && $enum_atom == $defaultValue) ) {
				$return .=  ' selected="selected"';
			}
			$return .=  '>' . htmlspecialchars($enum_atom) . "</option>\n";
		} // end for
		
		$list[$field.'_option'] = $return;
	} // end for
} // end function

// 테이블에서 기본값들을 가져오는 함수
// $field값이 있을 경우, 해당 필드 기본값을 string으로 return
// $field값이 없을 경우, 모든 필드의 기본값을 array로 return
function  userGetDefaultFromTable($table,$field='') { // 05/02/03 박선민
	$sql_like = '';
	if($field) {
		$safe_field = db_escape($field); // SQL 인젝션 방지
		$sql_like = ' LIKE \'' . $safe_field . '\' ';
	}
	
	$sql_def = 'SHOW COLUMNS FROM `' . $table . '`' . $sql_like;
	$rs_def = db_query($sql_def);
	
	if(!$rs_def) return;
	
	$list = array();

	$fields_cnt	 = db_count($rs_def);
	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($rs_def);
		$list[$row_table_def['Field']] = $row_table_def['Default'];
	} // end for
	
	if($field) {
		return isset($list[$field]) ? $list[$field] : null; // field가 없으면 null 반환
	}
	else return $list;
} // end function
?>