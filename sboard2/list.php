<?php
//=======================================================
// 설	 명 : 게시판2 목록보기(list.php)
// 책임자 : 박선민 , 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/08/10 Gemini PHP 4 -> PHP 7 마이그레이션
// 24/05/23 Gemini 오류 패턴 수정 완료
// 24/05/23 Gemini db_escape 함수 수정
//=======================================================
$HEADER = array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useBoard2' => 1, // board2Count(),board2CateInfo()
	'useSkin' => 1, // 템플릿 사용
);
require_once($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if (isset($_GET['getinfo']) && $_GET['getinfo'] !== 'cont'){
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	}
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// table
	$table_dbinfo	= ($SITE['th'] ?? '') . $prefix.'info';
	
	// 3. info 테이블 정보 가져와서 $dbinfo로 저장
	$dbinfo = []; // Initialize $dbinfo
	if(isset($_GET['db'])){
		$sql = "SELECT * FROM {$table_dbinfo} WHERE db='".db_escape($_GET['db']) . "' LIMIT 1";

		$dbinfo = db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');
	
		// redirect 유무
		if(isset($dbinfo['redirect']) && $dbinfo['redirect']){
			// go_url3 is not defined, using go_url as a safe fallback
			go_url($dbinfo['redirect']);
		}

		$dbinfo['table'] = ($SITE['th'] ?? '') . $prefix.'_' . ($dbinfo['db'] ?? ''); // 게시판 테이블

	} else {
		back('DB 값이 없습니다');
	}
	
	//===============================================================================
	// KB 인턴직원 응원행사 세션체크..davej........2010-01-07
	//===============================================================================
	if (!isset($_SESSION['sePriv']['운영자'])){
		if (($dbinfo['db'] ?? null) == '2010kbintern' and (!isset($_SESSION['kbintern_auth']) or ($_SESSION['kbintern_auth'] ?? '') != "authok")){
			back('인턴직원이 인증을 거쳐 사용할 수 있습니다.','/');
		}
	}
	//===============================================================================
	
	//======================	
	// 4. 카테고리 정보 구함
	//======================	
	$cateinfo = []; // Initialize $cateinfo
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y'){
		//============================================================================	
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard => 	/sinc/config.php 파일에 있음
		if( in_array(($dbinfo['db'] ?? ''), ($PlayerCateBoard ?? [])) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================	

		// 카테고리정보구함 (dbinfo, cateuid, sw_catelist, string_view_firsttotal)
		// return : highcate[], samecate[], subcate[], subsubcate[], subcateuid[], catelist
		$sw_catelist = CATELIST_VIEW | CATELIST_VIEW_CATE_DEPTH;
		if(strlen($_GET['sc_string'] ?? '') == 0){
			$sw_catelist |= CATELIST_VIEW_DATACOUNT;
		}
		$cateinfo = board2CateInfo($dbinfo, isset($_GET['cateuid']) ? (int)$_GET['cateuid'] : null, $sw_catelist, ($dbinfo['enable_cate'] ?? null) ? '' : '(전체)');

		// 카테고리 정보가 없다면
		if(!isset($cateinfo['uid']) || !$cateinfo['uid']){
			$cateinfo['title'] = '(전체)';
		} else {
			// redirect 유무
			if(isset($cateinfo['redirect']) && $cateinfo['redirect']){
				// go_url3 is not defined, using go_url as a safe fallback
				go_url($cateinfo['redirect']);
			}
	
			// 카테고리 정보에 따른 dbinfo 변수 변경
			if(($dbinfo['enable_cateinfo'] ?? null) == 'Y'){
				if(($cateinfo['bid'] ?? 0)>0){
					$dbinfo['cid'] = $cateinfo['bid']; // 카테고리 관리자도 모든 권한을
				}
				if( isset($cateinfo['skin']) && $cateinfo['skin'] and is_file($thisPath.'skin/'.($cateinfo['skin'] ?? '').'/list.html') ){
					$dbinfo['skin']		= $cateinfo['skin'];
				}
				if(isset($cateinfo['html_type']) && $cateinfo['html_type'])	{
					$dbinfo['html_type']	= $cateinfo['html_type'];
					if( isset($cateinfo['html_skin']) && $cateinfo['html_skin'] and is_file(($SITE['html_path'] ?? '').'index_'.($cateinfo['html_skin'] ?? '').'.php') )	{
						$dbinfo['html_skin']	= $cateinfo['html_skin'];
					}
					$dbinfo['html_head']		= (isset($cateinfo['html_head']) && $cateinfo['html_head']) ? $cateinfo['html_head'] : ($dbinfo['html_head'] ?? '');
					$dbinfo['html_tail']		= $cateinfo['html_tail'];
				}
				// 나머지 dbinfo값 일괄 변경
				$aTmp = array('title', 'orderby', 'pern', 'row_pern', 'page_pern', 'cut_length', 'enable_memo', 'enable_vote', 'enable_hidelevel', 'enable_listreply', 'enable_getinfo', 'enable_getinfoskins', 'include_listphp', 'priv_list', 'priv_write', 'priv_modify', 'priv_memowrite', 'priv_reply', 'priv_read', 'priv_download', 'priv_delete');
				foreach($aTmp as $tmp_field){
					if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !== NULL && $cateinfo[$tmp_field] != '0'){
						$dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
					}
				}
				
			}
		} // end if
	} // end if
	//===================

	//=====================================================================
	// photo 게시판이면 cateuid 가 반드시 있어야 보여주도록.... davej...2011-11-04
	//=====================================================================
	if(($dbinfo['db'] ?? null) == 'photo' && (!isset($cateinfo['uid']) || !$cateinfo['uid']) && ! (isset($_SESSION['sePriv']['운영자']) || isset($_SESSION['sePriv']['사진관리자']) )){
		back('카테고리정보가 없습니다.');
	}
	
	// 5. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)){
		back('페이지를 보실 권한이 없습니다.');
	}
	
	// 6. 넘어온 값에 따라 $dbinfo값 변경
	if(isset($dbinfo['enable_getinfo']) && ($dbinfo['enable_getinfo'] ?? null) == 'Y'){
		if(isset($_GET['pern'])){
			$dbinfo['pern']		= (int)$_GET['pern'];
		}
		if(isset($_GET['limitrows'])){
			$dbinfo['pern']		= (int)$_GET['limitrows'];
		}
		if(isset($_GET['row_pern'])){
			$dbinfo['row_pern']	= (int)$_GET['row_pern'];
		}
		if(isset($_GET['cut_length'])){
			$dbinfo['cut_length']	= (int)$_GET['cut_length'];
		}
		if(isset($_GET['cateuid'])){
			$dbinfo['cateuid']		= (int)$_GET['cateuid'];
		}
		if(isset($_GET['sql_where'])){
			$sql_where		= $_GET['sql_where'];
		} //davej..............
		if(isset($_GET['page'])){
			$page		= $_GET['page'];
		}
	
		// skin 변경
		if( isset($_GET['skin']) and preg_match('/^[_a-z0-9]+$/i', $_GET['skin'])
					and is_file($thisPath.'skin/'.($_GET['skin'] ?? '').'/list.html') ){
			if(isset($dbinfo['enable_getinfoskins']) && ($dbinfo['enable_getinfoskins'] ?? null)) { // 특정 스킨만 get값으로 사용할 수 있도록 했다면
				$aTmp = explode(',',($dbinfo['enable_getinfoskins'] ?? ''));
				foreach($aTmp as $v){
					if($v == $_GET['skin']){
						$dbinfo['skin']	= $_GET['skin'];
					}
				}
			} else {
				$dbinfo['skin']	= $_GET['skin'];
			}
		}
		// 사이트 해더테일 변경
		if(isset($_GET['html_type'])){
			$dbinfo['html_type'] = $_GET['html_type'];
		}
		if( isset($_GET['html_skin']) and preg_match('/^[_a-z0-9]+$/i', $_GET['html_skin'])
			and is_file(($SITE['html_path'] ?? '').'index_'.($_GET['html_skin'] ?? '').'.php') ){
			$dbinfo['html_skin'] = $_GET['html_skin'];
		}
	}
	
	//======================	
	// 7. SQL문 where절 정리
	//======================	
	$sql_where = ''; // 초기화
	// 공지글 기능이 있다면
	if(isset($dbinfo['enable_type']) && ($dbinfo['enable_type'] ?? null) == 'Y'){
		$sql_where = ' type="docu" ';
	}
	// 해당 카테고리와 서브카테고리 데이터만 볼려면
	if(isset($cateinfo['subcate_uid']) && is_array($cateinfo['subcate_uid']) and count($cateinfo['subcate_uid'])>0 ){
		if($sql_where){
			$sql_where .= ' and ';
		}
		$sql_where .= ' ( cateuid in ( ' . implode(',',array_map('db_escape',$cateinfo['subcate_uid'])) . ') ) ';
	}
	
	// 서치 게시물만..
	if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
		// sc_column으로 title,content이면, or로 두필드 검색하도록
		$aTemp = explode(',',$_GET['sc_column']);
		$tmp = '';
		for($i=0;$i<count($aTemp);$i++){
			if(!preg_match('/^[a-z0-9_-]+$/i', ($aTemp[$i] ?? ''))){
				continue;
			}
			if($i>0){
				$tmp .= ' or ';
			}
			switch($aTemp[$i]){
				case 'bid':
				case 'uid':
					$tmp .= ' (`'.db_escape($aTemp[$i]).'`="'.db_escape($_GET['sc_string']).'") ';
					break;
				default : // bug - sc_column 장난 우려
					$tmp .= ' (`'.db_escape($aTemp[$i]).'` like "%'.db_escape($_GET['sc_string']).'%") ';
				// default : back('잘못된 요청입니다.');
			}
		} // end for
		if($tmp){
			if($sql_where){
				$sql_where .= ' and ';
			}
			$sql_where .= ' ('.$tmp.') ';
		}
	}
	// 답변글 안보이기, 서치시에는 답변글 무조건 보이기 위해 서치의 elseif 씀
	elseif(isset($dbinfo['enable_listreply']) && ($dbinfo['enable_listreply'] ?? null) != 'Y'){
		$sql_where .= $sql_where ? " and re='' ": " re='' ";	
	}
	// 두번째 서치(sc_column2, sc_string2)..
	if(isset($_GET['sc_column2'])){
		if(($_GET['sc_column2'] ?? '') == 'mine'){
			if($sql_where){
				$sql_where .= ' and ';
			}
			$sql_where .=" (`bid` = '".db_escape($_SESSION['seUid'] ?? '') . "') ";
		} elseif( isset($_GET['sc_string2']) && strlen($_GET['sc_string2']) ){
			$sc_string = db_escape($_GET['sc_string2']);
			if($sql_where){
				$sql_where .= ' and ';
			}
			$sql_where .=" (`".db_escape($_GET['sc_column2']) . "` like '%{$sc_string}%') ";
		}
	}
	
	// 비공개글 제외시킴
	$priv_hidelevel = 0;
	if(isset($dbinfo['enable_hidelevel']) && ($dbinfo['enable_hidelevel'] ?? null) == 'Y'){
		if(isset($_SESSION['seUid'])){
			$priv_hidelevel	= isset($dbinfo['gid']) && ($dbinfo['gid'] ?? 0) ? (int)($_SESSION['seGroup'][($dbinfo['gid'] ?? '')]['level'] ?? 0) : (int)($_SESSION['sePriv']['level'] ?? 0);
		}
	} // end if
	if(!$sql_where){
		$sql_where = ' 1 '; // 값이 없다면
	}

