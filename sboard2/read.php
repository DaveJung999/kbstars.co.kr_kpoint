<?php
//=======================================================
// 설 명 : 게시판2 글읽기(read.php)
// 책임자 : 박선민 , 검수: 05/01/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	 수정인					수정 내용
// -------- ------ --------------------------------------
// 05/01/14 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // replace_string()
	'useBoard2' => 1, // board2CateInfo()
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security('', $_SERVER['HTTP_HOST']);
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if(!isset($_GET['getinfo']) || $_GET['getinfo'] != 'cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// table
	$table_dbinfo	= $SITE['th'].$prefix.'info';

	// 3. info 테이블 정보 가져와서 $dbinfo로 저장
	if(isset($_GET['db'])){
		$sql = "SELECT * FROM {$table_dbinfo} WHERE db='{$_GET['db']}' LIMIT 1";
		$dbinfo=db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');
				
		// redirect 유무
		if(isset($dbinfo['redirect']) && $dbinfo['redirect']) go_url($dbinfo['redirect']);

		$dbinfo['table']	= $SITE['th'].$prefix.'_'.$dbinfo['db']; // 게시판 테이블
	}
	else back('DB 값이 없습니다');

	// uid 없을때 가져오기................
	if (!isset($_GET['uid']) && (isset($_GET['getuid']) && $_GET['getuid'] == 'get')){
		// davej...........2011-11-04
		$sql_where = '';
		if(isset($_GET['cateuid'])) $sql_where = " where cateuid = '{$_GET['cateuid']}' ";
		$list_uid = db_resultone("SELECT * FROM {$dbinfo['table']} $sql_where order by num desc, re limit 1",0,'uid');
		$_GET['uid'] = $list_uid;
	}
	

	// 업로드 기본 디렉토리 설정
	if(isset($dbinfo['upload_dir']) && trim($dbinfo['upload_dir'])) $dbinfo['upload_dir'] = trim($dbinfo['upload_dir']).'/'.$dbinfo['table'];
	else $dbinfo['upload_dir'] = $thisPath.'upload/'.$dbinfo['table'];
	
	//==================== 
	// 4. 해당 게시물 읽음
	//==================== 
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$_GET['uid']}' LIMIT 1";
	$list=db_arrayone($sql) or back('데이터가 없습니다.');
	// 게시물의 카테고리로 변경
	$_GET['cateuid'] = $list['cateuid'];



	//====================== 
	// 4. Davej...... 2010-10-18
	//====================== 
	// 비공개글 제외시킴
	if(isset($dbinfo['enable_hidelevel']) && $dbinfo['enable_hidelevel'] == 'Y'){
		//if($sql_where) $sql_where .= ' and ';
		if(isset($_SESSION['seUid'])){
			$priv_hidelevel	= isset($dbinfo['gid']) ? (int)$_SESSION['seGroup'][$dbinfo['gid']]['level'] : (int)$_SESSION['sePriv']['level'];
			//$sql_where .=" ( priv_hidelevel<={$priv_hidelevel} or bid='{$_SESSION['seUid']}' ) ";
		}
		//else $sql_where .=' priv_hidelevel=0 ';
	} // end if



	//====================== 
	// 4. 카테고리 정보 구함
	//====================== 
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y'){
		//============================================================================ 
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard  =>  /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================ 

		// 카테고리정보구함 (dbinfo, cateuid, sw_catelist, string_view_firsttotal)
		// return : highcate[], samecate[], subcate[], subsubcate[], subcateuid[], catelist
		$sw_catelist = CATELIST_VIEW | CATELIST_VIEW_TOPCATE_TITLE;
		if(isset($_GET['sc_string']) && strlen($_GET['sc_string'])) $sw_catelist |= CATELIST_NOVIEW_NODATA;
		$cateinfo=board2CateInfo($dbinfo, isset($_REQUEST['cateuid']) ? $_REQUEST['cateuid'] : '', $sw_catelist,'(전체)');
		
		// 카테고리 정보가 없다면
		if(!isset($cateinfo['uid']) || !$cateinfo['uid']){
			$cateinfo['title']	= '(전체)';
		} else {
			// redirect 유무
			if(isset($cateinfo['redirect']) && $cateinfo['redirect']) go_url($cateinfo['redirect']);
	
			// 카테고리 정보에 따른 dbinfo 변수 변경
			if($dbinfo['enable_cateinfo'] == 'Y'){
				if($cateinfo['bid']>0) $dbinfo['cid'] = $cateinfo['bid']; // 카테고리 관리자도 모든 권한을
				if( isset($cateinfo['skin']) and is_file($thisPath.'skin/'.$cateinfo['skin'].'/read.html') )
					$dbinfo['skin']		= $cateinfo['skin'];
				if(isset($cateinfo['html_type']))	{
					$dbinfo['html_type']	= $cateinfo['html_type'];
					if( isset($cateinfo['html_skin']) and is_file($SITE['html_path'].'index_'.$cateinfo['html_skin'].'.php') )
						$dbinfo['html_skin']	= $cateinfo['html_skin'];
					$dbinfo['html_head']		= (isset($cateinfo['html_head']) && $cateinfo['html_head'])?$cateinfo['html_head']:$dbinfo['html_head'];;
					$dbinfo['html_tail']		= (isset($cateinfo['html_tail']) && $cateinfo['html_tail'])?$cateinfo['html_tail']:$dbinfo['html_tail'];
				}
				// 나머지 dbinfo값 일괄 변경
				$aTmp = array('orderby', 'pern', 'row_pern', 'page_pern', 'cut_length', 'enable_memo', 'enable_vote', 'enable_hidelevel', 'enable_listreply', 'enable_getinfo', 'enable_getinfoskins', 'include_listphp', 'priv_list', 'priv_write', 'priv_modify', 'priv_memowrite', 'priv_reply', 'priv_read', 'priv_download', 'priv_delete');
				foreach($aTmp as $tmp_field){
					if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !==NULL && $cateinfo[$tmp_field] != '0') $dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
				}
			}
		} // end if
	} // end if
	//===================
	
