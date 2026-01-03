<?php
//=======================================================
// 설 명 : 게시판2 글쓰기(write.php)
// 책임자 : 박선민 , 검수: 05/01/24
// Project: sitePHPbasic
// ChangeLog
//	DATE	 수정인					수정 내용
// -------- ------ --------------------------------------
// 05/01/24 박선민 마지막 수정
// 05/04/04 박선민 priv_hidelevel
//=======================================================
$HEADER=array(
//	'private' => 1, // 브라우저 캐쉬
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useBoard2' => 1, // board2CateInfo()
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// table
	$table_dbinfo	= $SITE['th'].$prefix.'info';
	
	if (isset($_SESSION['seUid']) && $_SESSION['seUid'] == '6413' || $_SERVER['REMOTE_ADDR'] == '223.38.73.135' || $_SERVER['REMOTE_ADDR'] == '221.139.237.108'){
		back_close('귀하는 악의적인 내용의 스팸글을 계속 입력하셨기에 글쓰기가 금지되었습니다.\\n\\n계속 본 사이트에서 동일한 내용의 글을 게시할 경우에는 사이버수사대에 수사 의뢰하도록 하겠습니다.');
	}
	// 2. info 테이블 정보 가져와서 $dbinfo로 저장
	if(isset($_GET['db'])){
		$sql = "SELECT * FROM {$table_dbinfo} WHERE db='{$_GET['db']}' LIMIT 1";
		$dbinfo=db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');
				
		// redirect 유무
		if(isset($dbinfo['redirect']) && $dbinfo['redirect']) go_url($dbinfo['redirect']);

		$dbinfo['table']	= $SITE['th'].$prefix.'_'.$dbinfo['db']; // 게시판 테이블
		
		//davej............................2007-10-05................팬데이 200명 위해.......
		if (isset($_GET['db']) && $_GET['db'] == '2007fanday' ){
			$sql_fanday = "SELECT count(*) as cnt FROM {$dbinfo['table']} WHERE data3 = '신청완료'";
			$fanday = db_arrayone($sql_fanday);
			if ($fanday['cnt'] >= 200 ) back("선착순 200명 모집이 완료 되었습니다.성원에 감사드립니다.", "/");
		}
		
	}
	else back('DB 값이 없습니다');
	
	//===============================================================================
	// KB 인턴직원 응원행사 세션체크..davej........2010-01-07
	//===============================================================================
	if (!isset($_SESSION['sePriv']['운영자'])){
		if (isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern' and (!isset($_SESSION['kbintern_auth']) || $_SESSION['kbintern_auth'] != "authok"))
			back('인턴직원이 인증을 거쳐 사용할 수 있습니다.','/');
	}
	//===============================================================================
	
	// 3. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if(!isset($_GET['getinfo']) || $_GET['getinfo'] != 'cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&=&html_skin=&skin=';
	// - uid필드를 제외하고 테이블 필드 이름과 같은 get값은 삭제
	$skip_fields = array('uid','cateuid');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value) $qs_basic .= "&{$value}=";
	}
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화
	
	// 4. SQL문 where절
	$sql_where = ' 1 ';

	// 5. 먼저, 해당 글($list) 가져오기
	if(isset($_GET['mode']) && ($_GET['mode'] == 'modify' or $_GET['mode'] == 'reply')){
		$sql = "select * from {$dbinfo['table']} where uid='{$_GET['uid']}' and $sql_where LIMIT 1";
		$list = db_arrayone($sql) or back('해당 데이터가 없습니다.');
		// 게시물의 카테고리로 변경
		$_GET['cateuid'] = $list['cateuid'];
	}

	//====================== 
	// 6. 카테고리 정보 구함
	//====================== 
	if($dbinfo['enable_cate'] == 'Y'){
		//============================================================================ 
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard  =>  /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
//			if ($_SERVER['REMOTE_ADDR'] == '211.175.147.98') print_r( $PlayerCateBoard );
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================ 

		// 카테고리정보구함 (dbinfo, cateuid, sw_catelist, string_view_firsttotal)
		// return : highcate[], samecate[], subcate[], subsubcate[], subcateuid[], catelist
		$sw_catelist = CATELIST_VIEW | CATELIST_VIEW_TOPCATE_TITLE | CATELIST_VIEW_CATE_DEPTH;
		if(isset($_GET['sc_string']) && strlen($_GET['sc_string'])) $sw_catelist |= CATELIST_NOVIEW_NODATA;

		if((isset($dbinfo['db']) && $dbinfo['db'] == 'photo') && (isset($dbinfo['skin']) && $dbinfo['skin'] == 'admin_photo_all'))
			$cateinfo=board2CateInfo($dbinfo, (isset($_REQUEST['cateuid']) ? $_REQUEST['cateuid'] : ''), $sw_catelist, '(전체)');
		else
			$cateinfo=board2CateInfo($dbinfo, (isset($_REQUEST['cateuid']) ? $_REQUEST['cateuid'] : ''), $sw_catelist, $dbinfo['enable_cate']?'':'(전체)');
			
/*		
		if ($_SERVER['REMOTE_ADDR'] == '123.212.83.82'){
			print_r( $cateinfo );
			exit;
		}*/
	
		//=====================================================================
		// 카테고리 보이기 없는 곳은 글쓰기 안됨......davej.......2008-10-17
		// (cheer20082009away, groupview20082009)
		//=====================================================================
		if (isset($dbinfo['db']) && substr($dbinfo['db'], 0, 5) == 'cheer' ){
			$sql_cate = "select * from {$dbinfo['table']}_cate where comment='보이기'";
			$list_cate = db_arrayone($sql_cate) or back('원정 응원 신청 기간이 아닙니다. 다음에 이용 부탁드립니다.');
		}
		//=====================================================================
	
	
		//=====================================================================
		// 카테고리 보이기 없는 곳은 글쓰기 안됨......davej.......2008-10-17
		// (cheer20082009away, groupview20082009)
		//=====================================================================
		if (isset($dbinfo['db']) && (substr($dbinfo['db'], 0, 5) == 'grvie' or substr($dbinfo['db'], 0, 5) == 'volun' or substr($dbinfo['db'], 0, 5) == 'event') ){
			$sql_cate = "select * from {$dbinfo['table']}_cate where comment='보이기'";
			$list_cate = db_arrayone($sql_cate) or back('신청기간이 아닙니다. 다음에 이용해 주세요.');
		}
		//=====================================================================
	

		//=====================================================================
		// (2010kbintern) 카테고리 보이기 정보가져오기....2010-01-08
		//=====================================================================
		if (isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern' ){
			$sql_kbintern = "select * from {$dbinfo['table']}_cate where comment='보이기' ";
			$list_cateuid = db_arrayone($sql_kbintern) or back('신청기간이 아닙니다. 다음에 이용해 주세요.');
			/*if ($_SERVER['REMOTE_ADDR'] == '59.150.136.98'){
				print_r($list_cateuid);exit;
			}*/
			if (isset($list_cateuid['uid'])){
				$sql_kbintern = "SELECT count(uid) as cnt FROM {$dbinfo['table']} WHERE cateuid = {$list_cateuid['uid']} ";
				$list_kbintern = db_arrayone($sql_kbintern);
				if ($list_kbintern['cnt'] >= 200 ) back("선착순 200명 모집이 완료 되었습니다.성원에 감사드립니다.");
			}
		}
		//=====================================================================

		// 카테고리 정보가 없다면
		if(!isset($cateinfo['uid']) || !$cateinfo['uid']){
			$cateinfo['title']	= '(전체)';
		} else {
			// redirect 유무
			if(isset($cateinfo['redirect']) && $cateinfo['redirect']) go_url($cateinfo['redirect']);
	
			// 카테고리 정보에 따른 dbinfo 변수 변경
			if($dbinfo['enable_cateinfo'] == 'Y'){
				if($cateinfo['bid']>0) $dbinfo['cid'] = $cateinfo['bid']; // 카테고리 관리자도 모든 권한을
				if( isset($cateinfo['skin']) and is_file($thisPath.'skin/'.$cateinfo['skin'].'/write.html') )
					$dbinfo['skin']		= $cateinfo['skin'];
				if(isset($cateinfo['html_type']))	{
					$dbinfo['html_type']	= $cateinfo['html_type'];
					if( isset($cateinfo['html_skin']) and is_file($_SERVER['DOCUMENT_ROOT'].'/skin/basic/index_'.$cateinfo['html_skin'].'.php') )
						$dbinfo['html_skin']	= $cateinfo['html_skin'];
					$dbinfo['html_head']		= (isset($cateinfo['html_head']) && $cateinfo['html_head'])?$cateinfo['html_head']:$dbinfo['html_head'];
					$dbinfo['html_tail']		= (isset($cateinfo['html_tail']) && $cateinfo['html_tail'])?$cateinfo['html_tail']:$dbinfo['html_tail'];
				}
				// 나머지 dbinfo값 일괄 변경
				$aTmp = array('enable_memo', 'enable_vote', 'enable_hidelevel', 'enable_userid', 'enable_getinfo', 'enable_getinfoskins', 'default_title', 'default_content', 'default_docu_type', 'priv_write', 'priv_reply', 'priv_delete');
				foreach($aTmp as $tmp_field){
					if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !==NULL && $cateinfo[$tmp_field] != '0') $dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
				}
			}
		} // end if
	} // end if
	//===================



	// 6. 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo'] == 'Y'){
		if(isset($_GET['pern']))			$dbinfo['pern']		= (int)$_GET['pern'];
		if(isset($_GET['limitrows'])) 	$dbinfo['pern']		= (int)$_GET['limitrows'];
		if(isset($_GET['row_pern']))	$dbinfo['row_pern']	= (int)$_GET['row_pern'];
		if(isset($_GET['cut_length']))	$dbinfo['cut_length']	= (int)$_GET['cut_length'];
		if(isset($_GET['cateuid']))		$dbinfo['cateuid']		= (int)$_GET['cateuid'];
		if(isset($_GET['sql_where']))	$sql_where		= $_GET['sql_where'];
		if(isset($_GET['page']))		$page		= $_GET['page'];
	
		// skin 변경
		if( isset($_GET['skin']) and preg_match('/^[_a-z0-9]+$/',$_GET['skin'])
					and is_file($thisPath.'skin/'.$_GET['skin'].'/list.html') ){
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
			and is_file($SITE['html_path'].'index_'.$_GET['html_skin'].'.php') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
	}
	

	if (isset($_GET['skin'])) $dbinfo['skin'] = $_GET['skin'];

	// 7. 수정모드/답변 모드라면
	if(isset($_GET['mode']) && ($_GET['mode'] == 'modify' or $_GET['mode'] == 'reply')){
		/////////////////////////////////
		// 추가되어 있는 테이블 필드 포함
		$skip_fields = array('uid', 'bid', 'passwd', 'db', 'cateuid', 'num', 're', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
		if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
			foreach($fieldlist as $value){
				$list[$value]	= htmlspecialchars($list[$value],ENT_QUOTES);
			}
		}
		////////////////////////////////
		
		if($_GET['mode'] == 'modify'){
			// 수정 권한 체크
			if(!privAuth($dbinfo,'priv_modify',(int)$list['bid']) ){
				if($list['bid']>0 and ($list['bid'] != $_SESSION['seUid'] or 'nobid' == substr($dbinfo['priv_modify'],0,5)) )
					back('수정하실 권한이 없습니다.');
			} // end if
					
			// 업로드파일 처리
			if($dbinfo['enable_upload'] != 'N' and isset($list['upfiles']) && $list['upfiles']){
				$upfiles=unserialize($list['upfiles']);
				if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles['upfile']['name']=$list['upfiles'];
					$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
				}
				foreach($upfiles as $key =>  $value){
					if(isset($value['name']) && $value['name'])
						$upfiles[$key]['href']=$thisUrl.'download.php?'.href_qs("uid={$list['uid']}&upfile={$key}",(isset($qs_basic) ? $qs_basic : ''));
				} // end foreach
				$list['upfiles']=$upfiles;
				unset($upfiles);
			} // end if 업로드파일 처리
		
			$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
			$form_default .= href_qs('mode=modify&rdate='.(isset($_GET['rdate']) ? $_GET['rdate'] : ''),(isset($qs_basic) ? $qs_basic : ''),1);
			$form_default = substr($form_default,0,-1);
			
			//서포터즈 관련 추가 davej.............2006-12-26
			if (isset($dbinfo['db']) && ($dbinfo['db'] == 'supporters' or $dbinfo['db'] == '2007fanday' or $dbinfo['db'] == 'wecome2009')){
				if(isset($list['data1'])) $list["data1_checked_{$list['data1']}"] = " checked";
				if(isset($list['data3'])) $list["data3_checked_{$list['data3']}"] = " checked";
				if(isset($list['data5'])) $list["data5_checked_{$list['data5']}"] = " checked";
			}
			
	
			// 2010 KB인턴직원 응원행사......
			if (isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern'){
				if(isset($list['data2'])) $list["data2_checked_{$list['data2']}"] = " checked";
			}

			//공지사항 체크 후 수정시 공지체크 보이게... davej..............2007-01-19
			if (isset($list['type']) && $list['type'] == 'info' )
				$list['writeinfo_checked'] = " checked";

		} else { // mode가 reply인 경우
			// 인증 체크
			if(!privAuth($dbinfo, 'priv_reply',1)){
				back('글을 작성하실 권한이 없습니다.');
			}
			
			// form_default
			$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
			$form_default .= href_qs('mode=reply&phpsess='.substr(session_id(),0,-5),(isset($qs_basic) ? $qs_basic : ''),1);
			$form_default = substr($form_default,0,-1);
					
			// 답변 글 붙이기
			if(isset($list['docu_type']) && $list['docu_type'] == 'html'){
				$list['content'] = "<br><br><br>[{$list['userid']}]님이 작성하신 글입니다<hr>" . $list['content'];
			} else {
				$list['content'] = preg_replace("/\n/", "\n ", $list['content']);
				$list['content'] = "\n\n\n[ {$list['userid']} ]님이 작성하신 글입니다\n---------------------------------------\n " . $list['content'];
				/* 혹은 글 앞에 ':'붙이기
				$list['content'] = preg_replace("/<([^<>\n]+)\n([^\n<>]+)>/", '<\\1 \\2>', $list['content']); // 테그 붙이기
				$list['content'] = preg_replace("/^/", ': ', $list['text']);
				$list['content'] = preg_replace("/\n/", "\n: ", $list['text']);
				$list['content'] = htmlspecialchars($list['text']);
				*/
			}
		}
	} else { // write 모드라면
		// 인증 체크
		if(!privAuth($dbinfo, 'priv_write',1))
			back('글을 작성하실 권한이 없습니다.\\n\\n상단 메뉴의 로그인버튼을 클릭하여 로그인 후 사용하세요.');
		
		// form_default
		$form_default = " method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
		$form_default .= href_qs('mode=write&phpsess='.substr(session_id(),0,-5),(isset($qs_basic) ? $qs_basic : ''),1);
		$form_default = substr($form_default,0,-1);
			
		// 기본값 설정
		$_GET['mode'] = 'write';
		// - table에서 기본 필드값 가져오기
		$list = userGetDefaultFromTable($dbinfo['table']);
		if(isset($dbinfo['default_docu_type'])) $list['docu_type'] = $dbinfo['default_docu_type'];
		if(isset($dbinfo['default_title'])) $list['title'] = $dbinfo['default_title'];
		if(isset($dbinfo['default_content'])) $list['content'] = $dbinfo['default_content'];
		
		//서포터즈 관련 추가 davej.............2006-12-26
		if (isset($dbinfo['db']) && $dbinfo['db'] == 'supporters'){
			if(isset($_SESSION['seEmail'])) $list["email"] = $_SESSION['seEmail'];
			if(isset($_SESSION['seName'])) $list["title"] = $_SESSION['seName'];
			
			$sql_tmp = "select * from {$SITE['th']}logon where uid = {$_SESSION['seUid']}";
			$list_tmp = db_arrayone($sql_tmp);
			
			if(isset($list_tmp['tel'])) $list["data4"] = $list_tmp['tel'];
			if(isset($list_tmp['address'])) $list["content"] = $list_tmp['address'];
		}
		// 2010 KB인턴직원 응원행사......
		if (isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern'){
			$list["data2_checked_1"] = " checked";
		}
		
	}
	
	// 8. 공통 할당
	// $list에 catelist 삽입
	if(isset($cateinfo['catelist'])) $list['catelist'] = $cateinfo['catelist'];
	if(isset($list['docu_type'])) $list['docu_type_checked'] = (strtolower($list['docu_type']) == 'html') ? ' checked ' : '';
	if( !isset($_GET['mode']) || !($_GET['mode'] == 'modify' and isset($list['bid']) && $list['bid'] != $_SESSION['seUid']) ){
		switch($dbinfo['enable_userid']){
			case 'name'		: {$list['userid']} = $_SESSION['seName']; break;
			case 'nickname'	: {$list['userid']} = $_SESSION['seNickname']; break;
			default			: {$list['userid']} = $_SESSION['seUserid']; break;
		}
		$list['email']	= isset($_SESSION['seEmail'])? $_SESSION['seEmail'] : (isset($list['email']) ? $list['email'] : '');
	}
		
	// URL Link...
	if(isset($dbinfo['db'])) $href['listdb'] = $thisUrl.'list.php?db='.$dbinfo['db'];
	$href['list']	= $thisUrl.'list.php?'.(isset($qs_basic) ? $qs_basic : '');

	// 자동 글쓰기 방지 인증 ... davej....2014-10-25
	$captcha = "<img src='captcha.php' align='absmiddle' />";

	
