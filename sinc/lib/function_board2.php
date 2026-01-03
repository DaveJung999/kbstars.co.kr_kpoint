<?php
//=======================================================
// 설 명 : 게시판2 사용에 있어서 사용되는 함수 모음 (function_board2.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/31
// Project: sitePHPbasic
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/06/02 박선민 처음
// 04/12/29 박선민 더블쿼터,배열변수 문법정리
// 05/01/08 박선민 개선 - board2Count()
// 05/01/31 박선민 개선 - board2Cateinfo()
// 2025/09/11 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
/*
	포함함수
		board2count( $total, $nowpage, $pern = 5, $page_pern=5);
		board2Cateinfo(&$dbinfo, $cateuid=0,$enable_catelist='Y', $tmp_sw_view_topcatetitles=1, $tmp_sw_view_cate_notitems=1, $tmp_sw_view_cate_itemcount=1, $catelist_view_firsttop_str='(전체)')
		board2SqlSort($table,$sort) // order by ..
		//inputfield($table,$list_uid=NULL)
*/


/**
* 게시판 구현을 위한 각종 계산 - 현재, 전체, 이전, 다음 페이지, 첫 게시물 번호, 페이지 블럭 등을 계산
*
* @return		array ( total, pern, totalpage, nowpage, prevpage, nextpage, firstno, lastnum, page_pern, nowblock, firstpage, lastpage)
* @update		05/01/08 by Sunmin Park
* @update		2025/09/11 by Gemini
*/
// function_board.php의 board2Count()와 동일
function board2Count( $total, $nowpage, $pern=5, $page_pern=5){
	// total, pern
	$count['total']		= (int)$total;
	if($pern < 0) $pern = $count['total']; // pern이 -1 등 음수이면, 모든 게시물 보여주기 위해
	$count['pern']		= (int)$pern ? (int)$pern : 5; //페이지당 게시물수
		
	// totalpage
	$count['totalpage'] = ($count['total']%$count['pern']) ? (int)ceil($count['total'] / $count['pern']) : (int)($count['total'] / $count['pern']);
	if($count['totalpage']<=0) $count['totalpage'] = 1;

	// nowpage
	$count['nowpage']	= (int)$nowpage;
	if($count['nowpage'] <= 0) $count['nowpage'] = 1;
	if($count['nowpage'] > $count['totalpage']) $count['nowpage']=$count['totalpage'];

	// privpage, nextpage
	$count['prevpage'] = $count['nextpage'] = $count['nowpage'];
	if($count['nowpage'] > 1) $count['prevpage'] = $count['nowpage'] - 1;
	if($count['nowpage'] < $count['totalpage']) $count['nextpage'] = $count['nowpage'] + 1;

	// firstno - 레코드의 처음 위치(SQL문 ... limit ???, pern)를 구한다.
	$count['firstno'] = ($count['nowpage'] - 1) * $count['pern'];

	// lastnum - 마지막 게시물 번호 구하기
	if($count['nowpage'] == 1) $count['lastnum']=$count['total'];
	else $count['lastnum']=$count['total'] - ($count['nowpage'] - 1)*$count['pern'];

	// page_pern, nowblock
	$count['page_pern'] = (int)$page_pern ? (int)$page_pern : 5; // 혹시나 DB에 값이 0이라면..
	$count['nowblock'] = ($count['nowpage']%$count['page_pern']) ? (int)ceil($count['nowpage']/$count['page_pern']) : (int)($count['nowpage']/$count['page_pern']);
	
	// firstpage, lastpage - 현재블럭의 첫페이지와 마지막 페이지 구함
	$count['firstpage'] = ($count['nowblock']-1)*$count['page_pern']+1;
	$count['lastpage'] = $count['nowblock']*$count['page_pern'];
	if ($count['lastpage'] > $count['totalpage']) $count['lastpage']=$count['totalpage'];

	return $count;
} // end func board2Count

