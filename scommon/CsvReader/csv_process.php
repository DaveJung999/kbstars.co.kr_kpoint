<?php
//=======================================================
// 설  명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
// 25/01/XX  PHP 7 업그레이드: mysql_* → db_* 함수 교체
//=======================================================
$HEADER = array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useCheck'		=>1, // DB 커넥션 사용
	'usePoint'	=>1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

setlocale(LC_CTYPE, 'ko_KR.utf8');
//setlocale(LC_CTYPE, 'ko_KR.eucKR'); //CSV데이타 추출시 한글깨짐방지
ECHO ('<meta http-equiv="content-type" content="text/html; charset=utf-8">');


$upfile_name = $_FILES['upfile']['name'];

//확장자 csv 파일만 업로드
$upfile_ext = explode(".", $_FILES['upfile']['name']);
$cnt_ext = count($upfile_ext);
//echo strtolower($upfile_ext[$cnt_ext-1]);
//if(strtolower($upfile_ext[$cnt_ext-1]) != "csv" && strtolower($upfile_ext[$cnt_ext-1]) != "txt"){
	
if(strtolower($upfile_ext[$cnt_ext-1]) != "txt"){
	back("파일 확장자 txt 파일만 업로드 가능합니다. \\n탭분리자(*.txt)로 저장하여 업로드 해 주시기 바랍니다.");	
}

