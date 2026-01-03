<?php
	$dbinfo['table_kpoint']				= "{$SITE['th']}" . "kpoint"; // new21_slist_event
	$dbinfo['table_kpointinfo']				= "{$SITE['th']}" . "kpointinfo"; // new21_slist_event
	$dbinfo['table_kmember']				= "{$SITE['th']}" . "kmember"; // new21_slist_event
	$dbinfo['table_kpresent']				= "{$SITE['th']}" . "kpoint_present"; // new21_slist_event

	$dbinfo['title']		= "kpoint";
	$dbinfo['skin']		= "";
	$dbinfo['pern']		= 20;
	$dbinfo['row_pern']		= 1;	 
	$dbinfo['cut_length']	= 50;
	$dbinfo['priv_list']	= "운영자,포인트관리자"; // 본 list.php 볼 권한 설정
	$dbinfo['priv_write']	= "운영자,포인트관리자"; // write.php 글 올릴 권한 설정
	$dbinfo['priv_read']	= "운영자,포인트관리자"; // 본 read.php 볼 권한 설정
	$dbinfo['priv_delete']= "운영자,포인트관리자"; // 무조건 삭제권한
	$dbinfo['enable_upload']="Y"; // 업로드지원 
	$dbinfo['html_headpattern'] = "ht";
	$dbinfo['html_headtpl'] = "intro";
	$dbinfo['orderby'] = " name ";
	$dbinfo['enable_cate'] = "Y";
	$dbinfo['enable_type'] = "Y";
	$dbinfo['enable_getinfo'] = "Y";
	$dbinfo['enable_level'] = "Y";

?>