/**
* 카테고리 정보, 리스트 등을 구함
*
* @param array &$dbinfo
* @param int $cateuid
* @param int $sw_catelist
* @param string $catelist_view_firsttop_str
* @return array|bool
* @update 2025/09/11 by Gemini
*/
// 카테고리정보구함 (dbinfo, cateuid, sw_catelist, catelist_view_firsttop_str)
// $catelist_view_firsttop_str - catelist 첫옵션값에 (전체) 등을 넣을 것인지. 값이 있으면 해당 문자열이, 없으면 넣지 않음.
// return : highcate[], samecate[], subcate[], subsubcate[], subcateuid[], catelist
define('CATELIST_VIEW',					1); // catelist : <option value=cateuid>catetitle</option> 리스트들 구함
define('CATELIST_VIEW_TOPCATE_TITLE',	2); // catelist에서 상위 카테고리 보일것인지
define('CATELIST_NOVIEW_NODATA',		4); // catelist에서 데이터없는 카테고리 숨길 것인지
define('CATELIST_VIEW_DATACOUNT',		8); // catelist에서 데이터 수 보여줄 것인지
define('CATELIST_VIEW_CATE_DEPTH',		16); // catelist에서 cate_depth 위치의 카테고리만 보이기
define('CATEINFO_ONLY',					32); // 오직 cateinfo만 리턴

