<?php
//=======================================================
// $Id: list.php 158 2009-03-11 07:42:34Z chjun77 $
// 설  명 : 리스트(페이지 구분) 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 08/11/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 08/11/19 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (split -> explode, mysql_* -> db_*, 변수 처리)
//=======================================================
$HEADER = array(
	'usedb2'	=>1
);
require($_SERVER['DOCUMENT_ROOT'].'/spb3/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

setlocale(LC_CTYPE, 'ko_KR.utf8'); // CSV데이타 추출시 한글깨짐방지 (eucKR 대신 utf8 권장)
//ECHO ('<meta http-equiv="content-type" content="text/html; charset=utf-8">');


$upfile_name = $_FILES['upfile']['name'];
$upfile_tmp = $_FILES['upfile']['tmp_name']; // [!] FIX: 임시 파일명
$upfile_size = $_FILES['upfile']['size'];   // [!] FIX: 파일 크기

//확장자 csv 파일만 업로드
$upfile_ext = explode(".", $upfile_name);
$cnt_ext = count($upfile_ext);
//echo strtolower($upfile_ext[$cnt_ext-1]);
//if(strtolower($upfile_ext[$cnt_ext-1]) != "csv" && strtolower($upfile_ext[$cnt_ext-1]) != "txt"){
if(strtolower($upfile_ext[$cnt_ext-1]) != "txt"){
	back("파일 확장자 txt 파일만 업로드 가능합니다. \\n\\n csv 파일은 사용할 수 없습니다. \\n탭분리자(*.txt)로 저장하여 업로드 해 주시기 바랍니다.");	
}

IF($upfile_name){

	//폴더내에 동일한 파일이 있는지 검사하고 있으면 삭제
	 if(file_exists("./data/$upfile_name") ){
				unlink("./data/$upfile_name");
	}
	if(!$upfile_tmp) { // [!] FIX: $upfile -> $upfile_tmp
		ECHO("<script>window.alert('파일이 존재하지 않습니다.');
					  history.go(-1)
					</script>");
		EXIT;
	}
	if( strlen($upfile_size) < 7 ) {
		$filesize = sprintf("%0.2f KB", $upfile_size/1000);
	}else{
		$filesize = sprintf("%0.2f MB", $upfile_size/1000000);
	}
	
	// [!] FIX: copy($upfile, ...) -> copy($upfile_tmp, ...)
	if(!copy($upfile_tmp, "./data/$upfile_name")){
		ECHO("<script>window.alert('디렉토리에 업로드 파일복사 실패');
					  history.go(-1)
					</script>");
		 EXIT;
	}
/*	// 임시 파일 삭제는 주석 처리되어 있으므로 그대로 유지
	if(!unlink($upfile)){
		 ECHO("<script>window.alert('임시 파일삭제 실패');
						history.go(-1)
					</script>");
		 EXIT;
	}*/
}

//테이블 정보
$table_mntnotice  = "spb3_board3_mntnotice_2013";
$table_logon  = "spb3_logon";

// 저장된 파일을 읽어 들인다
$csvLoad  = file("./data/$upfile_name");
// 행으로 나누어서 배열에 저장
// [!] FIX: split("\n",implode($csvLoad)) -> explode("\n",...)
$csvArray = explode("\n",implode($csvLoad));

$db_field = "bid,data1,data2,name,userid,title,content,
			data0,data3,data4,data5,data6,data7,data8,data9,data10,
			data11,data12,data13,data14,data15,data16,data17,data18,data19,data20,
			data21,data22,data23,data24,data25,data26,data27,data28,data29,data30,
			data31,data32,data33,data34,data35,
			data51,data52,data53,data54,data55,data56,data57,data58,data59,data60,
			data61,data62,data63,data64,data65,data66,data67,data68,data69,data70,
			data71,data72,data73,data74,data75,data76,data77,data78,data79,data80,
			data81,data82,data83,data84,data86,data87
			";


// 행으로 나눠진 배열 갯수 만큼 돌린다($enter['0']에는 필드 이름이 있으므로 $i는 1번 부터 시작하고 총 갯수는 $csvArray에서 1를 뺀다
for($i=1;$i<count($csvArray)-1;$i++){
	//자료 자르기
	
	//콤마로 들어오는 자료의 쌍따옴표 없애기......2011-01-19
	$csvArray[$i] = str_replace("\"", "", $csvArray[$i]);
	$col	 = array_map('trim', explode(chr(9),$csvArray[$i]));

	//실제 회원이 있는지 확인 없으면 에러처리.
	// [!] FIX: $table_logon, $col 변수 중괄호 {} 적용 및 따옴표 추가
	$sql = "select uid from {$table_logon} where userid='{$col[3]}' and name='{$col[2]}'";
	$logon_uid = db_resultone($sql);

	if ($logon_uid){
		//해당 년/월, 소속 데이터가 같은게 있는지 체크 ------- 2013-05-14 요청..............
		// [!] FIX: $table_mntnotice, $logon_uid, $col 변수 중괄호 {} 적용 및 따옴표 추가
		$sql_where = "bid = '{$logon_uid}' and data1='{$col[0]}' and data2='{$col[1]}' and content = '{$col[5]}' ";
		$sql = "select uid from {$table_mntnotice} where {$sql_where}";
		$mntnotice_uid = db_resultone($sql, 0, "uid");
		
		//앞에 bid를 붙임.
		$csvArray[$i] = $logon_uid.chr(9).$csvArray[$i];
		//각 행을 탭을 기준으로 각 필드에 나누고 DB입력시 에러가 없게 하기위해서 addslashes함수를 이용해 \를 붙인다
		// [!] FIX: split(chr(9),...) -> explode(chr(9),...)
		$field	 = array_map('trim', explode(chr(9),addslashes($csvArray[$i])));
		
		//나누어진 각 필드에 앞뒤에 공백을 뺸뒤 ''따옴표를 붙이고 ,콤마로 나눠서 한줄로 만든다.
		$value	 = "'" . implode("','",array_map('trim', $field)) . "'";
		
		// php쿼리문을 이용해서 입력한다.
		if ($mntnotice_uid){	//이미 있으면 업데이트
			$up_field_arr = array_map('trim', explode(",",$db_field));
			$up_value_arr = array_map('trim', explode(chr(9),$csvArray[$i]));
			$cnt = count($up_field_arr);
			$up_str = "";
			for($k=0;$k<$cnt;$k++){
				// [!] FIX: $up_value[$k] 값에 따옴표를 넣고, db_escape를 사용하여 SQL Injection 방지
				$up_str .= "{$up_field_arr[$k]} = '" . db_escape($up_value_arr[$k]) . "',";
			}
			// [!] FIX: $_POST 변수 중괄호 {} 적용 및 따옴표 추가
			$up_str .= "data85 = '{$_POST['data85']}', rdate	= UNIX_TIMESTAMP() ";
			
			// [!] FIX: $table_mntnotice, $up_str, $sql_where 변수 중괄호 {} 적용 및 db_query 사용
			$SQL = sprintf("UPDATE %s SET %s WHERE %s", $table_mntnotice , $up_str, $sql_where);
		}else{					// 없으면 인서트
			// [!] FIX: $table_mntnotice 변수 중괄호 {} 적용
			$sql_num = "SELECT max(num) as max_num FROM {$table_mntnotice}";
			$val_num = db_resultone($sql_num,0,'max_num') + 1;	

			// [!] FIX: $table_mntnotice, $db_field, $value, $_POST 변수 중괄호 {} 적용 및 db_query 사용
			// [!] FIX: $val_num에 따옴표 추가
			$SQL = sprintf("INSERT INTO %s (%s,data85,rdate,num) VALUES (%s,'{$_POST['data85']}',UNIX_TIMESTAMP(),'{$val_num}')", $table_mntnotice , $db_field, $value);
		}
/*			if($_SERVER['REMOTE_ADDR'] == '218.144.19.91') {
				echo $SQL;
				exit;
			}*/
			
		$Result		= db_query($SQL) or db_error("DB 입력 오류", $SQL); // [!] FIX: mysql_query/mysql_error -> db_query/db_error
		$suc_line++;
	}else{
		$el = $i+1;
		// [!] FIX: $col 변수 중괄호 {} 적용
		$err_line .= "{$el} : ({$col[3]},{$col[2]}), ";
	}
}
//입력이 된후 업로드된 파일을 삭제한다
@unlink("./data/$upfile_name"); 

if ($err_line){
	$err_line = substr($err_line, 0, -1);
	$err_str = "\\n\\n업로드실패(회원인증실패) 자료라인 : $err_line";
}

if(isset($Result)){ // [!] FIX: $Result가 쿼리 결과 객체이므로 isset() 체크
	echo("
		<script>
			window.alert('{$suc_line}건의 자료를 성공적으로 저장하였습니다.{$err_str}');
			history.go(-1)
		</script>");
}


@db_close(); // [!] FIX: @mysql_close -> @db_close
?>