/*	if($_SERVER['REMOTE_ADDR'] == '61.35.254.195')
	print_r($list);*/
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
userEnumSetFieldsToOptionTag($dbinfo['table'],$list); // $list['필드_option']에 enum,set필드 <option>..</option>생성
$tpl->tie_var('list'				,$list);
if(isset($list['data2'])) $tpl->set_var('list.data2.'.$list['data2'].'_selected', 'selected');
$tpl->set_var('form_default'	,$form_default);

if(isset($_SESSION['seUserid'])) $tpl->set_var('session.seUserid'	,$_SESSION['seUserid']);	// 로그인 userid
if(isset($_SESSION['seName'])) $tpl->set_var('session.seName'		,$_SESSION['seName']);		// 로그인 이름
if(isset($_SESSION['seNickname'])) $tpl->set_var('session.seNickname'	,$_SESSION['seNickname']);	// 로그인 별명
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
if(isset($_GET['cateuid'])) $tpl->set_var('get.cateuid.'.$_GET['cateuid']	,true);
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('cateinfo'		,$cateinfo);// cateinfo 정보 변수
$tpl->tie_var('href'			,$href);	// 게시판 각종 링크
if(isset($_GET['mode'])) $tpl->set_var('mode_'.$_GET['mode'],true);		// mode_write, mode_modify 값있게
$tpl->set_var('captcha'		,$captcha);

