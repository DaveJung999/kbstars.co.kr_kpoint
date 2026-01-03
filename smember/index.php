<?php
//=======================================================
// 설	명 : MyPage(/smember/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/11/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/03 박선민 마지막 수정
// 2025/09/11 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
	$HEADER=array(
		'priv' => '회원,비회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용
		'useSkin' => 1, // 템플릿 사용
	);
	require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
	$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$dbinfo	= array(
		'skin' => 'dojaki',
		'html_type' => 'ht', 
		'html_skin' => '2015_d12'
	);

	$table_logon	= $SITE['th'] . "logon";
	$table_lastlog	= $SITE['th'] . "log_lastlog";

	$sql = "SELECT * FROM {$table_logon} WHERE uid='".db_escape($_SESSION['seUid'])."' and userid='".db_escape($_SESSION['seUserid'])."'";
	$logon		= db_arrayone($sql) or back("회원님의 회원 정보를 읽지 못하였습니다.\\n로그아웃되며 다시 로그인하여 이용하여주시기 바랍니다.\\n\\n계속 문제 발생시 종합질문페이지에 문의 바랍니다.","/sjoin/logout.php");

	// 마지막로그인정보가져오기
	$sql = "select rdate from {$table_lastlog} where bid='".db_escape($logon['uid'])."' and gid=0";
	$lastlogin = db_resultone($sql,0,"rdate");
	$logon['lastlogin'] = $lastlogin ? date("Y-m-d g:ia",$lastlogin) : "미접속";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

	$tpl->set_var("logon"				,$logon);
	$tpl->set_var("session"				,$_SESSION);
	$tpl->set_var("href"				,$href);

	//주문통계
	$status = array('입금필요' => 0,'입금완료' => 0,'배송요청' => 0,'재고준비' => 0,'배송준비' => 0,'배송중' => 0,'OK' => 0,'삭제접수' => 0,'반품교환' => 0);
	$start_rdate = mktime(0,0,0,date('m'),date('d')-7,date('Y'));
	$sql = "select status, count(*) as count from {$SITE['th']}payment where bid='".db_escape($_SESSION['seUid'])."' and re='' and rdate>{$start_rdate} group by status";
	$rs=db_query($sql);
	while($list=db_array($rs)){
		$status[$list['status']] = $list['count'];
	}
	$tpl->set_var('status',$status);

	board_qna($tpl,'cmqna');

// 마무리
	$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

	function board_qna(&$tpl,$db){
		global $SITE, $GAMEINFO, $PlayerCateBoard, $DEBUG; // global 변수 추.
		
		$list_config = array(
			'db' => $db,
			'limitno' => 0,
			'limitrows' => 5,
			'sql_where' => " type='docu' and re='' and bid='".db_escape($_SESSION['seUid'])."' ",
			'sql_order' => " num DESC ",
			'cut_string' => 30
		);
		$list_config['table']	= "new21_board2_".$list_config['db'];

		$sql = "SELECT * FROM {$list_config['table']}
			WHERE {$list_config['sql_where']}
			ORDER BY {$list_config['sql_order']}
			LIMIT {$list_config['limitno']}, {$list_config['limitrows']}";
		$rs_list = db_query($sql);
		if(db_count($rs_list)){
			while($list = db_array($rs_list)){
				$list['rdate_date'] = date("y/m/d", $list['rdate']);	//	날짜 변환
				if(!$list['title']) $list['title'] = "제목없음…";
				$list['cut_title'] = cut_string($list['title'], $list_config['cut_string']);

				//	답변 게시물 답변 아이콘 표시
				if($list['rede'] > 0){
					$list['cut_title'] = "<img src='/scommon/spacer.gif' width='" . (($list['rede']-1)*8)	. "' border=0><img src='/scommon/re.gif' align='absmiddle' border=0> {$list['cut_title']}";
				}
				// URL Link...
				$list['href']['read']= "/sboard2/read.php?db=".db_escape($list_config['db'])."&uid={$list['uid']}";
				
				// 답변여부
				$sql = "select uid from {$list_config['table']}_memo where pid='".db_escape($list['uid'])."' LIMIT 1";
				if(db_resultone($sql)) $list['status']='답변완료';
				else $list['status'] = '처리중';
				
				
				// 템플릿하당
				$tpl->set_var('list',$list);
				$tpl->process(strtoupper($db),$db,TPL_OPTIONAL|TPL_APPEND);
				$tpl->drop_var('list',$list);			
			} // end while		
		} // end if
	} 

?>
