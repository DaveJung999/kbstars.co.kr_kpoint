<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useBoard2'	=>1, // board2Count()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

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
	//include_once($thisPath.'config.php');

	// 4. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5. SQL문 where절 정리
	//======================
	$sql_where = ''; // init
	//page_code에 따른 sql 문 
	switch($_GET['page_code']){
		case "year_member":	//년도별 통계
			$year_list = array();
			$month_list = array();
			$rs = db_query("SELECT	from_unixtime(rdate,'%Y') as year, from_unixtime(rdate,'%m') as month , count(userid) as count FROM {$SITE['th']}logon WHERE priv <>'비회원'	and rdate > 0 GROUP BY year , month ");
			$total = db_count();
			for($i =0 ; $i < $total ; $i++){
				$list = db_array($rs);
					$statistics_list[$list['year']][$list['month']] = $list['count'];
					$year_list[] = $list['year'];
			}
			$year_list = array_unique($year_list);
			$x_bar_count = count($year_list);
			$y_bar_count = 12;
			$total =	$x_bar_count * $y_bar_count;
	
		break;


	
	}
	
	
	if(!$sql_where) $sql_where= ' 1 '; // 값이 없다면
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile='list.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);
echo "<pre>";
print_r($statistics_list);
echo "</pre>";
print_r($year_list);
if(!$total) {	// 게시물이 하나도 없다면...
	if($_GET['sc_string']) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면.. 
		$tpl->process('LIST', 'nolist');
}
else{
	for($i = 0 ; $i < $x_bar_count ; $i ++){
		$x_bar_list['year'] = $year_list[$i];
		$tpl->set_var('x_bar_list'			, $x_bar_list);
		$tpl->set_var('blockloop',true);
		$tpl->process('X_BAR_LIST','x_bar_list',TPL_OPTIONAL|TPL_APPEND);
	}
print_r($x_bar_list);
	if($x_bar_count<1 or !$tpl->get_var('cell')) $x_bar_count=1; // 스킨에 cell 블럭이 없으면, row_pern을 1로 바꿈
	for($i=0; $i < $y_bar_count ; $i++) {
	 	if($y_bar_count > 1) {
			$tpl->drop_var('blockloop');
			$tpl->drop_var('CELL');
		}
		$y_bar_list['month'] = $i + 1;
		$y_bar_list['month'] = str_pad($y_bar_list['month'] ,2, "0", STR_PAD_LEFT);
		$tpl->set_var('y_bar_list'			, $y_bar_list);
		$list = array();
		for($j=0; $j	< $x_bar_count ; $j++) { // 한줄에 여러값 출력시 루틴
			if( $j>=$total ) {
			 	if($y_bar_count > 1) $tpl->process('CELL','nocell',TPL_APPEND);
				continue;
			}
			$list['member_count'] = $statistics_list[$year_list[$j]][$y_bar_list['month']];		
			$list['member_count'] = $list['member_count']?$list['member_count']:0;

			$tpl->set_var('list'			, $list);
			if($y_bar_count > 1) {
				$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
		} // end for (j)
		if($i==0) $tpl->drop_var('blockloop');
		else $tpl->set_var('blockloop',true);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('list',$list);
} // end if (게시물이 있다면...)
// 템플릿 마무리 할당
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수

// 서치 폼의 hidden 필드 모두!!


// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