function board2Cateinfo(&$dbinfo, $cateuid=0, $sw_catelist=0, $catelist_view_firsttop_str='(전체)') { // 05/01/29 박선민
	// $sw_catelist 옵션값 분리
	$sw_catelist_view				 = $sw_catelist & CATELIST_VIEW;
	$sw_catelist_view_topcate_title	= $sw_catelist & CATELIST_VIEW_TOPCATE_TITLE;
	$sw_catelist_noview_nodata		= $sw_catelist & CATELIST_NOVIEW_NODATA;
	$sw_catelist_view_datacount		= $sw_catelist & CATELIST_VIEW_DATACOUNT;
	$sw_catelist_view_cate_depth	= $sw_catelist & CATELIST_VIEW_CATE_DEPTH;
	$sw_cateinfo_only				 = $sw_catelist & CATEINFO_ONLY;
	
	// $dbinfo['table_cate']가 없으면, _cate 붙여서 table_cate 정의
	if(!isset($dbinfo['table_cate']) || !$dbinfo['table_cate']) $dbinfo['table_cate'] = $dbinfo['table'] . '_cate';
	
	
	//	선수테이블 카테고리 화.......... davej....2007-10-06
	//============================================================================
	$app_where = '';
	if (isset($dbinfo['table_cate']) && $dbinfo['table_cate'] == "`savers_secret`.player"){
		$app_where = " and tid = '13' and p_gubun = '현역' ";	
		$sql_orderby = "	order by p_name ";
	} else {
		if ( isset($dbinfo['db']) && (substr($dbinfo['db'], 0, 5) == 'cheer' or substr($dbinfo['db'], 0, 5) == 'group') ) $app_where = " and comment='보이기' ";
		$sql_orderby = " order by num, re";
	}
	//============================================================================
	
	// $cateinfo 가져오기
	$cateinfo = [];
	if($cateuid){
		$sql = "SELECT * FROM ".$dbinfo['table_cate']." WHERE uid = '".db_escape($cateuid)."' ". (isset($app_where) ? $app_where : '');
		$cateinfo = db_arrayone($sql) or back('없는 카테고리를 보시고자 하였습니다.');
		if($sw_cateinfo_only) return $cateinfo; // define CATEINFO_ONLY

		$cateinfo['subcate_uid'][]=$cateinfo['uid']; // 서버카테고들 uid수집
	
		// num=2, re=AB인 경우 re_beforekey, re_key로, '2-65', '2-65-66' 구함
		if(($tmp_len_re=strlen($cateinfo['re'] ?? '')) > 0){
			$cateinfo['re_beforekey']	= $cateinfo['num'];
			for($i=1;$i<$tmp_len_re;$i++){
				$cateinfo['re_beforekey'] .= '-' . ord(substr($cateinfo['re'],$i-1,1));
			}
			$cateinfo['re_key']	= $cateinfo['re_beforekey'] . '-' . ord(substr($cateinfo['re'] ?? '',-1));
		}
		else $cateinfo['re_key']	= $cateinfo['num'];
	}
	elseif($sw_cateinfo_only) return false;

	// catelist 준비
	$catelist = [];
	if($sw_catelist_view){
		// $dbinfo['table']만 넘어온 경우라면(게시판이 아닌 카테고리테이블에서 쓸려고)
		if(!isset($dbinfo['uid'])){
			$sw_catelist_noview_nodata	=0;
			$sw_catelist_view_datacount	=0;
		}
	
		// 먼저 카테고리별 상품수 가져와 저장
		$cate_dbcount = [];
		if($sw_catelist_noview_nodata or $sw_catelist_view_datacount){
			$rs_count_per_cate=db_query("select cateuid, count(*) as count from ".$dbinfo['table']." group by cateuid");
			while($row=db_array($rs_count_per_cate)){
				$cate_dbcount[$row['cateuid']]=$row['count'];
			}
			db_free($rs_count_per_cate);
		} // end if
	
		// catelist 첫 <option>결정
		$cateinfo['catelist'] = '';
		if($catelist_view_firsttop_str)
			$cateinfo['catelist'] .= "\n<option value=''>".db_escape($catelist_view_firsttop_str)."</option>";
	} // end if

//	 if ($_SERVER['REMOTE_ADDR'] == '211.175.147.98') print_r("SELECT * FROM {$dbinfo['table_cate']} where 1 {$app_where} {$sql_orderby}");

	// cate 각종 정보 가져오기
	$sql_ct = "SELECT * FROM ".$dbinfo['table_cate']." where 1 " . (isset($app_where) ? $app_where : '') . " " . (isset($sql_orderby) ? $sql_orderby : '');
	
	$rs_cate = db_query($sql_ct);
	
	if($rs_cate_total = db_count($rs_cate)){
		// 임시 사용 변수 초기화
		$tmp_cate=array();
		$tmp_before_num=-1;
		$tmp_len_re=0;
		$tmp_re_key=array(); // 특정 re_key의 uid를 구함($tmp_re_key['re_key']=uid)
		$cate_dbsubcount = [];

		while($list_cate = db_array($rs_cate)){
			///	davej..............
			//============================================================================
			if (isset($dbinfo['table_cate']) && $dbinfo['table_cate']	 == "`savers_secret`.player")	
				$list_cate['p_name'] = $list_cate['p_name']." [".$list_cate['p_position']."]";
			//============================================================================

			// num=2, re=AB인 경우 re_beforekey, re_key로, '2-65', '2-65-66' 구함
			if(($tmp_len_re=strlen($list_cate['re'] ?? '')) > 0){
				$list_cate['re_beforekey']	= $list_cate['num'];
				for($i_tmp_len_re=1;$i_tmp_len_re<$tmp_len_re;$i_tmp_len_re++){
					$list_cate['re_beforekey'] .= '-' . ord(substr($list_cate['re'],$i_tmp_len_re-1,1));
				}
				$list_cate['re_key']	= $list_cate['re_beforekey'] . '-' . ord(substr($list_cate['re'] ?? '',-1));
			}
			else $list_cate['re_key']	= $list_cate['num'];

			
			//subsubcate를 구하기 위해 re_beforebeforekey 구함
			$tmp_re_key[$list_cate['re_key']] = $list_cate['uid'];
			if(strpos($list_cate['re_beforekey'],'-') !==false){
				$list_cate['re_beforebeforekey']=substr($list_cate['re_beforekey'],0,strrpos($list_cate['re_beforekey'],'-'));
			}
			else $list_cate['re_beforebeforekey']='';
			
			if(!$cateuid){
				if($tmp_len_re == 0) {// subcate
					$cateinfo['subcate'][$list_cate['uid']]= $list_cate['title'] ?? '';
				}
				elseif($tmp_len_re == 1) {// subsubcate
					$cateinfo['subsubcate'][$tmp_re_key[$list_cate['re_beforekey']]][$list_cate['uid']]= $list_cate['title'] ?? '';
				}
			} else {
				if(isset($cateinfo['re_beforekey']) && strcmp($list_cate['re_beforekey'], $cateinfo['re_beforekey']) == 0) { // samecate이다
					$cateinfo['samecate'][$list_cate['uid']]	= $list_cate['title'];
				}
				elseif(isset($cateinfo['num']) && strcmp($list_cate['num'], $cateinfo['num']) == 0){
					if(isset($cateinfo['re_key']) && preg_match('/^'.preg_quote($list_cate['re_key'],'/').'-/',$cateinfo['re_key'])) { // highcate이다
						$cateinfo['highcate'][$list_cate['uid']]		= $list_cate['title'];
					}
					elseif(isset($cateinfo['re_key']) && preg_match('/^'.preg_quote($cateinfo['re_key'],'/').'-/',$list_cate['re_key'])) { // 서브카테고리들이다
						$cateinfo['subcate_uid'][]=$list_cate['uid']; // 서버카테고들 uid수집
						if(isset($cateinfo['re_key']) && strcmp($list_cate['re_beforekey'],$cateinfo['re_key']) == 0) {// subcate이다
							$cateinfo['subcate'][$list_cate['uid']]		= $list_cate['title'];
						}
						elseif(isset($cateinfo['re_key']) && strcmp($list_cate['re_beforebeforekey'] ?? '',$cateinfo['re_key']) == 0) {// subsubcate이다
							$cateinfo['subsubcate'][$tmp_re_key[$list_cate['re_beforekey']]][$list_cate['uid']]	= $list_cate['title'];
						}
					} // if.. elseif..
				} // if.. elseif..
			} // end if.. else..

			// catelist 구하기
			if($sw_catelist_view){
				if($list_cate['num'] != $tmp_before_num) { // num 값이 바뀐경우
					$tmp_before_num = $list_cate['num'];
					unset($tmp_cate);
				}
				$tmp_cate[$tmp_len_re] = $list_cate['title'] ?? '';
	
				// $dbinfo['cate_depth'] 만 보이기
				if($sw_catelist_view_cate_depth and isset($dbinfo['cate_depth']) and $dbinfo['cate_depth'] and $tmp_len_re != $dbinfo['cate_depth']-1) continue;
				// 해당 카테고리 데이터가 없으면 보이지 않음
				if($sw_catelist_noview_nodata and !isset($cate_dbcount[$list_cate['uid']])){
					if(!isset($cate_dbcount[$list_cate['uid']])) {
						// 이 카테고리 아래 서브 카테고리들에도 데이터가 있는지 체크
						$tmp_hignre = $list_cate['re_key'];
						$sql_child_count = "SELECT COUNT(*) FROM ".$dbinfo['table_cate']." WHERE re LIKE '".db_escape($list_cate['re'])."%'";
						$child_count = db_resultone($sql_child_count, 0, 'COUNT(*)');
						if($child_count == 0 && ($cate_dbcount[$list_cate['uid']] ?? 0) == 0) {
							continue;
						}
					}
				}
				
				// 타이틀 구하기
				if($sw_catelist_view_topcate_title) { // 상위 카테고리 제목을 찾아서 보일 것인가?
					for($count_title=$tmp_len_re-1;$count_title >= 0;$count_title--){
						$list_cate['title'] = ($tmp_cate[$count_title] ?? '') . ' > ' . ($list_cate['title'] ?? '');
					}
				}
				elseif($tmp_len_re > 0) $list_cate['title'] = str_repeat('&nbsp;&nbsp;&nbsp;', $tmp_len_re) . '↘ ' . ($list_cate['title'] ?? '');
				
				// 타이틀 끝에 카테고리 사용한 db수 넣기
				if($sw_catelist_view_datacount and isset($cate_dbcount[$list_cate['uid']]))
					$list_cate['title'] .= '('.$cate_dbcount[$list_cate['uid']].')';
				// 카테고리 선택되도록
				
//				if ($_SERVER['REMOTE_ADDR'] == '211.175.147.98') print_r( $list_cate['p_name']."<br>");

				///	davej..............
				//============================================================================
				if (isset($dbinfo['table_cate']) && $dbinfo['table_cate']	 == "`savers_secret`.player"){
					$catelist_title = ($list_cate['title'] ?? '') . ($list_cate['p_name'] ?? '');
					if(isset($list_cate['uid']) && $list_cate['uid'] == $cateuid)
						$catelist[] = "\n<option value='".$list_cate['uid']."' selected>".db_escape($catelist_title)."</option>";		//davej.....$list_cate['p_name'] 추가
					else
						$catelist[] = "\n<option value='".$list_cate['uid']."'>".db_escape($catelist_title)."</option>";					//davej.....$list_cate['p_name'] 추가
				} else {
					$catelist_title = ($list_cate['title'] ?? '') . ($list_cate['p_name'] ?? '');
					if(isset($list_cate['uid']) && $list_cate['uid'] == $cateuid)
						$catelist[$list_cate['re_key']] ="\n<option value='".$list_cate['uid']."' selected>".db_escape($catelist_title)."</option>";
					else
						$catelist[$list_cate['re_key']] ="\n<option value='".$list_cate['uid']."'>".db_escape($catelist_title)."</option>";
				}
												
			} // end if
		} // end while

		// catelist - dbsubcount가 없으면 catelist제외
		if($sw_catelist_view and $sw_catelist_noview_nodata and count($catelist)){
			foreach($catelist as $k => $v){
				if(!isset($cate_dbsubcount[$k])) {
					unset($catelist[$k]);
				}
			}
		}

	} // end if
	db_free($rs_cate);
	//print_r($cateinfo);

	if($sw_catelist_view && count($catelist)) $cateinfo['catelist'] .= implode('',$catelist);
	return $cateinfo;
} // end func board2CateList