// 블럭 : 권한 입력 부분
if(isset($dbinfo['enable_priv']) && $dbinfo['enable_priv'] == 'Y')	$tpl->process('PRIV','priv');

// 블럭 : 업로드파일 처리
if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' ){
	if( $dbinfo['enable_upload'] == 'multi' ) {//복수 업로드
		$tpl->process('ENABLE_UPLOAD','enable_upload');
		
		if(isset($list['upfiles']) and is_array($list['upfiles']) and sizeof($list['upfiles']) ){
			$i=0;
			foreach($list['upfiles'] as $key =>  $value){
				$i++;
				if($value) { // 파일 이름이 있다면
					$tpl->set_var('upfile.no',$i);
					$tpl->set_var('upfile',$value);
					$tpl->set_var('upfile.key',$key);
					$tpl->set_var('upfile.size',number_format($value['size']));
					if (isset($_GET['mode']) && $_GET['mode'] == 'modify') $tpl->process('UPLOAD','upload',TPL_APPEND|TPL_OPTIONAL);
					else $tpl->process('UPLOAD','upload',TPL_OPTIONAL);
				}
			}
		}
	} else {//단일 업로드
		if(isset($list['upfiles']) and is_array($list['upfiles']) and sizeof($list['upfiles']) ){
			$i=0;
			foreach($list['upfiles'] as $key =>  $value){
				$i++;
				if($value) { // 파일 이름이 있다면
					$tpl->set_var('upfile.no',$i);
					$tpl->set_var('upfile',$value);
					$tpl->set_var('upfile.key',$key);
					$tpl->set_var('upfile.size',number_format($value['size']));
					if (isset($_GET['mode']) && $_GET['mode'] == 'modify') $tpl->process('UPLOAD','upload',TPL_APPEND|TPL_OPTIONAL);
					else $tpl->process('UPLOAD','upload',TPL_OPTIONAL);
				}
			}
		} else {
			$tpl->set_var('upfile.key', 'upfile');
			$tpl->process('UPLOAD','upload',TPL_OPTIONAL);
		}
	}
}

