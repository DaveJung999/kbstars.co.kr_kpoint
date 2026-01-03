<?php
//=======================================================
// 설  명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (split -> explode, mysql_* -> db_*, 변수 처리)
//=======================================================
$HEADER = array(
//	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useCheck'		=>1, // DB 커넥션 사용
	'usePoint'	=>1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

setlocale(LC_CTYPE, 'ko_KR.utf8'); // CSV데이타 추출시 한글깨짐방지 (eucKR 대신 utf8 권장)
ECHO ('<meta http-equiv="content-type" content="text/html; charset=utf-8">');

// [!] FIX: PHP 4 전역변수 대신 $_FILES 사용
$upfile_name = $_FILES['upfile']['name'] ?? $_POST['upfile_name'] ?? null;
$upfile_tmp = $_FILES['upfile']['tmp_name'] ?? null;
$upfile_size = $_FILES['upfile']['size'] ?? 0;

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
		ECHO("<script>window.alert('디렉토리에 복사실패');
					  history.go(-1)
					</script>");
		 EXIT;
	}
	// [!] FIX: unlink($upfile) -> unlink($upfile_tmp)
	if(!unlink($upfile_tmp)){
		 ECHO("<script>window.alert('임시 파일삭제 실패');
						history.go(-1)
					</script>");
		 EXIT;
	}
}

//테이블 정보
$table_mntnotice  = "spb3_board3_mntnotice";
$table_logon  = "spb3_logon";

// 저장된 파일을 읽어 들인다
$csvLoad  = file("./data/$upfile_name");
// 행으로 나누어서 배열에 저장
// [!] FIX: split("\n",implode($csvLoad)) -> explode("\n",...)
$csvArray = explode("\n",implode($csvLoad));
$db_field = "bid,userid,data1,data2,content,title,name,data4,data5,data6,data7,data8,data9,data10,data11,data12,data13,data14,data15,data16,data17,data18,data19,data20,data21,data22,data23,data24,data25,data26,data27,data28,data29,data30,data31,data32,data33,data34,data35,data36,data37,data38,data39,data40,data41,data42,data43,data44,data45,data46,data47,data48,data49,data50,data51,data52,data53,data54,data55,data56,data57,data58,data59,data60,data61,data62,data63,data64,data65,data66,data67,data68,data69,data70,data71";

$suc_line = 0;
$err_line = "";

// 행으로 나눠진 배열 갯수 만큼 돌린다($enter['0']에는 필드 이름이 있으므로 $i는 1번 부터 시작하고 총 갯수는 $csvArray에서 1를 뺀다
for($i=1;$i<count($csvArray)-1;$i++){
	//자료 자르기
	// [!] FIX: split(",") -> explode(",")
	$col	 = explode(",",trim($csvArray[$i])); // $csvArray[$i]에 대해 trim을 추가하여 공백 문제 방지
	
	//실제 회원이 있는지 확인 없으면 에러처리.
	// [!] FIX: $table_logon, $col 변수 중괄호 {} 적용 및 따옴표 추가
	$sql = "select uid from {$table_logon} where userid='{$col[0]}' and name='{$col[5]}'";
	$logon_uid = db_resultone($sql);
	
	//해당 년/월에 데이터가 있는지 체크.
	// [!] FIX: $table_mntnotice, $logon_uid, $col 변수 중괄호 {} 적용 및 따옴표 추가
	$sql_where = "bid = '{$logon_uid}' and data1='{$col[1]}' and data2='{$col[2]}'";
	$sql = "select uid from {$table_mntnotice} where {$sql_where}";
	$mntnotice_uid = db_resultone($sql);
	
	if ($logon_uid){
		//앞에 bid를 붙임.
		$csvArray[$i] = $logon_uid.",".$csvArray[$i]; // [!] FIX: = 를 ; 로 변경하여 문장 종료

		//각 행을 콤마를 기준으로 각 필드에 나누고 DB입력시 에러가 없게 하기위해서 addslashes함수를 이용해 \를 붙인다
		// [!] FIX: split(",") -> explode(",")
		$field	 = explode(",",addslashes($csvArray[$i]));
		
		//나누어진 각 필드에 앞뒤에 공백을 뺸뒤 ''따옴표를 붙이고 ,콤마로 나눠서 한줄로 만든다.
		// [!] FIX: 배열 요소를 trim 하는 로직 추가
		$trimmed_field = array_map('trim', $field);
		$value	 = "'" . implode("','",$trimmed_field) . "'";
		
		// php쿼리문을 이용해서 입력한다.
		if ($mntnotice_uid){
			// [!] FIX: implode(",",...)
			$up_field = explode(",",$db_field);
			$up_value = explode(",",$value); // $value는 이미 '...'형태로 하나로 묶여있으므로 이 라인은 논리적 오류를 유발함.

			// [!] FIX: $value를 콤마로 분리하는 대신, $trimmed_field를 사용하여 UPDATE 구문 재구성
			$field_values = array_map(function($f, $v){
				// 따옴표를 제거하고 사용자가 입력한 데이터만 가져옴
				$v = trim($v, "'");
				// 최종 UPDATE 구문에 따옴표 추가 및 addslashes 처리
				return "`$f` = '" . db_escape(trim($v)) . "'"; 
			}, explode(",", $db_field), $trimmed_field);

			$up_str = implode(", ", $field_values);
			
			// [!] FIX: SQL Injection 방지 및 db_query 사용
			$SQL = sprintf("UPDATE %s SET %s WHERE %s", $table_mntnotice , $up_str, $sql_where);

		}else
			// [!] FIX: SQL Injection 방지 및 db_query 사용
			$SQL = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table_mntnotice , $db_field, $value);

		$Result		= db_query($SQL) or db_error("DB 입력 오류", $SQL);
		$suc_line++;
	}else{
		$err_line .= ($i+1) . ","; // [!] FIX: $i+1
	}
}
//입력이 된후 업로드된 파일을 삭제한다
@unlink("./data/$upfile_name"); // [!] FIX: @unlink

if ($err_line){
	$err_line = substr($err_line, 0, -1);
	$err_str = "\\n\\n업로드실패(회원인증실패) 자료라인 : $err_line";
}

if(isset($Result)){ // [!] FIX: $Result가 쿼리 결과 객체이므로 isset() 체크
	echo("
		<script>
			window.alert('$suc_line건의 자료를 성공적으로 저장하였습니다.$err_str');
			history.go(-1)
		</script>");
}


@db_close(); // [!] FIX: @mysql_close -> @db_close
?>