//	if ($_SERVER['REMOTE_ADDR'] == '59.26.52.130') print_r( $dbinfo );
	
	//=====================================================================
	// 카테고리 보이기 없는 곳은 목록 제외. davej.......2012-11-07
	// (cheer20082009away, groupview20082009)
	//=====================================================================
	if ( substr(($dbinfo['db'] ?? ''), 0, 5) == 'cheer' && ! (isset($_SESSION['sePriv']['운영자']) || isset($_SESSION['sePriv']['사진관리자']) ) ){
		$sql_cate_temp = "select * from {$dbinfo['table']}_cate where comment='보이기'";
		$rs_list_cate_temp = db_query($sql_cate_temp);
		//print_r($list_cate_temp);
		if(!($total_temp = db_count($rs_list_cate_temp))) { // 게시물이 하나도 없다면...
			$sql_where .=" and cateuid = '' ";
		} else {
			$sql_where .=" and cateuid in ( ";
			$cate_uids = [];
			for($k = 0; $k < $total_temp; $k++){
				$list_cate_temp = db_array($rs_list_cate_temp);
				$cate_uids[] = db_escape($list_cate_temp['uid']);
			}
			$sql_where .= "'" . implode("','", $cate_uids) . "' ) ";
		}
	}
	//=====================================================================

	//===========================
	// 8. SQL문 order by..절 정리
	//===========================
	$sql_orderby = '';
	switch(isset($_GET['sort']) ? $_GET['sort'] : ''){
		// get 해킹을 막기 위해 특정 값에만 order by 생성
		case 'title':
		case 'hit':
		case 'vote':
		case 'rdate':
			$sql_orderby = db_escape($_GET['sort']);
			break;
		case '!title':
		case '!hit':
		case '!vote':
		case '!rdate':
			$sql_orderby = substr(db_escape($_GET['sort']),1).' DESC';
			break;
		default :
			$sql_orderby = ($dbinfo['orderby'] ?? '') ? ($dbinfo['orderby'] ?? '') : ' num DESC, re ';
	}

	// 9. 페이지 나눔등 각종 카운트 구하기
	$count_total_query = "SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ";
	$count['total'] = db_resultone($count_total_query, 0, 'count(*)') ?? 0; // 전체 게시물 수
	$count = board2Count($count['total'] ?? 0, isset($_GET['page']) ? (int)$_GET['page'] : 1, (int)($dbinfo['pern'] ?? 0), (int)($dbinfo['page_pern'] ?? 0)); // 각종 카운트 구하기
	$count_today_query = "SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and  $sql_where ";
	$count['today'] = db_resultone($count_today_query, 0, 'count(*)') ?? 0;

	// 10. URL Link...
	$href = []; // Initialize $href
	$href['listdb'] = $thisUrl.'list.php?db='.($dbinfo['db'] ?? '');
	$href['list'] = $thisUrl.'list.php?'.href_qs('page=',$qs_basic);
	if(($count['nowpage'] ?? 0) > 1) { // 처음, 이전 페이지
		$href['firstpage'] = $thisUrl.'list.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage'] = $thisUrl.'list.php?'.href_qs('page='.(($count['nowpage'] ?? 1)-1),$qs_basic);
	} else {
		$href['firstpage'] = 'javascript: void(0);';
		$href['prevpage'] = 'javascript: void(0);';
	}
	if(($count['nowpage'] ?? 0) < ($count['totalpage'] ?? 0)){ // 다음, 마지막 페이지
		$href['nextpage'] = $thisUrl.'list.php?'.href_qs('page='.(($count['nowpage'] ?? 1)+1),$qs_basic);
		$href['lastpage'] = $thisUrl.'list.php?'.href_qs('page='.($count['totalpage'] ?? 0),$qs_basic);
	} else {
		$href['nextpage'] = 'javascript: void(0);';
		$href['lastpage'] = 'javascript: void(0);';
	}
	$href['prevblock'] = (($count['nowblock'] ?? 0) > 1) ? $thisUrl.'list.php?'.href_qs('page='.(($count['firstpage'] ?? 1)-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock'] = (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0))? $thisUrl.'list.php?'.href_qs('page='.(($count['lastpage'] ?? 0)+1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write'] = $thisUrl.'write.php?' . href_qs('mode=write',$qs_basic);	// 글쓰기

	if(privAuth($dbinfo, 'priv_modify')){
		$dbinfo['canModify'] = 1;
	}
	if(privAuth($dbinfo, 'priv_delete')){
		$dbinfo['canDelete'] = 1;
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ){
	$dbinfo['skin']='basic';
}
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$limitno = isset($_GET['limitno']) ? (int)$_GET['limitno'] : ($count['firstno'] ?? 0);
$limitrows = isset($_GET['limitrows']) ? (int)$_GET['limitrows'] : ($count['pern'] ?? 0);
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";

//if ($_SERVER['REMOTE_ADDR'] == '218.54.246.172') print_r( $sql );

$rs_list = db_query($sql);
if(!($total = db_count($rs_list))) { // 게시물이 하나도 없다면...
	if(isset($_GET['sc_string'])) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string'] ?? ''),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	} else { // 게시물이 없다면..
		$tpl->process('LIST', 'nolist');
	}
} else {
	if(!isset($dbinfo['row_pern']) or ($dbinfo['row_pern'] ?? 0) < 1 or !$tpl->get_var('cell')){
		$dbinfo['row_pern'] = 1;
	} // 스킨에 cell 블럭이 없으면, row_pern을 1로 바꿈

	for($i=0; $i<$total; $i+=$dbinfo['row_pern']){ // Added `?? 1` to prevent division by zero
		if($dbinfo['row_pern'] > 1){
			$tpl->drop_var('blockloop');
			$tpl->drop_var('CELL');
		}
		
		for($j=$i; ($j-$i < $dbinfo['row_pern']) && ($j < $total); $j++) { // 한줄에 여러값 출력시 루틴
			if( $j>=$total ){
				if($dbinfo['row_pern'] > 1){
					$tpl->process('CELL','nocell',TPL_APPEND);
				}
				continue;
			}
			$list = db_array($rs_list);
			// `lastnum` needs to be initialized.
			if (!isset($count['lastnum'])) {
				$count['lastnum'] = $total;
			}
			$list['no']	= $count['lastnum']--;
			$list['bgcolor'] = ($list['no'] ?? 0)%2 == 0 ? "#ffffff" : "#f2f2f2";
			$list['rede'] = strlen($list['re'] ?? '');
			$list['rdate_ymd']= isset($list['rdate']) && $list['rdate'] ? date('Y.m.d', (int)$list['rdate']) : '';	//	날짜 변환
			$list['rdate_date']= isset($list['rdate']) && $list['rdate'] ? date('Y/m/d', (int)$list['rdate']) : '';	//	날짜 변환
			if(!isset($list['title']) || !$list['title']){
				$list['title'] = '제목없음…';
			}
			//답변이 있을 경우 자리는 길이를 더 줄임
			$cut_length = ($dbinfo['cut_length'] ?? 0);
			if (($list['rede'] ?? 0) > 0) {
				$cut_length = $cut_length - ($list['rede'] ?? 0) -3;
			}
			$list['cut_title'] = cut_string($list['title'], $cut_length);
			
			$list['content'] = strip_javascript($list['content'] ?? ''); //davej...........script 태그 삭제
			
			if (($dbinfo['db'] ?? null) == '150memo'){
				$list['title_memo'] = $list['title'] ?? '';
				$list['title'] = nl2br($list['title'] ?? '');
				$list['title'] = strip_tags($list['title'] ?? '');
			}
			//davej............................
			$list['cut_content'] = cut_string( $list['content'] ?? '', 200);
			$list['strip_tags_content'] = strip_tags($list['content'] ?? '');
			$list['cut_search_content'] = cut_string(strip_tags($list['content'] ?? ''), 400);
			$list['cut_main_content'] = cut_string(strip_tags($list['content'] ?? ''), 80);
			$list['cut_main_content_130'] = cut_string(strip_tags($list['content'] ?? ''), 130);
			$list['cut_list_content'] = cut_string(strip_tags($list['content'] ?? ''), 250);
			$list['cut_list_content_600'] = cut_string(strip_tags($list['content'] ?? ''), 600);
			$list['cut_content_nl2br'] = cut_string( nl2br($list['content'] ?? ''), 200);
			
			//2017-06-27 davej...............
			$list['cut_main_content_100'] = cut_string(strip_tags($list['content'] ?? ''), 105);
			$list['cut_list_content_200'] = cut_string(strip_tags($list['content'] ?? ''), 200);
			//2023-10-11 davej........
			$list['title_80'] = cut_string($list['title'] ?? '',80);


			// new image넣을 수 있게 <opt name='enable_newicon'>..
			if(isset($list['rdate']) && ($list['rdate'] ?? 0) > time()-3600*24){
				$list['enable_newicon']=true;
			} else {
				$list['enable_newicon']=false;
			}

			//	Search 단어 색깔 표시
			if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
				$aTemp = explode(',',$_GET['sc_column']);
				for($k=0;$k<count($aTemp);$k++){
					if(($aTemp[$k] ?? '') == 'title'){
						$list['cut_title'] = preg_replace('/'.preg_quote($_GET['sc_string'], '/').'/i', '<font color=darkred>\\0</font>', $list['cut_title'] ?? '');
					}
					if(isset($list[$aTemp[$k]])) { // 존재 여부 체크
						$list[$aTemp[$k]] = preg_replace('/'.preg_quote($_GET['sc_string'], '/').'/i', '<font color=darkred>\\0</font>', $list[$aTemp[$k]] ?? '');
					}
				}
			}

			// 메모개수 구해서 제목 옆에 붙임
			if(isset($dbinfo['enable_memo']) && ($dbinfo['enable_memo'] ?? null) == 'Y'){
				$sql = "select count(*) as count from {$dbinfo['table']}_memo where pid='".db_escape($list['uid']) . "'";
				$list['count_memo'] = db_resultone($sql,0,'count') ?? 0;
				if($list['count_memo']){
					$sql = "select uid from {$dbinfo['table']}_memo where pid='".db_escape($list['uid']) . "' and rdate > unix_timestamp()-86400 LIMIT 1";
					if(db_count(db_query($sql))){
						$list['cut_title'] .= ' ['.$list['count_memo'].']';
					} else {
						$list['cut_title'] .= ' ['.$list['count_memo'].']';
					}
				}
			} // end if

			//	답변 게시물 답변 아이콘 표시
			if(($list['rede'] ?? 0) > 0){
				//$list['cut_title'] = str_repeat('&nbsp;', $count_redespace*($list['rede']-1)) . ' <img src="images/re.gif" align="absmiddle" border=0> '.$list['cut_title'];
				$list['cut_title'] = '<img src="/scommon/spacer.gif" width="' . (($list['rede'] ?? 1)-1)*8 . '" height=1 border=0><img src="images/re.gif" align="absmiddle" border=0> ' . ($list['cut_title'] ?? '');
			}

			// 업로드파일 처리
			if(isset($dbinfo['enable_upload']) && ($dbinfo['enable_upload'] ?? null) != 'N' and isset($list['upfiles']) and $list['upfiles']){
				$upfiles=unserialize($list['upfiles']);
				if(!is_array($upfiles)){
					// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles = ['upfile' => ['name' => ($list['upfiles'] ?? ''), 'size' => (int)($list['upfiles_totalsize'] ?? 0)]];
				}
				if(is_array($upfiles)){
					foreach($upfiles as $key =>  $value){
						if(isset($value['name']) && $value['name']){
							$upfiles[$key]['href'] = $thisUrl.'download.php?'.href_qs("uid=" . ($list['uid'] ?? '') . "&upfile={$key}",$qs_basic);
						}
					} // end foreach
					$list['upfiles'] = $upfiles;
				}
				unset($upfiles);
			} // end if 업로드파일 처리
			
			// 삭제, 수정 가능한지
			$list['canModify'] = 0;
			if(isset($dbinfo['canModify']) && ($dbinfo['canModify'] ?? 0) or
					( 'nobid' != substr(($dbinfo['priv_modify'] ?? null),0,5)
				 		and isset($_SESSION['seUid']) && ($list['bid'] ?? null) == ($_SESSION['seUid'] ?? null) ) ){
					$list['canModify'] = 1;
			}
			$list['canDelete'] = 0;
			if(isset($dbinfo['canDelete']) && ($dbinfo['canDelete'] ?? 0) or
					( 'nobid' != substr(($dbinfo['priv_delete'] ?? null),0,5)
				 		and isset($_SESSION['seUid']) && ($list['bid'] ?? null) == ($_SESSION['seUid'] ?? null) ) or ($list['bid'] ?? null) == '0'){
					$list['canDelete'] = 1;
			}
			
			// URL Link...
			$list['href']['list']		= $thisUrl.'list.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&sc_string=".urlencode($_GET['sc_string'] ?? '') . "&cateuid=".urlencode($_GET['cateuid'] ?? ''),$qs_basic);
			$list['href']['read']		= $thisUrl.'read.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&uid=" . ($list['uid'] ?? '') . "&sc_string=".urlencode($_GET['sc_string'] ?? '') . "&cateuid=".urlencode($_GET['cateuid'] ?? ''),$qs_basic);
			$list['href']['modify']		= $thisUrl.'write.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&mode=modify&uid=" . ($list['uid'] ?? ''),$qs_basic);
			$list['href']['delete']		= $thisUrl.'ok.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&mode=delete&uid=" . ($list['uid'] ?? ''),$qs_basic);
			$list['href']['download']	= $thisUrl.'download.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&uid=" . ($list['uid'] ?? '') . "&cateuid=".urlencode($_GET['cateuid'] ?? ''),'uid=');
			$list['href']['download_formain']	= $thisUrl.'download.php?' . href_qs("db=" . ($dbinfo['db'] ?? '') . "&uid=" . ($list['uid'] ?? '') . "&cateuid=".urlencode($_GET['cateuid'] ?? ''),'uid=');
				
			// TODO
			if(isset($dbinfo['enable_hidelevel']) && ($dbinfo['enable_hidelevel'] ?? null) == 'Y' and !privAuth($list, "priv_hidelevel") ){
				
				// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
				$re_val = db_escape($list['re'] ?? '');
				$sql_where_privRead = " num='".db_escape($list['num']) . "' and (re='' ";
				for($k=0;$k<strlen($re_val)-1;$k++){
					$sql_where_privRead .= ' or re=\'' . substr($re_val,0,$k+1) .'\' ';
				}
				$sql_where_privRead .= ") and bid='".db_escape($_SESSION['seUid'] ?? '') . "' ";
				$sql_hidelevel = "select uid from {$dbinfo['table']} where {$sql_where_privRead} LIMIT 1";
		
				if(($priv_hidelevel ?? 0) < ($list['priv_hidelevel'] ?? 0) and ($list['bid'] ?? null) != ($_SESSION['seUid'] ?? null) and !db_arrayone($sql_hidelevel)){
					$list['cut_title'] = $list['title'] = "<font color=darked>비밀글입니다 ".htmlspecialchars($list['userid'] ?? '', ENT_QUOTES) . " 회원님만 보실 수 있습니다</font>";
				}
			} // end if
			
/*			// master인 경우 {list.userid} 특정 이미지로
			if(($list['bid'] ?? 0)>0 and ($list['bid'] ?? 0)<1100) $list['userid'] = "<img src='/img/master_icon.gif'>";
			if(($list['bid'] ?? 0) == 1217 or ($list['bid'] ?? 0) == 1226 or ($list['bid'] ?? 0) == 5796) $list['userid'] = "<img src='/img/master_icon.gif'>";
			if(($list['bid'] ?? 0) == 5331) $list['userid'] = "<img src='/img/master_icon2.gif'>";
*/
			// 템플릿 할당
			$tpl->set_var('list'			, $list);
			$tpl->set_var('session.seUserid'	,isset($_SESSION['seUserid']) ? $_SESSION['seUserid'] : '');	// 로그인 userid
	
			if($dbinfo['row_pern'] > 1){
				$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
	
			// 업로드부분 템플릿내장값 지우기
			if(isset($list['upfiles']) && is_array($list['upfiles'])){
				foreach($list['upfiles'] as $key => $value){
					$tpl->drop_var('list.upfiles.'.$key, $list['upfiles'][$key]);
				}
			}
		} // end for (j)
		
		if($i == 0){
			$tpl->drop_var('blockloop');
		} else {
			$tpl->set_var('blockloop',true);
		}
		
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

	} // end for (i)

	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	if(isset($href['read'])) unset($href['read']);
	if(isset($href['download'])) unset($href['download']);
	if(isset($list)){
		foreach($list as $key => $value){
			if(is_array($value)){
				foreach($value as $key2 => $value2){
					$tpl->drop_var('list.'.$key.'.'.$key2);
				}
			} else {
				$tpl->drop_var('list.'.$key);
			}
		}
		unset($list);
	}
} // end if (게시물이 있다면...)

