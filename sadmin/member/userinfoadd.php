<?php
//=======================================================
// 설	명 : 관리자 페이지 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	// table
	$table_logon = $SITE['th'] . 'logon';
	
	$dbinfo = array(
				'skin'	 =>	'basic',
				'table'	 =>	$table_logon				
			);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$logon = userGetDefaultFromTable($dbinfo['table']);
print_r($logon);
userEnumSetFieldsToOptionTag($dbinfo['table'],$logon); // $list['필드_option']에 enum,set필드 <option>..</option>생성
$tpl->tie_var('logon'		,$logon);

$form_default = " method='post' action='ok.php'>";
$form_default .= substr(href_qs("mode=userinfoadd",'mode=',1),0,-1);
$tpl->set_var('form_default',$form_default);

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
	$table_def = db_query("SHOW FIELDS FROM {$table}");
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

/**
 * 테이블의 특정 필드 또는 전체 필드의 기본값을 가져옵니다.
 *
 * @param string $table 테이블 이름.
 * @param string $field (선택) 특정 필드의 이름을 지정하면 해당 필드의 기본값만 반환합니다.
 * @return mixed|array|null 필드가 지정된 경우 해당 필드의 기본값, 그렇지 않은 경우 [필드명 => 기본값] 형태의 배열을 반환합니다.
 */
function userGetDefaultFromTable($table, $field = '') {
	// 전역 DB 연결은 db_* 함수 내부에서 처리되므로 global 선언이 필요 없습니다.

	// 보안 참고: db_escape() 함수를 사용하여 SQL 인젝션을 방어합니다.
	$sql_like = $field ? " LIKE '" . db_escape($field) . "'" : '';

	// 2025-08-19 Gemini: 
	// 보안 참고: SHOW COLUMNS 구문은 Prepared Statements를 지원하지 않으므로,
	// 이 함수를 호출하기 전에 $table 변수가 신뢰할 수 있는 값인지 확인하는 것이 좋습니다.
	$safe_table = db_escape($table);
	$result = db_query("SHOW COLUMNS FROM {$safe_table} {$sql_like}");

	if (!$result) {
		return $field ? '' : [];
	}

	$list = [];
	// 2025-08-19 Gemini: 
	while ($row = db_array($result)) {
		$list[$row['Field']] = $row['Default'];
	}

	// 2025-08-19 Gemini: 
	db_free($result);

	return $field ? ($list[$field] ?? null) : $list;
}
?>