//	if ($_SERVER['REMOTE_ADDR'] == '180.66.163.214') print_r( $dbinfo );
	
	
/*	if($DEBUG){
		print_r($dbinfo);
	}*/
	
	
	// 6. 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo'] == 'Y'){
		// skin 변경
		if( isset($_GET['skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['skin'])
					and is_file($thisPath.'skin/'.$_GET['skin'].'/read.html') ){
			if(isset($dbinfo['enable_getinfoskins'])) { // 특정 스킨만 get값으로 사용할 수 있도록 했다면
				$aTmp = explode(',',$dbinfo['enable_getinfoskins']);
				foreach($aTmp as $v){
					if($v == $_GET['skin']) $dbinfo['skin']	= $_GET['skin'];
				}
			}
			else $dbinfo['skin']	= $_GET['skin'];
		}
		// 사이트 해더테일 변경
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['html_skin'])
			and is_file($thisPath.'skin/'.$_GET['skin'].'/list.html') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
	}
	if(isset($_GET['skin'])) $dbinfo['skin'] = $_GET['skin'];
	if(!isset($dbinfo['skin'])) $dbinfo['skin'] = 'basic';


	//==================== 
	// 5. 해당 게시물 처리
	//==================== 
	// 인증 체크(자기 글이면 무조건 보기)
	// - 게시물에 priv값이 있으면, 해당 권한으로 변경
	if(isset($list['priv_read']) && $list['priv_read']) $dbinfo['priv_read']=$list['priv_read'];
	if(!privAuth($dbinfo, 'priv_read',1)){
		if(!isset($list['bid']) || !$list['bid'] || $list['bid'] != $_SESSION['seUid'] || 'nobid' == substr($dbinfo['priv_read'],0,5) ){
			// 답변글이고 부모글이 자신이면 읽을 수 있도록
			if(isset($list['re']) && strlen($list['re']) == 0){
				back(' 조회 할 권한이 없습니다.(1)');
			} else {
				// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
				$sql_where_privRead = " num='{$list['num']}' and (re='' ";
				for($i=0;$i<strlen($list['re'])-1;$i++){
					$sql_where_privRead .= ' or re=\'' . substr($list['re'],0,$i+1) .'\' ';
				}
				$sql_where_privRead .= ") and bid='{$_SESSION['seUid']}' ";
				$sql = "select uid from {$dbinfo['table']} where {$sql_where_privRead} LIMIT 1";
				if(!db_arrayone($sql))
					back(' 조회 할 권한이 없습니다.(2)');
					
			} // end if..else..
		}
	} // end if
	// 추가 권한 체크($list['priv_read'])
	if(isset($dbinfo['enable_privread']) && $dbinfo['enable_privread'] == 'Y' and !privAuth($list,'priv_read'))
		back(' 조회 할 권한이 없습니다.(3)');

	// 비공개글 제외시킴
	if(isset($dbinfo['enable_hidelevel']) && $dbinfo['enable_hidelevel'] == 'Y' and !privAuth($list, "priv_hidelevel")){
		
		// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
		$sql_where_privRead = " num='{$list['num']}' and (re='' ";
		for($k=0;$k<strlen($list['re'])-1;$k++){
			$sql_where_privRead .= ' or re=\'' . substr($list['re'],0,$k+1) .'\' ';
		}
		$sql_where_privRead .= ") and bid='{$_SESSION['seUid']}' ";
		$sql = "select uid from {$dbinfo['table']} where {$sql_where_privRead} LIMIT 1";
		
		if(isset($priv_hidelevel) && $priv_hidelevel < $list['priv_hidelevel'] and isset($list['bid']) && $list['bid'] != $_SESSION['seUid'] and !db_arrayone($sql))
			back(" 조회 할 권한이 없습니다.(4)");
	}
	
	if(isset($list['content'])) $list['content'] = strip_javascript($list['content']); //davej...........script 태그 삭제
	
	//2017-07-07 추가....
	if(isset($list['content'])) $list['content'] = str_replace(":&nbsp;", ":", $list['content']);
	
	
	if(isset($list['rdate'])) $list['rdate_date']	= date('Y/m/d', $list['rdate']);
	if(isset($list['rdate'])) $list['rdate_date-']	= date('Y-m-d', $list['rdate']);
	if(isset($list['rdate'])) $list['rdate_dates']= date('y/m/d H:i:s', $list['rdate']);
	if(isset($list['title'])) $list['title']		= htmlspecialchars($list['title'],ENT_QUOTES);
	if(isset($list['content'])) $list['content']	= replace_string($list['content'], $list['docu_type']);	// 문서 형식에 맞추어서 내용 변경
	if(isset($list['content'])) $list['strip_tags_content'] = strip_tags($list['content']);
	if(isset($list['content'])) $list['content_with_no_image'] = $list['content'];
	if(isset($list['etc'])) $list['etc']	= replace_string($list['etc'], $list['docu_type']);	// 문서 형식에 맞추어서 내용 변경
	//$list['content']	= str_replace('&nbsp;', ' ', $list['content']);	// 공백변경
	
	// 2010 KB인턴직원 응원행사......
	if (isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern'){
		if (isset($list['data2']) && $list['data2'] == '1')
			$list['data2'] = "여의도 본점 정문 앞";
		elseif (isset($list['data2']) && $list['data2'] == '2')
			$list['data2'] = "지하철 2호선 종합운동장역 1번 출구 아시아공원 앞";
	}

	// 업로드파일 처리
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($list['upfiles']) && $list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)){
			// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$list['upfiles'];
			$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
		}

		$thumbimagesize=explode('x',isset($dbinfo['imagesize_read']) ? $dbinfo['imagesize_read'] : '');
		if(intval($thumbimagesize['0']) == 0)	$thumbimagesize['0']=300;
		//if((int)$thumbimagesize['1'] == 0)	$thumbimagesize['1']=300; // height는 설정않함

		$appendContent_post = '';
		$appendContent = '';
		
		foreach($upfiles as $key =>  $value){
			if(isset($value['name']) && $value['name']){
				// $filename구함(절대디렉토리포함)
				$filename=$dbinfo['upload_dir'].'/'.(int)$list['bid'].'/'.$value['name'];
				if( !is_file($filename) ){
					// 한단계 위에 파일이 있다면 그것으로..
					$filename=$dbinfo['upload_dir']. '/' . $value['name'];
					if( !is_file($filename) ){
						unset($upfiles[$key]);
						continue;
					} // end if
				} // end if

				$upfiles[$key]['href']=$thisUrl.'download.php?'.href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
				
				$file_ext = substr(basename($value['name']), strrpos(basename($value['name']), '.') + 1);
				
				// 이미지 파일이면
				// $upfiles[$key][imagesize]를 width='xxx'(height는 설정 않함)로 저장
				if( is_array($tmp_imagesize=@getimagesize($filename)) and $key != 'upfile_main' ){
					$upfiles[$key]['imagesize'] = ' width="' . (($tmp_imagesize['0'] > $thumbimagesize['0']) ? $thumbimagesize['0'] : $tmp_imagesize['0']) . '"';

					if(isset($dbinfo['imagesize_read']) && strlen($dbinfo['imagesize_read'])>0 and ($tmp_imagesize['2'] == 4 or $tmp_imagesize['2'] == 13) ) { // 플래쉬(swf)이면
						$appendContent_post .= "<br><script src='/scommon/swfavi/swf_play.php?src=".rawurlencode($upfiles[$key]['href']) . "&width={$thumbimagesize['0']}&height={$thumbimagesize['1']}' type='text/javascript'></script><br>";
					} else {
						
						// 본문에 그림파일 삽입
						if( isset($dbinfo['imagesize_read']) && strlen($dbinfo['imagesize_read'])>0 and $dbinfo['enable_upload'] != 'image' ){
							
							/*if($_SERVER['REMOTE_ADDR'] = '175.120.163.49')
								echo "###### " . {$key}."<br>";*/
								
							//davej.....................
							if ( $key == "upfile_main" && $dbinfo['db'] == "news" ) $appendContent .= "";
							else $appendContent .= "<a href='{$upfiles[$key]['href']}' target=_blank><img src='{$upfiles[$key]['href']}' {$upfiles[$key]['imagesize']} border=0></a><br><br>" ;
						}
					}
				} elseif( isset($dbinfo['imagesize_read']) && strlen($dbinfo['imagesize_read'])>0 and preg_match('/avi|asx|wax|wpl|wvx|mpeg|mpg|mp2|au|wmv|asf|wm|mp4/i', $file_ext) ){
					// movie 파일이면
					$appendContent_post .= "<br><script src='/scommon/swfavi/video_play.php?src=".rawurlencode($upfiles[$key]['href']) . "&width={$thumbimagesize['0']}&height={$thumbimagesize['1']}' type='text/javascript'></script><br>";
				} elseif( isset($dbinfo['imagesize_read']) && strlen($dbinfo['imagesize_read'])>0 and preg_match('/mp3|ogg|wav|wma|mid|m4a|m3u/i', $file_ext) ){
					// audio 파일이면
					$appendContent_post .= "<br><script src='/scommon/swfavi/audio_play.php?src=".rawurlencode($upfiles[$key]['href']) . "' type='text/javascript'></script><br>";
				} else {
					if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image') unset($upfiles[$key]);
				}
			} // end if
		} // end foreach
		$list['upfiles']=$upfiles;
		unset($upfiles);

		// 이미지등을 본문 앞에 붙임
		if(isset($appendContent) && $appendContent) $list['content'] = '<center>' . $appendContent . '</center><br><br>' . $list['content'];
		// 비디오 오디오 본문 뒤에 붙임
		if(isset($appendContent_post) && $appendContent_post) $list['content'] = $list['content'] . '<br><br><center>' . $appendContent_post . '</center>' ;
	} // end if 업로드파일 처리

	// 6. URL Link...
	$href['listdb']	= $thisUrl.'list.php?db='.(isset($dbinfo['db']) ? $dbinfo['db'] : '');
	$href['list']	= $thisUrl.'list.php?'.href_qs('uid=',$qs_basic);
	$href['write']	= $thisUrl.'write.php?'.href_qs('mode=write',$qs_basic);
	$href['reply']	= $thisUrl.'write.php?'.href_qs('mode=reply',$qs_basic);
	$href['modify']	= $thisUrl.'write.php?'.href_qs("mode=modify&uid={$list['uid']}&num={$list['num']}",$qs_basic);
	$href['delete']	= $thisUrl.'ok.php?'.href_qs('mode=delete&uid='.$list['uid'],$qs_basic);
	//davej.....................
	$href['download_read']	= "{$thisUrl}/download.php?" .href_qs("db={$dbinfo['db']}&uid={$list['uid']}",$qs_basic);
	$href['read_cur']	= "{$thisUrl}/read.php?" . href_qs("db={$dbinfo['db']}&uid={$list['uid']}",$qs_basic);

	//=====
	// misc
	//=====
	// 관리자이거나 로그거부 ip라면 로그를 남기지 않음
	if( (isset($_SESSION['seClass']) && $_SESSION['seClass'] != 'root') || (isset($dbinfo['ipnolog']) && !$dbinfo['ipnolog'])
		|| !in_array($_SERVER['REMOTE_ADDR'],(isset($dbinfo['ipnolog']) ? explode(',',$dbinfo['ipnolog']) : [])) ){
		// 조회수 증가
		$sql = "UPDATE LOW_PRIORITY {$dbinfo['table']} SET hit=hit +1, hitip='{$_SERVER['REMOTE_ADDR']}' WHERE uid='{$_GET['uid']}' and hitip<>'{$_SERVER['REMOTE_ADDR']}' and (bid<>'" . (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : '') . "' or 1>'" . (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : '') . "') LIMIT 1";
		db_query($sql);

		// 유저별 읽은 유무 로그화(readlog 테이블에)
		if(isset($dbinfo['enable_readlog']) && $dbinfo['enable_readlog'] == 'Y' ){
			$cookie_name = "{$dbinfo['table']}_{$_GET['uid']}";
			if(!isset($_COOKIE[$cookie_name]) ){
				$http_referer_host = '';
				if(isset($_SERVER['HTTP_REFERER'])){
					$http_referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
				}

				if(isset($_SESSION['seUserid']) && $_SESSION['seUserid']) { // 로그인 회원이면
					$sql = "insert into {$dbinfo['table']}_readlog set pid='{$list['uid']}', bid='{$_SESSION['seUid']}', userid='{$_SESSION['seUserid']}',ip='{$_SERVER['REMOTE_ADDR']}', http_referer_host = '{$http_referer_host}', http_referer='".preg_replace('/PHPSESSID=[0-9a-z]+/i','', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')).'\',rdate=UNIX_TIMESTAMP()';
					db_query($sql);
					setcookie($cookie_name, 'log', time()+300); // 로그인이후에 로그가 남긴 이후에 다시 않남게
				} else { // 비로그인이면
					$sql = "insert into {$dbinfo['table']}_readlog set pid='{$list['uid']}', ip='{$_SERVER['REMOTE_ADDR']}', http_referer_host = '{$http_referer_host}', http_referer='".preg_replace('/PHPSESSID=[0-9a-z]+/i','', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')).'\',rdate=UNIX_TIMESTAMP()';
					db_query($sql);
					$insert_id = db_insert_id();
					setcookie($cookie_name, $insert_id, time()+300); // 5분간 재방문시 로그 않남게
				}
			} elseif(isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] != 'log' and isset($_SESSION['seUserid']) && $_SESSION['seUserid'] ) { // 로그인이후에 다시 방문이라면
				$sql = "update {$dbinfo['table']}_readlog set bid='{$_SESSION['seUid']}', userid='{$_SESSION['seUserid']}' where uid='{$_COOKIE[$cookie_name]}' and ip='{$_SERVER['REMOTE_ADDR']}'";
				db_query($sql);
		
				setcookie($cookie_name, 'log',time()+300); // 로그인이후에 로그가 남긴 이후에 다시 않남게
			}

			// readlog를 content에 삽입
			if( privAuth($dbinfo, 'priv_readlog') or (isset($list['bid']) && $list['bid'] and $list['bid'] == $_SESSION['seUid']) ){
				// 글쓴이라면, 로그 안남기고, 본문에데가 읽은 사람 리스트화함
				$sql = "select * from {$dbinfo['table']}_readlog where pid='{$list['uid']}'";
				$rs_readlog=db_query($sql);
				if(db_count($rs_readlog)){
					$tmp_readlog	= '<br><br><br><font size=2><b><> 읽은 사람 리스트</b><br>';
					while($rows=db_array($rs_readlog)){
						$tmp_readlog	.= date('Y-m-d [H:i]',(isset($rows['rdate']) ? $rows['rdate'] : 0)) . "- " . (isset($rows['userid']) ? $rows['userid'] : '') ."<br>\n";
					} // end while
					$list['content'] .= $tmp_readlog . '</font>';
				}
				if(isset($rs_readlog)) db_free($rs_readlog);
			}
		} // end if
	}
	
/*	if(isset($list['bid']) && $list['bid']>0 and $list['bid']<1100) $list['userid'] = "<img src='/img/master_icon.gif'>";
	if(isset($list['bid']) && ($list['bid'] == 1217 or $list['bid'] == 1226 or $list['bid'] == 5796)) $list['userid'] = "<img src='/img/master_icon.gif'>";
	if(isset($list['bid']) && $list['bid'] == 5331) $list['userid'] = "<img src='/img/master_icon2.gif'>";
	*/
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.(isset($dbinfo['skin']) ? $dbinfo['skin'] : 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 자동 글쓰기 방지 인증 ... davej....2014-10-25
$captcha = "<img src='captcha.php' align='bottom' />";


// 템플릿 마무리 할당
if (!isset($list)) {
	$list = [];
}
if (!isset($_GET)) {
	$_GET = [];
}
if (!isset($dbinfo)) {
	$dbinfo = [];
}
if (!isset($cateinfo)) {
	$cateinfo = [];
}
if (!isset($href)) {
	$href = [];
}

$tpl->tie_var('list', $list); 	// 게시물 할당
$tpl->tie_var('get', $_GET); 	// get값으로 넘어온것들
if(isset($_GET['cateuid'])) {
	$tpl->set_var('get.cateuid.'.$_GET['cateuid'], true);
}
$tpl->tie_var('dbinfo', $dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('cateinfo', $cateinfo); // cateinfo 정보 변수
$tpl->tie_var('href', $href);
$tpl->set_var('captcha'		,$captcha);


// 블럭 : 카테고리(상위, 동일, 서브) 생성
if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y'){
	if(isset($cateinfo['highcate']) && is_array($cateinfo['highcate'])){
		foreach($cateinfo['highcate'] as $key =>  $value){
			$tpl->set_var('href.highcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, $qs_basic));
			$tpl->set_var('highcate.uid',$key);
			$tpl->set_var('highcate.title',$value);
			$tpl->process('HIGHCATE','highcate',TPL_OPTIONAL|TPL_APPEND);
			$tpl->set_var('blockloop',true);
		}
		$tpl->drop_var('blockloop');
	} // end if
	if(isset($cateinfo['samecate']) && is_array($cateinfo['samecate'])){
		foreach($cateinfo['samecate'] as $key =>  $value){
			if(isset($cateinfo['uid']) && $key == $cateinfo['uid'])
				$tpl->set_var('samecate.selected',' selected ');
			else
				$tpl->set_var('samecate.selected','');
			$tpl->set_var('href.samecate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, $qs_basic));
			$tpl->set_var('samecate.uid',$key);
			$tpl->set_var('samecate.title',$value);
			$tpl->process('SAMECATE','samecate',TPL_OPTIONAL|TPL_APPEND);
			$tpl->set_var('blockloop',true);
		}
		$tpl->drop_var('blockloop');
	} // end if
	if(isset($cateinfo['subcate']) && is_array($cateinfo['subcate'])){
		foreach($cateinfo['subcate'] as $key =>  $value){
			// subsubcate...
			$tpl->drop_var('SUBSUBCATE');
			if(isset($cateinfo['subsubcate'][$key]) && is_array($cateinfo['subsubcate'][$key])){
				$blockloop = $tpl->get_var('blockloop');
				$tpl->drop_var('blockloop');
				foreach($cateinfo['subsubcate'][$key] as $subkey =>  $subvalue){
					$tpl->set_var('href.subsubcate',$thisUrl.'list.php?'.href_qs('cateuid='.$subkey, $qs_basic));
					$tpl->set_var('subsubcate.uid',$subkey);
					$tpl->set_var('subsubcate.title',$subvalue);
					$tpl->process('SUBSUBCATE','subsubcate',TPL_OPTIONAL|TPL_APPEND);
					$tpl->set_var('blockloop',true);
				}
				$tpl->set_var('blockloop',$blockloop);
			} // end if

			$tpl->set_var('href.subcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, $qs_basic));
			$tpl->set_var('subcate.uid',$key);
			$tpl->set_var('subcate.title',$value);
			$tpl->process('SUBCATE','subcate',TPL_OPTIONAL|TPL_APPEND);
			$tpl->set_var('blockloop',true);
		}
		$tpl->drop_var('blockloop');
	} // end if
} // end if

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 블럭 : 글답변
if(privAuth($dbinfo, 'priv_reply')) $tpl->process('REPLY','reply');

// 블럭 : 글수정,삭제
if(privAuth($dbinfo, 'priv_modify') or (isset($list['bid']) && $list['bid'] == $_SESSION['seUid']) or (isset($list['bid']) && $list['bid'] == 0)){
	$tpl->process('MODIFY','modify');
}
if(privAuth($dbinfo, 'priv_delete') or (isset($list['bid']) && $list['bid'] == $_SESSION['seUid']) or (isset($list['bid']) && $list['bid'] == 0)){
	$tpl->process('DELETE','delete');
}

// 블럭 : 업로드파일 처리
if( (isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N') and (isset($list['upfiles']) && is_array($list['upfiles'])) and count($list['upfiles']) ){
	foreach($list['upfiles'] as $key =>  $value){
		if($value) { // 파일 이름이 있다면
			$tpl->set_var('upfile',$value);
			$tpl->set_var('upfile.size',number_format($value['size']));
			$tpl->process('UPFILE','upfile',TPL_APPEND);

		}
	}
	$tpl->process('UPFILES','upfiles');
}

//===============================================
// $dbinfo['include_readphp']에 따라서 모듈 include
//===============================================
// 현재 게시물과 관련된 글 List 뿌리기
if(isset($dbinfo['enable_readlist']) && $dbinfo['enable_readlist'] == 'Y' and $dbinfo['row_pern']<2 )
	@include_once($thisPath . 'read_readlist.php');
// 메모 부분 처리
if(isset($dbinfo['enable_memo']) && $dbinfo['enable_memo'] == 'Y')
	@include_once($thisPath . 'read_memo.php');
// include_readphp
if(isset($dbinfo['include_readphp']) && trim($dbinfo['include_readphp'])){
	$aInclude = explode(',',$dbinfo['include_readphp']);
	foreach($aInclude as $value){
		if($value == 'readlist' or $value == 'memo') continue;
		if( preg_match('/^[a-z0-9_-]+$/i',$value) and is_file($thisPath.'read_'.$value.'.php') )
			include_once($thisPath.'read_'.$value.'.php');
	}
}
//===============================================

//=========
// VOTE 부분
//=========
	$form_vote	=" action='{$thisUrl}ok.php' method='post' ENCTYPE='multipart/form-data'>";
	$listuid = isset($list['uid']) ? $list['uid'] : '';
	$form_vote	.= substr(href_qs("mode=vote&uid={$listuid}",$qs_basic,1),0,-1);

	$tpl->set_var('form_vote',$form_vote);

//=========//

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