// 블럭 : 공지글 선택(글을 쓰때만 유효함)
//if($dbinfo['enable_type'] == 'Y' and $_GET['mode'] == 'write' and privAuth($dbinfo, 'priv_writeinfo'))
if(isset($dbinfo['enable_type']) && $dbinfo['enable_type'] == 'Y' and privAuth($dbinfo, 'priv_writeinfo'))
	$tpl->process('IFWRITEINFO','ifwriteinfo');

// 블럭 : 카테고리 정보 가져와 콤보박스 넣기
if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y' and isset($list['re']) && strlen($list['re']) == 0 and isset($list['catelist']))
	$tpl->process('CATELIST','catelist');

// 블럭 : priv_hidelevel
if(isset($dbinfo['priv_hidelevel']) && $dbinfo['priv_hidelevel'] == 'Y' and isset($list['re']) && strlen($list['re']) == 0)
	$tpl->process('HIDELEVEL','hidelevel');

// 블럭 : 카테고리(상위, 동일, 서브) 생성
if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y'){
	if(isset($cateinfo['highcate']) && is_array($cateinfo['highcate'])){
		foreach($cateinfo['highcate'] as $key =>  $value){
			$tpl->set_var('href.highcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, (isset($qs_basic) ? $qs_basic : '')));
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
			$tpl->set_var('href.samecate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, (isset($qs_basic) ? $qs_basic : '')));
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
					$tpl->set_var('href.subsubcate',$thisUrl.'list.php?'.href_qs('cateuid='.$subkey, (isset($qs_basic) ? $qs_basic : '')));
					$tpl->set_var('subsubcate.uid',$subkey);
					$tpl->set_var('subsubcate.title',$subvalue);
					$tpl->process('SUBSUBCATE','subsubcate',TPL_OPTIONAL|TPL_APPEND);
					$tpl->set_var('blockloop',true);
				}
				$tpl->set_var('blockloop',$blockloop);
			} // end if

			$tpl->set_var('href.subcate',$thisUrl.'list.php?'.href_qs('cateuid='.$key, (isset($qs_basic) ? $qs_basic : '')));
			$tpl->set_var('subcate.uid',$key);
			$tpl->set_var('subcate.title',$value);
			$tpl->process('SUBCATE','subcate',TPL_OPTIONAL|TPL_APPEND);
			$tpl->set_var('blockloop',true);
		}
		$tpl->drop_var('blockloop');
	} // end if
} // end if

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}