IF($upfile_name){

	//폴더내에 동일한 파일이 있는지 검사하고 있으면 삭제
	 if(file_exists("./data/$upfile_name") ){
				unlink("./data/$upfile_name");
	}
	if(!$upfile) {
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
	
	if(!copy($upfile, "./data/$upfile_name")){
		ECHO("<script>window.alert('디렉토리에 업로드 파일복사 실패');
					  history.go(-1)
					</script>");
		 EXIT;
	}
/*	if(!unlink($upfile)){
		 ECHO("<script>window.alert('임시 파일삭제 실패');
						history.go(-1)
					</script>");
		 EXIT;
	}*/
}

//테이블 정보
$table_logon  = "new21_logon";
$insert_line = 0;
$update_line = 0;

// 저장된 파일을 읽어 들인다
$csvLoad  = file("./data/$upfile_name");
// 행으로 나누어서 배열에 저장
$csvArray = split("\n",implode($csvLoad));

// [0]이름, [1]연락처, [2]생년월일, [3]카드번호, [4]시즌회원/직원서포터즈, 시즌년도, 메모, 회원구분
$db_field = "name,hp,birth,accountno,spts_cate,spts_year,spts_memo,priv";

//시즌년도
$spts_year = substr($_POST['s_name'], 0, 4);

// 오늘 날짜
$today = date("Ymd");

// 행으로 나눠진 배열 갯수 만큼 돌린다
for($i=0;$i<count($csvArray);$i++){
	
	if(!$csvArray[$i]) {
		continue;
	}

	// 초기화
	$spts_cate = "";
	$spts_memo = "";
	$priv = "";
	$strpos = "";
	

	// 자료 자르기
	$col	 = explode(chr(9),$csvArray[$i]);
	
	//실제 회원이 있는지 확인 없으면 에러처리.
	$sql = "select uid,priv,spts_cate,spts_memo,accountno from $table_logon where name={$col['0']} and hp={$col['1']} order by spts_year desc ";
	$logon = db_arrayone($sql);
	
	// 기존회원이면
	if ($logon['uid']){
		
		// 영구회원 skip
		if($logon['spts_cate'] == '영구회원' && $_POST['perm_update'] == ""){ 
			$perm_msg = $perm_msg."<br>- {$col['0']}({$col['1']}, {$col['3']}) ";
			$perm_line++;
			continue;
		}
		
		// 메모정리 위해
		if($logon['spts_cate'] != "")
			$spts_cate = "[".{$logon['spts_cate']}."]";
		
		//메모정리
		if($logon['spts_memo'] != "" || $logon['accountno'] != "" ) 
			$spts_memo = $logon['spts_memo'] ? $logon['spts_memo'].", $spts_cate 기존카드번호($today) : {$logon['accountno']}" : $logon['spts_memo']."$spts_cate 기존카드번호($today) : {$logon['accountno']}";
		else
			$spts_memo = "{$col['4']} 가입 : $today";
			
//		echo "<br>=====>".$logon['spts_memo']. " :: ".$spts_memo."<=======<br>";
		
		//회원구분 정리
		if( strpos($logon['priv'], '서포터즈') !== FALSE ) {
			$priv = $logon['priv'];
		}else{
			$priv = $logon['priv'].",서포터즈";	
		}
			
		// 뒤에 시즌년도, 메모, 회원구분 붙이기
		$csvArray[$i] = $csvArray[$i].chr(9).$spts_year.chr(9).$spts_memo.chr(9).$priv ;
			
		//콤마로 들어오는 자료의 쌍따옴표 없애기......2011-01-19
		$csvArray[$i] = str_replace("\"", "", $csvArray[$i]);

		//해당 년/월에 데이터가 있는지 체크.
		$sql_where = "uid = {$logon['uid']} ";
		
		// php쿼리문을 이용해서 입력한다.
		$up_field = explode(",",$db_field);
		$up_value =  array_map('trim', explode(chr(9),$csvArray[$i]));
		$cnt = count($up_field);
		$up_str = "";
		for($k=0;$k<$cnt;$k++){
			$up_str .= "$up_field[$k] = '{$up_value[$k]}',";
		}
		$up_str .= "mdate	= UNIX_TIMESTAMP() ";
		
		$SQL = sprintf("UPDATE %s SET %s WHERE %s", $table_logon , $up_str, $sql_where);
		$update_line++;
	}else{
		
		//메모정리
		$spts_memo = "{$col['4']} 가입 : $today";
			
		//회원구분 정리
		$priv = "서포터즈";
		
		// 뒤에 시즌년도, 메모, 회원구분 붙이기
		$csvArray[$i] = $csvArray[$i].chr(9).$spts_year.chr(9).$spts_memo.chr(9).$priv ;
			
		//콤마로 들어오는 자료의 쌍따옴표 없애기......2011-01-19
		$csvArray[$i] = str_replace("\"", "", $csvArray[$i]);
		
		
		//각 행을 탭을 기준으로 각 필드에 나누고 DB입력시 에러가 없게 하기위해서 addslashes함수를 이용해 \를 붙인다
		$field	 = array_map('trim', split(chr(9),addslashes($csvArray[$i])));
		//나누어진 각 필드에 앞뒤에 공백을 뺸뒤 ''따옴표를 붙이고 ,콤마로 나눠서 한줄로 만든다.
		$value	 = "'" . trim(implode("','",$field)) . "'";
		
		$SQL = sprintf("INSERT INTO %s (userid,%s,rdate) VALUES ({$field['0']}_{$field['3']},%s,UNIX_TIMESTAMP())", $table_logon , $db_field, $value);
		$insert_line++;
	}
	
	if($_SESSION['seUserid'] == 'davej') {
		echo $SQL."<br/>";
		//exit;
	}
		
	// PHP 7 업그레이드: mysql_query() → db_query(), mysql_error() → db_error()
	$Result		= @db_query($SQL) or db_error("DB 입력 오류", $SQL);

}
//입력이 된후 업로드된 파일을 삭제한다
unlink("./data/$upfile_name"); 

$result_msg = "신규회원 : $insert_line 건, 업데이트 : $update_line 건의 자료를 저장하였습니다.";
if ($perm_line > 0)
	$result_msg = $result_msg ."<br><br>** 영구회원 확인 필요 ($perm_line 건) : $perm_msg";

echo "<br><br>".$result_msg;

/*if($Result || $perm_line > 0){
	ECHO("
		<script>
			window.alert('$result_msg');
			history.go(-1)
		</script>");
}
*/

// PHP 7 업그레이드: mysql_close() → db_close()
@db_close();

?>
