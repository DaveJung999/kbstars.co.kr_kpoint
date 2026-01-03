<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '쇼핑몰관리', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'admin/money/cashtax'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

// 쇼핑물 전용 함수 include
//include_once($thisPath.'userfunctions.php');
		
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	// 1. 넘어온값 체크
	$getinfo_get = $_GET['getinfo'] ?? '';
	$startdate_get = $_GET['startdate'] ?? '';
	$enddate_get = $_GET['enddate'] ?? '';
	$taxcash_status_get = $_GET['taxcash_status'] ?? '';
	$sc_string_get = $_GET['sc_string'] ?? '';
	$sc_column_get = $_GET['sc_column'] ?? '';
	$sort_get = $_GET['sort'] ?? '';
	$page_get = $_GET['page'] ?? 1;

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if($getinfo_get!='cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once($thisPath.'config.php');
	$dbinfo['table'] = $SITE['th'].'payment';

	// 4. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5. SQL문 where절 정리
	//======================
	$sql_where_parts = [];
	$sql_where_parts[] = "taxcash_name <> '' AND re =''";

	if($startdate_get && $enddate_get){
		$starttime = strtotime($startdate_get);
		$endtime = strtotime($enddate_get) + 86400 - 1; // 23:59:59까지 포함
		$sql_where_parts[] ="rdate >= '{$starttime}' AND rdate <= '{$endtime}'";
	}
	
	//상태 검색
	if($taxcash_status_get) {
		$status_safe = db_escape($taxcash_status_get);
		$sql_where_parts[] = "taxcash_status = '{$status_safe}'";
	}
	
	// 서치 게시물만..
	if($sc_string_get && $sc_column_get) {
		$sc_string_safe = db_escape($sc_string_get);
		$aTemp = explode(',', $sc_column_get);
		$tmp_parts = [];
		foreach($aTemp as $column) {
			if(!preg_match('/^[a-z0-9_-]+$/i', $column)) continue;
			
			switch($column) {
				case 'bid':
				case 'uid':
					$tmp_parts[] ="`".db_escape($column)."`='{$sc_string_safe}'";
					break;
				default :
					$search_term = str_replace(['%', '_'], ['\%', '\_'], $sc_string_safe);
					$tmp_parts[] ="`".db_escape($column)."` LIKE '%{$search_term}%'";
			}
		} // end for
		if(isset($tmp_parts)) {
			$sql_where_parts[] = ' (' . implode(' OR ', $tmp_parts) . ') ';
		}
	} // end if

	$sql_where = implode(' AND ', $sql_where_parts);
	if(!$sql_where) $sql_where = ' 1 ';

	//===========================
	// 6. SQL문 order by..절 정리
	//===========================
	switch($sort_get) {
		// get 해킹을 막기 위해 특정 값에만 order by 생성
		case 'uid':
		case 'title':
		case 'rdate':
			$sql_orderby = "`".db_escape($sort_get)."` ASC"; break;
		case '!uid':
		case '!title':
		case '!rdate':
			$sql_orderby = '`' . db_escape(substr($sort_get,1)) . '` DESC'; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? db_escape($dbinfo['orderby']) : 'uid DESC';
	}	
	
	// 7. 페이지 나눔등 각종 카운트 구하기
	$sql_count = "SELECT count(*) as cnt FROM {$dbinfo['table']} WHERE  $sql_where ";
	$result_count = db_query($sql_count);
	$count_total = $result_count ? (int)(db_array($result_count)['cnt'] ?? 0) : 0;
	
	$count=board2Count($count_total, $page_get, ($dbinfo['pern'] ?? 15), ($dbinfo['page_pern'] ?? 10)); // 각종 카운트 구하기
	
	$sql_today = "SELECT count(*) as cnt FROM {$dbinfo['table']} WHERE rdate > unix_timestamp(curdate()) AND  $sql_where ";
	$result_today = db_query($sql_today);
	$count['today'] = $result_today ? (int)(db_array($result_today)['cnt'] ?? 0) : 0;

	// 8. URL Link...
	$href['list']		= $thisUrl.'list.php?'.href_qs('page=',$qs_basic);
	if(($count['nowpage'] ?? 0) > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'list.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']		=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if(($count['nowpage'] ?? 0) < ($count['totalpage'] ?? 0)){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'list.php?'.href_qs('page='.$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= (($count['nowblock'] ?? 0)>1)					? $thisUrl.'list.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0))? $thisUrl.'list.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write']	= $thisUrl.'write.php?' . href_qs('mode=write',$qs_basic);	// 글쓰기

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tableoption=array();
$tableoption['taxcash_status'] = $taxcash_status_get;
userEnumSetFieldsToOptionTag($dbinfo['table'],$tableoption); // $list['필드_option']에 enum,set필드 <option>..</option>생성

$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT ".($count['firstno'] ?? 0).",".($count['pern'] ?? 0);

$rs_list = db_query($sql);
$total = $rs_list ? db_count($rs_list) : 0;

if(!$total) {
	if($sc_string_get) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string_get),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면..
		$tpl->process('LIST', 'nolist');
} else {
	while($list = db_array($rs_list)) {
		$list['no']	= ($count['lastnum'] ?? 0)--;
		$list['rede']	= strlen($list['re'] ?? '');
		$list['rdate_date']= isset($list['rdate']) ? date('y/m/d', (int)$list['rdate']) : '';	//	날짜 변환
				
		//가격 변신
		$list['totalprice'] = number_format($list['totalprice'] ?? 0);
		
		//발행관련 결과
		if(($list['taxcash_status'] ?? '') == "발행완료"){
			$list['taxcash_status_ok'] = "ok";
		}else{
			$list['taxcash_status_ok'] = "";
		}
		
		// 옵션부분 콤보박스
		if(isset($list['options'])) $list['options'] = userGetShopOptions($list['options']);
		if(isset($list['optionlink'])) $list['optionlink']	= userGetShopOptionLink($list['optionlink']);

		//	Search 단어 색깔 표시
		if($sc_string_get && $sc_column_get) {
			$highlighted_text = '<font color=darkred>$0</font>';
			$pattern = '/' . preg_quote($sc_string_get, '/') . '/i';
			if($sc_column_get=='title' && isset($list['cut_title']))
				$list['cut_title'] = preg_replace($pattern, $highlighted_text, $list['cut_title']);
			if(isset($list[$sc_column_get]))
				$list[$sc_column_get]	= preg_replace($pattern, $highlighted_text, $list[$sc_column_get]);
		}

		// URL Link...
		$href['read']		= '/sshop2/read.php?' . href_qs("db=" . ($list['db'] ?? '') . "&uid={$list['uid']}&cateuid=" . ($list['cateuid'] ?? ''),$qs_basic);
		$href['modify']	= $thisUrl.'write.php?'.href_qs("mode=modify&uid={$list['uid']}",$qs_basic);
		
		userEnumSetFieldsToOptionTag($dbinfo['table'],$list); // $list['필드_option']에 enum,set필드 <option>..</option>생성

		// 템플릿 할당
		$tpl->set_var('href.modify'		, $href['modify']);
		$tpl->set_var('list'			, $list);
	
		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 업로드부분 템플릿내장값 지우기
		$tpl->drop_var('list.upfiles');
	} // end for (i)
	db_free($rs_list);
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href['read']);
	$tpl->drop_var('href.download'); unset($href['download']);
	$tpl->drop_var('list');
} // end if (게시물이 있다면...)
// 템플릿 마무리 할당

// 템플릿 마무리 할당
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,htmlspecialchars(stripslashes($sc_string_get),ENT_QUOTES));	// 서치 단어
$tpl->set_var('get.taxcash_status_option',$tableoption['taxcash_status_option'] ?? '');
$tpl->set_var('cateinfo.catelist',$cateinfo['catelist'] ?? '');
$tpl->set_var('get.view_status_option',$tableoption['view_status_option'] ?? '');
$tpl->tie_var('dbinfo'			,($dbinfo ?? []));	// dbinfo 정보 변수
$tpl->tie_var('count'			,($count ?? []));	// 게시판 각종 카운트
$tpl->tie_var('href'			,($href ?? []));	// 게시판 각종 링크
$tpl->set_var('sort_'.$sort_get,true);	// sort_???

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'list.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if(($count['nowpage'] ?? 0) > 1) {
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
} else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
	// <-- (이전블럭) 부분
	if (($count['nowblock'] ?? 0)>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=($count['firstpage'] ?? 1);$i<=($count['lastpage'] ?? 1);$i++) {
		$tpl->set_var('blockcount',$i);
		if($i==($count['nowpage'] ?? 0))
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', $thisUrl.'list.php?'.href_qs('page='.$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	} // end for
	// --> (다음블럭) 부분
	if (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0)) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if(($count['nowpage'] ?? 0) < ($count['totalpage'] ?? 0)) {
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
} else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml(($dbinfo ?? []), $SITE, $thisUrl);


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