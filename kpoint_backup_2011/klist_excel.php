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
				"&mid=$mid".
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
	

	$table_kmember	= $SITE['th'] . "kmember";
	$table_kpoint		= $SITE['th'] . "kpoint";
	$table_kpointinfo	= $SITE['th'] . "kpointinfo";
	
	if (!$sql_where) $sql_where = " 1 ";
	// 서치 게시물만..
	if(trim($act)) {
		if(trim($sdate) AND trim($edate)) {
			$ssdate = strtotime($sdate);
			$eedate = strtotime(date('Y-m-d', strtotime($edate)+(86400*1))) ; 
			//$eedate = strtotime($edate);
			if($ssdate > $eedate) back("기간이 잘못설정했습니다");		
				$sql_where .= " and (rdate >= $ssdate) AND (rdate <= $eedate ) ";
		}

		if(trim($name)) 	$sql_where .=" and (name like '%$name%')";
		if(trim($accountno)) 	$sql_where .=" and (accountno like '%$accountno%')";
	}
	// 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM $table_kmember	WHERE $sql_where LIMIT 1", 0, 'count(*)'); // 전체 게시물 수
	$count=board2Count($count['total'],$_GET['page'],$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM $table_kmember	WHERE (rdate > unix_timestamp(curdate())) and $sql_where LIMIT 1", 0, 'count(*)');

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
		if ($_GET['sorderby'] == 'balance') $sql_orderby = $sql_orderby . " desc ";
	}
	if (!$sql_orderby) $sql_orderby = $dbinfo['orderby'] ? $dbinfo['orderby'] : ' name ';


	//시즌정보
	$sql = " SELECT *, sid as s_id FROM savers_secret.season ORDER BY s_start DESC ";
	$rs = db_query($sql);
	$cnt = db_count($rs);
	
	if($cnt)	{
		for($i = 0 ; $i < $cnt ; $i++)	{
			$list_s = db_array($rs);
			//최신 시즌
			if ($i == 0 && !$_GET['s_id']) {
				$_GET['s_id'] = $list_s['s_id'];
				$kpoint['s_name'] = $list_s['s_name'];
			}
			
			if($_GET['s_id'] == $list_s['s_id'])
				$sselect .= "<option value={$list_s['s_id']} selected>{$list_s['s_name']}</option>";
			else
				$sselect .= "<option value={$list_s['s_id']}>{$list_s['s_name']}</option>";
		}		
	}	


// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$_GET['limitno'] = $_GET['limitno'] ? $_GET['limitno'] : $count['firstno'];
$_GET['limitrows'] = $_GET['limitrows'] ? $_GET['limitrows'] : $count['pern'];
// 회원 Account 정보를 모두 가져옴
$sql = "SELECT * FROM $table_kmember	WHERE $sql_where ORDER BY $sql_orderby";
$rs_member=db_query($sql);
while($list=db_array($rs_member)) {
	
	$list['no']	= $count['lastnum']--;
	$list['rdate_date'] = date("Y/m/d", $list['rdate']);
	
	$href['inquiry'] = "kread.php?". href_qs('mode=inquiry&mid='.$list['mid']."&accountno=".$list['accountno']."&s_id=".$_GET['s_id'],$qs_basic);
	$href['member'] = "kmember.php?". href_qs('mode=modify&mid='.$list['mid']."&accountno=".$list['accountno']."&s_id=".$_GET['s_id'],$qs_basic);

	$sql_kpointinfo = "SELECT balance FROM $table_kpointinfo WHERE bid={$list['mid']} and s_id = {$_GET['s_id']} ORDER BY uid";
	$list['balance'] = db_resultone($sql_kpointinfo,0,"balance");
	if (!$list['balance']) $list['balance'] = 0;
	
	$tpl->set_var('href.inquiry',$href['inquiry']);
	$tpl->set_var('href.member',$href['member']);
	$tpl->set_var('list',$list);
	$tpl->set_var('accountinfo',$accountinfo);
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
} // end while

$href['win']="kwin.php?mode=win&s_id=$_GET['s_id']";
$href['windelete']="kwin.php?mode=windelete&s_id=$_GET['s_id']";

$tpl->set_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('href.win'			,$href['win']);	// 게시판 각종 링크
$tpl->set_var('href.windelete'			,$href['windelete']);	// 게시판 각종 링크
$tpl->set_var('sort_'.$_GET['sorderby'], " selected");	// sort_???
$tpl->set_var('sdate'			,$sdate);	// 게시판 각종 링크
$tpl->set_var('edate'			,$edate);	// 게시판 각종 링크
$tpl->set_var('name'			,$name);	// 게시판 각종 링크
$tpl->set_var('accountno'			,$accountno);	// 게시판 각종 링크
$tpl->set_var('sselect', $sselect);
$tpl->set_var('s_id', $_GET['s_id']);


// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1) {
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
}
else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
	// <-- (이전블럭) 부분
	if ($count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) { 
		$tpl->set_var('blockcount',$i);
		if($i==$count['nowpage']) 
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', $thisUrl.'klist.php?'.href_qs('page='.$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	} // end for
	// --> (다음블럭) 부분
	if ($count['totalpage'] > $count['lastpage']  ) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if($count['nowpage'] < $count['totalpage']) {
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
}
else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}



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
