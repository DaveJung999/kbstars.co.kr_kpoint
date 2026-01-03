<?php
//======================== 
// 이전, 이후 게시물 구하기
//======================== 
//////////////////////
// SQL문 where절 정리
$sql_where = ""; //초기화
if(is_array($cateinfo['subcate_uid']) and sizeof($cateinfo['subcate_uid'])>0 ){
	$sql_where = $sql_where ? $sql_where . " and ( cateuid in ( " . implode(",",$cateinfo['subcate_uid']) . ") ) " : " ( cateuid in ( " . implode(",",$cateinfo['subcate_uid']) . ") ) ";
}

// 서치 게시물만..
if(isset($_GET['sc_string']) && strlen($_GET['sc_string'])){
	if($sql_where) $sql_where .= ' and ';
	if(isset($_GET['sc_column'])){
		// 가격에 따른 서치
		if( $_GET['sc_column'] == "price" and preg_match("/-/",$_GET['sc_string']) ){
			$tmp_price = explode("-",$_GET['sc_string']);
			if(intval($tmp_price['0'])>0){
				$sql_where .= " {$_GET['sc_column']}>={$tmp_price['0']} ";
			}
			if(intval($tmp_price['1'])>0){
				if(isset($tmp_price['0'])) $sql_where .= " and ";
				$sql_where .= " {$_GET['sc_column']}<={$tmp_price['1']} ";

			}
		}
		else $sql_where .=" ({$_GET['sc_column']} like '%{$_GET['sc_string']}%') ";
	}
	else
		$sql_where .=" ((brand like '%{$_GET['sc_string']}%') or (title like '%{$_GET['sc_string']}%') ) ";
}
// 두번째 서치(sc_column2, sc_string2)..
if(isset($_GET['sc_column2']) && strlen($_GET['sc_column2']) and isset($_GET['sc_string2']) && strlen($_GET['sc_string2']) and $_GET['sc_string2'] != "all"){
	if($sql_where) $sql_where .= " and ";
	$sql_where .=" ({$_GET['sc_column2']} like '%{$_GET['sc_string2']}%') ";
}

// 비공개글 제외시킴
if(isset($dbinfo['enable_level']) && $dbinfo['enable_level'] == 'Y'){
	if($sql_where) $sql_where .= " and ";
	if(isset($_SESSION['seUid'])){
		$priv_level	= isset($dbinfo['gid']) ? (int)$_SESSION['seGroup'][$dbinfo['gid']] : (int)$_SESSION['seLevel'];
		$sql_where .=" ( priv_level<={$priv_level} or bid='{$_SESSION['seUid']}' ) ";
	}
	else $sql_where .=" priv_level=0 ";
} // end if

if(!$sql_where) $sql_where= " 1 ";

//////////////////////////////
// SQL문 order by..부분 만들기
$sql_orderby = '';
if(isset($_GET['sort'])){
	switch($_GET['sort']){
		case 'title': $sql_orderby = 'title'; break;
		case '!title':$sql_orderby = 'title DESC'; break;
		case 'rdate': $sql_orderby = 'rdate'; break;
		case '!rdate':$sql_orderby = 'rdate DESC'; break;
		case 'hit' : $sql_orderby = 'hit';	break;
		case '!hit' : $sql_orderby = 'hit DESC'; break;
		case 'vote' : $sql_orderby = 'vote'; break;
		case '!vote' : $sql_orderby = 'vote DESC'; break;
		case "hot" : $sql_orderby = "enable_hot DESC"; break;
		case "new" : $sql_orderby = "enable_new DESC"; break;
		case "sale": $sql_orderby = "enable_sale DESC"; break;
		case "price": $sql_orderby = "price"; break;
		case "!price": $sql_orderby = "price DESC"; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? $dbinfo['orderby'] : " 1 ";
	}
} else {
	$sql_orderby = isset($dbinfo['orderby']) ? $dbinfo['orderby'] : " 1 ";
}

