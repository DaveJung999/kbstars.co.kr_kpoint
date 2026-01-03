<?php
//=======================================================
// 설  명 : 관리자 회원 관리 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // file_upload(),remote_addr()
	'useCheck'	=>1, // check_value()
	'usePoint'	=>1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 기본 URL QueryString
	$qs_basic = "db=$db".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid=$cateuid".		//cateuid
				"&pern=$pern" .	// 페이지당 표시될 게시물 수
				"&sc_column=$sc_column".	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
				"&mid=$mid".
				"&s_id=$s_id".
				"&cur_sid=$cur_sid".
				"&page=$page".
				"&sdate=$sdate".
				"&edate=$edate".
				"&search=$search".
				"&pay_cate=$pay_cate".
				"&term_id=$term_id"
				;				//현재 페이지
	// table
	$table_kmember		= $SITE['th'].'kmember';
	$table_kpoint		= $SITE['th'] . "kpoint";
	$table_kpointinfo	= $SITE['th'] . "kpointinfo";


$row = 1; 
$i = 0;
$fp = fopen("Book1.csv", "r"); 
while ($data = fgetcsv($fp, 1000000, ",")) { 
	
	if ($i == 0){
		$d = $data;
	}else{
		 
		$qs['mid'] = $i;
		$qs['accountno'] = $data['2'];
		$qs['branch'] = '';
		$qs['s_id'] = '11';
		$qs['s_name'] = '2007-2008 시즌';
		for ($k = 3; $k<=23; $k++){
			$qs['rdate_date'] = $d[$k];
			$rdate = strtotime( $d[$k] ) ;
			$qs['deposit'] = $data[$k];
			if ($qs['deposit'] == '' or $qs['deposit'] == '0') continue;
			if ($qs['deposit'] == '100'){
				$qs['type'] = '홈경기(주말)';
				$qs['remark'] = '홈경기(주말) 포인트적립';
			}else if ($qs['deposit'] == '200'){
				$qs['type'] = '홈경기(주중)';
				$qs['remark'] = '홈경기(주중) 포인트적립';
			}else if ($qs['deposit'] == '300'){
				$qs['type'] = '어웨이경기';
				$qs['remark'] = '어웨이경기 포인트적립';
			}
			// 이미 적립했는지
			$sql = "select pid from $table_kpoint where accountno={$qs['accountno']} and rdate_date={$qs['rdate_date']} and remark={$qs['remark']}";
			if(!db_resultone($sql,0,'pid')) 
				//new21Kpoint($qs['mid'], {$qs['accountno']}, $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']);
			else
				$error .= $sql."  <br>";
			
		}
				 


		echo $error . "<br>"; 
	}
	$i++;
} 
?> 