/**
* 정렬을 위한 SQL ORDER BY 절을 생성합니다.
*
* @param string $table 테이블 이름
* @param string $sort 정렬 기준 필드 ('!' 접두사는 내림차순을 의미)
* @return string 생성된 ORDER BY 절, 유효하지 않으면 빈 문자열
* @update 2025/09/11 by Gemini
*/
// 04/01/10 $sort가 테이블의 필드라면, order by 절 만듦
// $sort값 앞에 '!'이 있다면 역순 정렬
// function_board.php의 board2SqlSort()와 동일
function board2SqlSort($table,$sort){
	global $SITE;
	if(!isset($SITE['database'])) return '';
	if(!$sort) return '';

	$sort_option = ''; // init
	if(substr($sort, 0, 1) == '!'){
		$sort = substr($sort,1);
		$sort_option = ' DESC';
	}
	
	$sort = db_escape($sort);

	// $sort가 테이블의 필드인지 확인
	$fields = db_query("SHOW COLUMNS FROM `{$table}`");
	if (!$fields) {
		return '';
	}
	$sw_find = false;
	while ($row = db_array($fields)) {
		if ($sort === $row['Field']) {
			$sw_find = true;
			break;
		}
	}
	db_free($fields);
	
	if($sw_find) return $sort . $sort_option;
	else return '';
}


?>