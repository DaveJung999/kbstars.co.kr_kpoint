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

/*	$dbinfo['table_kpoint']		= $SITE['th'] . "kpoint";
	$dbinfo['table_kpointinfo']	= $SITE['th'] . "kpointinfo";
	$dbinfo['table_kpresent']		= $SITE['th'] . "kpoint_present";*/

if (!$_GET['bid']) 
	back("고유번호가 넘어오지 않았습니다. 시즌정보를 등록 하신 후 다시 시도 해 주세요.");

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 회원 정보 가져옴
$sql_where_logon = " uid = {$_GET['bid']} ";
$sql_logon = "SELECT * 
				 FROM {$dbinfo['table_logon']}	
				WHERE $sql_where_logon ";
$list_logon=db_arrayone($sql_logon);

// 회원 Account 정보를 모두 가져옴
$sql_kpointinfo = "SELECT * 
					from {$dbinfo['table_kpointinfo']} 
					WHERE bid=$_GET['bid'] 
					ORDER BY uid";
$rs_kpointinfo=db_query($sql_kpointinfo);
$total = db_count($rs_kpointinfo);
if ($total > 0) {
	while($list=db_array($rs_kpointinfo)) {
		$kpointtype[]=$list['accounttype'];
		
		$list['state']= $list['errorno'] ? "에러발생" : "정상";
		// 숫자에 콤모(,) 붙이기
		$list['balance']=number_format($list['balance']);
	
		// URL link..
		$href['inquiry']="{$_SERVER['PHP_SELF']}?mode=inquiry&accountno={$list['accountno']}&bid={$list['bid']}&s_id={$list['s_id']}";
		$href['season']="kseason.php?accountno={$list['accountno']}&bid={$list['bid']}&s_id={$list['s_id']}&name={$list['name']}";
		$href['s_modify']="kseason.php?mode=s_modify&accountno={$list['accountno']}&bid={$list['uid']}&bid={$list['bid']}&s_id={$list['s_id']}&name={$list['name']}";
		$href['s_delete']="kok.php?mode=s_delete&accountno={$list['accountno']}&bid={$list['uid']}&bid={$_GET['bid']}&s_id={$list['s_id']}";
		
		if ($list['s_id'] == $_GET['s_id']){
			$kpointinfo=$list;
			$kpointinfo['state'] = $kpointinfo['state']=="정상" ? $kpointinfo['state'] : $kpointinfo['state'] . $kpointinfo['errornotice'];
			$kpointinfo['comment'] = nl2br($kpointinfo['comment']);
			$kpointinfo['rdate'] =date("Y-m-d",$kpointinfo['rdate']);
		}
		
		$tpl->set_var('href.inquiry',$href['inquiry']);
		$tpl->set_var('href.season',$href['season']);
		$tpl->set_var('href.s_modify',$href['s_modify']);
		$tpl->set_var('href.s_delete',$href['s_delete']);
	
		$tpl->set_var('list',$list);
		$tpl->set_var('kpointinfo',$kpointinfo);
		$tpl->set_var('list_logon',$list_logon);
		
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		$mode="inquiry";
	} // end while
}else
		$tpl->process('LIST','nolist',TPL_OPTIONAL|TPL_APPEND);
