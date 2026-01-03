<?php
//=======================================================
// 설	명 : 회원 정보 수정 처리(/smember/profile.ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/02
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/24 박선민 추가수정
// 04/07/02 박선민 회원정보 변경에 따른 회원정도 세션값도 변경
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'auth' => 2, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useClassSendmail' =>  1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE, $Action_domain;

	$table			= $SITE['th'] . "logon";	// 회원 아이디/패스워드 테이블
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? ''){
	case 'changepasswd':
		changepasswd();
		go_url("{$Action_domain}/smember/profile.php",0,"비밀번호가 변경되었습니다.");
		break;
	case 'changeprofile':
		changeprofile();
		go_url("{$Action_domain}/smember/profile.php",0,"회원정보가 변경되었습니다.");
		break;
	case 'joinout':
		joinout();
		go_url("{$Action_domain}/sjoin/logout.php",0,"회원 탈퇴가 정상적으로 처리되었습니다.");
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function changepasswd(){
	global $conn, $table;

	$qs=array(	'passwd_old' =>  "post,trim,notnull=" . urlencode("기존 패스워드를 정확히 입력하시기 바랍니다."),
				'passwd' =>  "post,trim,notnull=" . urlencode("새 패스워드를 입력하시기 바랍니다."),
				'passwd_ok' =>  "post,trim,notnull=" . urlencode("새 패스워드를 두번 정확히 입력하시기 바랍니다."),
				'docu_type' =>  "post"
		);
	$qs=check_value($qs);

	if($qs['passwd'] != $qs['passwd_ok'])
		back("새 패스워드를 두번 정확히 입력하시기 바랍니다.");

	// SQL Injection 방지
	$seUid_safe = (int)($_SESSION['seUid'] ?? 0);
	$seUserid_safe = db_escape($_SESSION['seUserid'] ?? '');
	$passwd_safe = db_escape($qs['passwd']);
	$passwd_old_safe = db_escape($qs['passwd_old']);

	// 비밀번호 변경
	$sql = "UPDATE `{$table}` SET passwd=password('{$passwd_safe}') WHERE uid='{$seUid_safe}' AND userid='{$seUserid_safe}' AND passwd=password('{$passwd_old_safe}')";
	db_query($sql);
	if(db_count() == 0) back("회원 본인 확인을 실패하였습니다 . 확인 바랍니다.");
} // end func.

function changeprofile(){
	global $conn, $table;

	$qs=array(	'email' =>  "post,trim,notnull=" . urlencode("메일 주소를 정확히 입력하시기 바랍니다."),
				'address' =>  "post,trim,notnull=" . urlencode("주소를 정확히 입력하시기 바랍니다."),
	);

	$qs=check_value($qs);

	// logon 테이블 정보 가져오기
	$seUid_safe = (int)($_SESSION['seUid'] ?? 0);
	$seUserid_safe = db_escape($_SESSION['seUserid'] ?? '');
	$rs_logon = db_query("SELECT * FROM `{$table}` WHERE uid='{$seUid_safe}' AND userid='{$seUserid_safe}'");
	if(!(db_count($rs_logon) > 0)) back("인증 세션에 문제가 있거나 회원 DB에 이상이 있습니다.");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array( 'uid', 'userid', 'passwd', 'rdate', 'ip', 'host');
	if($fieldlist = userGetAppendFields($table, $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'birth' :
					$qs['birth'] = ($_POST['birth2'] ?? '').($_POST['birth3'] ?? '').($_POST['birth_lunar'] ?? '').($_POST['birth1'] ?? '');
					break;
				case 'zip' :
					$qs['zip'] = ($_POST['zip1'] ?? '') .'-'. ($_POST['zip2'] ?? '');
					break;
				case 'tel' :
					$qs['tel'] = ($_POST['tel1'] ?? '') . '-' .	($_POST['tel2'] ?? '') . '-'	. ($_POST['tel3'] ?? '');
					break;
				case 'hp' :
					$qs['hp'] = ($_POST['hp1'] ?? '') . '-' .	($_POST['hp2'] ?? '') . '-'	. ($_POST['hp3'] ?? '');
					break;
				case 'nickname' :
					$qs['nickname'] = isset($_POST['name']) ? $_POST['name'] : ($_SESSION['seName'] ?? '');
					break;
				case 'yesmail' :
					$qs['yesmail'] =	isset($_POST['yesmail']) ? $_POST['yesmail'] : '0';
					break;
				case 'yessms' :
					$qs['yessms'] =	isset($_POST['yessms']) ? $_POST['yessms'] : '0';
					break;
			} // end switch
	
			// sql_set 만듦
			if(isset($qs[$value])){
				$safe_value = db_escape($qs[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			} elseif(isset($_POST[$value])){
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		} // end foreach
	} // end if
		
	// logon 테이블 회원정보변경
	$sql_set = implode(', ', $set_parts);
	$sql = "UPDATE `{$table}` SET `mdate` = UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . " WHERE uid='{$seUid_safe}' AND userid='{$seUserid_safe}'";
	db_query($sql);
	
	// 회원 정보 변경에 따른 세션값도 변경
	$_SESSION['seEmail'] = $qs['email'] ?? '';
	$_SESSION['seNickname']= $qs['nickname'] ?? '';
	
} // end func.

// 삭제
function joinout(){
	global $conn, $table;

	$qs=array('passwd' =>  "post,trim,notnull=" . urlencode("패스워드를 입력하시기 바랍니다."));
	$qs=check_value($qs);

	// SQL Injection 방지
	$seUid_safe = (int)($_SESSION['seUid'] ?? 0);
	$seUserid_safe = db_escape($_SESSION['seUserid'] ?? '');
	$passwd_safe = db_escape($qs['passwd']);

	// 회원인지 확인 후 삭제
	$sql = "DELETE FROM `{$table}` WHERE uid='{$seUid_safe}' AND userid='{$seUserid_safe}' AND passwd=password('{$passwd_safe}')";
	db_query($sql);
	if(db_count() == 0){
		back("회원 본인 확인을 실패하였습니다 . 확인 바랍니다.");
	}
} // end func.

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>