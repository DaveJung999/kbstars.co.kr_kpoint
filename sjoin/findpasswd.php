<?php
//=======================================================
// 설  명 : 패스워드 찾기(/sjoin/findpasswd.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php, eregi_replace() → preg_replace() 변환
//=======================================================
$HEADER=array(
		'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2'		=>1, // DB 커넥션 사용
		'useCheck'	=>1, // check_value()
		'useClassSendmail'=> 1, // mime_mail()
		'useSkin'	=>1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//ini_set("SMTP","mail.mokpo.ac.kr");
ini_set("sendmail_from","sponsor@new21.com");

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기
	
	// table
	$table_logon		= $SITE['th'] . 'logon';
	
	$nowdate = date('Y-m-d [H:m:s]');

	//////////////////////
	// 회원아이디 찾아주기
	if($_POST['mode'] == 'finduserid') { 
		$qs=array(	'name'	=> 'post,trim,notnull=' . urlencode('회원 이름을 입력하시기 바랍니다.'),
					'idnum1'=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
					'idnum2'=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
			);
		$qs=check_value($qs);
		$qs['idnum'] = $qs['idnum1'].'-'.$qs['idnum2'];

		$sql = "select userid from $table_logon where name='{$qs['name']}' and idnum='{$qs['idnum']}'";
		$rs_search = db_query($sql);
		if(!db_count($rs_search)) back('입력하신 정보와 일치한 회원아이디가 없습니다.');
		while($list=db_array($rs_search)) {
			$list_userid .="\\n{$list['userid']}";
		}
		back("입력한 정보와 일치한 다음의 아이디가 검색되었습니다.\\n$list_userid");
	}
	
	//////////////////////////////////
	// 패스워드 확인 메일 발송 등 처리
	elseif($_POST['mode']=='sendcertifycode') {
		$qs=array(	'userid'	=> 'post,trim,notnull=' . urlencode('회원 아이디를 입력하시기 바랍니다.'),
			);
		$qs=check_value($qs);

		// 회원 정보 가져오기
		$sql="SELECT * FROM $table_logon WHERE userid='{$qs['userid']}' and level>=0";
		$list = db_arrayone($sql) or back('해당 유저아이디가 존재하지 않습니다.\\n가입하지 않았거나 자동 탈퇴처리가 되었을 것입니다.');

		// 인증코드 생성
		// - 해킹을 대비하여 사이트마다 순서등 조금씩 틀리게하는 것도 좋을 것임.
		$certifycode = substr(md5( $list['uid'].$list['userid'].$list['passwd'].date('d')),0,10); 

		// 해당 회원님께 인증 코드 발송
		$mail = new mime_mail;
		$mail->from		= $SITE['webmaster'];
		$mail->to		= $list['email'];
		$mail->name		= $list['email'];
		$mail->subject	= '[중요-인증코드] 패스워드 찾기 위해서 회원님이 요청하신 인증코드입니다.';
		$mail->body		= "{$_SERVER['HTTP_HOST']} 사이트에서 패스워드를 재 설정하기 위한 인증코드를 보내드립니다.
IP주소 {$_SERVER['REMOTE_ADDR']}에서 회원아이디 {$list['userid']}에 대한 패스워드를 잊어버려,
새로운 패스워드로 변경하기 위해서 요청되었습니다.

만일 해킹으로 생각이 되시면 회원님의 중요 패스워드를 임시 변경하시고,
만일을 대비하시기 바랍니다.
해킹 관련 피해는 경찰청 사이버범죄수사대에 신고하시면 됩니다.

아래 인증 코드를 입력하시고 패스워드를 재 설정하시면 됩니다.

회원아이디 : {$list['userid']}
인증코드 : $certifycode
요청시간 : {$nowdate} 

------------------------------------------------------------------
기타 궁금한 사항이 있다면 사이트내 종합질문페이지에 문의 바랍니다.
				";
		$mail->html		= 0;
		$mail->send();

		// PHP 7+ 호환성: eregi_replace() → preg_replace() 변환
		$emaildomain = preg_replace('/.*@(.*)/i','\1',$list['email']);

		back("인증코드를 메일로 보내드렸습니다.\\n\\n메일제목:[중요-인증코드] 패스워드 찾기 위해서 회원님이 요청하신 인증코드입니다.\\n메일서버도메인: $emaildomain\\n\\n스팸메일로 분류되어 있을지 모르니, 스팸메일함도 확인하시기 바랍니다.\\n오랜시간동안 메일이 도착하지 않으면 질문게시판에 문의주시기 바랍니다.");
	}
	
	
	//////////////////////
	// 패스워드 실제 변경
	elseif($_POST['mode']=='changepasswd') { 
		$qs=array(	'userid'		=> 'post,trim,notnull',
					'certifycode'	=> 'post,trim,notnull=' . urlencode('메일로 보내드린 인증코드를 입력하시기 바랍니다.'),
					'passwd'		=> 'post,trim,notnull=' . urlencode('비밀번호를 입력하시기 바랍니다.'),
					'passwd2'		=> 'post,trim,notnull=' . urlencode('비밀번호를 입력하시기 바랍니다.')
			);
		$qs=check_value($qs);

		if($qs['passwd']!=$qs['passwd2'])
			back('비밀번호를 두번 정확히 입력하여 주시기 바랍니다.');
		if(strlen($qs['passwd']) < 4)
			back('비밀번호를 4자 이상으로 정확히 다시 입력 바랍니다.');
		
		// 회원 정보 가져오기
		$sql="SELECT * FROM $table_logon WHERE userid='{$qs['userid']}' and level>=0";
		$list = db_arrayone($sql) or back('해당 유저아이디가 존재하지 않습니다.\\n가입하지 않았거나 자동 탈퇴처리가 되었을 것입니다.');
		if( $qs['certifycode']==substr(md5( $list['uid'].$list['userid'].$list['passwd'].date('d')),0,10) ) {
			db_query("update $table_logon set passwd=password('{$qs['passwd']}') where uid='{$list['uid']}'");
			$mail = new mime_mail;
			$mail->from		= $SITE['webmaster'];
			$mail->to		= $list['email'];
			$mail->name		= $list['email'];
			$mail->subject	= '[중요알림] 회원님의 패스워드가 재 설정되었습니다.';
			$mail->body		= "{$_SERVER['HTTP_HOST']} 사이트에서 알려드립니다.
	아이디/패스워드분실페이지를 통한 회원님의 웹인증패스워드가 변경되었슴을 알려드립니다.

	회원아이디 : {$list['userid']}
	회원이름 : {$list['name']}
	접속 IP  : {$_SERVER['REMOTE_ADDR']}
	요청시간 : {$nowdate} 
	
	만일 해킹으로 생각이 되시면 회원님의 중요 패스워드를 임시 변경하시고,
	만일을 대비하시기 바랍니다.
	해킹 관련 피해는 경찰청 사이버범죄수사대에 신고하시면 됩니다.

	------------------------------------------------------------------
	기타 궁금한 사항이 있다면 사이트내 종합질문페이지에 문의 바랍니다.
	감사합니다.

					";
			$mail->html		= 0;
			$mail->send();

			$go_url = 'http://www.kbsavers.com/';
			go_url($go_url,0,'비밀번호가 변경되었습니다.\\n메인 페이지로 이동합니다.');		
		}
		else { // 인증코드가 틀렸을 경우
			// 해당 회원님께 사실 통보
			$mail = new mime_mail;
			$mail->from		= $SITE['webmaster'];
			$mail->to		= $list['email'];
			$mail->name		= $list['email'];
			$mail->subject	= '[중요알림] 회원님의 패스워드를 누군가 변경하려고 하였습니다.';
			$mail->body		= "{$_SERVER['HTTP_HOST']} 사이트에서 알려드립니다.
	IP주소 {$_SERVER['REMOTE_ADDR']} 에서 회원아이디 {$qs['userid']}에 대한 패스워드를 변경하고자 하였습니다.
	만일 해킹으로 생각이 되시면 회원님의 중요 패스워드를 임시 변경하시고,
	만일을 대비하시기 바랍니다.
	해킹 관련 피해는 경찰청 사이버범죄수사대에 신고하시면 됩니다.

	회원아이디 : {$list['userid']}
	회원이름 : {$list['name']}
	접속 IP  : {$_SERVER['REMOTE_ADDR']}
	요청시간 : {$nowdate} 
	
	기타 궁금한 사항이 있다면 사이트내 종합질문페이지에 문의 바랍니다.
					";
			$mail->html		= 0;
			$mail->send();

			back('요청한 정보가 일치하지 않습니다.\\n해당 회원님의 메일로 본 사실이 통보되었습니다.');
		} // end if .. else ..
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$form_changepasswd=" method='post' action='https://www.kbsavers.com{$_SERVER['PHP_SELF']}'>
			<input type='hidden' name='mode' value='changepasswd'";
$tpl->set_var('form_changepasswd', $form_changepasswd);
$form_finduserid=" method='post' action='https://www.kbsavers.com{$_SERVER['PHP_SELF']}'>
			<input type='hidden' name='mode' value='finduserid'";
$tpl->set_var('form_finduserid', $form_finduserid);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

?>