/*
카드번호 내역 조회
*/
//if($mode=="inquiry") {
	if(is_array($kpointinfo)) {
		
		// 경품 지급
		$sql_present = "select * 
						FROM {$dbinfo['table_kpresent']} 
						where bid=$_GET['bid'] and s_id = $_GET['s_id']  
						order by pdate desc";
		$rs_present=db_query($sql_present);
		$total = db_count($rs_present);
		if(!$total){
			$tpl->process('KPRESENT','nokpresent');
		}else{
			while($kpresent_list=db_array($rs_present)) {
				$kpresent_list['pdate']=date("Y-m-d",$kpresent_list['pdate']);
				
				// 숫자에 콤모(,) 붙이기
				$kpresent_list['point']=number_format($kpresent_list['point'],0,"",",");
	
				$href['p_modify']="kpresent.php?mode=p_modify&bid={$kpresent_list['uid']}&bid={$kpresent_list['bid']}&s_id={$kpresent_list['s_id']}";
				$href['p_delete']="kok.php?mode=p_delete&bid={$kpresent_list['uid']}&bid={$kpresent_list['bid']}&s_id={$kpresent_list['s_id']}";
	
				$tpl->set_var('href.p_modify',$href['p_modify']);
				$tpl->set_var('href.p_delete',$href['p_delete']);
				$tpl->set_var('kpresent_list',$kpresent_list);
	
				$tpl->process('KPRESENT','kpresent',TPL_OPTIONAL|TPL_APPEND);
	
			}
		}
		$href['p_write']="kpresent.php?mode=p_write&bid={$kpointinfo['bid']}&s_id={$kpointinfo['s_id']}&point={$kpointinfo['balance']}";
		
		// 포인트 적립 내역
		$sql_account = "select * 
						from {$dbinfo['table_kpoint']} 
						where bid={$_GET['bid']} 
						  and accountno={$kpointinfo['accountno']} 
						  and s_id = {$_GET['s_id']}  
						order by rdate desc";
		$rs_account=db_query($sql_account);
		$total = db_count($rs_account);
		if(!$total){
			$tpl->process('KPOINT_LIST','nokpoint_list');
		}else{
			while($kpoint_list=db_array($rs_account)) {
				$kpoint_list['rdate']=date("Y-m-d",$kpoint_list['rdate']);
				
				// 숫자에 콤모(,) 붙이기
				$kpoint_list['deposit']=number_format($kpoint_list['deposit'],0,"",",");
				$kpoint_list['withdrawal']=number_format($kpoint_list['withdrawal'],0,"",",");
				$kpoint_list['balance']=number_format($kpoint_list['balance'],0,"",",");
	
				$href['modify']="kpoint.php?mode=modify&accountno={$kpoint_list['accountno']}&pid={$kpoint_list['pid']}&bid={$_GET['bid']}&s_id={$kpoint_list['s_id']}";
				$href['delete']="kok.php?mode=kpointdelete&accountno={$kpoint_list['accountno']}&pid={$kpoint_list['pid']}&bid={$_GET['bid']}&s_id={$kpoint_list['s_id']}";
	
				$tpl->set_var('href.modify',$href['modify']);
				$tpl->set_var('href.delete',$href['delete']);
				$tpl->set_var('kpoint_list',$kpoint_list);
	
				$tpl->process('KPOINT_LIST','kpoint_list',TPL_OPTIONAL|TPL_APPEND);
	
			}
		}
	
		$href['write']="kpoint.php?mode=write&accountno={$kpointinfo['accountno']}&bid={$kpointinfo['bid']}&s_id={$kpointinfo['s_id']}";
		$href['win']="kwin.php?mode=win&s_id={$kpointinfo['s_id']}";
		$href['windelete']="kwin.php?mode=windelete&s_id={$kpointinfo['s_id']}";
		//form 내용 입략하기
		$form_inquiry = " action={$_SERVER['PHP_SELF']} method='get'>
							<input type='hidden' name='uid' value={$_GET['bid']}>
							<input type='hidden' name='accountno' value='$kpointno'>
							<input type='hidden' name='mode' value='inquiry'";
	/*	$from_year = $from_year ? $from_year : date(Y);
		$from_month = $from_month ? $from_month : date(m)-1;
		$from_day = $from_day ? $from_day : date(d);
		$to_year = $to_year ? $to_year : date(Y);
		$to_month = $to_month ? $to_month : date(m);
		$to_day = $to_day ? $to_day : date(d);*/
	
		$tpl->set_var('href.write'		,$href['write']);	
		$tpl->set_var('href.win'		,$href['win']);	
		$tpl->set_var('href.windelete'		,$href['windelete']);	
		$tpl->set_var('href.p_write'		,$href['p_write']);	
		
		$tpl->set_var('form_inquiry'		,$form_inquiry);	
		$tpl->process('INQUIRY','inquiry');
	}else
		$tpl->process('INQUIRY','noinquiry');


//} // end if($mode=="inquiry")

// 마무리
header ("Expires: Mon, 26 Jul 2007 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
header ("Cache-Control: cache, must-revalidate");	
header ("Pragma: cache");	
header ('Content-type: application/x-msexcel');
header ("Content-Disposition: attachment; filename=" . $kpointinfo['name'] . "_" . gmdate("ymdHis") . "_personal_point.xls" ); 
header ("Content-Description: PHP/INTERBASE Generated Data" );
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

?>
