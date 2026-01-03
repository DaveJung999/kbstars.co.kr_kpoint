<?php
//=======================================================
// 설	명 : 매입세금계산서 리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/09/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/09/07 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>  1, // 템플릿 사용
	'useBoard2' => 1, // board2CateInfo(), board2Count()
	'useApp' => 1, // cut_string()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$urlprefix	= "taxbuy"; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

// $dbinfo
include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
$dbinfo['table'] = $SITE['th'] . "comptaxbuy";

// 기본 URL QueryString
$qs_basic = "mode=&limitno=&limitrows=";
if(($_GET['getinfo'] ?? null) != "cont") 
	$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
$qs_basic		= href_qs($qs_basic); // 해당값 초기화

// 넘오온값 체크
// - startdate와 enddate가 없다면
if(!isset($_GET['startdate']) || $_GET['startdate'] == ""){
	$_GET['startdate']=date("Y-m-d",time()-3600*24*30); // 한달전
}
$starttime = strtotime($_GET['startdate']);

if(!isset($_GET['enddate']) || $_GET['enddate'] == ""){
	$_GET['enddate']=date("Y-m-d");
}
$endtime = strtotime($_GET['enddate'])+3600*24-1;

if(!isset($_GET['sc_rdate'])) $_GET['sc_rdate'] = 'rdate';
$_GET['sc_from_c_num'] = preg_replace('/[^0-9]/','',($_GET['sc_from_c_num'] ?? ''));
$_GET['sc_to_c_num'] = preg_replace('/[^0-9]/','',($_GET['sc_to_c_num'] ?? ''));

//===================
// SQL문 where절 정리
//===================
$sql_where = " bid='".db_escape($_SESSION['seUid'])."' "; // init
if(isset($_GET['sc_from_c_num']) && $_GET['sc_from_c_num']) $sql_where .= " and from_c_num='".db_escape($_GET['sc_from_c_num'])."' "; 
if(isset($_GET['sc_to_c_num']) && $_GET['sc_to_c_num']) $sql_where .= " and to_c_num='".db_escape($_GET['sc_to_c_num'])."' ";	
// sc_rdate에따라
if( isset($_GET['sc_rdate']) && in_array($_GET['sc_rdate'],array('rdate','rdate_send','rdate_receive','rdate_reject','rdate_resend','rdate_cancle')) ){
	$sql_where .= " and {$_GET['sc_rdate']}>={$starttime} and {$_GET['sc_rdate']} <={$endtime} "; 
}
if( isset($_GET['sc_status']) && in_array($_GET['sc_status'],array('발행', '통지', '승인', '반송', '재통지', '폐기', '등록번호오류')) ) $sql_where .= " and status='".db_escape($_GET['sc_status'])."' ";
if( isset($_GET['sc_type']) && in_array($_GET['sc_type'],array('직접입력','사이트발행')) ) $sql_where .= " and type='".db_escape($_GET['sc_type'])."' ";

//============================ 
// SQL문 order by..부분 만들기
//============================ 
switch($_GET['sort'] ?? ''){
	case 'from_c_num': $sql_orderby = 'from_c_num'; break;
	case '!from_c_num':$sql_orderby = 'from_c_num DESC'; break;
	case 'to_c_num': $sql_orderby = 'to_c_num'; break;
	case '!to_c_num':$sql_orderby = 'to_c_num DESC'; break;
	default : 
		$sql_orderby = (isset($dbinfo['orderby']) ? $dbinfo['orderby'] : ' rdate DESC ');
}

