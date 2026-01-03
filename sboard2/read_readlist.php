<?php
// 해당 카테고리와 서브카테고리 데이터만 볼려면
$sql_where_readlist = '';
if(is_array($cateinfo['subcate_uid']) and count($cateinfo['subcate_uid'])>0 ){
	if($sql_where) $sql_where .= ' and ';
	$sql_where_readlist .= ' ( cateuid in ( ' . implode(',',$cateinfo['subcate_uid']) . ') ) ';
}
if(!$sql_where_readlist) $sql_where_readlist= ' 1 '; // 값이 없다면


// 이전,현재,다음 num값을 구함
$readlist_num = array($list['num']);
$tmp_num = db_resultone("select num from {$dbinfo['table']} where {$sql_where_readlist} and num<'{$list['num']}' order by num DESC limit 1",0,'num');
if($tmp_num) $readlist_num[] = $tmp_num;
$tmp_num = db_resultone("select num from {$dbinfo['table']} where {$sql_where_readlist} and num>'{$list['num']}' order by num limit 1",0,'num');
if($tmp_num) $readlist_num[] = $tmp_num;
// SQL문
$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$sql_where_readlist} and num in (".implode(',',$readlist_num).') ORDER BY num, re';
$re_readlist	= db_query($sql);
while($readlist=db_array($re_readlist)){
	// 현제게시물에 ▶ 넣음
	if($readlist['uid'] == $list['uid']) $readlist['no']	= '<font color=blue>▶</font>';
	else $readlist['no']	= '';

	$readlist['rede']	= strlen($readlist['re']);
	$readlist['rdate_date']= isset($readlist['rdate']) ? date('y/m/d', $readlist['rdate']) : '';	//	날짜 변환
	if(!isset($readlist['title']) || !$readlist['title']) $readlist['title'] = '제목없음…';
	//답변이 있을 경우 자리는 길이를 더 줄임
	$cut_length = $readlist['rede'] ? $dbinfo['cut_length'] - $readlist['rede'] -3 : $dbinfo['cut_length'];
	$readlist['cut_title'] = cut_string($readlist['title'], $cut_length);

	// new image넣을 수 있게 <opt name='enable_new'>..
	if(isset($readlist['rdate']) && $readlist['rdate']>time()-3600*24) $readlist['enable_new']=true;
	else $readlist['enable_new']=false;

	//	Search 단어 색깔 표시
	if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
		if($_GET['sc_column'] == 'title')
			$readlist['cut_title'] = preg_replace('/('.preg_quote($_GET['sc_string'], '/').')/i', '<font color=darkred>\\0</font>', $readlist['cut_title']);
		$readlist[$_GET['sc_column']] = preg_replace('/('.preg_quote($_GET['sc_string'], '/').')/i', '<font color=darkred>\\0</font>', $readlist[$_GET['sc_column']]);
	}

	// 메모개수 구해서 제목 옆에 붙임
	if(isset($dbinfo['enable_memo']) && $dbinfo['enable_memo'] == 'Y'){
		$sql = "select count(*) as count from {$dbinfo['table']}_memo where pid='{$readlist['uid']}'";
		$count_memo=db_resultone($sql,0,'count');
		if($count_memo){
			$sql = "select uid from {$dbinfo['table']}_memo where pid='{$readlist['uid']}' and rdate > unix_timestamp()-86400 LIMIT 1";
			if(db_count(db_query($sql))) $readlist['cut_title'] .= " [{$count_memo}+]";
			else $readlist['cut_title'] .= " [{$count_memo}]";
		}
	} // end if

	//	답변 게시물 답변 아이콘 표시
	if($readlist['rede'] > 0){
		//$readlist['cut_title'] = str_repeat('&nbsp;', $count_redespace*($readlist['rede']-1)) . '<img src="images/re.gif" align="absmiddle" border=0> '.$readlist['cut_title'];
		$readlist['cut_title'] = '<img src="/scommon/spacer.gif" width="' . ($readlist['rede']-1)*8 . '" height=1 border=0><img src="images/re.gif" align="absmiddle" border=0> '.$readlist['cut_title'];
	}

	// 업로드파일 처리
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($readlist['upfiles']) && $readlist['upfiles']){
		$upfiles=unserialize($readlist['upfiles']);
		if(!is_array($upfiles)){
			// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$readlist['upfiles'];
			$upfiles['upfile']['size']=(int)$readlist['upfiles_totalsize'];
		}
		foreach($upfiles as $key =>  $value){
			if(isset($value['name']) && $value['name'])
				$upfiles[$key]['href']=$thisUrl.'download.php?'.href_qs("uid={$readlist['uid']}&upfile={$key}",$qs_basic);
		} // end foreach
		$readlist['upfiles']=$upfiles;
		unset($upfiles);
	} // end if 업로드파일 처리

	// URL Link...
	$readlist['href']['read']		= $thisUrl.'read.php?' . href_qs('uid='.$readlist['uid'],$qs_basic);
	$readlist['href']['download']	= $thisUrl.'download.php?' . href_qs("db={$dbinfo['db']}&uid={$readlist['uid']}",'uid=');

	// 템플릿 할당
	$tpl->set_var('readlist'		, $readlist);
	$tpl->process('READLIST','readlist',TPL_OPTIONAL|TPL_APPEND);
} // end while
?>
