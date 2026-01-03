<?php
//=======================================================
// 설  명 : email 체크(emailcheck.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/09/26 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	=>0, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	//set_time_limit(0);

	include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_domainwhois.php");

	// DB connection
	echo "메일 : $email 검사 시작합니다. <br>";
	echo "+:성공, _:No User, d:denied, x: noreg domain, .:No Connect, !:No Email<br><br>";
	for ($i=0; $i<300; $i++) print (" ");
	print ("\n");
	flush();

	$emailcheck=SnowCheckMail($email,true);
	switch ($emailcheck) {
		case 1: // 메일 OK
			$count_ok++;
			echo "O";
			break;
		case 0: // 메일 가짜
			$count_nouser++;
			echo "_";
			break;
		case -1: // 도메인접속실패
			$count_noconnect++;
			echo ".";
			break;
		case -4: // 도메인접속실패
			$count_denied++;
			echo "d";
			break;
		case -98: // 도메인이 미등록
			$count_noregister++;
			echo "x";
			break;
		case -99: // 메일주소가 장난
			$count_noemail++;
			echo "!";
			break;
	} // end switch

	echo "\n<br><br>완료되었습니다.\n";

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
/* ======================================================================= 
  눈이오면의 메일 체크 함수 SnowCheckMail ($Email,$Debug=false) 
 $Email : 체크하기 위한 메일 주소 
 $Debug : 디버깅을 위한 변수, true로 하면 각 과정의 진행상황이 출력된다. 

 * 함수명을 바꾸시지 않고 사용하시면 누구나 사용하실수 있습니다. 
 참고 : O'REILLY - Internet Email Programming 
========================================================================= */ 

function SnowCheckMail($Email,$Debug=false) { 
	global $HTTP_HOST; 
	$Return =array();  
	// 반환용 변수 
	// $Return['0'] : [true|false] - 처리결과의 true,false 값을 저장. 
	// $Return['1'] : 처리결과에 대한 메세지 저장. 

	// Email 형식 체크를 위한 정규식 표현. 많이 공개된 것이니 설명을 하지않도록 하겠습니다. 
	if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $Email)) { 
		$Return=-99; 
		return $Return; 
	} 
	else if ($Debug) echo "확인 : {$Email}은 올바른 메일 형식입니다.<br>"; 

	// 메일은 @를 기준으로 2개로 나눠줍니다. 만약에 $Email 이 "lsm@ebeecomm.com"이라면 
	// $Username : lsm 
	// $Domain : ebeecomm.com 이 저장 
	// list 함수 레퍼런스 : http://www.php.net/manual/en/function.list.php 
	// split 함수 레퍼런스 : http://www.php.net/manual/en/function.split.php 
	list ( $Username, $Domain ) = split ("@",$Email); 

	// 도메인에 MX(mail exchanger) 레코드가 존재하는지를 체크. 근데 영어가 맞나 모르겠네여 -_-+ 
	// checkdnsrr 함수 레퍼런스 : http://www.php.net/manual/en/function.checkdnsrr.php 
	if ( checkdnsrr ( $Domain, "MX" ) )  { 
		if($Debug) echo "확인 : {$Domain}에 대한 MX 레코드가 존재합니다.<br>"; 
		// 만약에 MX 레코드가 존재한다면 MX 레코드 주소를 구해옵니다. 
		// getmxrr 함수 레퍼런스 : http://www.php.net/manual/en/function.getmxrr.php 
		if ( getmxrr ($Domain, $MXHost))  { 
	  if($Debug) { 
				echo "확인 : MX LOOKUP으로 주소 확인중입니다.<br>"; 
			  for ( $i = 0,$j = 1; $i < count ( $MXHost ); $i++,$j++ ) { 
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;결과($j) - {$MXHost[$i]}<BR>";  
		} 
			} 
		} 
		// getmxrr 함수는 $Domain에 대한 MX 레코드 주소를 $MXHost에 배열형태로 저장시킵니다. 
		// $ConnectAddress는 소켓접속을 하기위한 주소입니다. 
		$ConnectAddress = $MXHost['0']; 
	} 
	else { 
		// MX 레코드가 없다면 그냥 @ 다음의 주소로 소켓접속을 합니다. 
		$ConnectAddress = $Domain;		 
		if ($Debug) echo "확인 : {$Domain}에 대한 MX 레코드가 존재하지 않습니다.<br>"; 
	} 

	// $ConnectAddress에 메일 포트인 25번으로 소켓 접속을 합니다. 
	// fsockopen 함수 레퍼런스 : http://www.php.net/manual/en/function.fsockopen.php 
	$Connect = fsockopen ( $ConnectAddress, 25, $errno, $errstr, 10 ); 

	// 소켓 접속에 성공 
	if ($Connect)	
	{ 
		if ($Debug) echo "{$ConnectAddress}의 SMTP에 접속 성공했습니다.<br>"; 
		// 접속후 문자열을 얻어와 220으로 시작해야 서비스가 준비중인 것이라 판단. 
		// 220이 나올때까지 대기 처리를 하면 더 좋겠지요 ^^; 
		// fgets 함수 레퍼런스 : http://www.php.net/manual/en/function.fgets.php 
		if ( ereg ( "^220", $Out = fgets ( $Connect, 1024 ) ) ) { 
			 
			// 접속한 서버에게 클라이언트의 도착을 알립니다. 
			fputs ( $Connect, "HELO $HTTP_HOST\r\n" ); 
				if ($Debug) echo "실행 : HELO $HTTP_HOST<br>"; 
			$Out = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다. 
				if ($Debug) echo "결과 : $Out<br>"; 

			// 서버에 송신자의 주소를 알려줍니다. 
			fputs ( $Connect, "MAIL FROM: <{$Email}>\r\n" ); 
				if ($Debug) echo "실행 : MAIL FROM: &lt;{$Email}&gt;<br>"; 
			$From = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다. 
				if ($Debug) echo "결과 : $From<br>"; 

			// 서버에 수신자의 주소를 알려줍니다. 
			fputs ( $Connect, "RCPT TO: <{$Email}>\r\n" ); 
				if ($Debug) echo "실행 : RCPT TO: &lt;{$Email}&gt;<br>"; 
			$To = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다. 
				if ($Debug) echo "결과 : $To<br>"; 

			// 세션을 끝내고 접속을 끝냅니다. 
			fputs ( $Connect, "QUIT\r\n"); 
				if ($Debug) echo "실행 : QUIT<br>"; 

			fclose($Connect); 

				// MAIL과 TO 명령에 대한 서버의 응답코드가 답긴 문자열을 체크합니다. 
				// 명령어가 성공적으로 수행되지 않았다면 몬가 문제가 있는 것이겠지요. 
				// 수신자의 주소에 대해서 서버는 자신의 메일 계정에 우편함이 있는지를 
				// 체크해 없다면 550 코드로 반응을 합니다. 
				if ( !ereg ( "^250", $From ) ) {
					$Return=-4; 
					return $Return; 
				}
				elseif ( !ereg ( "^250", $To ) ) { 
					$Return=0; 
					return $Return; 
				} 
		} 
	} 
	// 소켓 접속에 실패 
	else { 
		if ($Debug) echo "<br>$proccess_time, 커넥션실패 : $email, MX:$ConnectAddress<br>";

		unset($domaiwhois);
		$domaiwhois = new domain($url);
		if($domaiwhois->is_available()) return -98; 
		else return -1; 
	} 
	// 오~ 위를 모두 통과한 메일에 대해서는 맞는 메일이라고 생각하고 눈 딱 감아주져.^^; 
	$Return=1; 
	return $Return; 
} // end func.
?> 
