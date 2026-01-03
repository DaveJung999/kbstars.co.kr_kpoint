<?
//=======================================================
// 설  명 : 대량메일 발송 수신거부 처리(nospam.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/19 박선민 마지막 수정
//=======================================================
$HEADER=array(
		usedb2	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	);
require("$_SERVER['DOCUMENT_ROOT']/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table="$SITE['th']dmailnospam";

	// 넘어온값
	$email=$_GET['email'];

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

if($email) {
	$rs=db_query("select * from $table where email='$email'");
	if(db_count()) {
		$yesmail=db_result($rs,0,"yesmail");
		if($yesmail) {
			db_query("update $table set yesmail=0 where email='$email'");
			back("광고성메일 수신거부리스트에 귀하의 메일주소를 추가하였습니다.\\n\\n리스트삭제는 {$_SERVER['HTTP_HOST']}/sjoin/dmail/yesmail.php?email=youremail로 접속하시면 되겠습니다.\\n\\n광고성 메일을 보내드린점 대단히 죄송합니다.\\n\\n[English] This e-mail address is deleted from our mailinglist.","/");
		}
		else {
			back("이미 광고성메일 수신거부리스트에 포함되어있습니다.\\n\\n리스트삭제는 {$_SERVER['HTTP_HOST']}/sjoin/dmail/yesmail.php?email=youremail로 접속하시면 되겠습니다.\\n\\n광고성 메일을 보내드린점 다시한번 죄송합니다.\\n\\n[English] This e-mail address is ALREADY deleted from our mailinglist.","/");
		} // end if
	}
	else {
		db_query("insert into $table (email, rdate) values ('$email',UNIX_TIMESTAMP())");
		back("광고성메일 수신거부리스트에 귀하의 메일주소를 추가하였습니다.\\n\\n리스트삭제는 {$_SERVER['HTTP_HOST']}/sjoin/dmail/yesmail.php?email=youremail로 접속하시면 되겠습니다.\\n\\n광고성 메일을 보내드린점 대단히 죄송합니다.\\n\\n[English] This e-mail address is deleted from our mailinglist.","/");
	} // end if
}
else {
	back("메일주소가 넘어오지 않았습니다.\\n{$_SERVER['HTTP_HOST']}/sjoin/dmail/nospam?email=youremail로 접속하여 주시면 앞으로 광고성메일을 발송하지 않습니다\\n\\n[English] Please, Go url for delete your email from our mailinglist.\\nhttp://{$_SERVER['HTTP_HOST']}/sjoin/dmail/nospam?email=youremail.","/");
} // end if
?>