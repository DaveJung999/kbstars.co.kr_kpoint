<?php
//=======================================================
// 설  명 : 포인트 카드번호 관리 페이지
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/11/19 박선민 카드번호개설시 3000포인트 자동 적립
// 03/11/19 박선민 이체,충전 버튼 없앰
// 03/12/03 박선민 신규카드번호개설은 firstaccount.php에서
//=======================================================
$HEADER=array(
//	'private'	=>1, // 브라우저 캐쉬
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'useApp'	=>1, // cut_string()
	'useBoard2'	=>1, // board2Count(),board2CateInfo()
	'usedb2'		=>1, // DB 커넥션 사용
	'useCheck'	=>1, // cut_string()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 기본 URL QueryString
	$qs_basic = "db=$db".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid=$cateuid".		//cateuid
				"&pern=$pern" .	// 페이지당 표시될 게시물 수
				"&sc_column=$sc_column".	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
				"&bid=$bid".
				"&s_id=$s_id".
				"&cur_sid=$cur_sid".
				"&page=$page".
				"&sdate=$sdate".
				"&edate=$edate".
				"&search=$search".
				"&pay_cate=$pay_cate".
				"&term_id=$term_id"
				;				//현재 페이지
				
	include_once("$thisPath/dbinfo.php");	// $dbinfo 가져오기

	// 인증 체크
	if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.");
	


	
	if (!$sql_where) $sql_where = " 1 ";
	// 서치 게시물만..
	if(trim($act)) {
		if(trim($sdate) AND trim($edate)) {
			$ssdate = strtotime($sdate);
			$eedate = strtotime(date('Y-m-d', strtotime($edate)+(86400*1))) ; 
			//$eedate = strtotime($edate);
			if($ssdate > $eedate) back("기간이 잘못설정했습니다");		
				$sql_where .= " and (B.rdate >= $ssdate) AND (B.rdate <= $eedate ) ";
		}

		if(trim($name)) 	$sql_where .=" and (A.name like '%$name%')";
		if(trim($accountno)) 	$sql_where .=" and (A.accountno like '%$accountno%')";
	}
	
	$sql_where .= $sql_where ? " and  B.s_id = {$_GET['s_id']} " : " B.s_id = {$_GET['s_id']} ";

	// 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) 
									FROM {$dbinfo['table_logon']} as A LEFT JOIN {$dbinfo['table_kpointinfo']} as B ON B.bid=A.uid 
									WHERE $sql_where LIMIT 1", 0, 'count(*)'); // 전체 게시물 수
	$count=board2Count($count['total'],$_GET['page'],$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) 
									FROM {$dbinfo['table_logon']} as A LEFT JOIN {$dbinfo['table_kpointinfo']} as B ON B.bid=A.uid 
									WHERE (B.rdate > unix_timestamp(curdate())) 
										and $sql_where LIMIT 1", 0, 'count(*)');

	$limitno	= $_GET['limitno'] ? $_GET['limitno'] : $count['firstno'];
	$limitrows	= $_GET['limitrows'] ? $_GET['limitrows'] : $count['pern'];

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']="#";
		$href['prevpage']	="#";
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	="#";
		$href['lastpage'] ="#";
	}
	$href['prevblock']= ($count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=".($count['firstpage']-1) ,$qs_basic): "#";// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=".($count['lastpage'] +1),$qs_basic) : "#";// 다음 페이지 블럭

	//===========================
	// 8. SQL문 order by..절 정리
	//===========================
	if($_GET['sorderby']) {
		$sql_orderby = $_GET['sorderby'];
		if ($_GET['sorderby'] == 'balance') $sql_orderby = "B.".$sql_orderby . " desc, A.name ";
	}
	if (!$sql_orderby) $sql_orderby = $dbinfo['orderby'] ? $dbinfo['orderby'] : ' A.name ';


// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$_GET['limitno'] = $_GET['limitno'] ? $_GET['limitno'] : $count['firstno'];
$_GET['limitrows'] = $_GET['limitrows'] ? $_GET['limitrows'] : $count['pern'];
// 회원 Account 정보를 모두 가져옴
$sql = "SELECT A.uid, B.accountno, A.name, B.rdate, B.balance 
		from {$dbinfo['table_logon']} as A LEFT JOIN $dbinfo['table_kpointinfo'] as B ON B.bid=A.uid 
		WHERE $sql_where 
		ORDER BY $sql_orderby";
$rs_logon=db_query($sql);
while($list=db_array($rs_logon)) {
	
	$list['no']	= $count['lastnum']--;
	$list['rdate_date'] = date("Y/m/d", $list['rdate']);
	
	if (!$list['balance']) $list['balance'] = 0;
	
	$tpl->set_var('list',$list);
	$tpl->set_var('accountinfo',$accountinfo);
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
} // end while

$tpl->set_var('sort_'.$_GET['sorderby'], " selected");	// sort_???
$tpl->set_var('sdate'			,$sdate);	// 게시판 각종 링크
$tpl->set_var('edate'			,$edate);	// 게시판 각종 링크
$tpl->set_var('name'			,$name);	// 게시판 각종 링크
$tpl->set_var('accountno'			,$accountno);	// 게시판 각종 링크
$tpl->set_var('sselect', $sselect);

$tpl->set_var('s_id', $_GET['s_id']);


// 마무리
header ("Expires: Mon, 26 Jul 2007 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
header ("Cache-Control: cache, must-revalidate");	
header ("Pragma: cache");	
header ('Content-type: application/x-msexcel');
header ("Content-Disposition: attachment; filename=" . gmdate("ymdHis") . "_allmember_point.xls" ); 
header ("Content-Description: PHP/INTERBASE Generated Data" );
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
