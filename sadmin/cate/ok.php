<?php
//=======================================================
// 설 명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 005/01/27 박선민 마지막 수정
// 24/05/21 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useCheck' => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1 . 넘어온값 체크

	// 3 . $dbinfo 가져오기
	include_once($thisPath.'config.php');

	global $SITE, $db_conn;

	// 2 . 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? ($dbinfo['table'] ?? '')) .	//table 이름
				"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
				"&sc_string=" . urlencode(stripslashes($_REQUEST['sc_string'] ?? '')) . //search string
				"&html_headtpl=" . ($_REQUEST['html_headtpl'] ?? '').
				"&pid=" . ($_REQUEST['pid'] ?? '').
				"&pname=" . ($_REQUEST['pname'] ?? '').
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

	if (isset($_GET['getinfo']) && $_GET['getinfo'] != 'cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 넘어온값 기본 처리
	$qs=array(
				'title' =>	'post,trim,notnull='	. urlencode('제목을 입력하시기 바랍니다.'),
		);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? null){
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if (isset($_REQUEST['goto'])) $goto = $_REQUEST['goto'];
		elseif (isset($dbinfo['goto_write'])) $goto = $dbinfo['goto_write'];
		else $goto = $thisUrl.'read.php?'	. href_qs('uid='.$uid,$qs_basic);
		back('',$goto);
		break;
	case 'modify':
		modify_ok($dbinfo, $qs);

		// 어느 페이지로 이동할 것인지 결정
		if (isset($_REQUEST['goto'])) $goto = $_REQUEST['goto'];
		elseif (isset($dbinfo['goto_modify'])) $goto = $dbinfo['goto_modify'];
		else $goto = $thisUrl.'read.php?'	. href_qs('uid='.($_REQUEST['uid'] ?? ''),$qs_basic);
		back('',$goto);
		break;
	case 'delete':
		delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if (isset($_REQUEST['goto'])) $goto = $_REQUEST['goto'];
		elseif (isset($dbinfo['goto_delete'])) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'list.php?'.href_qs('uid=',$qs_basic);
		back('',$goto);
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		/*if( isset($_REQUEST['mode']) and preg_match('/^[a-z0-9\-\_]+$/i',$_REQUEST['mode'])
			and function_exists('mode_'.$$_REQUEST['mode']) ){
			$func = 'mode_'.$_REQUEST['mode'];
			$func();			
		}
		else */
			back('잘못된 요청입니다.');
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok(&$dbinfo, $qs){
	global $db_conn;
	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	if (isset($_POST['phpsess']) && $_POST['phpsess'] != substr(session_id(),0,-5))
		back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바람니다.');
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	// 권한 검사
	if (!privAuth($dbinfo, 'priv_write')) back('이용이 제한되었습니다(레벨부족) . 확인바랍니다.');
	
	$sql_set = '';
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array( 'uid', 're', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if (isset($dbinfo['table']) && $fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// slist write
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if (isset($qs['content'])) $qs['content'] = preg_replace('/<br>([^\r\n])/i', "<br>\n\\1", $qs['content']);
					elseif (isset($_POST['content'])) $_POST['content'] = preg_replace('/<br>([^\r\n])/i', "<br>\n\\1", $_POST['content']);
					break;
				case 'docu_type' : // html값이 아니면 text로
					if (!isset($_POST['docu_type'])) $_POST['docu_type'] = $dbinfo['default_docu_type'] ?? 'text';
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if ($_POST['docu_type'] != 'html') $_POST['docu_type']='text';
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} where  $sql_where ";
					$max_num = db_resultone($sql,0,'max(num)');
					$qs['num'] = (int)$max_num + 1;
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' :
					if (isset($_SESSION['seUid'])){
						switch($dbinfo['enable_userid'] ?? 'userid'){
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if (isset($_POST['email'])) $qs['email']	= check_email($_POST['email']);
					elseif (isset($_SESSION['seUid'])) $qs['email']	= $_SESSION['seEmail'];
					break;
				case 'passwd' :
					if (isset($qs['passwd'])) $sql_set .= ", passwd=password('".db_escape($qs['passwd']) . "')";
					elseif (isset($_POST['passwd'])) $sql_set .= ", passwd=password('".db_escape($_POST['passwd']) . "')";
					break;
				default:
					// sql_set 만듦
					if (isset($qs[$value])){
						$sql_set .= ", {$value} = '" . db_escape($qs[$value]) . "' ";
					}
					elseif (isset($_POST[$value])){
						$sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . "' ";
					}
					break;
			} // end switch
		} // end foreach
	} // end if
	////////////////////////////////
	
	$sql="INSERT INTO {$dbinfo['table']} SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs){
	global $db_conn, $SITE;
	$sql_where = ' 1 '; // init
	
	// $qs 추가, 체크후 값 가져오기
	$qs['uid']	= 'post,trim,notnull='	. urlencode('고유번호가 넘어오지 않았습니다');
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('" . db_escape($_POST['passwd'] ?? '') . "') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list=db_arrayone($sql) or back('수정할 게시물이 없습니다 . 확인 바랍니다.');
	
	// 수정 권한 체크
	if (!privAuth($dbinfo,'priv_modify') ){
		if (isset($list['bid']) && $list['bid']>0){
			if ( ($list['bid'] ?? 0) != ($_SESSION['seUid'] ?? 0) or 'nobid' == substr($dbinfo['priv_modify'] ?? '',0,5) )
				back('수정하실 권한이 없습니다.');
		} else {
			if ( ($list['passwd'] ?? '') != ($list['pass'] ?? '')) back('정확한 비밀번호를 입력하여 주십시오');
		}
	} // end if

	$sql_set = '';
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type',
					'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if (isset($dbinfo['table']) && $fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value) {
				// slist modify
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if (isset($qs['content'])) $qs['content'] = preg_replace('/<br>([^\r\n])/i', "<br>\n\\1", $qs['content']);
					elseif (isset($_POST['content'])) $_POST['content'] = preg_replace('/<br>([^\r\n])/i', "<br>\n\\1", $_POST['content']);
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if (!isset($_POST['docu_type'])) $_POST['docu_type'] = $dbinfo['default_docu_type'] ?? 'text';
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if ($_POST['docu_type'] != 'html') $_POST['docu_type']='text';
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'userid' :
					if (($list['bid'] ?? 0) == ($_SESSION['seUid'] ?? 0)) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid'] ?? 'userid'){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? null; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? null; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? null; break;
						}
					}
					break;
				case 'email' :
					if (isset($_POST['email'])) $qs['email']	= check_email($_POST['email']);
					elseif (($list['bid'] ?? 0) == ($_SESSION['seUid'] ?? 0)) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'] ?? null;
					break;
				default:
					if (isset($qs[$value])) $sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
					elseif (isset($_POST[$value])) $sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
					break;
			} // end switch
		} // end foreach
	} // end if

	$sql = "UPDATE {$dbinfo['table']} SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set}
			WHERE
				uid='{$qs['uid']}'
		";
	db_query($sql);

	return true;
} // end func.
// 삭제
function delete_ok(&$dbinfo){
	global $thisUrl, $db_conn, $SITE;
	$sql_where = ' 1 ' ;

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid' =>	'request,trim,notnull='	. urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd' =>	'request,trim'
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('" . db_escape($qs['passwd'] ?? '') . "') as pass FROM {$dbinfo['table']} WHERE uid='" . ($qs['uid'] ?? '') . "' and $sql_where LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');

	// 삭제 권한 체크
	if (!privAuth($dbinfo,'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if ('nobid' == substr($dbinfo['priv_delete'] ?? '',0,5) )
			back('삭제하실 수 없습니다.');
		elseif (isset($list['bid']) && $list['bid']>0){
			if ( ($list['bid'] ?? 0) != ($_SESSION['seUid'] ?? 0))
				back('삭제하실 수 없습니다.');
		} else {
			if (($list['passwd'] ?? '') != ($list['pass'] ?? '')){
				if (isset($_SERVER['QUERY_STRING']))
					back('비밀번호를 입력하여 주십시오',$thisUrl.'delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='" . ($qs['uid'] ?? '') . "' and  $sql_where ");
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