//////////////////////////////
// LIMIT 구함
// 페이지 나눔등 각종 카운트 구하기
$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, "count(*)"); // 전체 게시물 수
$page = isset($page) ? $page : null;
$count=board2Count($count['total'], $page, $dbinfo['pern'], $dbinfo['page_pern']); // 각종 카운트 구하기

// Limit로 필요한 게시물만 읽음.
$_GET['limitno']	= (isset($_GET['limitno']) && $_GET['limitno']) ? $_GET['limitno'] : $count['firstno'];
$_GET['limitrows']= (isset($_GET['limitrows']) && $_GET['limitrows']) ? $_GET['limitrows'] : $count['pern'];
if(isset($_GET['limitno']) && $_GET['limitno']) $_GET['limitno']--; // 이전을 위해 하나 더 앞에서부터
if(isset($_GET['limitrows']) && $_GET['limitrows']) $_GET['limitrows'] += 2; // 이후를 위해 하나 더 뒤에까지

//////////////////////
// 이전, 이후 받아오기
$table = $dbinfo['table'];
$list_prevnext=userPrevNext(isset($_GET['limitno']) ? $_GET['limitno'] : 0, isset($_GET['limitrows']) ? $_GET['limitrows'] : 0, $sql_where, $sql_orderby, $count);

// 템플릿 할당
// 이전
if(is_array($list_prevnext['prev'])){
	$href['prev'] = "read.php?".href_qs("uid={$list_prevnext['prev']['uid']}", $qs_basic);
	$tpl->set_var('href.prev', $href['prev']);
	$tpl->set_var('prevlist', $list_prevnext['prev']);

	$tpl->process('PREV' ,'prev',0,0,1);
}
else $tpl->process('PREV' ,'noprev',0,0,1);
// 이후
if(is_array($list_prevnext['next'])){
	$href['next'] = "read.php?".href_qs("uid={$list_prevnext['next']['uid']}", $qs_basic);
	$tpl->set_var('href.next', $href['next']);
	$tpl->set_var('nextlist', $list_prevnext['next']);

	$tpl->process('NEXT' ,'next',0,0,1);
}
else $tpl->process('NEXT' ,'nonext',0,0,1);

/**
 * 실제 SQL문에서 이전, 이후를 재귀호출을 하면서 끝끝내 찾아서 리턴하는 함수
 * 03/11/03
 */
function userPrevNext($limitno="",$limitrows="",$sql_where="", $sql_orderby="",$count=""){
	global $table;

	global $SITE;
	// $_GET['uid']

	$rs_list = db_query("SELECT uid,title FROM {$table} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}");

	if(!$total=db_count($rs_list)) { // 게시물이 하나도 없다면...
		return false;
	}
	else{
		//echo "limit no : {$limitno}, {$limitrows}, {$total}, {$count['total']}<br>";
		$sw_find_uid = 0;
		for($i=0; $i<$total; $i++){
			if(isset($list)) $list_prev=$list;
			$list		= db_array($rs_list);
			if($list['uid'] == $_GET['uid']){
				$sw_find_uid = 1;
				// 이전 게시물
				if(isset($list_prev) && is_array($list_prev))	$list_return['prev']=$list_prev;
				elseif($limitno != 0){
					// 전체 게시물에서 구함
					return userPrevNext(0,$limitno+2, $sql_where, $sql_orderby,$count); // 재귀 호출
				}
				// 이후 게시물
				if($i+1<$total)	$list_return['next']=db_array($rs_list);
				elseif($count['total']>$limitno+$i+1){
					// 전체 게시물에서 구함
					return userPrevNext($limitno+$i-1,$count['total']-$_GET['limitno']-$i+1, $sql_where, $sql_orderby,$count); // 재귀 호출
				}
				return $list_return;
			}
		} // end for
		
		if($sw_find_uid == 0){
			// 전체 리스트에서 구함
			return userPrevNext(0,$count['total'], $sql_where, $sql_orderby,$count);
		}
	} // end if.. else..
} // end func
?>
