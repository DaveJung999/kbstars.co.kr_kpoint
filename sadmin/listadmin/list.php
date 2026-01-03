<?php
//=======================================================
// 설	명 : 관리자 리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
//
// 25/08/12 Gemini (PHP 7, MariaDB 11 호환성 개선)
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// $table
	$table_logon 	= $SITE['th'] . 'logon';
	$table_log_lastlog=$SITE['th'] . 'log_lastlog';
	
	$dbinfo = array (
				'title'	 =>	'운영자 전체 리스트',
				'skin'	 =>	'basic'
			);
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// SQL문 완성
$sql = "SELECT {$table_logon}.*, {$table_log_lastlog}.rdate as lastlog FROM {$table_logon}
		left join {$table_log_lastlog} on {$table_logon}.uid={$table_log_lastlog}.bid
									and {$table_log_lastlog}.gid=0
	WHERE find_in_set('운영자',{$table_logon}.priv) or find_in_set('root',{$table_logon}.priv)";
$rs_list = db_query($sql);

if(!db_count($rs_list)) {	// 게시물이 하나도 없다면...
	$tpl->process('LIST', 'nolist');
} else {
	while($list = db_array($rs_list)) {
		$list['lastlog_date'] = date('y-m-d [H:i:s]', $list['lastlog']);
		$list['rdate_date'] = date('y-m-d', $list['rdate']);
		
		// 템플릿 할당
		$tpl->set_var('list', $list);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		$tpl->set_var('blockloop',true);
	} // end while
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			, $dbinfo);// info 정보 변수

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('/([="\'])images\//', $val, $tpl->process('', 'html',TPL_OPTIONAL));
?>
