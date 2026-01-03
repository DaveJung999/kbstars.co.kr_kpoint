<?php
//=======================================================
// 설	 명 : 심플리스트 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
//=======================================================
$HEADER = array();
$HEADER['priv']		= '회원'; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['usedb2']	= 1; // DB 커넥션 사용
$HEADER['useApp']	= 1; // remote_addr()
$HEADER['useCheck']	= 1; // check_value()
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if($_GET['getinfo']!='cont') 
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once('config.php');
	$dbinfo['table']			=$SITE['th']."seller";
	$dbinfo['enable_upload']="Y";
	$dbinfo['enable_uploadextension']='gif,jpg,jpeg';
	
	// 넘어온값 기본 처리
	$qs=array(
				'title'		=> 'post,trim,notnull=' . urlencode('판매자몰 이름을 입력하시기 바랍니다.'),
		);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		back('신청되었습니다. 판매자페이지로 이동합니다.','/sshop2/scm');
		break;
/*	case 'modify':
		modify_ok($dbinfo, $qs);

		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_modify']) $goto = $dbinfo['goto_modify'];
		else $goto = 'read.php?' . href_qs('uid='.$_REQUEST['uid'],$qs_basic);
		back('',$goto);
		break;
	case 'delete':
		delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = 'list.php?'.href_qs('uid=',$qs_basic);
		back('',$goto);
		break;*/
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		/*if( isset($_REQUEST['mode']) && preg_match('/^[a-z0-9\-\_]+$/i', $_REQUEST['mode']) )
			and function_exists('mode_'.$_REQUEST['mode']) ) {
			$func = 'mode_'.$_REQUEST['mode'];
			$func();			
		}
		else */
			back('잘못된 요청입니다.');
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function write_ok(&$dbinfo, $qs)
{
	global $SITE;
	$table_seller = $SITE['th'] . "seller";
	// 이미 가입신청되었는지 체크
	$sql = "select * from $table_seller where bid='{$_SESSION['seUid']}'";
	$rs=db_query($sql);
	if(db_count($rs)) back('이미 판매자 가입 신청이 되었습니다.\\n판매자페이지로 이동합니다.','/sshop2/scm');
	
	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	//if($_POST['phpsess']!=substr(session_id(),0,-5)) 
	//	back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바람니다.');
	
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);

	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array( 'uid', 're', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'],$skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				case 'name' : 
					$qs['name'] = $_SESSION['seName'];
					break;
				case 'userid' : 
					$qs['userid'] = $_SESSION['seUserid'];
					break;
				case 'c_num':
					$qs['c_num'] = $_POST['c_num1'].'-'.$_POST['c_num2'].'-'.$_POST['c_num3'];
					break;
				case 'c_idnum':
					$qs['c_idnum'] = $_POST['c_idnum1'].'-'.$_POST['c_idnum2'];
					break;
				// slist write
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$qs['content']);
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$_POST['content']);
					break;
				case 'docu_type' : // html값이 아니면 text로
					if(!$_POST['docu_type']) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type']!='html') $_POST['docu_type']='text';
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} where $sql_where";
					$qs['num'] = db_resultone($sql,0,'max(num)') + 1;	
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'];
					break;
/*				case 'userid' :
					if($_SESSION['seUid']) {
						switch($dbinfo['enable_userid']) {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;*/
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($_SESSION['seUid']) $qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' : $qs['ip'] = remote_addr(); break; // 정확한 IP 주소
				case 'fdate' : $qs['fdate'] = time(); break; // 처음 등록한 시간
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				if($value=='passwd') $sql_set .= ', passwd	=password("'.$qs['passwd'].'") ';
				else $sql_set .= ', '.$value.' ="'.$qs[$value].'"';
			}
			elseif(isset($_POST[$value])) {
				if($value=='passwd') $sql_set .= ', passwd	=password("'.$_POST['passwd'].'") ';
				else $sql_set .= ', '.$value.' ="'.$_POST[$value]. '"';
			}
		} // end foreach
	} // end if
	////////////////////////////////

	$sql="INSERT INTO {$dbinfo['table']} SET
				rdate	= UNIX_TIMESTAMP()
				$sql_set
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs)
{
	$sql_where = ' 1 '; // init
	
	// $qs 추가, 체크후 값 가져오기
	$qs['uid']	= 'post,trim,notnull=' . urlencode('고유번호가 넘어오지 않았습니다');
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$_POST['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list=db_arrayone($sql) or back('수정할 게시물이 없습니다. 확인 바랍니다.');
	
	// 수정 권한 체크
	if(!privAuth($dbinfo,'priv_modify') ) {
		if($list['bid']>0) {
			if( $list['bid']!=$_SESSION['seUid'] or 'nobid'==substr($dbinfo['priv_modify'],0,5) )
				back('수정하실 권한이 없습니다.');
		}
		else {
			if( $list['passwd']!=$list['pass']) back('정확한 비밀번호를 입력하여 주십시오');
		}
	} // end if

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 
					'uid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate', 'fdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'],$skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) {
				// slist modify
				/*
				case 'bid' : // 변경되면 안되는 필드
				case 'num' :
				case 're' :	continue 2; break; // 다음 foreach 로...
				*/
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])) $qs['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$qs['content']);
					elseif(isset($_POST['content'])) $_POST['content'] = preg_replace("/<br>([^\r\n])/i","<br>\n$1",$_POST['content']);
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!$_POST['docu_type']) $_POST['docu_type']=$dbinfo['default_docu_type'];
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type']!='html') $_POST['docu_type']='text';
					break;
				case 'userid' :
					if($list['bid']==$_SESSION['seUid']) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid']) {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid']==$_SESSION['seUid']) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
				case 'ip' :	$qs['ip'] = remote_addr(); break; // 정확한 IP 주소
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ', '.$value.' ="'.$qs[$value]. '"';
			elseif(isset($_POST[$value])) $sql_set .= ', '.$value.' ="'.$_POST[$value]. '"';
		} // end foreach
	} // end if
	////////////////////////////////

	$sql = "UPDATE {$dbinfo['table']} SET 
				rdate	=UNIX_TIMESTAMP()
				$sql_set
			WHERE 
				uid='{$qs['uid']}'
		";
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
			'uid'		=> 'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd'	=> 'request,trim'
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,password('{$qs['passwd']}') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');

	// 삭제 권한 체크
	if(!privAuth($dbinfo,'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if( 'nobid'==substr($dbinfo['priv_delete'],0,5) ) 
			back('삭제하실 수 없습니다.');
		elseif($list['bid']>0) { // 회원이면
			if($list['bid']!=$_SESSION['seUid'])
				back('삭제하실 수 없습니다.');
		}
		else { // 비회원이면 passwd 검사
			if($list['passwd']!=$list['pass']) {
				if($_SERVER['QUERY_STRING']) 
					back('비밀번호를 입력하여 주십시오','delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where");
	return true;
} // end func delete_ok()

// 추가 입력해야할 필드
function userGetAppendFields($table,$skip_fields='') { // 05/02/03 박선민
	global $SITE;

	if(!is_array($skip_fields) or sizeof($skip_fields)<1)
		$skip_fields = array();
	
	$fieldlist = array();
	$sql = "SHOW COLUMNS FROM $table";
	$rs = db_query($sql);
	if (!$rs) return false; // 쿼리 실패 시

	while ($row = db_array($rs)) {
		$a_fields = $row['Field'];
		
		if(!in_array($a_fields,$skip_fields)) {
			$fieldlist[] = $a_fields;
		}
	}

	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}
?>