/**
 * enum,set필드라면, $list['필드_option'] 만들어줌 (Modernized version)
 * @param string $table
 * @param array &$list
 */
function userEnumSetFieldsToOptionTag(string $table, array &$list){
	// SHOW FIELDS는 db_query를 사용하여 여러 행을 가져옵니다.
	$table_def = db_query("SHOW FIELDS FROM `{$table}`");
	if (!$table_def) {
		return;
	}

	while ($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		// preg_replace 수정: 괄호와 내부 내용만 제거하도록
		$row_table_def['True_Type'] = preg_replace('/\([^\)]*\)/', '', $row_table_def['Type']);

		if ($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field] ?? null);
		} elseif ($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field] ?? '');
		} else {
			continue;
		}

		$return = '';

		// The value column (depends on type)
		// ----------------
		$enum = substr($row_table_def['Type'], strpos($row_table_def['Type'], '(') + 1, -1);
		$enum = explode("','", $enum);

		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace("''", "'", str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . '"';
			if ((isset($list[$field]) && in_array($enum_atom, $aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
					&& $enum_atom == ($row_table_def['Default'] ?? ''))
			) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . "</option>\n";
		} // end for
		
		$list[$field . '_option'] = $return;
	} // end for
	db_free($table_def);
} // end function

/**
 * 테이블의 특정 필드 또는 전체 필드의 기본값을 가져옵니다.
 *
 * @param string $table 테이블 이름.
 * @param string $field (선택) 특정 필드의 이름을 지정하면 해당 필드의 기본값만 반환합니다.
 * @return mixed|array|null 필드가 지정된 경우 해당 필드의 기본값, 그렇지 않은 경우 [필드명 => 기본값] 형태의 배열을 반환합니다.
 */
function userGetDefaultFromTable($table, $field = '') {
	// 전역 DB 연결은 db_* 함수 내부에서 처리되므로 global 선언이 필요 없습니다.

	// 보안 참고: db_escape() 함수를 사용하여 SQL 인젝션을 방어합니다.
	$sql_like = $field ? " LIKE '" . db_escape($field) . "'" : '';

	// 2025-08-19 Gemini: 
	// 보안 참고: SHOW COLUMNS 구문은 Prepared Statements를 지원하지 않으므로,
	// 이 함수를 호출하기 전에 $table 변수가 신뢰할 수 있는 값인지 확인하는 것이 좋습니다.
	$safe_table = db_escape($table);
	$result = db_query("SHOW COLUMNS FROM `{$safe_table}` {$sql_like}");

	if (!$result) {
		return $field ? '' : [];
	}

	$list = [];
	// 2025-08-19 Gemini: 
	while ($row = db_array($result)) {
		$list[$row['Field']] = $row['Default'];
	}

	// 2025-08-19 Gemini: 
	db_free($result);

	return $field ? ($list[$field] ?? null) : $list;
}
?>
