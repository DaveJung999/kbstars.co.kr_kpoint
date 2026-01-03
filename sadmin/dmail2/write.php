<?php
//=======================================================
// 설	명 : 심플리스트-추가/수정
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/11/26 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
);
require(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '' . "/sinc/header.php");
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
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 1. 넘어온값 체크
	$mode = $_GET['mode'] ?? '';
	$uid = $_REQUEST['uid'] ?? '';

	// 2. $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	global $SITE, $db_conn;

	// 3. 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$skip_fields = array('uid'); // uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
	$dbinfo_table = {$dbinfo['table']} ?? '';
	if($fieldlist = userGetAppendFields($dbinfo_table, $skip_fields)) { 
		foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
	}		
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화
	
	// 수정모드라면
	if(isset($mode) && $mode == "modify"){
		$uid_safe = db_escape($uid);
		$sql = "SELECT * FROM {$dbinfo_table} WHERE uid='{$uid_safe}'";
		$list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		
		$table_dmail = ($SITE['th'] ?? '') . "dmail_" . ($list['db'] ?? '');
	
		/////////////////////////////////
		// 추가되어 있는 테이블 필드 포함
		$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip' ,	'rdate');
		if($fieldlist = userGetAppendFields($dbinfo_table, $skip_fields)){
			foreach($fieldlist as $value){
				$list[$value]	= htmlspecialchars($list[$value] ?? '', ENT_QUOTES);
			}
		}
		////////////////////////////////
		
		// 업로드파일 처리
		if((($dbinfo['enable_upload'] ?? '') != 'N') and isset($list['upfiles']) && $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles = array();
				$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
				$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name']) && $value['name'])
					$upfiles[$key]['href']="/smember/companytax/download.php?" . href_qs("uid=" . ($list['uid'] ?? '') . "&upfile={$key}",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리
	
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$rdate = $_GET['rdate'] ?? '';
		$form_default .= href_qs("mode=modify&rdate={$rdate}", $qs_basic,1);
		$form_default = substr($form_default,0,-1);
	} else {
		$mode = "write";
		
		$form_default = " method='post' action='{$thisUrl}/{$urlprefix}ok.php' ENCTYPE='multipart/form-data'>";
		$rdate = $_GET['rdate'] ?? '';
		$form_default .= href_qs("mode=write&rdate={$rdate}",$qs_basic,1);
		$form_default = substr($form_default,0,-1);
	}
	
	// URL Link...
	$href['list'] = "list.php?{$qs_basic}";
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skin = $dbinfo['skin'] ?? 'basic';
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.$skin.'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 메일리스트 추가 필드
$list_tpl_field = '';
if(isset($mode) && $mode == "modify"){
	$db_list = $list['db'] ?? '';
	$table_dmail = ($SITE['th'] ?? '') . "dmail_" . db_escape($db_list);
	$fields_query = "SHOW COLUMNS FROM {$table_dmail}";
	$fields = db_query($fields_query);
	if ($fields){
		$columns = db_count($fields);		
		for ($i = 0; $i < $columns; $i++){
			$a_fields=db_array($fields);
			
			if( !in_array($a_fields['Field'],array('uid','status','emailcheck','readtime')) ){
				$list_tpl_field .=	"<option>".htmlspecialchars($a_fields['Field']) . "</option>\n";
			}
		}
		db_free($fields);
	}

} 
elseif(isset($mode) && $mode == "write"){
	$list_tpl_field = "
	<option>email</option>
	<option>userid</option>
	";
}
if(isset($list)){
	$list['tpl_field'] = $list_tpl_field;
}

// 템플릿 마무리 할당
userEnumFieldsToOptionTag(($dbinfo['table'] ?? ''), (isset($list) ? $list : null)); // enum필드 <option>..</option>생성
$tpl->set_var('list'		,(isset($list) ? $list : null));
$tpl->set_var("form_default",	$form_default);

$tpl->set_var('get'				, (isset($_GET) ? $_GET : null)); 	// get값으로 넘어온것들
$tpl->set_var('dbinfo'			, (isset($dbinfo) ? $dbinfo : null));	// dbinfo 정보 변수
$tpl->set_var('href'			, (isset($href) ? $href : null));	// 게시판 각종 링크
$tpl->set_var("mode_" . ($mode ?? ''),1); // mode_write, mode_modify 값있게

// 마무리
$tpl->echoHtml((isset($dbinfo) ? $dbinfo : null), $SITE, $thisUrl);

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

// enum필드라면, $list[필드이름_options] 만들어줌
// 04/08/17 박선민
function	userEnumFieldsToOptionTag($table,&$list){
	global $db_conn;
	$table_safe = db_escape($table);
	$table_def = db_query("SHOW FIELDS FROM {$table_safe}");
	/**
	 * Displays the form
	 */
	if ($table_def){
		$fields_cnt	 = db_count($table_def);
		for ($i = 0; $i < $fields_cnt; $i++){
			$row_table_def	 = db_array($table_def);
			$field			 = $row_table_def['Field'];

			//$len			 = @mysql_field_len($result, $i);

			$row_table_def['True_Type'] = preg_replace('/\\(.*$/', '', $row_table_def['Type']);
			if($row_table_def['True_Type'] != 'enum') continue;
			
			$return	= '';

			// The value column (depends on type)
			// ----------------
			$enum		= str_replace('enum(', '', $row_table_def['Type']);
			$enum		= preg_replace('/\\)$/', '', $enum);
			$enum_array		= explode("','", substr($enum, 1, -1));
			$enum_cnt	= count($enum_array);

			// show dropdown or radio depend on length
			for ($j = 0; $j < $enum_cnt; $j++){
				// Removes automatic MySQL escape format
				$enum_atom = str_replace("''", "'", str_replace('\\\\', '\\', $enum_array[$j]));
				$return .= '<option value="' . htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
				if ((isset($list[$field]) && $list[$field] == $enum_atom)
					|| (!isset($list[$field]) && ($row_table_def['Null'] != 'YES')
						 && $enum_atom == $row_table_def['Default'])){
					$return .=	' selected="selected"';
				}
				$return .=	'>' . htmlspecialchars($enum_atom) . '</option>' . "\n";
			} // end for
			
			if(is_array($list)) {
				$list["{$field}_option"] = $return;
			}
		} // end for
		db_free($table_def);
	}
} // end function
?>