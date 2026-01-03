<?php
//=======================================================
// 설	명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/01/12 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // remote_addr()
	'useCheck' => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1 . 넘어온값 체크

	// 2 . 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? $table) .			//table 이름
				"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
				"&sc_string=" . urlencode(stripslashes(isset($sc_string) ? $sc_string : '')) . //search string
				"&team=" . ($_REQUEST['team'] ?? '').
				"&html_headtpl=" . (isset($html_headtpl) ? $html_headtpl : '').
				"&pid=" . ($_REQUEST['pid'] ?? '').
				"&pname=" . ($_REQUEST['pname'] ?? '').
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

	if($_GET['getinfo'] != 'cont') 
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once($thisPath.'config.php');
	
	// 넘어온값 기본 처리
	$qs=array(
				//'title' =>	'post,trim,notnull='	. urlencode('제목을 입력하시기 바랍니다.'),
		);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_write']) $goto = $dbinfo['goto_write'];
		else $goto = $thisUrl.'read.php?'	. href_qs('uid='.$uid,$qs_basic);
		back('',$goto);
		break;
	case 'modify':
		modify_ok($dbinfo, $qs);

		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_modify']) $goto = $dbinfo['goto_modify'];
		else $goto = $thisUrl.'read.php?'	. href_qs('uid='.$_REQUEST['uid'],$qs_basic);
		back('',$goto);
		break;
	case 'delete':
		delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'list.php?'.href_qs('uid=',$qs_basic);
		back('',$goto);
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		/*if( $_REQUEST['mode'] and preg_match('/^[a-z0-9\-\_]+$/i',$_REQUEST['mode']) // eregi -> preg_match
			and function_exists('mode_'.$_REQUEST['mode']) ){
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
	global $db_conn; // mysqli를 위해 추가
	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	if($_POST['phpsess'] != substr(session_id(),0,-5)) 
		back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바람니다.');
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	// 권한 검사
	if(!privAuth($dbinfo, 'priv_write')) back('이용이 제한되었습니다(레벨부족) . 확인바랍니다.');
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array( 'uid', 're', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// TODO
				case 'db' :
					if(!preg_match('/^[a-z0-9]+$/i',$_POST['db'])) // eregi -> preg_match
						back('db값은 영문자숫자로만 입력하십시오');
					break;
			
				// slist write
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n\\1",$qs['content']); // eregi_replace -> preg_replace
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n\\1",$_POST['content']); // eregi_replace -> preg_replace
					break;
				case 'docu_type' : // html값이 아니면 text로
					if(!$_POST['docu_type']) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html') $_POST['docu_type']='text';
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} where  $sql_where ";
					$qs['num'] = db_resultone($sql,0,'max(num)') + 1;	
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'];
				case 'userid' :
					if($_SESSION['seUid']){
						switch($dbinfo['enable_userid']){
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($_SESSION['seUid']) $qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' : $qs['ip'] = remote_addr(); break; // 정확한 IP 주소
				case 'fdate' : $qs['fdate'] = time(); break; // 처음 등록한 시간
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ', passwd	=password("'.$qs['passwd'].'") ';
				else $sql_set .= ', '.$value.' ="'.$qs[$value].'"';
			} elseif(isset($_POST[$value])){
				if($value == 'passwd') $sql_set .= ', passwd	=password("'.$_POST['passwd'].'") ';
				else $sql_set .= ', '.$value.' ="'.$_POST[$value] . '"';
			}
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
	$sql_where = ' 1 '; // init
	
	// $qs 추가, 체크후 값 가져오기
	$qs['uid']	= 'post,trim,notnull='	. urlencode('고유번호가 넘어오지 않았습니다');
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$_POST['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list=db_arrayone($sql) or back('수정할 게시물이 없습니다 . 확인 바랍니다.');
	
	// 수정 권한 체크
	if(!privAuth($dbinfo,'priv_modify') ){
		if($list['bid']>0){
			if( $list['bid'] != $_SESSION['seUid'] or 'nobid' == substr($dbinfo['priv_modify'],0,5) )
				back('수정하실 권한이 없습니다.');
		} else {
			if( $list['passwd'] != $list['pass']) back('정확한 비밀번호를 입력하여 주십시오');
		}
	} // end if

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 
					'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate', 'fdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// TODO
				case 'db' : // 수정불가
					continue 2;
					break;
			
				// slist modify
				/*
				case 'bid' : // 변경되면 안되는 필드
				case 'num' :
				case 're' :	continue 2; break; // 다음 foreach 로...
				*/
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n\\1",$qs['content']); // eregi_replace -> preg_replace
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n\\1",$_POST['content']); // eregi_replace -> preg_replace
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!$_POST['docu_type']) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html') $_POST['docu_type']='text';
					break;
				case 'userid' :
					if($list['bid'] == $_SESSION['seUid']) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid']){
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid'] == $_SESSION['seUid']) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' :	$qs['ip'] = remote_addr(); break; // 정확한 IP 주소
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ', '.$value.' ="'.$qs[$value] . '"';
			elseif(isset($_POST[$value])) $sql_set .= ', '.$value.' ="'.$_POST[$value] . '"';
		} // end foreach
	} // end if
	////////////////////////////////

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
	global $thisUrl;
	$sql_where = ' 1 ' ;

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid' =>	'request,trim,notnull='	. urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd' =>	'request,trim'
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');

	// 삭제 권한 체크
	if(!privAuth($dbinfo,'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if( 'nobid' == substr($dbinfo['priv_delete'],0,5) ) 
			back('삭제하실 수 없습니다.');
		elseif($list['bid']>0) { // 회원이면
			if($list['bid'] != $_SESSION['seUid'])
				back('삭제하실 수 없습니다.');
		} else { // 비회원이면 passwd 검사
			if($list['passwd'] != $list['pass']){
				if($_SERVER['QUERY_STRING']) 
					back('비밀번호를 입력하여 주십시오',$thisUrl.'delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// TODO
	userDeleteBoard2DB($list['db']); // 해당 게시판 테이블 삭제

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ");
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

// 해당 게시판 테이블 삭제
// $forceDelete - 1값이면 무조건 삭제 - 하지만 구현안됨
function userDeleteBoard2DB($db,$forceDelete=0) { // 05/01/24 박선민

	global $db_conn, $SITE;

	// 넘어온값 체크
	if(!$db) return false;

	$prefix = 'board2';
	$table = $SITE['th'].$prefix.'_'.$db;
	
	// 게시물이 있으면 삭제불가
	$sql = "select count(*) as count from {$table}";
	$count_db = db_resultone($sql,0,'count');
	if($forceDelete == 0 and $count_db) back('해당 게시판에 데이터가 있어서 삭제를 취소합니다.');
	
	// 테이블 삭제
	$sql = 'DROP TABLE IF EXISTS '.$table.'';
	db_query($sql);
	// cate 테이블 삭제
	$sql = 'DROP TABLE IF EXISTS '.$table.'_cate';
	db_query($sql);
	// memo 테이블 삭제
	$sql = 'DROP TABLE IF EXISTS '.$table.'_memo';
	db_query($sql);
	
	// 업로드된 것도 삭제 - 하지만 구현 안됨
} 

?>
