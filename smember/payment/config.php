
<?php
$dbinfo	= array(
				skin => "dojaki",
				html_type => "ht", 
				html_skin => "mypage",
				
				// 세금계산서 관련
				enable_tax =>  'Y', // 발행 세금계산서 조회 여부
				defalut_tax_status =>  '승인', // 세금계산서 자동발행시 status
				tax1_3 =>  '0410',	// 1분기는 4월 10일까지 발행가능
				tax4_6 =>  '0710',	// 2분기는 7월 10일까지 발행가능
				tax7_9 =>  '1010',	// 3분기는 10월 10일까지 발행가능
				tax10_12 =>  '1310',	// 4분기는 다음년도 1월 10일까지 발행가능
			); 
?>