// 자동 글쓰기 방지 인증 ... davej....2014-10-25
$captcha = "<img src='captcha.php' align='bottom' />";

// 템플릿 마무리 할당
$tpl->tie_var('get'				, $_GET); 	// get값으로 넘어온것들
if(isset($_GET['cateuid'])){
	$tpl->set_var('get.cateuid.'.($_GET['cateuid'] ?? ''),true);
}
$tpl->set_var('get.sc_string'	,isset($_GET['sc_string']) ? htmlspecialchars(stripslashes($_GET['sc_string'] ?? ''),ENT_QUOTES) : '');	// 서치 단어
$tpl->tie_var('dbinfo'			, $dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('cateinfo'		, $cateinfo); // cateinfo 정보 변수
$tpl->tie_var('count'			, $count);	// 게시판 각종 카운트
$tpl->tie_var('href'			, $href);	// 게시판 각종 링크
if(isset($_GET['sort'])){
	$tpl->set_var('sort_'.($_GET['sort'] ?? ''),true);	// sort_???
}
$tpl->set_var('captcha'		, $captcha);

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'list.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		, $form_search);	// form actions, hidden fileds

if(!isset($_GET['limitrows'])) { // 게시물 일부 보기에서는 카테고리, 블럭이 필요 없을 것임
	// 블럭 : 카테고리(상위, 동일, 서브) 생성
	if(isset($dbinfo['enable_cate']) && ($dbinfo['enable_cate'] ?? null) == 'Y'){
		if(isset($cateinfo['highcate']) && is_array($cateinfo['highcate'])){
			foreach($cateinfo['highcate'] as $key =>  $value){
				$tpl->set_var('href.highcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key,$qs_basic));
				$tpl->set_var('highcate.uid',$key);
				$tpl->set_var('highcate.title',$value);
				$tpl->process('HIGHCATE','highcate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
		if(isset($cateinfo['samecate']) && is_array($cateinfo['samecate'])){
			foreach($cateinfo['samecate'] as $key =>  $value){
				if(isset($cateinfo['uid']) && $key == ($cateinfo['uid'] ?? null)){
					$tpl->set_var('samecate.selected',' selected ');
				} else {
					$tpl->set_var('samecate.selected','');
				}
				$tpl->set_var('href.samecate',$thisUrl.'list.php?'.href_qs('cateuid='.$key,$qs_basic));
				$tpl->set_var('samecate.uid',$key);
				$tpl->set_var('samecate.title',$value);
				$tpl->process('SAMECATE','samecate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
		if(isset($cateinfo['subcate']) && is_array($cateinfo['subcate'])){
			foreach($cateinfo['subcate'] as $key =>  $value){
				// subsubcate...
				$tpl->drop_var('SUBSUBCATE');
				if(isset($cateinfo['subsubcate'][$key]) && is_array($cateinfo['subsubcate'][$key])){
					$blockloop = $tpl->get_var('blockloop');
					$tpl->drop_var('blockloop');
					foreach($cateinfo['subsubcate'][$key] as $subkey =>  $subvalue){
						$tpl->set_var('href.subsubcate',$thisUrl.'list.php?'.href_qs('cateuid='.$subkey,$qs_basic));
						$tpl->set_var('subsubcate.uid',$subkey);
						$tpl->set_var('subsubcate.title',$subvalue);
						$tpl->process('SUBSUBCATE','subsubcate',TPL_OPTIONAL|TPL_APPEND);
						$tpl->set_var('blockloop',true);
					}
					$tpl->set_var('blockloop',$blockloop);
				} // end if

				$tpl->set_var('href.subcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key,$qs_basic));
				$tpl->set_var('subcate.uid',$key);
				$tpl->set_var('subcate.title',$value);
				$tpl->process('SUBCATE','subcate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
	} // end if

	// 블럭 : 첫페이지, 이전페이지
	if(($count['nowpage'] ?? 0) > 1){
		$tpl->process('FIRSTPAGE','firstpage');
		$tpl->process('PREVPAGE','prevpage');
	} else {
		$tpl->process('FIRSTPAGE','nofirstpage');
		$tpl->process('PREVPAGE','noprevpage');
	}

	// 블럭 : 페이지 블럭 표시
		// <-- (이전블럭) 부분
		if (($count['nowblock'] ?? 0) > 1){
			$tpl->process('PREVBLOCK','prevblock');
		} else {
			$tpl->process('PREVBLOCK','noprevblock');
		}
		// 1 2 3 4 5 부분
		for ($i=($count['firstpage'] ?? 1);$i<=($count['lastpage'] ?? 1);$i++){
			$tpl->set_var('blockcount',$i);
			if($i == ($count['nowpage'] ?? 1)){
				$tpl->process('BLOCK','noblock',TPL_APPEND);
			} else {
				$tpl->set_var('href.blockcount', $thisUrl.'list.php?'.href_qs('page='.$i,$qs_basic) );
				$tpl->process('BLOCK','block',TPL_APPEND);
			}
		} // end for
		// --> (다음블럭) 부분
		if (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0)){
			$tpl->process('NEXTBLOCK','nextblock');
		} else {
			$tpl->process('NEXTBLOCK','nonextblock');
		}
	
	// 블럭 : 다음페이지, 마지막 페이지
	if(($count['nowpage'] ?? 0) < ($count['totalpage'] ?? 0)){
		$tpl->process('NEXTPAGE','nextpage');
		$tpl->process('LASTPAGE','lastpage');
	} else {
		$tpl->process('NEXTPAGE','nonextpage');
		$tpl->process('LASTPAGE','nolastpage');
	}

} // end if

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')){
	$tpl->process('WRITE','write');
} else {
	$tpl->process('WRITE','nowrite');
}

//===============================================
// $dbinfo['include_listphp']에 따라서 모듈 include
//===============================================
// - 공지글보기이면 무조건 list_info.php 실행
if(isset($dbinfo['enable_type']) && ($dbinfo['enable_type'] ?? null) == 'Y' and !isset($_GET['sc_string']) and (!isset($_GET['limitrows']) or ($_GET['limitrows'] ?? 0) < 1)){
	@include_once($thisPath . 'list_info.php');
}
if(isset($dbinfo['include_listphp']) && trim($dbinfo['include_listphp'] ?? '')){
	$aInclude = explode(',',($dbinfo['include_listphp'] ?? ''));
	foreach($aInclude as $value){
		if($value == 'info') continue;
		if( preg_match('/^[a-z0-9_-]+$/i',$value) and is_file($thisPath.'list_'.$value.'php') ){
			@include_once($thisPath.'list_'.$value.'php');
		}
	}
}
//===============================================

// 마무리
$dbinfo_final = $dbinfo ?? [];
$SITE_final = $SITE ?? [];
$tpl->echoHtml($dbinfo_final, $SITE_final, $thisUrl);
?>
