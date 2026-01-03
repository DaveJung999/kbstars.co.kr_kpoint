<?php
/////////////////////////////
// 게시판 맨 위에 무조건 공지글(type필드에 info인 것) 읽어오기
if($dbinfo['enable_type'] == 'Y' and (!isset($_GET['sc_string']) or strlen($_GET['sc_string']) == 0) and (!isset($_GET['limitrows']) or $_GET['limitrows']<1) ){
	$sql_where_info = ' type=\'info\' ';
	// 공지도 해당 카테고리만
	if(is_array($cateinfo['subcate_uid']) and sizeof($cateinfo['subcate_uid'])>0 ){
		if($sql_where_info) $sql_where_info .= ' and ';
		$sql_where_info .= ' ( cateuid in ( ' . implode(',',$cateinfo['subcate_uid']) . ') ) ';
	}
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$sql_where_info} ORDER BY num DESC, re";
	$rs_list_writeinfo = db_query($sql);
	$total_writeinfo=db_count($rs_list_writeinfo);
	for($i=0;$i<$total_writeinfo;$i++){
		$list		= db_array($rs_list_writeinfo);

		$list['no']	= $count['lastnum']--;
		$list['rede']	= strlen($list['re']);
		$list['rdate_date']= $list['rdate'] ? date('Y/m/d', (int)$list['rdate']) : '';	//	날짜 변환
		if(!$list['title']) $list['title'] = '제목없음…';
		//답변이 있을 경우 자리는 길이를 더 줄임
		$cut_length = $list['rede'] ? $dbinfo['cut_length'] - $list['rede'] -3 : $dbinfo['cut_length'];
		$list['cut_title'] = cut_string($list['title'], $cut_length);
		
		$list['content'] = strip_javascript($list['content']); //davej...........script 태그 삭제
	
		// new image넣을 수 있게 <opt name='enable_new'>..
		if($list['rdate']>time()-3600*24) $list['enable_new']=true;
		else $list['enable_new']=false;

		//	Search 단어 색깔 표시
		if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
			if($_GET['sc_column'] == 'title')
				$list['cut_title'] = preg_replace('/('.preg_quote($_GET['sc_string'], '/').')/i', '<font color=darkred>\\0</font>', $list['cut_title']);
			
			// $_GET['sc_column'] 값이 배열이 아닐 때만 처리
			if (!is_array($_GET['sc_column']) && isset($list[$_GET['sc_column']])) {
				$list[$_GET['sc_column']] = preg_replace('/('.preg_quote($_GET['sc_string'], '/').')/i', '<font color=darkred>\\0</font>', $list[$_GET['sc_column']]);
			}
		}

		// 메모개수 구해서 제목 옆에 붙임
		if($dbinfo['enable_memo'] == 'Y'){
			$sql = "select count(*) as count from {$dbinfo['table']}_memo where pid='{$list['uid']}'";
			$count_memo=db_resultone($sql,0,'count');
			if($count_memo){
				$sql = "select uid from {$dbinfo['table']}_memo where pid='{$list['uid']}' and rdate > unix_timestamp()-86400 LIMIT 1";
				if(db_count(db_query($sql))) $list['cut_title'] .= " [{$count_memo}+]";
				else $list['cut_title'] .= " [{$count_memo}]";
			}
		} // end if

		// 업로드파일 처리
		if($dbinfo['enable_upload'] != 'N' and isset($list['upfiles'])){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles) && $list['upfiles']){
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
			if(is_array($upfiles)){
				foreach($upfiles as $key =>  $value){
					if(isset($value['name']) && $value['name'])
						$upfiles[$key]['href']=$thisUrl.'/download.php?' . href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
				} // end foreach
				$list['upfiles']=$upfiles;
			}
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$list['href']['read']		= $thisUrl.'/read.php?' . href_qs('uid='.$list['uid'],$qs_basic);
		$list['href']['download']	= $thisUrl.'/download.php?' . href_qs("db={$dbinfo['db']}&uid={$list['uid']}",'uid=');

		// 템플릿 할당
		$tpl->set_var('list'			, $list);

		$tpl->process('INFO','info',TPL_OPTIONAL|TPL_APPEND);
		$tpl->set_var('blockloop',true);

		// 업로드부분 템플릿내장값 지우기
		if(is_array($list['upfiles'])){
			foreach($list['upfiles'] as $key =>  $value){
				if(is_array($list['upfiles'][$key])){
					foreach($list['upfiles'][$key] as $key2 =>  $value)
						$tpl->drop_var("list.upfiles.{$key}.{$key2}");
				}
			}
		} // end if
	} // end for
	// 템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	if(is_array($list)){
		foreach($list as $key =>  $value){
			if(is_array($list[$key]))
				foreach($list as $key2 =>  $value) $tpl->drop_var("list.{$key}.{$key2}");
			else $tpl->drop_var('list.'.$key);
		}
		unset($list);
	}
} // end if
///////////////////////////////////
?>