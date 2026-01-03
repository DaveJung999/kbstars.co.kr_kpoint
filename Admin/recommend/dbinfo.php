<?php
	$table				= $SITE['th'] . "board2_books"; // new21_slist_event
	$table_logon				= $SITE['th'] . "logon"; // new21_slist_event
	$table_hist				= $SITE['th'] . "board2_books_history"; // new21_slist_event

	$dbinfo['db']		= "books"; // new21_slist_event
	$dbinfo['db_history']		= "history"; // new21_slist_event
	$dbinfo['title']		= "오늘의 책";
	$dbinfo['skin']		= "basic";
	$dbinfo['pern']		= 100;
	$dbinfo['cut_length']	= 50;
	$dbinfo['priv_list']	= 99; // 본 list.php 볼 권한 설정
	$dbinfo['priv_write']	= 99; // write.php 글 올릴 권한 설정
	$dbinfo['priv_read']	= 99; // 본 read.php 볼 권한 설정
	$dbinfo['priv_delete']	= 99; // 무조건 삭제권한
	$dbinfo['enable_upload']	="multi"; // 업로드지원
	$dbinfo['html_headpattern'] = "no";
?>
