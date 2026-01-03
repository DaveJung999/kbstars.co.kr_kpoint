<?
//=======================================================
// 설  명 : 광고성 메일 수신 OK 처리(yesmail.php)
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
		if($yesmail==0) {
			db_query("update $table set yesmail=1,rdate=UNIX_TIMESTAMP() where email='$email'");
			back("광고성메일 수신거부리스트에서 귀하의 메일을 삭제하였습니다.\\n\\n앞으로 유용한 정보가 있을 경우 메일을 드리도록 하겠습니다.\\n\\n다시 수신거부는\\n{$_SERVER['HTTP_HOST']}/sjoin/dmail/nospam.php?email=youremail 로 접속하시면 되겠습니다.\\n\\n감사합니다.","/");
		}
		else {
			back("메일 수신을 허락해주셔서 감사합니다.\\n\\n앞으로 유용한 정보가 있을 경우 메일을 드리도록 하겠습니다.\\n\\n다시 수신거부는\\n{$_SERVER['HTTP_HOST']}/sjoin/dmail/nospam.php?email=youremail 로 접속하시면 되겠습니다.\\n\\n감사합니다.","/");
		} // end if
	}
	else {
		db_query("insert into $table (email, yesmail, rdate) values ('$email',1,UNIX_TIMESTAMP())");
		back("메일 수신을 허락해주셔서 감사합니다.\\n\\n앞으로 유용한 정보가 있을 경우 메일을 드리도록 하겠습니다.\\n\\n다시 수신거부는\\n{$_SERVER['HTTP_HOST']}/sjoin/dmail/nospam.php?email=youremail 로 접속하시면 되겠습니다.\\n\\n감사합니다.","/");
	} // end if
}
else {
	back("메일주소가 넘어오지 않았습니다.\\n{$_SERVER['HTTP_HOST']}/sjoin/dmail/yesmail?email=youremail로 접속하여 주시면 앞으로 광고성메일을 발송하지 않습니다","/");
} // end if?>