<?php
set_time_limit(0); // 네트워크 사정에 의해 중단되지 않도록..

//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
$HEADER=array(
	'priv' => 99, // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'html_echo' => '',	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'html_skin' => "admin_basic", // html header 파일(/stpl/basic/index_$HEADER['html'].php 파일을 읽음)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

$qs_basic = "db={$db}".					//table 이름
			"&mode=".					// mode값은 list.php에서는 당연히 빈값
			"&cateuid={$cateuid}".		//cateuid
			"&pern={$pern}" .	// 페이지당 표시될 게시물 수
			"&sc_column={$sc_column}".	//search column
			"&sc_string=" . urlencode(stripslashes($sc_string)) . //search string
			"&page={$page}".
			"&sup_bid={$sup_bid}"
			;				//현재 페이지

$thisPath	= dirname(__FILE__);
$thisUrl	= "/Admin/config"; // 마지막 "/"이 빠져야함
include_once("{$thisPath}/dbinfo.php"); // $dbinfo, $table_popup 값 정의

// 인증 체크
if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

if(isset($_GET['modify_uid'])) $modify_uid = $_GET['modify_uid'];
else $modify_uid = '';

// 넘어온 값에 따라 $dbinfo값 변경
if(isset($dbinfo['enable_getinfo']) && $dbinfo['enable_getinfo'] == 'Y'){
	if(isset($_GET['pern']))			$dbinfo['pern']		= (int)$_GET['pern'];
	if(isset($_GET['row_pern']))		$dbinfo['row_pern']	= (int)$_GET['row_pern'];
	if(isset($_GET['cut_length']))	$dbinfo['cut_length']	= (int)$_GET['cut_length'];
	if(isset($_GET['cateuid']))			$dbinfo['cateuid']		= (int)$_GET['cateuid'];
	if(isset($_GET['enable_listreply']))			$dbinfo['enable_listreply']		= $_GET['enable_listreply'];
	if(isset($_GET['sql_where']))		$sql_where		= $_GET['sql_where'];	//davej..............
	if(isset($_GET['page']))			$page		= $_GET['page'];

	// skin 변경
	if( isset($_GET['skin']) and preg_match("/^[_a-z0-9]+$/i",$_GET['skin'])
				and is_file("{$thisPath}/stpl/{$_GET['skin']}/popup.htm") ){
		if(isset($dbinfo['enable_getinfoskins'])) { // 사용 스킨을 제안했다면,
			$aTmp = explode(',',$dbinfo['enable_getinfoskins']);
			foreach($aTmp as $v){
				if($v == $_GET['skin']) $dbinfo['skin']	= $_GET['skin'];
			}
		}
		else $dbinfo['skin']	= $_GET['skin'];
	}
	// 사이트 해더테일 변경
	if(isset($_GET['html_headpattern']))	$dbinfo['html_headpattern'] = $_GET['html_headpattern'];
	if( isset($_GET['html_headtpl']) and preg_match("/^[_a-z0-9]+$/i",$_GET['html_headtpl'])
		and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$_GET['html_headtpl']}.php") )
		$dbinfo['html_headtpl'] = $_GET['html_headtpl'];
}

//===================
// SQL문 where절 정리
//===================
// 서치 게시물만..
if(!isset($sql_where)) $sql_where= " 1 ";

//=====
// misc
//=====
	// 페이지 나눔등 각종 카운트 구하기
$count['total']=db_count(db_query("SELECT * from {$table_popup} WHERE $sql_where group by rdate ORDER BY data4 desc ")); // 전체 게시물 수
$count=board2Count($count['total'],$page,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
$count['today']=db_result(db_query("SELECT count(*) FROM {$table_popup} WHERE (rdate > unix_timestamp(curdate())) and  $sql_where 	") , 0, "count(*)");

$limitno	= isset($_GET['limitno']) ? $_GET['limitno'] : $count['firstno'];
$limitrows	= isset($_GET['limitrows']) ? $_GET['limitrows'] : $count['pern'];

// 서치 폼의 hidden 필드 모두!!
$form_search =" name=search action='{$_SERVER['PHP_SELF']}' method='post'>
				<input type='hidden' name='db' value='{$db}'>
				<input type='hidden' name='cateuid' value='{$cateuid}'>
				<input type='hidden' name='pern' value='{$count['pern']}'
			";

// URL Link...
$href['write']	= "write.php?" . href_qs("mode=write",$qs_basic);	// 글씨기
if($count['nowpage'] > 1) { // 처음, 이전 페이지
	$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
	$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
} else {
	$href['firstpage']="#";
	$href['prevpage']	="#";
}
if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
	$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
	$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
} else {
	$href['nextpage']	="#";
	$href['lastpage'] ="#";
}
$href['prevblock']= ($count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "#";// 이전 페이지 블럭
$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "#";// 다음 페이지 블럭

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/{$dbinfo['skin']}/popup.htm") ) $dbinfo['skin']="basic";
$tpl->set_file('html',"{$thisPath}/stpl/{$dbinfo['skin']}/popup.htm",TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$rs_list = db_query("SELECT * from {$table_popup} WHERE $sql_where ORDER BY	data4 desc LIMIT {$limitno},{$limitrows}");
if(!db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($sc_string) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));

		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
}
else{
	if($dbinfo['row_pern']<1) $dbinfo['row_pern']=1; // 한줄에 여러값 출력이 아닌 경우
	$no = 0;
	while($list = db_array($rs_list)) {
		$list['no']	= $count['lastnum'];
		$list['rede']	= strlen($list['re']);
		$list['rdate_ymd']= $list['rdate'] ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환
		$list['data4']= $list['data4'] ? date("Y/m/d", $list['data4']) : "";	//	날짜 변환
		$list['data5']= $list['data5'] ? date("Y/m/d", $list['data5']) : "";	//	날짜 변환
		$list_modify['skin'] = $list['skin'];

		if($list['data3'] == 'yes') $list['data3'] = "<font color='red'>사용</font>";
		else	$list['data3'] = '미사용';

		$no ++;

		//제목과 내용 자르기 :: 정대입
		$list['cut_title'] = cut_string($list['title'], $dbinfo['cut_length']);
		$list['cut_content'] = cut_string($list['content'],300);
		$list['scr_title'] = addslashes($list['title']);

		if(!$list['title']) $list['title'] = "제목없음…";

		${"checked_data2_{$list['data2']}"} = " checked";
		${"checked_data3_{$list['data3']}"} = " checked";

		//	Search 단어 색깔 표시
		if($sc_string){
			if($sc_column){
				if($sc_column == "title")
					$list['cut_title'] = preg_replace("/(".preg_quote($sc_string, '/') . ")/i", "<font color=darkred>\\0</font>",	$list['cut_title']);
				else
					$list[$sc_column]	= preg_replace("/(".preg_quote($sc_string, '/') . ")/i", "<font color='darkred'>\\0</font>", $list[$sc_column]);
			} else {
				$list['userid']	= preg_replace("/(".preg_quote($sc_string, '/') . ")/i", "<font color=darkred>\\0</font>", $list['userid']);
				$list['cut_title']= preg_replace("/(".preg_quote($sc_string, '/') . ")/i", "<font color=darkred>\\0</font>",	$list['cut_title']);
			}
		}

		// 업로드파일 처리
		if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
			foreach($upfiles as $key =>	$value){
				if($value['name'])
					$upfiles[$key]['href']="download.php?" . href_qs("uid={$list['uid']}&name={$key}",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
		$href['list']	= "list.php?db={$db}";
		$href['download']	= "download.php?db={$db}&uid={$list['uid']}";

		// 템플릿 YESRESULT 값들 입력
		$tpl->set_var('href.read'		,$href['read']);
		$tpl->set_var('href.download'	,$href['download']);
		$tpl->set_var('href.list'		,$href['list']);
		$tpl->set_var('list'			,$list);
		$tpl->set_var('no'			,$no);// boardinfo 정보 변수
		$tpl->set_var('count.lastnum'	,$count['lastnum']--);

		if(isset($dbinfo['row_pern']) && $dbinfo['row_pern'] > 1) $tpl->process('CELL','cell',TPL_APPEND);

		$tpl->process('LIST','list',TPL_APPEND);
	} // end while
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
$tpl->set_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sc_string'		,htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));	// 서치 단어
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

if ($modify_uid != "" ){
//	$modify_uid = db_resultone("SELECT uid FROM {$table_popup} order by	data4 desc LIMIT 0, 1", "uid", 0);

/*
if($_SERVER['REMOTE_ADDR'] == '59.27.126.160'){
	echo "SELECT * from {$table_popup} WHERE $sql_where and uid = {$modify_uid} ";
}
*/
	// Limit로 필요한 게시물만 읽음.
	$rs_list_modify = db_query("SELECT * from {$table_popup} WHERE $sql_where and uid = '" . db_escape($modify_uid) . "'");

	if (db_count() > 0){
		$list_modify		= db_array($rs_list_modify);
		$list_modify['no']	= $count['lastnum'];
		$list_modify['rede']	= strlen($list_modify['re']);
		//$list_modify['content'] = strip_tags($list_modify['content']);
		//$list_modify['content'] = replace_string($list_modify['content'], $list_modify['docu_type']);
		$list_modify['content'] = htmlspecialchars($list_modify['content']);

		$list_modify['rdate_ymd']= $list_modify['rdate'] ? date("Y/m/d", $list_modify['rdate']) : "";	//	날짜 변환
		$list_modify['data4']= $list_modify['data4'] ? date("Y/m/d", $list_modify['data4']) : "";	//	날짜 변환
		$list_modify['data5']= $list_modify['data5'] ? date("Y/m/d", $list_modify['data5']) : "";	//	날짜 변환
		$list_modify['scr_title'] = addslashes($list_modify['title']);

		if(!$list_modify['title']) $list_modify['title'] = "제목없음…";

		if(!isset($list_modify['skin']) || !$list_modify['skin']) $list_modify['skin'] = "1";

		${"checked_data2_" . $list_modify['data2']} = " checked";
		${"checked_data3_" . $list_modify['data3']} = " checked";
		${"selected_skin_" . $list_modify['skin']} = " selected";

		// 템플릿 YESRESULT 값들 입력
		$tpl->set_var("list_modify"			,$list_modify);
		$tpl->set_var("checked_data2_{$list_modify['data2']}", ${"checked_data2_" . $list_modify['data2']});
		$tpl->set_var("checked_data3_{$list_modify['data3']}", ${"checked_data3_" . $list_modify['data3']});
		$tpl->set_var("selected_skin_{$list_modify['skin']}", ${"selected_skin_" . $list_modify['skin']});
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1){
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
}
else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
// <-- (이전블럭) 부분
if ($count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
else $tpl->process('PREVBLOCK','noprevblock');
// 1 2 3 4 5 부분
for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) {
	$tpl->set_var('blockcount',$i);
	if($i == $count['nowpage'])
		$tpl->process('BLOCK','noblock',TPL_APPEND);
	else {
		$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
		$tpl->process('BLOCK','block',TPL_APPEND);
	}
} // end for
// --> (다음블럭) 부분
if ($count['totalpage'] > $count['lastpage']	) $tpl->process('NEXTBLOCK','nextblock');
else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if($count['nowpage'] < $count['totalpage']){
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
}
else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기

if(privAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 블럭 : 미리보기
if(isset($list_modify['uid']) && $list_modify['uid'] != 0) $tpl->process('PREVIEW','preview');

// 마무리
$val="\\1stpl/{$dbinfo['skin']}/images/";
if(isset($_GET['skin'])){
	echo preg_replace("/([\"|\'])images\//i", "{$val}", $tpl->process('', 'html')); // 1 mean loop
}
else {
	switch($dbinfo['html_headpattern']){
		case "ht":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] = 2;
			if( isset($dbinfo['html_headtpl']) && $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//i", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		case "h":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] = 2;
			if( isset($dbinfo['html_headtpl']) && $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//i", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'];
			break;
		case "t":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] = 2;
			if( isset($dbinfo['html_headtpl']) && $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//i", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		default:
			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//i", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'];
	} // end switch
} // end if
?>
