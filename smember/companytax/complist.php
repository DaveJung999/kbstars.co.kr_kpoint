<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/21
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/08/21 박선민 마지막 수정
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
	$urlprefix	= "comp"; // ???list.php ???write.ephp ???ok.php
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// $dbinfo
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	$dbinfo['table'] = $SITE['th'] . "companyinfo";

	// 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if($_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	//===================
	// SQL문 where절 정리
	//===================
	$sql_where = " bid='{$_SESSION['seUid']}' "; // init
	// 서치 게시물만..
	if(trim($_GET['sc_string'])){
		if($sql_where) $sql_where .= ' and ';
		if($_GET['sc_column']) 
			if(in_array($_GET['sc_column'],array('bid','uid'))) // 일치해야 되는 필드
				$sql_where .=" ({$_GET['sc_column']}='{$_GET['sc_string']}') ";
			else
				$sql_where .=" ({$_GET['sc_column']} like '%{$_GET['sc_string']}%') ";
		else 
			$sql_where .=" ((userid like '%{$_GET['sc_string']}%') or (title like '%{$_GET['sc_string']}%') or (content like '%{$_GET['sc_string']}%')) ";
	}
	if(!$sql_where) $sql_where= " 1 ";

	//============================ 
	// SQL문 order by..부분 만들기
	//============================ 
	switch($_GET['sort']){
		case 'from_c_num': $sql_orderby = 'from_c_num'; break;
		case '!from_c_num':$sql_orderby = 'from_c_num DESC'; break;
		case 'to_c_num': $sql_orderby = 'to_c_num'; break;
		case '!to_c_num':$sql_orderby = 'to_c_num DESC'; break;
		default : 
			$sql_orderby = $dbinfo['orderby'] ? $dbinfo['orderby'] : ' rdate DESC ';
	}

	//=====
	// misc
	//=====
	// 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, "count(*)"); // 전체 게시물 수
	$count=board2Count($count['total'],$page,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기

	// URL Link...
	$href['listdb']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}";
	$href['list']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}&cateuid={$cateinfo['uid']}";
	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']="javascript: void(0);";
		$href['prevpage']	="javascript: void(0);";
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
	} else {
		$href['nextpage']	="javascript: void(0);";
		$href['lastpage'] ="javascript: void(0);";
	}
	$href['prevblock']= ($count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭

	$href['write']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=write&time=".time(),$qs_basic);	// 글쓰기 

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$limitno	= $_GET['limitno'] ? $_GET['limitno'] : $count['firstno'];
$limitrows	= $_GET['limitrows'] ? $_GET['limitrows'] : $count['pern'];
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);
if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($_GET['sc_string']) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. . 
		$tpl->process('LIST', 'nolist');
}
else{
	for($i=0; $i<$total; $i++){
		$list		= db_array($rs_list);
		$list['no']	= $count['lastnum']--;
		$list['rede']	= strlen($list['re']);
		$list['rdate_date']= $list['rdate'] ? date("y/m/d", $list['rdate']) : "";	//	날짜 변환

		//	Search 단어 색깔 표시
		if($_GET['sc_string'] and $_GET['sc_column']){
			$list[$_GET['sc_column']]	= eregi_replace($_GET['sc_string'], "<font color='darkred'>\\0</font>", $list[$_GET['sc_column']]);
		}

		// 업로드파일 처리
		if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)){
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
			foreach($upfiles as $key =>  $value){
				if($value['name'])
					$upfiles[$key]['href']="{$thisUrl}/{$urlprefix}download.php?" . href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$href['read']		= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=modify&uid={$list['uid']}",$qs_basic);
		$href['download']	= "{$thisUrl}/{$urlprefix}download.php?" . href_qs("db={$dbinfo['db']}&uid={$list['uid']}","uid=");

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('href.download'	, $href['download']);
		$tpl->set_var('list'			, $list);

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 업로드부분 템플릿내장값 지우기
		if(is_array($list['upfiles'])){
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
	$tpl->drop_var('href.read'); unset($href['read']);
	$tpl->drop_var('href.download'); unset($href['download']);
	if(is_array($list)){
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
$tpl->set_var('dbinfo'			,$dbinfo);// dbinfo 정보 변수
$tpl->set_var('cateinfo'		,$cateinfo);
$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
$tpl->set_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sc_string'		,htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));	// 서치 단어
// 서치 폼의 hidden 필드 모두!!
$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
$form_search .= href_qs("",$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1){
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
		if($i == $count['nowpage']) 
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	} // end for
	// --> (다음블럭) 부분
	if ($count['totalpage'] > $count['lastpage']	) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if($count['nowpage'] < $count['totalpage']){
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
