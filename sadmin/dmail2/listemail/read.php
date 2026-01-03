<?php
//=======================================================
// 설	명 : 심플리스트읽기(read.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/11/27 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 1 . 넘어온값 체크

	// 2 . 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if($_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	//==================== 
	// 4 . 해당 게시물 읽음
	//==================== 
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$_GET['uid']}' ";
	$list=db_arrayone($sql) or back("데이터가 없습니다.");

	//==================== 
	// 5 . 해당 게시물 처리
	//==================== 
	// 인증 체크(자기 글이면 무조건 보기)
	if(!siteAuth($dbinfo, "priv_read",1)){
		if($list['bid']){
			if($list['bid'] != $_SESSION['seUid'])
				back("이용이 제한되었습니다.(레벨부족)");
		}
		else back("이용이 제한되었습니다.(레벨부족)");
	} // end if
	
	$list['rdate_date']	= date("Y/m/d", $list['rdate']);
	$list['title']		= htmlspecialchars($list['title'],ENT_QUOTES);
	$list['content']		= replace_string($list['content'], $list['docu_type']);	// 문서 형식에 맞추어서 내용 변경

	// 업로드파일 처리
	if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles))	{ 
			// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$list['upfiles'];
			$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
		}

		$thumbimagesize=explode("x",$dbinfo['imagesize_read']);
		if((int)$thumbimagesize['0'] == 0)	$thumbimagesize['0']=300;
		//if((int)$thumbimagesize['1'] == 0)	$thumbimagesize['1']=300; // height는 설정않함

		foreach($upfiles as $key =>	$value){
			if($value['name']){
				// $filename구함(절대디렉토리포함)
				$filename=$dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name'];
				if( !is_file($filename) ){
					// 한단계 위에 파일이 있다면 그것으로..
					$filename=$dbinfo['upload_dir'] . "/" . $value['name'];
					if( !is_file($filename) ){
						unset($upfiles[$key]);
						continue;
					} // end if
				} // end if

				$upfiles[$key]['href']="{$thisUrl}/{$urlprefix}download.php?" . href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
				
				// 이미지 파일이면
				// $upfiles[$key][imagesize]를 width="xxx"(height는 설정 않함)로 저장
				if( is_array($tmp_imagesize=@getimagesize($filename)) ){
					$upfiles[$key][imagesize] = " width='" . (($tmp_imagesize['0'] > $thumbimagesize['0']) ? $thumbimagesize['0'] : $tmp_imagesize['0']) . "'";
				}
			} // end if
		} // end foreach
		$list['upfiles']=$upfiles;
		unset($upfiles);
	} // end if 업로드파일 처리

	// 6 . URL Link...
	$href['list']	= "{$thisUrl}/{$urlprefix}list.php?" . href_qs("uid=",$qs_basic);
	$href['listdb'] = "{$urlprefix}list.php?db={$dbinfo['db']}";	
	$href['write']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=write&time=".time(),$qs_basic);
	$href['modify']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=modify&uid={$list['uid']}&num={$list['num']}&time=".time(),$qs_basic);
	$href['delete']	= "{$thisUrl}/{$urlprefix}ok.php?" . href_qs("mode=delete&uid={$list['uid']}",$qs_basic);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var('list'			,$list);	// 게시물 할당

$tpl->set_var('get'				,$_GET);	// get값으로 넘어온것들
$tpl->set_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->set_var('href'			,$href);

// 블럭 : 글쓰기
if(siteAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');

// 블럭 : 글수정,삭제
if(siteAuth($dbinfo, "priv_delete") or $list['bid'] == $_SESSION['seUid'] or $list['bid'] == 0){
	$tpl->process('MODIFY','modify');
	$tpl->process('DELETE','delete');
}

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