//=====
// misc
//=====
// 페이지 나눔등 각종 카운트 구하기
$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, "count(*)"); // 전체 게시물 수
$page = $_GET['page'] ?? 1;
$dbinfo['pern'] = $dbinfo['pern'] ?? 10;
$dbinfo['page_pern'] = $dbinfo['page_pern'] ?? 5;
$count=board2Count($count['total'],$page,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기

// URL Link...
$href['listdb']	= "{$_SERVER['PHP_SELF']}?db=".db_escape($dbinfo['db']);
$href['list']	= "{$_SERVER['PHP_SELF']}?db=".db_escape($dbinfo['db'])."&cateuid=".db_escape($cateinfo['uid'] ?? '');
if(($count['nowpage'] ?? 1) > 1) { // 처음, 이전 페이지
	$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
	$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . (($count['nowpage'] ?? 1)-1),$qs_basic);
} else {
	$href['firstpage']="javascript: void(0);";
	$href['prevpage']	="javascript: void(0);";
}
if(($count['nowpage'] ?? 1) < ($count['totalpage'] ?? 1)){ // 다음, 마지막 페이지
	$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . (($count['nowpage'] ?? 1)+1),$qs_basic);
	$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".($count['totalpage'] ?? 1),$qs_basic);
} else {
	$href['nextpage']	="javascript: void(0);";
	$href['lastpage'] ="javascript: void(0);";
}
$href['prevblock']= (($count['nowblock'] ?? 1)>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . (($count['firstpage'] ?? 1)-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
$href['nextblock']= (($count['totalpage'] ?? 1) > ($count['lastpage'] ?? 1))? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . (($count['lastpage'] ?? 1) +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭

$href['write']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=write&time=".time(),$qs_basic);	// 글쓰기 

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$limitno	= isset($_GET['limitno']) ? (int)$_GET['limitno'] : ($count['firstno'] ?? 0);
$limitrows	= isset($_GET['limitrows']) ? (int)$_GET['limitrows'] : ($count['pern'] ?? 10);
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);
if(!($total=db_count($rs_list))) {	// 게시물이 하나도 없다면...
	if(isset($_GET['sc_string'])) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. . 
		$tpl->process('LIST', 'nolist');
}
else{
	$last_num = $count['lastnum'] ?? $total + $limitno;
	while($list = db_array($rs_list)){
		$list['no']	= $last_num--;
		$list['rede']	= strlen($list['re'] ?? '');
		$list['rdate_date']= isset($list['rdate']) && $list['rdate'] ? date("y/m/d", $list['rdate']) : "";	//	날짜 변환

		//	Search 단어 색깔 표시
		if(isset($_GET['sc_string']) && isset($_GET['sc_column'])){
			$list[$_GET['sc_column']]	= preg_replace('/'.preg_quote($_GET['sc_string'], '/').'/i', "<font color='darkred'>\\0</font>", $list[$_GET['sc_column']]);
		}

		// 업로드파일 처리
		if(($dbinfo['enable_upload'] ?? '') != 'N' && ($list['upfiles'] ?? '')){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)){
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
			foreach($upfiles as $key =>  $value){
				if($value['name'] ?? null)
					$upfiles[$key]['href']="{$thisUrl}/{$urlprefix}download.php?" . href_qs("uid=".($list['uid'] ?? '')."&upfile=$key",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$href_read		= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=modify&uid=".($list['uid'] ?? ''),$qs_basic);
		$href_download	= "{$thisUrl}/{$urlprefix}download.php?" . href_qs("db=".db_escape($dbinfo['db'])."&uid=".($list['uid'] ?? ''),"uid=");

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href_read);
		$tpl->set_var('href.download'	, $href_download);
		$tpl->set_var('list'			, $list);

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 업로드부분 템플릿내장값 지우기
		if(is_array($list['upfiles'] ?? null)){
			foreach($list['upfiles'] as $key =>  $value){
				if(is_array($list['upfiles'][$key])){
					foreach($list['upfiles'][$key] as $key2 =>  $value)
						$tpl->drop_var("list.upfiles.{$key}.{$key2}");
				}
			}
		} // end if
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href_read);
	$tpl->drop_var('href.download'); unset($href_download);
	if(is_array($list ?? null)){
		foreach($list as $key =>  $value){
			if(is_array($list[$key])){
				foreach($list as $key2 =>  $value) $tpl->drop_var("list.{$key}.{$key2}");
			}
			else $tpl->drop_var("list.{$key}"); 
		}
		unset($list);
	}
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->tie_var('get'				, $_GET); // get값으로 넘어온것들
$tpl->tie_var('dbinfo'			, $dbinfo);// dbinfo 정보 변수
$tpl->tie_var('cateinfo'		, $cateinfo ?? []);
$tpl->tie_var('count'			, $count);	// 게시판 각종 카운트
$tpl->tie_var('href'			, $href);	// 게시판 각종 링크

// 서치 폼의 hidden 필드 모두!!
$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
$form_search .= substr(href_qs('','',1),0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if(($count['nowpage'] ?? 1) > 1){
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
}
else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
// <-- (이전블럭) 부분
if (($count['nowblock'] ?? 1)>1) $tpl->process('PREVBLOCK','prevblock');
else $tpl->process('PREVBLOCK','noprevblock');
// 1 2 3 4 5 부분
for ($i=($count['firstpage'] ?? 1);$i<=($count['lastpage'] ?? 1);$i++) { 
	$tpl->set_var('blockcount',$i);
	if($i == ($count['nowpage'] ?? 1)) 
		$tpl->process('BLOCK','noblock',TPL_APPEND);
	else {
		$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
		$tpl->process('BLOCK','block',TPL_APPEND);
	}	
} // end for
// --> (다음블럭) 부분
if (($count['totalpage'] ?? 1) > ($count['lastpage'] ?? 1)	) $tpl->process('NEXTBLOCK','nextblock');
else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if(($count['nowpage'] ?? 1) < ($count['totalpage'] ?? 1)){
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
}
else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(siteAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>