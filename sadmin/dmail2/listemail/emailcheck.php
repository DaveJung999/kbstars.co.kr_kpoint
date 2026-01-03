<?php
set_time_limit(0);
//=======================================================
// 설	명 : dmail의 email 체크(emailcheck.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/06/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/06/12 박선민 마지막 수정
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
	page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

	$debug =1 ; // DEBUG
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_dmailinfo	= "{$SITE['th']}dmailinfo";
	$db = isset($_REQUEST['db']) ? $_REQUEST['db'] : '';
	$table_dmail		= "{$SITE['th']}dmail_" . $db;

	if(!$dmailinfo = db_arrayone("SELECT * from {$table_dmailinfo} WHERE db='". db_escape($db) ."'"))
		back("db값이 넘어오지 않았습니다");

	//$table		= $_GETS['table'];
	$fromemail	= "test@yahoo.com";

//	header('Cache-Control: no-cache, must-revalidate');
//	header('Pragma: no-cache');
	//include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_domainwhois_old.php");
	echo "E-mail 실제 유무 체크 시작<br>\n";

	echo "+*:성공, _:No User, d:denied, x: noreg domain, .:No Connect, !:No email<br>";
	echo str_repeat("	",300);	echo ("\n");
	ob_flush();flush();

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
//$result=db_query("SELECT * from {$table}");
	$sql = "SELECT uid, email, SUBSTRING(email FROM INSTR(email,'@')+1) AS domain FROM $table_dmail
					WHERE emailcheck = 0	ORDER BY domain";
	$result=db_query($sql);

	$total=db_count($result);
	$start_unixtime=time();
	$start_date=date("Y년 m월 d일 H시 i분 s초",$start_unixtime);

	echo "$start_date, {$total}개 메일 검사 시작합니다 . <br>";
	echo str_repeat(" ",300);	echo ("\n");
	ob_flush();flush();

	$connect_ok = 0;
	$Connect = null;
	$before_domain = null;
	$okstring = "+";

	for($i=0;$i<$total;$i++){
		$emailcheck=0;
		$proccess_time = time() - $start_unixtime;

		$list = db_array($result, $i);
		$uid = $list['uid'];
		$email = $list['email'];
		$domain = $list['domain'];

		if($debug) echo "<br>{$i} 번째 {$email} -><br>";

		// email 형식 체크를 위한 정규식 표현 . 많이 공개된 것이니 설명을 하지않도록 하겠습니다 .
		if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email)) {
			$emailcheck=-99;
		}
		//메일 체크후 정상일 경우
		else {
			// 메일은 @를 기준으로 2개로 나눠줍니다 . 만약에 $email 이 "lsm@ebeecomm.com"이라면
			// $Username : lsm
			// $Domain : ebeecomm.com 이 저장
			// list 함수 레퍼런스 : http://www.php.net/manual/en/function.list.php
			// split 함수 레퍼런스 : http://www.php.net/manual/en/function.split.php
			$parts = explode ("@",$email);
			if (count($parts) >= 2) {
				list ( $Username, $Domain ) = $parts;
			} else {
				$emailcheck = -99;
				goto update_db;
			}


			// 도메인에 MX(mail exchanger) 레코드가 존재하는지를 체크 . 근데 영어가 맞나 모르겠네여 -_-+
			// checkdnsrr 함수 레퍼런스 : http://www.php.net/manual/en/function.checkdnsrr.php
			if($before_domain != $Domain){
				$before_domain=$Domain;
				$connect_ok=0;
				if ( checkdnsrr ( $Domain, "MX" ) )	{
					if($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;확인 : {$Domain}에 대한 MX 레코드가 존재합니다.<br>";
					// 만약에 MX 레코드가 존재한다면 MX 레코드 주소를 구해옵니다 .
					// getmxrr 함수 레퍼런스 : http://www.php.net/manual/en/function.getmxrr.php
					if ( getmxrr ($Domain, $MXHost))	{
						if($debug) {
							echo "&nbsp;&nbsp;&nbsp;&nbsp;확인 : MX LOOKUP으로 주소 확인중입니다.<br>";
							for ( $i_mxhost = 0,$j_mxhost = 1; $i_mxhost < count ( $MXHost ); $i_mxhost++,$j_mxhost++ ) {
								echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;결과({$j_mxhost}) - {$MXHost[$i_mxhost]}<BR>";
							} // end for
						} // end if
					} // end if
					// getmxrr 함수는 $Domain에 대한 MX 레코드 주소를 $MXHost에 배열형태로 저장시킵니다 .
					// $ConnectAddress는 소켓접속을 하기위한 주소입니다 .
					$ConnectAddress = $MXHost['0'];
				}
				else {
					// MX 레코드가 없다면 그냥 @ 다음의 주소로 소켓접속을 합니다 .
					$ConnectAddress = $Domain;
					if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;확인 : {$Domain}에 대한 MX 레코드가 존재하지 않습니다.<br>";
				}
			} // end if

			if(!$connect_ok || ($before_domain != $Domain)){
				//만약 커넥션이 되어있다면 끝내야 겠죠
				if($Connect){
					// 세션을 끝내고 접속을 끝냅니다 .
					stream_set_timeout($Connect, 5);
					fputs ( $Connect, "QUIT\r\n");
					if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : QUIT<br>";
					fclose($Connect);
				}

				// $ConnectAddress에 메일 포트인 25번으로 소켓 접속을 합니다 .
				// fsockopen 함수 레퍼런스 : http://www.php.net/manual/en/function.fsockopen.php
				$Connect = @fsockopen ( $ConnectAddress, 25, $errno, $errstr, 10 );
				if (!$Connect) {// 소켓 접속에 실패
					if ($debug) echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;{$proccess_time}, 커넥션실패 : {$email}, MX:{$ConnectAddress}, errno {$errno}({$errstr})<br>";

					$emailcheck = -1;
				}
				else { // 소켓 접속에 성공
					if ($debug) echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;{$proccess_time}초, 커넥션시도 : {$email}, MX:{$ConnectAddress}<br>";
					if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;{$ConnectAddress}의 SMTP에 접속 성공했습니다.<br>";
					// 접속후 문자열을 얻어와 220으로 시작해야 서비스가 준비중인 것이라 판단 .
					// 220이 나올때까지 대기 처리를 하면 더 좋겠지요 ^^;
					// fgets 함수 레퍼런스 : http://www.php.net/manual/en/function.fgets.php
					stream_set_timeout($Connect, 10);
					$Out = fgets ( $Connect, 1024 );
					if ( preg_match ( "/^220/", $Out ) ) {
						//커넥션 성공 스위치 변수
						$connect_ok=1;

						// 접속한 서버에게 클라이언트의 도착을 알립니다 .
						fputs ( $Connect, "HELO {$_SERVER['HTTP_HOST']}\r\n" );
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : HELO {$_SERVER['HTTP_HOST']}<br>";
						$Out = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다 .
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;결과 : {$Out}<br>";

						// 서버에 송신자의 주소를 알려줍니다 .
						fputs ( $Connect, "MAIL FROM: <{$fromemail}>\r\n" );
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : MAIL FROM: &lt;{$fromemail}&gt;<br>";
						$From = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다 .
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;결과 : {$From}<br>";

						// 서버에 수신자의 주소를 알려줍니다 .
						fputs ( $Connect, "RCPT TO: <{$email}>\r\n" );
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : RCPT TO: &lt;{$email}&gt;<br>";
						$To = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다 .
							if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;결과 : {$To}<br>";

						// MAIL과 TO 명령에 대한 서버의 응답코드가 답긴 문자열을 체크합니다 .
						// 명령어가 성공적으로 수행되지 않았다면 몬가 문제가 있는 것이겠지요 .
						// 수신자의 주소에 대해서 서버는 자신의 메일 계정에 우편함이 있는지를
						// 체크해 없다면 550 코드로 반응을 합니다 .
						if ( !preg_match ( "/^250/", $From ) ){
							$emailcheck=-4;
						} elseif ( !preg_match ( "/^250/", $To ) ) {
							$emailcheck=0;
						}
						else {
							$emailcheck=1;
							// 메일 체크 완성시 표시되는 echo 문자를 커넥션이 바뀔때마다 바꿈
							if($okstring == "+")	$okstring="*";
							else				$okstring="+";
						} // end if

					} // end if
					else {	// 임시로 넣음 03/06/11
						if ($debug) echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;{$proccess_time}, 커넥션실패 : {$email}, MX:{$ConnectAddress}<br>";

						$emailcheck=-1;
					}
				}
			}
			//이미 커넥션이 되어 있을때
			else {
				if ($debug) echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;{$proccess_time}, 커넥된이후 : {$email}, MX:{$ConnectAddress}<br>";

				if ($Connect){
					$Connect_status = stream_get_meta_data($Connect);
					if($Connect_status['time_out'] or $Connect_status['eof']){
						// 다시 커넥트 해서 시도
						$before_domain=null;
						$connect_ok=0;
						$i--;
						continue;
					}

					// 서버에 수신자의 주소를 알려줍니다 .
					fputs ( $Connect, "RCPT TO: <{$email}>\r\n" );
						if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : RCPT TO: &lt;{$email}&gt;<br>";
					$To = fgets ( $Connect, 1024 ); // 서버의 응답코드를 받아옵니다 .
						if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;결과 : {$To}<br>";

					// MAIL과 TO 명령에 대한 서버의 응답코드가 답긴 문자열을 체크합니다 .
					// 명령어가 성공적으로 수행되지 않았다면 몬가 문제가 있는 것이겠지요 .
					// 수신자의 주소에 대해서 서버는 자신의 메일 계정에 우편함이 있는지를
					// 체크해 없다면 550 코드로 반응을 합니다 .
					if ( !preg_match ( "/^250/", $To )) {
						$emailcheck=0;
					}
					else {
						// 오~ 위를 모두 통과한 메일에 대해서는 맞는 메일이라고 생각하고 눈 딱 감아주져.^^;
						$emailcheck=1;
					}
				} else {
					// 다시 커넥트 해서 시도
					$before_domain=null;
					$connect_ok=0;
					$i--;
					continue;
				} //end if. . else..
			} // end if. . else..
		} // end if. . else..

		update_db:
		//메일 체크후 DB에 기록
		/*
		// 03/06/11
		sendok
			0 : 발송 가능
			2 : 발송 실패
			4 : 발송 보류

			// emailcheck
			9 : 발송 준비
			41 : 메일 가짜
			42 : 메일 유저 없음
			43 : 도메인미등록
			44 : 메일 서버 거부
			45 : 접속실패
		*/
		switch ($emailcheck){
			case 1: // 메일 OK
				$count_ok++;
				echo $okstring;
				$sendok	= 1;
				break;
			case 0: // 메일 유저 없음
				$count_nouser++;
				echo "_";
				$sendok	= 42;
				break;
			case -1: // 접속실패
				$count_noconnect++;
				echo ".";
				$sendok	= 45;
				break;
			case -4: // 메일 서버 거부
				$count_denied++;
				echo "d";
				$sendok	= 44;
				break;
			case -98: // 도메인이 미등록
				$count_noregister++;
				echo "x";
				$sendok	= 43;
				break;
			case -99: // 메일주소가 장난
				$count_noemail++;
				echo "!";
				$sendok	= 41;
				break;
			default:
				$sendok = 0;
				break;
		} // end switch

		db_query("update {$table_dmail} SET emailcheck='". db_escape($sendok) ."' WHERE uid='". db_escape($uid) ."'");

		if($i % 10 == 0) flush();
		if($i % 50 == 0){
			$count_now=$i+1;
			echo "<br>{$proccess_time}초 지남, 현재 : {$count_now}, 성공: {$count_ok}, No User: {$count_nouser}, No Connect: {$count_noconnect}, No email : {$count_noemail}<br>";
		} // end if
	} // end for

	// 세션을 끝내고 접속을 끝냅니다 .
	if($Connect){
		fputs ( $Connect, "QUIT\r\n");
		if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;실행 : QUIT<br>";
		fclose($Connect);
	}
	echo "\n<br><br>일부 완료되었습니다.\n";

	db_free($result);
	$sql = "SELECT uid, email FROM {$table_dmail} WHERE emailcheck=0 LIMIT 1";
	$result=db_query($sql);
	if(db_count($result))
		echo "<br><br><meta http-equiv='Refresh' content='0; URL={$_SERVER['PHP_SELF']}?db={$_GET['db']}'>";
	else
		echo "\n<br><br>모두 완료되었습니다.\n";
?>
