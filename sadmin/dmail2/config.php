
<?php
$dbinfo	= array(
				// 심플리스트 제목
				title => "대량메일 발송리스트",
				
				// table
				table => "{$SITE['th']}dmailinfo",
				
				// 스킨설정
				skin => "basic",
				html_type => "no", 
				html_skin => "basic",
				html_head =>	'',
				html_tail =>	'',
				
				// 권한설정
				priv_write => 2,
				priv_read => 2,
				priv_delete => 99,
				
				// 기능설정 - 게시판기본
				pern => 15, // 게시물 수
				page_pern => 5,	// 페이지블럭 수
				cut_length => 40, // 제목 몇byte로 자를 것인지
				
				// 기능설정 - 업로드
				enable_upload => 'Y', // 파일업로드 Y/N/multi
				enable_uploadmust => 'N', // 업로드가 꼭 되어야 하는가 Y/N
				enable_uploadextension => '', // 특정 확장자면 업로드 허용 . 예: gif,jpg,png
				upload_dir => '', // 서버 특정 path에 업로드 되도록 할경우
				
				// 기능설정 - 기타
				enable_userid => 'userid', // userid 필드에 userid/name/nickname 중 어떤 값을 넣을지
								
				// SQL문 기본값
				orderby =>	' rdate DESC ', // order by .. . 기본값
			); 
		
$table = $dbinfo['table'];
?>
