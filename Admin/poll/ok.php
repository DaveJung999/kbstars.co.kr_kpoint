<?php
//=======================================================
// 설	명 : 설문 종합관리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
$HEADER=array(
	'priv' => 1, // 인증유무 (비회원,회원,운영자,서버관리자)
		'class' =>	'root', // 관리자만 로그인
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useCheck' => 1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
	page_security('', $_SERVER['HTTP_HOST']);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 기본 URL QueryString
	$qs_basic = 'db=' . ($_REQUEST['db'] ?? $table) .			//table 이름
				'&mode=' . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				'&cateuid=' . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				'&team=' . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				'&pern=' . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				'&sc_column=' . ($_REQUEST['sc_column'] ?? '') .	//search column
				'&sc_string='	. urlencode(stripslashes($_REQUEST['sc_string'] ?? '')) . //search string
				'&page=' . ($_REQUEST['page'] ?? '');

	$table_pollinfo=$SITE['th'] . 'pollinfo';	//게시판 관리 테이블
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
	if(!isset($_REQUEST['mode'])) {
		back('잘못된 웹 페이지에 접근하였습니다');
	}

	switch($_REQUEST['mode']){
		case 'write':
			$uid = write_ok($table_pollinfo);
			go_url('./list.php');
			break;
		case 'modify':
			modify_ok($table_pollinfo);
			go_url('./list.php');
			break;
		case 'delete':
			delete_ok($table_pollinfo);
			go_url('./list.php');
			break;
		default :
			back('잘못된 웹 페이지에 접근하였습니다');
	}
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
	function write_ok($table){
		global $SITE, $db_conn;

		##################################################################
		# member 0:모두참여	1이상:지정한 레벨 이상의 로그인회원만 참여
		# sex	0:전체	1:남자	2:여자
		# age	0:전체
		##################################################################

		$qs=array(
				'uid' =>	'post,trim',
				'db' =>	'post,trim,notnull='	. urlencode('생성 db명을 입력바랍니다'),
				'skin' =>	'post,trim',
				'member' =>	'post,trim',
				'sex' =>	'post,trim',
				'age' =>	'post,trim',
				'start_time_y' =>	'post,trim',
				'start_time_m' =>	'post,trim',
				'start_time_d' =>	'post,trim',
				'end_time_y' =>	'post,trim',
				'end_time_m' =>	'post,trim',
				'end_time_d' =>	'post,trim',
				'title' =>	'post,trim,notnull='	. urlencode('설문 제목을 입력바랍니다'),
				'q1' =>	'post,trim,notnull='	. urlencode('설문 내용을 입력바랍니다'),
				'q2' =>	'post,trim',
				'q3' =>	'post,trim',
				'q4' =>	'post,trim',
				'q5' =>	'post,trim',
				'q6' =>	'post,trim',
				'q7' =>	'post,trim',
				'q8' =>	'post,trim',
				'q9' =>	'post,trim',
				'q10' =>	'post,trim',
				'enable_mainpoll' =>	'post,trim'
			);
		$qs=check_value($qs);

		if(preg_match("/^as$|[^a-z0-9_\-]/i", $qs['db'])){
			back('입력한 db명을 영문자로 시작하여 영문자,숫자로만 입력바랍니다');
			exit;
		}
		if(empty($qs['skin'])) $qs['skin'] = 'poll_basic';

		$table_poll = "{$SITE['th']}poll_" . $qs['db'];
		if(db_istable($table_poll)) back('해당 db명으로 이미 설문이 생성되어 있습니다');

		$qs['startdate'] = mktime(0,0,1,$qs['start_time_m'],$qs['start_time_d'],$qs['start_time_y']);
		$qs['enddate']	= mktime(0,0,1,$qs['end_time_m'],$qs['end_time_d'],$qs['end_time_y']);
		$today_time = mktime(0,0,0,date('m'),date('d'),date('Y'));
		if( ($qs['startdate'] > $qs['enddate']) || ($qs['enddate'] < $today_time) )
			back("설문 마감일을 시작일보다 크게하시거나 \\n\\n 설문 마감일을 오늘 날짜 이상으로 조정해 주세요.");

		// 설문 개수 구함
		for($i=1; $i<11; $i++){
			if(empty($qs["q{$i}"])){
				$qs['q_num'] = $i - 1;
				break;
			}
		}

		// $SITE['th']poll_??? 테이블 생성
		if(!userTableCreate('poll',"{$SITE['th']}poll_" . $qs['db'])){
			echo "{$qs['db']} 설문 생성중 실패하였습니다 . 관리자에게 문의 바랍니다";
			exit;
		}

		if(isset($qs['enable_mainpoll'])) db_query("UPDATE {$table} SET enable_mainpoll='0'");

		$sql="INSERT
				INTO
					{$table}
				SET
					`db`				='" . db_escape($qs['db']) . "',
					`skin`				='" . db_escape($qs['skin']) . "',
					`member`			='" . db_escape($qs['member']) . "',
					`sex`				='" . db_escape($qs['sex']) . "',
					`q_num`				='{$qs['q_num']}',
					`startdate`			='{$qs['startdate']}',
					`enddate`			='{$qs['enddate']}',
					`title`				='" . db_escape($qs['title']) . "',
					`q1`				='" . db_escape($qs['q1']) . "',
					`q2`				='" . db_escape($qs['q2']) . "',
					`q3`				='" . db_escape($qs['q3']) . "',
					`q4`				='" . db_escape($qs['q4']) . "',
					`q5`				='" . db_escape($qs['q5']) . "',
					`q6`				='" . db_escape($qs['q6']) . "',
					`q7`				='" . db_escape($qs['q7']) . "',
					`q8`				='" . db_escape($qs['q8']) . "',
					`q9`				='" . db_escape($qs['q9']) . "',
					`q10`				='" . db_escape($qs['q10']) . "',
					`enable_mainpoll`	='" . db_escape($qs['enable_mainpoll']) . "',
					rdate				=UNIX_TIMESTAMP()
			";
		db_query($sql);
		return db_insert_id();
	}

	function modify_ok($table){
		global $SITE, $db_conn;

		###############################################################################
		# member 0:모두참여	1이상:지정한 레벨 이상의 로그인회원만 참여
		# sex	0:전체	1:남자	2:여자
		# age	0:전체
		################################################################################

		$qs=array(
				'uid' =>	'post,trim,notnull='	. urlencode('고유넘버가 넘어오지 않았습니다.'),
				'skin' =>	'post,trim',
				'member' =>	'post,trim',
				'sex' =>	'post,trim',
				'age' =>	'post,trim',
				'start_time_y' =>	'post,trim',
				'start_time_m' =>	'post,trim',
				'start_time_d' =>	'post,trim',
				'end_time_y' =>	'post,trim',
				'end_time_m' =>	'post,trim',
				'end_time_d' =>	'post,trim',
				'title' =>	'post,trim,notnull='	. urlencode('설문 제목을 입력바랍니다'),
				'q1' =>	'post,trim,notnull='	. urlencode('설문 내용을 입력바랍니다'),
				'q2' =>	'post,trim',
				'q3' =>	'post,trim',
				'q4' =>	'post,trim',
				'q5' =>	'post,trim',
				'q6' =>	'post,trim',
				'q7' =>	'post,trim',
				'q8' =>	'post,trim',
				'q9' =>	'post,trim',
				'q10' =>	'post,trim',
				'enable_mainpoll' =>	'post,trim'
			);
		$qs=check_value($qs);

		$qs['startdate'] = mktime(0,0,1,$qs['start_time_m'],$qs['start_time_d'],$qs['start_time_y']);
		$qs['enddate']	= mktime(0,0,1,$qs['end_time_m'],$qs['end_time_d'],$qs['end_time_y']);
		$today_time = mktime(0,0,0,date('m'),date('d'),date('Y'));
		if( ($qs['startdate'] > $qs['enddate']) || ($qs['enddate'] < $today_time) )
			back("설문 마감일을 시작일보다 크게하시거나 \\n\\n 설문 마감일을 오늘 날짜 이상으로 조정해 주세요.");

		// 설문 개수 구함
		for($i=1; $i<11; $i++){
			if(empty($qs["q{$i}"])){
				$qs['q_num'] = $i - 1 ;
				break;
			}
		}

		if(isset($qs['enable_mainpoll'])) db_query("UPDATE {$table} SET enable_mainpoll='0'");

		$sql="UPDATE
					{$table}
				SET
					`member`			='" . db_escape($qs['member']) . "',
					`sex`				='" . db_escape($qs['sex']) . "',
					`q_num`				='{$qs['q_num']}',
					`startdate`			='{$qs['startdate']}',
					`enddate`			='{$qs['enddate']}',
					`title`				='" . db_escape($qs['title']) . "',
					`q1`				='" . db_escape($qs['q1']) . "',
					`q2`				='" . db_escape($qs['q2']) . "',
					`q3`				='" . db_escape($qs['q3']) . "',
					`q4`				='" . db_escape($qs['q4']) . "',
					`q5`				='" . db_escape($qs['q5']) . "',
					`q6`				='" . db_escape($qs['q6']) . "',
					`q7`				='" . db_escape($qs['q7']) . "',
					`q8`				='" . db_escape($qs['q8']) . "',
					`q9`				='" . db_escape($qs['q9']) . "',
					`q10`				='" . db_escape($qs['q10']) . "',
					`enable_mainpoll`	='" . db_escape($qs['enable_mainpoll']) . "',
					rdate				=UNIX_TIMESTAMP()
				WHERE
					`uid`				='".db_escape($qs['uid'])."'
			";
		db_query($sql);
	}

	function delete_ok($table){
		global $SITE, $db_conn;

		$qs=array(
			'uid' =>	'request,trim,notnull='	. urlencode('고유넘버가 넘어오지 않았습니다.'),
		);
		$qs=check_value($qs);
		// 해당 데이터 읽기
		$sql_where	= ' 1 '; // $sql_where 시작
		$sql = "SELECT * FROM {$table} WHERE uid='".db_escape($qs['uid'])."' and  $sql_where ";
		if( !$list=db_arrayone($sql) )
			back('해당 데이터가 없습니다');

		// 권한체크
		if(!privAuth($dbinfo, 'priv_delete')){
			if(isset($list['bid']) && $list['bid'] != ($_SESSION['seUid'] ?? null)) back('삭제 권한이 없습니다');
		}

		db_query("DELETE FROM {$table} WHERE uid='".db_escape($qs['uid'])."' AND  $sql_where ");
		// 설문 응답 테이블도 삭제
		db_query("DROP TABLE IF EXISTS {$SITE['th']}poll_".db_escape($list['db']));

		return db_count();
	}
// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
// 03/08/25
	function userTableCreate($table,$createtable){
		global $SITE, $db_conn;

		$rs=db_query('SELECT sql_syntax FROM ' . $SITE['th'] . 'admin_tableinfo WHERE table_name='. db_escape($table));
		if(db_count($rs)){
			$sql='CREATE TABLE ' . $createtable . ' ('	. db_result($rs,0,'sql_syntax') . ')';
			if(db_query($sql))
				return 1;
			else // 아마 해당 데이터베이스가 존재할 경우겠지. . 생성하다가 실패했으니..
				return -1; // -1로 리턴함..
		} else {
			return 0;
		}
	} // end func
?>