<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/03/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/03/14 박선민 마지막 수정
//=======================================================

$dbinfo	= array(
			// 심플리스트 제목
			'title'				 => '쇼핑몰 SCM 관리',
			
			// table
			'table'				 => $SITE['th'].'shop2_'.$_REQUEST['db'],
			
			// 스킨설정
			'skin'				 => 'basic',
		'html_type'	 => 'no', // ht, h, t, no, N
		'html_skin'		 => 'basic',
		'html_head'			 => '',
		'html_tail'			 => '',
			
			// 권한설정
			'bid'				 => 1, // 게시판 관리자 uid
			'gid'				 => 0, // 그룹 gid
		'priv_list'			 => '운영자', // 자기가 올린 것만
		'priv_write'		 => '운영자',
		'priv_read'			 => '운영자', // 자기가 올린 것만
		'priv_modify'		 => '운영자', // 자기가 올린 것만
		'priv_delete'		 => '운영자', // 자기가 올린 것만
			
			// 기능설정 - 게시판기본
			'pern'				 => 20, // 게시물 수
			'page_pern'			 => 10,	// 페이지블럭 수
			'cut_length'		 => 40, // 제목 몇byte로 자를 것인지
			
			// 기능설정 - 업로드
			'enable_upload'		 => 'image', // 파일업로드 Y/N/multi/image
			'enable_uploadmust'	 => 'N', // 업로드가 꼭 되어야 하는가 Y/N
			'enable_uploadextension' => 'gif,jpg,png', // 특정 확장자면 업로드 허용. 예: gif,jpg,png
			'upload_dir'		 => '../upload', // 서버 특정 path에 업로드 되도록 할경우
			
			// 기능설정 - 기타
			'enable_onlymine'	 =>	1, // list sql where절에 bid={$_SESSION['seUid']} 삽입
			'enable_userid'		 => 'userid', // userid 필드에 userid/name/nickname 중 어떤 값을 넣을지
			'default_docu_type'	 => 'text', // 디폴트 본문 형식 (html,text)
			'default_title'		 => '',
			'default_content'	 => '',
			'enable_cate'	 =>	'Y', //카테고리 사용 유무
			'enable_cateinfo' => 'Y',
			
							
			// SQL문 기본값
			'orderby'			 => 'rdate desc	', // order by ... 기본값
			
			// ok 처리하고 goto
			'goto_write'		 => 'list.php?db='.$_REQUEST['db'],
			'goto_modify'		 => 'list.php?db='.$_REQUEST['db'],
			'goto_delete'		 => 'list.php?db='.$_REQUEST['db'],
		);
		
$table = $dbinfo['table'];
?>