<?php
//=======================================================
// 설	명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER = array();
$HEADER['priv']		= ''; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['usedb2']	= 1; // DB 커넥션 사용
$HEADER['useApp']	= 1; // remote_addr()
$HEADER['useCheck']	= 1; // check_value()
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $SITE;

	// 1. 넘어온값 체크
	$mode_req = $_REQUEST['mode'] ?? '';
	$goto_req = $_REQUEST['goto'] ?? '';
	$uid_req = $_REQUEST['uid'] ?? 0;

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? ($table ?? '')) .			//table 이름
				"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string ?? '')) . //search string
				"&team=" . ($_REQUEST['team'] ?? '').
				"&html_headtpl=" . ($html_headtpl ?? '').
				"&pid=" . ($_REQUEST['pid'] ?? '').
				"&pname=" . ($_REQUEST['pname'] ?? '').
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

	if(($_GET['getinfo'] ?? '') !='cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once('config.php');
	
	// 넘어온값 기본 처리
	$qs=array(
				//'title'		 =>	'post,trim,notnull=' . urlencode('제목을 입력하시기 바랍니다.'),
		);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($mode_req) {
	case 'beginwork':
		$uid = write_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if($goto_req) $goto = $goto_req;
		elseif(isset($dbinfo['goto_write'])) $goto = $dbinfo['goto_write'];
		else $goto = 'read.php?' . href_qs('uid='.$uid,$qs_basic);
		back('',$goto);
		break;
	case 'finishwork':
		modify_ok($dbinfo, $qs);

		// 어느 페이지로 이동할 것인지 결정
		if($goto_req) $goto = $goto_req;
		elseif(isset($dbinfo['goto_modify'])) $goto = $dbinfo['goto_modify'];
		else $goto = 'read.php?' . href_qs('uid='.$uid_req,$qs_basic);
		back('',$goto);
		break;
	case 'delete':
		delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($goto_req) $goto = $goto_req;
		elseif(isset($dbinfo['goto_delete'])) $goto = $dbinfo['goto_delete'];
		else $goto = 'list.php?'.href_qs('uid=',$qs_basic);
		back('',$goto);
		break;
	default :
		back('잘못된 요청입니다.');
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function write_ok(&$dbinfo, $qs){
	//TODO		
	// 새벽 6시 이전에는 출근 못함
	if(date('H')<6) {
		echo "새벽 6시 이전에는 출근 못합니다.";
		exit;
	}
	// 출근 여부 확인
	$seUid_safe = (int)($_SESSION['seUid'] ?? 0);
	$workday_safe = date("Ymd");
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE workday='{$workday_safe}' AND bid='{$seUid_safe}'";
	$result = db_query($sql);
	if($list_tmp = db_array($result)) {
		back("이미 {$list_tmp['status']}으로 되어 있습니다.\\n 즐거운 하루 되시기 바랍니다.","./list.php");
	}

	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	if(($_POST['phpsess'] ?? '') != substr(session_id(),0,-5))
		back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바람니다.');
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	// 권한 검사
	if(!privAuth($dbinfo, 'priv_write')) back('이용이 제한되었습니다(레벨부족). 확인바랍니다.');
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array( 'signbid','signdate','uid', 're', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'workday' :
					$qs['workday'] = date("Ymd");
					break;
				case 'begintime' :
					$qs['begintime'] = mktime((int)($_POST['beginhour'] ?? 0), (int)($_POST['beginmin'] ?? 0), 0, (int)date('m'), (int)date('d'), (int)date('Y'));
					break;
				case 'beginip' :
					$qs['beginip'] = remote_addr();// 원본 로직 유지 (ip필드와 겹칠 수 있음)
					break;
				case 'content' : // <br>태그 다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$qs['content']);
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$_POST['content']);
					break;
				case 'docu_type' : // html값이 아니면 text로
					if(empty($_POST['docu_type'])) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type']!='html') $_POST['docu_type']='text';
					break;
				case 'num' :
					$sql_num = "SELECT max(num) as max_num FROM {$dbinfo['table']} WHERE  $sql_where ";
					$res_num = db_query($sql_num);
					$row_num = $res_num ? db_array($res_num) : null;
					$qs['num'] = ($row_num['max_num'] ?? 0) + 1;
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' :
					if(isset($_SESSION['seUid'])) {
						switch($dbinfo['enable_userid'] ?? 'userid') {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if(isset($_POST['email'])) $qs['email']	= check_email($_POST['email']);
					elseif(isset($_SESSION['seUid'])) $qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' : $qs['ip'] = remote_addr(); break; // 정확한 IP 주소
				case 'fdate' : $qs['fdate'] = time(); break; // 처음 등록한 시간
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				if($value=='passwd') $set_parts[] = "`passwd`=password('{$safe_value}')";
				else $set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	$sql_set = implode(', ', $set_parts);
	$sql="INSERT INTO {$dbinfo['table']} SET `rdate` = UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "");
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs){
	$qs=array(
				"dayhours"	 =>	"post,trim",
				"overhours"	 =>	"post,trim",
				"nighthours" =>	"post,trim",
		);
	$qs=check_value($qs);

	// 퇴근처리하지 못한 출근이 있다면 그것부터
	$uid_safe = (int)($_POST['uid'] ?? 0);
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$uid_safe}' LIMIT 1";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back('해당 데이터가 없습니다.');
	
	// 수정이 불가능한지 체크
	if(isset($list['signbid'])) back('승인이 되어서 더 이상 수정할 수 없습니다.');
	
	// 최대 신청 가능 업무시간 구함
	if(isset($list['finishtime']))
		$list['maxworkhours'] = (int)( ( $list['finishtime'] - $list['begintime'] ) / 3600 + 1);
	else
		$list['maxworkhours'] = (int)( ( time() - $list['begintime'] ) / 3600 + 1);

	if($list['maxworkhours'] < ($qs['dayhours']+$qs['overhours']+$qs['nighthours']))
		back("^_^;;\\n출근후 지난시간({$list['maxworkhours']}시간)보다 더 적으셨습니다.");

	$qs['ip']			= remote_addr();	
	$qs['finishtime'] = strtotime($_POST['finishtime']);
	
	if($list['begintime'] > $qs['finishtime'] || ($list['begintime']+24*3600) < $qs['finishtime']) {
		back('퇴근 시간 설정이 잘못되었습니다.');
	}
	
		
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$set_parts = [];
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type',
					'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate', 'fdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'content' : // <br>태그 다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$qs['content']);
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$_POST['content']);
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(empty($_POST['docu_type'])) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type']!='html') $_POST['docu_type']='text';
					break;
				case 'userid' :
					if($list['bid']==($_SESSION['seUid'] ?? '')) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid'] ?? 'userid') {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if(isset($_POST['email'])) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid']==($_SESSION['seUid'] ?? '')) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' :	$qs['ip'] = remote_addr(); break; // 정확한 IP 주소
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$set_parts[] = "`{$value}` = '{$safe_value}'";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	$sql_set = implode(', ', $set_parts);
	$sql = "UPDATE {$dbinfo['table']} SET `rdate`=UNIX_TIMESTAMP()" . ($sql_set ? ", " . $sql_set : "") . " WHERE uid='{$uid_safe}'";
	db_query($sql);

	return true;
} // end func.

// 삭제
function delete_ok(&$dbinfo)
{
	global $thisUrl;
	$sql_where = ' 1 ' ;

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid'		 =>	'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd'	 =>	'request,trim'
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$uid_safe = db_escape($qs['uid']);
	$passwd_safe = db_escape($qs['passwd']);
	$sql = "SELECT *,password('{$passwd_safe}') as pass FROM {$dbinfo['table']} WHERE uid='{$uid_safe}' AND $sql_where LIMIT 1";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if(!$list) back('이미 삭제되었거나 잘못된 요청입니다');

	// 삭제 권한 체크
	if(!privAuth($dbinfo,'priv_delete')) {
		if( 'nobid'==substr($dbinfo['priv_delete'],0,5) )
			back('삭제하실 수 없습니다.');
		elseif($list['bid']>0) { // 회원이면
			if($list['bid']!=($_SESSION['seUid'] ?? ''))
				back('삭제하실 수 없습니다.');
		}
		else { // 비회원이면 passwd 검사
			if($list['passwd']!=$list['pass']) {
				if(isset($_SERVER['QUERY_STRING']))
					back('비밀번호를 입력하여 주십시오','delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$uid_safe}' AND  $sql_where ");
	return true;
} // end func delete_ok()

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
