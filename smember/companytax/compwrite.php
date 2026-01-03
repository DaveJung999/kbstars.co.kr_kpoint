<?php
//=======================================================
// 설	명 : 사이트 회사 정보 입력/수정 - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/17
// Project: sitePHPbasic
// ChangeLog
//	DATE		수정인			수정 내용
// --------	----------	--------------------------------------
// 25/08/11	Gemini AI	PHP 7.4+ 호환성 업데이트, MySQLi 적용, 보안 강화
// 04/08/17	박선민		마지막 수정
//=======================================================
$HEADER=array(
		'usedb2' =>  1,
		'useSkin' =>  1, // 템플릿 사용
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST'] ?? '');

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= "comp"; // ???list.php ???write.ephp ???ok.php
	$thisPath	= __DIR__;
	$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// $dbinfo
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	global $SITE, $mysqli;
	$dbinfo['table'] = ($SITE['th'] ?? '') . "companyinfo";

	$mode = $_GET['mode'] ?? 'write';
	$list = [];

	// 수정모드라면
	if($mode === "modify"){
		$uid = $_GET['uid'] ?? 0;
		$stmt = $mysqli->prepare("select * from {$dbinfo['table']} where uid=?");
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$result = $stmt->get_result();
		$list = $result->fetch_assoc();
		$stmt->close();

		if (!$list) {
			back('해당 데이터가 없습니다.');
		}
	
		if(strlen($list['c_num']) == 10){
			$list['c_num1'] = substr($list['c_num'],0,3);
			$list['c_num2'] = substr($list['c_num'],3,2);
			$list['c_num3'] = substr($list['c_num'],5);
		}
		elseif(strlen($list['c_num']) == 14){
			$list['c_idnum'] = $list['c_num']; // c_num에 들어가 있는 값으로 주민번호 변경
		}
		
		$list['c_idnum'] = preg_replace('/[^0-9]/','',$list['c_idnum']);
		$list['c_idnum1'] = substr($list['c_idnum'],0,6);
		$list['c_idnum2'] = substr($list['c_idnum'],6);
		
		/////////////////////////////////
		// 추가되어 있는 테이블 필드 포함
		$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
		if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
			foreach($fieldlist as $value){
				if (isset($list[$value])) {
					$list[$value]	= htmlspecialchars($list[$value],ENT_QUOTES, 'UTF-8');
				}
			}
		}
		////////////////////////////////
		
		// 업로드파일 처리
		if(($dbinfo['enable_upload'] ?? 'N') !== 'N' and isset($list['upfiles'])){
			$upfiles = @unserialize($list['upfiles']);
			if($upfiles === false) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>  $value){
				if(isset($value['name']))
					$upfiles[$key]['href']="/smember/companytax/download.php?" . href_qs("uid={$list['uid']}&upfile={$key}", ($qs_basic ?? ''));
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리
	
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$form_default .= href_qs("mode=modify&rdate=" . ($_GET['rdate'] ?? ''),"mode=",1);
		$form_default = substr($form_default,0,-1);
	} else {
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$form_default .= href_qs("mode=write&rdate=" . ($_GET['rdate'] ?? ''),"mode=",1);
		$form_default = substr($form_default,0,-1);
	}
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
userEnumFieldsToOptionTag($dbinfo['table'],$list); // enum필드 <option>..</option>생성
$tpl->set_var('list'		,$list);
$tpl->set_var("form_default",	$form_default);

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", $val, $tpl->process('', 'html', TPL_OPTIONAL));

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
 * enum필드라면, $list[필드이름_options] 만들어줌 (Modernized version)
 * @param string $table
 * @param array &$list
 */
function	userEnumFieldsToOptionTag(string $table, array &$list){
	global $mysqli;
	$table_def_result = $mysqli->query("SHOW FIELDS FROM " . $mysqli->real_escape_string($table));
	
	if (!$table_def_result) {
		return;
	}

	while ($row_table_def = $table_def_result->fetch_assoc()){
		$field = $row_table_def['Field'];

		$row_table_def['True_Type'] = preg_replace('/\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type'] != 'enum') continue;
		
		$return	= '';

		// The value column (depends on type)
		// ----------------
		$enum		= str_replace('enum(', '', $row_table_def['Type']);
		$enum		= preg_replace('/\)$/', '', $enum);
		$enum		= explode('\',\'', substr($enum, 1, -1));

		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom){
			// Removes automatic MySQL escape format
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="'	. htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			if ((isset($list[$field]) && $list[$field] == $enum_atom)
				|| (!isset($list[$field]) && ($row_table_def['Null'] != 'YES')
					 && $enum_atom == $row_table_def['Default'])){
				$return .=	' selected="selected"';
			}
			$return .=	'>'	. htmlspecialchars($enum_atom) . '</option>'	. "\n";
		} // end for
		
		$list["{$field}_option"] = $return;
	} // end while
	$table_def_result->free();
} // end function
?>
