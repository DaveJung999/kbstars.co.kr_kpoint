<?php
//=======================================================
// 설 명 : 게시판2 처리(ok.php)
// 책임자 : 박선민 , 검수: 05/04/06
// Project: sitePHPbasic
// ChangeLog
//	DATE	 수정인					수정 내용
// -------- ------ --------------------------------------
// 05/01/24 박선민 마지막 수정
// 05/04/06 박선민 bigfix - docu_type에서 값 없으면 text로
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
// 2025/08/15 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정 (function_mysql2.php 참조)
// 2025/09/11 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // file_upload(),remote_addr()
	'useCheck' => 1, // check_value()
	'useClassSendmail' => 1, // mime_mail
	'usePoint' => 1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
	$prefix		= 'board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

	if (isset($_SESSION['seUid']) && $_SESSION['seUid'] == '6413'){
		back_close('귀하는 스팸글을 계속 입력하셔서 글쓰기가 금지되었습니다.\\n\\n계속 본 사이트와 관련 없는 글을 게시 할 경우에는 사이버수사대에 수사 의뢰하도록 하겠습니다.');
	}

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2022-08-10 추
	if (isset($_POST['passwd'])){
		/*if($_SERVER['REMOTE_ADDR'] == '221.145.207.44'){
			echo "<BR><BR>";
			print_r($_POST);	//exit;
			echo "<BR><BR>";
		}*/

		$encoded = $_POST['passwd'];
		$encoded = base64_decode($encoded);
		$decoded = '';
		
		for( $i = 0; $i < strlen($encoded); $i++ ){
			$b = ord($encoded[$i]);
			$a = $b ^ 10; //
			$decoded .= chr($a);
		}
		
		$_POST['passwd'] = base64_decode(base64_decode($decoded));
		
		$_REQUEST['passwd'] = $_POST['passwd'];
		
		/*if($_SERVER['REMOTE_ADDR'] == '221.145.207.44'){
			echo "<BR>_POST<BR>";
			print_r($_POST);
			echo "<BR><BR>";
			echo "<BR>_REQUEST<BR>";
			print_r($_REQUEST);
			echo "<BR><BR>";
			exit;
		}*/
	}

	// 2. 기본 URL QueryString

	// 기본 URL QueryString
	$qs_basic = 'db=' . ($_REQUEST['db'] ?? '') .			//table 이름
				'&mode=' . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				'&cateuid=' . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				'&team=' . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				'&pern=' . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				'&sc_column=' . ($_REQUEST['sc_column'] ?? '') .	//search column
				'&sc_string='	. urlencode(stripslashes($_REQUEST['sc_string'] ?? '')) . //search string
				'&page=' . ($_REQUEST['page'] ?? '');
				
	if(isset($_REQUEST['getinfo']) && $_REQUEST['getinfo'] == 'cont'){
		$qs_basic .= "&html_type={$_REQUEST['html_type']}&html_skin={$_REQUEST['html_skin']}&skin={$_REQUEST['skin']}&getinfo={$_REQUEST['getinfo']}";
	}
	
	// table
	$table_dbinfo	= $SITE['th'].$prefix.'info';
	$table_logon	= $SITE['th'].'logon';
	
	// 저장 후 메시지 뿌리기 위해
	$msg = '';
	if(isset($_REQUEST['msg'])) $msg = $_REQUEST['msg'];
	
/*	if($_SERVER['REMOTE_ADDR'] == '61.35.254.195'){
		print_r($_REQUEST);	exit;
	}*/

	// 3. info 테이블 정보 가져와서 $dbinfo로 저장
	if(isset($_REQUEST['db'])){
		$sql = "SELECT * FROM {$table_dbinfo} WHERE db='".db_escape($_REQUEST['db'])."' LIMIT 1";
		$dbinfo = db_arrayone($sql) or back('사용하지 않은 DB입니다. 메인페이지로 이동합니다.','/');
		
		$table = $dbinfo['db'];

		// redirect 유무
		if(isset($dbinfo['redirect']) && $dbinfo['redirect']) go_url($dbinfo['redirect']);

		$dbinfo['table']	= $SITE['th'].$prefix.'_'.$dbinfo['db']; // 게시판 테이블
		$dbinfo['table_cate']= $dbinfo['table'].'_cate';
	} else {
		back('DB 값이 없습니다');
	}
	
	// 업로드 기본 디렉토리 설정
	if(isset($dbinfo['upload_dir']) && trim($dbinfo['upload_dir'])){
		$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']).'/'.$dbinfo['table'];
	} else {
		$dbinfo['upload_dir'] = $thisPath.'upload/'.$dbinfo['table'];
	}

	// 관리자 메일 발송을 위해 메일가져옮
	if( isset($dbinfo['enable_adm_mail']) && $dbinfo['enable_adm_mail'] == 'Y' and (isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'write' or $_REQUEST['mode'] == 'reply')) ){
		$sql = "select email from {$table_logon} where uid='{$dbinfo['bid']}' limit 1";
		$dbinfo['email'] = db_resultone($sql,0,'email');
	}
	
	//=================================================================================
	// 2014 대학생홍보대사 게시판 암호 관련
	//=================================================================================
	switch(isset($_REQUEST['mode']) ? $_REQUEST['mode'] : ''){
		case 'reply':
		case 'write':
		case 'modify':
		if( (isset($_REQUEST['db']) && $_REQUEST['db'] == '2014ambsdrbd') || (isset($_REQUEST['db']) && $_REQUEST['db'] == '2014ambsdrmv') ){
			switch(isset($_REQUEST['cateuid']) ? $_REQUEST['cateuid'] : ''){
				case '1':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'yeskb_1fu')
						back('1조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				case '2':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'rhflkb_2fu')
						back('2조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				case '3':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'qltmxkkb_3fu')
						back('3조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				case '4':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'qhffokb_4fu')
						back('4조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				case '5':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'sparkkb_5fu')
						back('5조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				case '6':
					if(isset($_POST['bd_passwd']) && $_POST['bd_passwd'] != 'tkrlkb_6fu')
						back('6조 홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
					break;
				default :
					back('홍보대사 게시판 암호가 잘 못 되었습니다. 다시 확인 해 주세요.');
			}//end switch
		}// end if
	}//end switch
	//=================================================================================
	
	

	// 넘어온값 기본 처리
	$qs=array(
		'title' =>  'post,trim,notnull=' . urlencode('제목을 입력하시기 바랍니다.'),
	);
	
	//davej.............2018/12/02
	if (!isset($_SESSION['sePriv']['운영자']) and ( (isset($dbinfo['db']) && substr($dbinfo['db'], 0, 5) == 'cheer') or (isset($dbinfo['db']) && substr($dbinfo['db'], 0, 5) == 'event')) ){
		// $qs 추가, 체크후 값 가져오기
		$qs=array(
				'title' =>  'post,trim,notnull=' . urlencode('제목을 입력하시기 바랍니다.'),
				'data1' =>  'request,trim,notnull=' . urlencode('연락처를 입력하여 주세요.')
			);
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch(isset($_REQUEST['mode']) ? $_REQUEST['mode'] : ''){
	case 'reply':
		if (isset($_POST['userid']) && ($_POST['userid'] == 'rgergerger' or preg_match("/^[a-z A-Z]+$/", $_POST['title']))){
			back('스팸글 방지를 위해 영문 제목 글쓰기가 금지되었습니다. \\n\\n한글을 포함한 제목으로 설정해 주시기 바랍니다.', '/');
			exit;
		}
		$uid = reply_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_REQUEST['goto'])){
			$goto = $_REQUEST['goto'];
		} elseif (isset($dbinfo['goto_reply'])){
			$goto = $dbinfo['goto_reply'];
		} else {
			$goto = $thisUrl.'read.php?' . href_qs('uid='.$uid,$qs_basic);
		}
		back('',$goto);
		break;
	case 'write':
		if (isset($_POST['userid']) && ($_POST['userid'] == 'rgergerger' or preg_match("/^[a-z A-Z]+$/", $_POST['title']))){
			back('스팸글 방지를 위해 영문 제목 글쓰기가 금지되었습니다. \\n\\n한글을 포함한 제목으로 설정해 주시기 바랍니다.', '/');
			exit;
		}
		//=====================================================================
		// (2010kbintern) 카테고리 보이기 정보가져오기....2010-01-08
		//=====================================================================
		if ( isset($dbinfo['db']) && $dbinfo['db'] == '2010kbintern' ){
			$sql_kbintern = "select * from {$dbinfo['table']}_cate where comment='보이기' ";
			$list_cateuid = db_arrayone($sql_kbintern) or back('신청기간이 아닙니다. 다음에 이용해 주세요.');
			/*if ($_SERVER['REMOTE_ADDR'] == '59.150.136.98'){
				print_r($list_cateuid);exit;
			}*/
			if (isset($list_cateuid['uid'])){
				$sql_kbintern = "SELECT count(uid) as cnt FROM {$dbinfo['table']} WHERE cateuid = '{$list_cateuid['uid']}' ";
				$list_kbintern = db_arrayone($sql_kbintern);
				if ($list_kbintern['cnt'] >= 200 ) back("선착순 200명 모집이 완료 되었습니다.성원에 감사드립니다.");
			}
		}
		//=====================================================================

		$uid = write_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_REQUEST['goto'])){
			$goto = $_REQUEST['goto'];
		} elseif (isset($dbinfo['goto_write'])){
			$goto = $dbinfo['goto_write'];
		} else {
			$goto = $thisUrl.'read.php?' . href_qs('uid='.$uid,$qs_basic);
		}
		
		back($msg,$goto);
		break;
	case 'modify':
		modify_ok($dbinfo, $qs);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_REQUEST['goto'])){
			$goto = $_REQUEST['goto'];
		} elseif (isset($dbinfo['goto_modify'])){
			$goto = $dbinfo['goto_modify'];
		} else {
			$goto = $thisUrl.'read.php?' . href_qs('uid='.$_REQUEST['uid'],$qs_basic);
		}

		back('',$goto);
		break;
	case 'delete':
		
		delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_REQUEST['goto'])){
			$goto = $_REQUEST['goto'];
		} elseif (isset($dbinfo['goto_delete'])){
			$goto = $dbinfo['goto_delete'];
		} else {
			$goto = $thisUrl.'list.php?' . href_qs('uid=',$qs_basic);
		}

		back('',$goto);
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		if( isset($_REQUEST['mode']) and preg_match('/^[a-z0-9\-\_]+$/',$_REQUEST['mode']) and function_exists('mode_'.$_REQUEST['mode']) ){
			$func = 'mode_'.$_REQUEST['mode'];
			$func();
		} else {
			back('잘못된 요청입니다.');
		}
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function reply_ok(&$dbinfo, $qs){
	global $PlayerCateBoard, $db_conn, $SITE;
	
	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	if(isset($_POST['phpsess']) && $_POST['phpsess'] != substr(session_id(),0,-5)){
		back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바랍니다.');
	}
	
	// $qs 추가, 체크후 값 가져오기
	$qs['uid']	= 'post,tirm,notnull=' . urlencode('답변할 게시물의 고유넘버가 넘어오지 않았습니다.');
	$qs=check_value($qs);
	
	// 자동 글쓰기 방지 인증 ........ davej.......2014-10-25
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['captcha'])){
		if (empty($_SESSION['cap_code']) || $_POST['captcha'] != $_SESSION['cap_code']){
			back("자동 글쓰기 방지 인증 값이 맞지 않아 내용이 초기화 됩니다.");
		}
	}

	// 부모글 가져오기
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}'";
	$list = db_arrayone($sql) or back('답변할 DB가 없습니다');
	if(isset($list['type']) && $list['type'] == 'info'){
		back('공지글에는 답변글을 올리실 수 없습니다.');
	}
	
	//////////////////////////////////////
	// 카테고리 정보 가져와 dbinfo 재 설정
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y' and isset($dbinfo['enable_cateinfo']) && $dbinfo['enable_cateinfo'] == 'Y' and isset($list['cateuid']) && $list['cateuid']){
		//============================================================================
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard => /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================

		$sql = "select * from {$dbinfo['table_cate']} where uid='".db_escape($list['cateuid'])."' LIMIT 1";
		$cateinfo = db_arrayone($sql);

		if(isset($cateinfo['bid']) && $cateinfo['bid']>0){
			$dbinfo['cid'] = $cateinfo['bid'];
		}
		// 나머지 dbinfo값 일괄 변경
		$aTmp = array('priv_reply','enable_userid','enable_upload','enable_uploadextension','enable_uploadmust','enable_adm_mail');
		foreach($aTmp as $tmp_field){
			if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !==NULL && $cateinfo[$tmp_field] != '0'){
				$dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
			}
		}
	} // end if
	//////////////////////////////////////	
	
	// 권한 검사
	if(!privAuth($dbinfo, 'priv_reply')){
		back('답변 글을 작성하실 권한이 없습니다.');
	}
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$sql_set = '';
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'gisu' :
					$qs['gisu'] = $_SESSION['seGisu'] ?? '';
					break;
				case 'url' :
					$qs['url'] = check_url(isset($_POST['url']) ? $_POST['url'] : '');
					break;			
				// board2 reply
				case 're' : // re값 구하는 함수 호출
					$qs['re'] = userReplyRe($dbinfo['table'], $list['num'], $list['re']);
					break;
				case 'num' : // 부모 글과 동일
					$qs['num'] = $list['num'];
					break;
				case 'cateuid' : // 부모 글과 동일
					$qs['cateuid'] = $list['cateuid'];
					break;
				case 'catetitle' : // 부모 글과 동일
					$qs['catetitle'] = $list['catetitle'];
					break;					
				case 'type' : // 부모 글과 동일
					$qs['type']	= $list['type'];
					break;
				
				// board2 write
				case 'priv_read' :
					if($dbinfo['enable_privread'] == 'Y'){
						$qs[$value] = str_replace(' ','',isset($_POST[$value]) ? $_POST[$value] : ''); // 공백제거
						$qs[$value] = str_replace(',,',',',$qs[$value]); // 콤머두개 콤머하나로
					}
					break;
				case 'priv_hidelevel' : // 정수값으로
					$qs['priv_hidelevel'] = (int)(isset($_POST['priv_hidelevel']) ? $_POST['priv_hidelevel'] : 0);
					break;
					
				// slist write
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])){
						$qs['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $qs['content']);
						$qs['content'] = strip_javascript($qs['content']); //davej...........script 태그 삭제
					} elseif (isset($_POST['content'])){
						$_POST['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $_POST['content']);
						$_POST['content'] = strip_javascript($_POST['content']); //davej...........script 태그 삭제
					}
					break;				
				case 'title' : //davej.........모든 태그 삭제
					if(isset($qs['title'])){
						$qs['title'] = strip_tags($qs['title']);
					} elseif(isset($_POST['title'])){
						$_POST['title'] = strip_tags($_POST['title']);
					}
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!isset($_POST['docu_type']) || !$_POST['docu_type']){
						$_POST['docu_type']=$dbinfo['default_docu_type'];
					}
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html'){
						$_POST['docu_type']='text';
					}
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' :
					if(isset($_SESSION['seUid'])){
						switch($dbinfo['enable_userid']){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
						}
					} elseif ((isset($dbinfo['priv_reply']) && $dbinfo['priv_reply'] > 0) && !isset($_SESSION['seUid'])){
							back("세션이 끊겼습니다. 로그인 후 사용이 가능합니다.");
					}
					break;
				case 'email' :
					if(isset($_POST['email'])){
						$qs['email'] = check_email($_POST['email']);
					} elseif (isset($_SESSION['seUid'])){
						$qs['email']	= $_SESSION['seEmail'] ?? '';
					}
					break;					

					
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($qs['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
				}
			} elseif(isset($_POST[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($_POST['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
				}
			}
		} // end foreach
	} // end if
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(04/12/28)
	/////////////////////////////////
	$sql_set_file = '';
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = $dbinfo['upload_dir'] . '/' . (int)(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0);

		// 사용변수 초기화
		$POSTFILE	= array(); // 업로드 폼값 다시 저장
		$upfiles	= array(); // 업로드 결과 저장
		$upfiles_totalsize=0; // 업로드된 총 파일 사이즈
		if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'Y'){
			$POSTFILE['upfile'] = &$_FILES['upfile']; // upfile 하나만 업로드
		} else {
			$POSTFILE = &$_FILES; // 모든 업로두 파일
		}
		
		// 업로드 파일 처리
		foreach($POSTFILE as $key => $value){
			if(isset($value['name']) && $value['name']) { // 파일이 업로드 되었다면
				if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($value['name'],'.'), 1)); //확장자
					if(!in_array($ext,$allow_extension)) continue;
				}
				if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
					AND !is_array(getimagesize($value['tmp_name'] ?? '')) )
					continue;
				$upfiles[$key]=file_upload($key,$updir);
				$upfiles_totalsize += $upfiles[$key]['size'];
			}
		} // end foreach
		
		// 에러 처리
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and $upfiles_totalsize == 0){
			if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
				back('다음의 파일 확장자만 업로드 가능합니다.\\n'.$dbinfo['enable_uploadextension']);
			} elseif ( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'){
				back('이미지파일을 선택하여 업로드하여 주시기 바랍니다');
			} else {
				back('파일이 업로드 되지 않았습니다');
			}
		}
		
		// $sql_set_file 생성
		if($upfiles_totalsize) $sql_set_file = ', upfiles=\''.db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	} // end if
	/////////////////////////////////

	$sql="INSERT INTO {$dbinfo['table']} SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	// E-Mail 전송
	if( (isset($dbinfo['enable_adm_mail']) && $dbinfo['enable_adm_mail'] == 'Y') or (isset($dbinfo['enable_rec_mail']) && $dbinfo['enable_rec_mail'] == 'Y') ){
		if(isset($dbinfo['enable_rec_mail']) && $dbinfo['enable_rec_mail'] == 'Y'){
			// dbinfo['email']에 부모글 메일 주소 추가
			if(isset($list['email']) && check_email($list['email'])){
				if(isset($dbinfo['email']) && $dbinfo['email']){
					$dbinfo['email'] .= ','.$list['email'];
				} else {
					$dbinfo['email'] = $list['email'];
				}
			}
		}

		$mail = new mime_mail;

		$mail->from		= $dbinfo['email'] ?? '';
		$mail->name		= '게시판 자동메일';
		$mail->to		= $dbinfo['email'] ?? '';
		$mail->subject	= '[게시판-답변] '. (isset($qs['title']) ? $qs['title'] : '');
		if(isset($qs['docu_type']) && $qs['docu_type'] == 'html'){
			$mail->body	= "[". (isset($qs['userid']) ? $qs['userid'] : '') ."]님께서 다음과 같은 게시물을 남겼습니다.]<br><hr>제목:". (isset($qs['title']) ? $qs['title'] : '') ."<hr>". (isset($qs['content']) ? $qs['content'] : '') ."<hr>예상되는게시판주소:http://{$_SERVER['HTTP_HOST']}/sboard2/read.php?db=". (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') ."&uid={$uid}";
			$mail->html	= 1;
		} else {
			$mail->body	= "[". (isset($qs['userid']) ? $qs['userid'] : '') ."]님께서 다음과 같은 답변 게시물을 남겼습니다.]\n
제목:". (isset($qs['title']) ? $qs['title'] : '') ."\n
--------------------------------------------\n
". (isset($qs['content']) ? $qs['content'] : '') ."\n
--------------------------------------------
\n\n\n
예상되는게시판URL: http://{$_SERVER['HTTP_HOST']}/sboard2/read.php?db=". (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') ."&uid={$uid}";
			$mail->html	= 0;
		}
		$mail->send();
	}

	return $uid;
} // end func

function write_ok(&$dbinfo, $qs){
	global $PlayerCateBoard, $db_conn, $SITE;

	// 같은글 등록 막기
	$user_id = isset($_POST['userid']) ? $_POST['userid'] : (isset($_SESSION['seName']) ? $_SESSION['seName'] : '');
	$sql = "SELECT uid FROM {$dbinfo['table']} WHERE title='".db_escape($_POST['title']) . "' and userid='".db_escape($user_id) . "' and DATE_FORMAT(FROM_UNIXTIME(rdate),'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d') ";
	$list = db_arrayone($sql);
	if(isset($list['uid'])){
		back('같은 날, 같은 제목의 글이 이미 등록 되어 있습니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바랍니다.');
	}

	$sql_where = ' 1 '; // init
	// 스팸글쓰기 거부 - phpsess 넘어온값과 session_id와 비교
	if(isset($_POST['phpsess']) && $_POST['phpsess'] != substr(session_id(),0,-5))
		back('잘못된 요청입니다.\\n계속 같은 메시지가 나오신다면,\\n웹브라우저를 새로 실행하여 작성하여 주시기 바랍니다.');

	//////////////////////////////////////
	// 카테고리 정보 가져와 dbinfo 재 설정
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y' and isset($dbinfo['enable_cateinfo']) && $dbinfo['enable_cateinfo'] == 'Y' and isset($_REQUEST['catelist'])){
		//============================================================================
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard => /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================

		$sql = "select * from {$dbinfo['table_cate']} where uid='".db_escape($_REQUEST['catelist'])."' LIMIT 1";
		$cateinfo = db_arrayone($sql) or back('없는 카테고리를 선택하였습니다.');

		if(isset($cateinfo['bid']) && $cateinfo['bid']>0) $dbinfo['cid'] = $cateinfo['bid'];
		// 나머지 dbinfo값 일괄 변경
		$aTmp = array('priv_writeinfo','priv_write','enable_userid','enable_upload','enable_uploadextension','enable_uploadmust','enable_adm_mail');
		foreach($aTmp as $tmp_field){
			if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !==NULL && $cateinfo[$tmp_field] != '0') $dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
		}
	} // end if
	//////////////////////////////////////	

	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);
	
	// 자동 글쓰기 방지 인증 ........ davej.......2014-10-25
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['captcha'])){
		if (empty($_SESSION['cap_code']) || $_POST['captcha'] != $_SESSION['cap_code']) back("자동 글쓰기 방지 인증 값이 맞지 않아 내용이 초기화 됩니다.");
	}

	// 권한 검사
	if(isset($dbinfo['enable_type']) && $dbinfo['enable_type'] == 'Y' and isset($_POST['type']) && $_POST['type'] == 'info'){
		//echo "<br><br><br><br><br>======================== =>  권한 검사 > if befor <br>";
		if(!privAuth($dbinfo, 'priv_writeinfo')) back('공지글을 작성하실 권한이 없습니다.');
		//echo "<br><br><br><br><br>======================== =>  권한 검사 > if after <br>";
	} else {
		//echo "<br><br><br><br><br>======================== =>  권한 검사 > else befor <br>";
		if(!privAuth($dbinfo, 'priv_write')) back('글을 작성하실 권한이 없습니다.');
		//echo "<br><br><br><br><br>======================== =>  권한 검사 > else after <br>";
	}
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array( 'uid', 're', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$sql_set = '';
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'gisu' :
					$qs['gisu'] = $_SESSION['seGisu'] ?? '';
					break;
				case 'url' :
					$qs['url'] = check_url(isset($_POST['url']) ? $_POST['url'] : '');
					break;
				// board2 write
				case 'cateuid' : // catelist에서 선택한 값을
					if(isset($_POST['catelist'])){
						$qs['cateuid'] = $_POST['catelist'];
					} elseif (isset($_POST['cateuid'])){
						$qs['cateuid'] = $_POST['cateuid'];
					}
					// 해당 카테고리가 있는지 체크
					if(isset($qs['cateuid']) && $qs['cateuid']){
				
						//============================================================================
						//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
						// $PlayerCateBoard => /sinc/config.php 파일에 있음
						if( in_array($dbinfo['db'], $PlayerCateBoard) ){
							$dbinfo['table_cate']	= "`savers_secret`.player";
						}
						//============================================================================
				
						$sql="select uid from {$dbinfo['table_cate']} where uid='".db_escape($qs['cateuid'])."' LIMIT 1";
						if(!db_arrayone($sql)) back('선택한 카테고리가 없습니다.');
					}					
					break;
				case 'catetitle' :
					if(isset($_POST['catelist'])){
						$qs['cateuid'] = $_POST['catelist'];
					} elseif (isset($_POST['cateuid'])){
						$qs['cateuid'] = $_POST['cateuid'];
					}
					// 해당 카테고리가 있는지 체크
					if(isset($qs['cateuid']) && $qs['cateuid']){
						$sql="select title from {$dbinfo['table_cate']} where uid='".db_escape($qs['cateuid'])."'";
						$qs['catetitle'] = db_resultone($sql,0,'title');
					}
					break;	
				case 'priv_read' :
					if(isset($dbinfo['enable_privread']) && $dbinfo['enable_privread'] == 'Y'){
						$qs[$value] = str_replace(' ','',isset($_POST[$value]) ? $_POST[$value] : ''); // 공백제거
						$qs[$value] = str_replace(',,',',',$qs[$value]); // 콤머두개 콤머하나로
					}
					break;
				case 'priv_hidelevel' : // 정수값으로
					$qs['priv_hidelevel'] = (int)(isset($_POST['priv_hidelevel']) ? $_POST['priv_hidelevel'] : 0);
					break;
				case 'type' :
					if(isset($dbinfo['enable_type']) && $dbinfo['enable_type'] == 'Y' and isset($_POST['writeinfo']) && $_POST['writeinfo'] == 'info')
						$qs['type']	= 'info';
					else $qs['type']	= 'docu';
					break;
					
				// slist write
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])){
						$qs['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $qs['content']);
						$qs['content'] = strip_javascript($qs['content']); //davej...........script 태그 삭제
					} elseif (isset($_POST['content'])){
						$_POST['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $_POST['content']);
						$_POST['content'] = strip_javascript($_POST['content']); //davej...........script 태그 삭제
					}
					break;				
				case 'title' : //davej.........모든 태그 삭제
					if(isset($qs['title'])){
						$qs['title'] = strip_tags($qs['title']);
					} elseif (isset($_POST['title'])){
						$_POST['title'] = strip_tags($_POST['title']);
					}
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!isset($_POST['docu_type']) || !$_POST['docu_type']){
						$_POST['docu_type']= isset($dbinfo['default_docu_type']) ? $dbinfo['default_docu_type'] : 'text';
					}
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html'){
						$_POST['docu_type']='text';
					}
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']} where  $sql_where ";
					$maxNumResult = db_resultone($sql,0,'max(num)');
					$qs['num'] = ($maxNumResult === null) ? 1 : $maxNumResult + 1;
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' :
					if(isset($_SESSION['seUid'])){
						switch(isset($dbinfo['enable_userid']) ? $dbinfo['enable_userid'] : ''){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
						}
					} elseif ((isset($dbinfo['priv_write']) && ($dbinfo['priv_write'] == '회원' or $dbinfo['priv_write'] == '운영자' )) && !isset($_SESSION['seUid'])){
							back("세션이 끊겼습니다. 로그인 후 사용이 가능합니다.");
					}
					break;
				case 'email' :
					if(isset($_POST['email'])){
						$qs['email'] = check_email($_POST['email']);
					} elseif (isset($_SESSION['seUid'])){
						$qs['email'] = $_SESSION['seEmail'] ?? '';
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($qs['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
				}
			} elseif(isset($_POST[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($_POST['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
				}
			}
		} // end foreach
	} // end if
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(04/12/28)
	/////////////////////////////////
	$sql_set_file = '';
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = $dbinfo['upload_dir'] . '/' . (int)(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0);

		// 사용변수 초기화
		$POSTFILE	= array(); // 업로드 폼값 다시 저장
		$upfiles	= array(); // 업로드 결과 저장
		$upfiles_totalsize=0; // 업로드된 총 파일 사이즈
		if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'Y'){
			$POSTFILE['upfile'] = &$_FILES['upfile']; // upfile 하나만 업로드
		} else {
			$POSTFILE = &$_FILES; // 모든 업로두 파일
		}
		
		// 업로드 파일 처리
		foreach($POSTFILE as $key => $value){
			if(isset($value['name']) && $value['name']) { // 파일이 업로드 되었다면
				if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($value['name'],'.'), 1)); //확장자
					if(!in_array($ext,$allow_extension)) continue;
				}
				if( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
					AND !is_array(getimagesize($value['tmp_name'] ?? '')) ){
					continue;
				}
				$upfiles[$key]=file_upload($key,$updir);
				$upfiles_totalsize += $upfiles[$key]['size'];
			}
		} // end foreach
		
		// 에러 처리
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and $upfiles_totalsize == 0){
			if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
				back('다음의 파일 확장자만 업로드 가능합니다.\\n'.$dbinfo['enable_uploadextension']);
			} elseif ( isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'){
				back('이미지파일을 선택하여 업로드하여 주시기 바랍니다');
			} else {
				back('파일이 업로드 되지 않았습니다');
			}
		}
		
		// $sql_set_file 생성
		if($upfiles_totalsize) $sql_set_file = ', upfiles=\''.db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	} // end if
	/////////////////////////////////

	$sql="INSERT INTO {$dbinfo['table']} SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	// E-Mail 전송
	if( isset($dbinfo['enable_adm_mail']) && $dbinfo['enable_adm_mail'] == 'Y' and isset($dbinfo['email']) && $dbinfo['email'] ){
		$mail = new mime_mail;

		$mail->from		= $dbinfo['email'];
		$mail->name		= '게시판 자동메일';
		$mail->to		= $dbinfo['email'];
		$mail->subject	= '[게시판자동메일] '. (isset($qs['title']) ? $qs['title'] : '');
		if(isset($qs['docu_type']) && $qs['docu_type'] == 'html'){
			$mail->body	= "[". (isset($qs['userid']) ? $qs['userid'] : '') ."]님께서 다음과 같은 게시물을 남겼습니다.]<br><hr>제목:". (isset($qs['title']) ? $qs['title'] : '') ."<hr>". (isset($qs['content']) ? $qs['content'] : '') ."<hr>예상되는게시판주소:http://{$_SERVER['HTTP_HOST']}/sboard2/read.php?db=". (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') ."&uid={$uid}";
			$mail->html	= 1;
		} else {
			$mail->body	= "[". (isset($qs['userid']) ? $qs['userid'] : '') ."]님께서 다음과 같은 답변 게시물을 남겼습니다.]\n
제목:". (isset($qs['title']) ? $qs['title'] : '') ."\n
--------------------------------------------\n
". (isset($qs['content']) ? $qs['content'] : '') ."\n
--------------------------------------------
\n\n\n
예상되는게시판URL: http://{$_SERVER['HTTP_HOST']}/sboard2/read.php?db=". (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') ."&uid={$uid}";
			$mail->html	= 0;
		}
		$mail->send();
	}
	
	// 포인트 적립해줌
	if( isset($_REQUEST['db']) && $_REQUEST['db'] == "cmmemo"){
		$remark = '힘내자!톡!톡! 1일 1회 포인트';
		// 이미 적립했는지
		$sql = "select uid from new21_account where bid='" . (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : '') . "' and rdate_date=curdate() and remark='{$remark}'";
		if(!db_resultone($sql,0,'uid'))
			new21PointDeposit(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0 , 50, $remark ,'적립');
	}
	// 포인트 적립해줌
	if( isset($_REQUEST['db']) && $_REQUEST['db'] == "cmletter"){
		$remark = '팬레터게시판 1일 1회 포인트';
		// 이미 적립했는지
		$sql = "select uid from new21_account where bid='" . (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : '') . "' and rdate_date=curdate() and remark='{$remark}'";
		if(!db_resultone($sql,0,'uid'))
			new21PointDeposit(isset($_SESSION['seUid']) ? $_SESSION['seUid'] : 0 , 50, $remark ,'적립');
	}	
	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs){
	global $PlayerCateBoard, $db_conn, $SITE;
	
	$sql_where = ' 1 '; // init
	$sw_change_cateuid = 0;
	
	// $qs 추가, 체크후 값 가져오기
	$qs['uid']	= 'post,trim,notnull=' . urlencode('고유번호가 넘어오지 않았습니다');
	$qs=check_value($qs);
	
	// 자동 글쓰기 방지 인증 ........ davej.......2014-10-25
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['captcha'])){
		if (empty($_SESSION['cap_code']) || $_POST['captcha'] != $_SESSION['cap_code']){
			back("자동 글쓰기 방지 인증 값이 맞지 않아 내용이 초기화 됩니다.");
		}
	}

	// 해당 게시물 읽어오기
	$sql = "SELECT *,PASSWORD('". db_escape((isset($_POST['passwd']) ? $_POST['passwd'] : '')) ."') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list=db_arrayone($sql) or back('수정할 게시물이 없습니다. 확인 바랍니다.');

	//////////////////////////////////////
	// 카테고리 정보 가져와 dbinfo 재 설정
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y' and isset($dbinfo['enable_cateinfo']) && $dbinfo['enable_cateinfo'] == 'Y' and isset($_POST['catelist'])){
		//============================================================================
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard => /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================

		$sql = "select * from {$dbinfo['table_cate']} where uid='".db_escape($_POST['catelist'])."' LIMIT 1";
		$cateinfo = db_arrayone($sql) or back('없는 카테고리를 선택하였습니다.');

		if(isset($cateinfo['bid']) && $cateinfo['bid']>0) $dbinfo['cid'] = $cateinfo['bid'];
		// 나머지 dbinfo값 일괄 변경
		$aTmp = array('priv_modify','enable_userid','enable_upload','enable_uploadextension','enable_uploadmust');
		foreach($aTmp as $tmp_field){
			if(isset($cateinfo[$tmp_field]) && $cateinfo[$tmp_field] !==NULL && $cateinfo[$tmp_field] != '0') $dbinfo[$tmp_field]	= $cateinfo[$tmp_field];
		}
	} // end if
	//////////////////////////////////////
	
	// 수정 권한 체크
	if(!privAuth($dbinfo,'priv_modify') ){
		if(isset($list['bid']) && $list['bid']>0){
			if( ($list['bid'] != ($_SESSION['seUid'] ?? 0)) or ('nobid' == substr($dbinfo['priv_modify'],0,5)) ){
				back('수정하실 권한이 없습니다.');
			}
		} else {
			if( isset($list['passwd']) && $list['passwd'] != $list['pass']){
				back('정확한 비밀번호를 입력 해 주십시오');
			}
		}
	} // end if

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
//	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$sql_set = '';
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'gisu' :
					if(isset($list['bid']) && $list['bid'] == ($_SESSION['seUid'] ?? 0)) { // 관리자권한으로 수정했으면 변경불가
						$qs['gisu'] = $_SESSION['seGisu'] ?? '';
					}
					break;
				case 'url' :
					$qs['url'] = check_url(isset($_POST['url']) ? $_POST['url'] : '');
					break;			
				// board2 modify
				case 'cateuid' : // catelist에서 선택한 값을
					// 답변이 아닌 경우에만 카테고리 수정 가능
					if(isset($_POST['catelist']) and strlen($list['re']) == 0){
						$qs['cateuid'] = $_POST['catelist'];
					} elseif (isset($_POST['cateuid']) and strlen($list['re']) == 0){
						$qs['cateuid'] = $_POST['cateuid'];
					} else {
						$qs['cateuid'] = $list['cateuid'];
					}
					if(isset($_POST['catelist']) && $list['cateuid'] != $qs['cateuid']){
						$sw_change_cateuid = 1;
					}
					break;
				case 'catetitle' :
					// 답변이 아닌 경우에만 카테고리 수정 가능
					if(isset($_POST['catelist']) and strlen($list['re']) == 0){
						$qs['cateuid'] = $_POST['catelist'];
					} elseif (isset($_POST['cateuid']) and strlen($list['re']) == 0){
						$qs['cateuid'] = $_POST['cateuid'];
					} else {
						$qs['cateuid'] = $list['cateuid'];
					}
					$sql="select title from {$dbinfo['table_cate']} where uid='".db_escape($qs['cateuid'])."'";
					$qs['catetitle'] = db_resultone($sql,0,'title');
					break;					
				case 'priv_read' :
					if(isset($dbinfo['enable_privread']) && $dbinfo['enable_privread'] == 'Y'){
						$qs[$value] = str_replace(' ','',isset($_POST[$value]) ? $_POST[$value] : ''); // 공백제거
						$qs[$value] = str_replace(',,',',',$qs[$value]); // 콤머두개 콤머하나로
					}
					break;
				// davej.........추가...........2007-01-19
				case 'type' :
					if(isset($dbinfo['enable_type']) && $dbinfo['enable_type'] == 'Y' and isset($_POST['writeinfo']) && $_POST['writeinfo'] == 'info'){
						$qs['type']	= 'info';
					} else {
						$qs['type']	= 'docu';
					}
					break;
				////////////////////////////////////////////
					
				// slist modify
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])){
						$qs['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $qs['content']);
						$qs['content'] = strip_javascript($qs['content']); //davej...........script 태그 삭제
					} elseif (isset($_POST['content'])){
						$_POST['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $_POST['content']);
						$_POST['content'] = strip_javascript($_POST['content']); //davej...........script 태그 삭제
					}
					break;				
				case 'title' : //davej.........모든 태그 삭제
					if(isset($qs['title'])){
						$qs['title'] = strip_tags($qs['title']);
					} elseif (isset($_POST['title'])){
						$_POST['title'] = strip_tags($_POST['title']);
					}
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!isset($_POST['docu_type']) || !$_POST['docu_type']) $_POST['docu_type']=isset($dbinfo['default_docu_type']) ? $dbinfo['default_docu_type'] : 'text';
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html'){
						$_POST['docu_type']='text';
					}
					break;
				case 'userid' :
					if(isset($list['bid']) && $list['bid'] == ($_SESSION['seUid'] ?? 0)) { // 관리자권한으로 수정했으면 변경불가
						switch(isset($dbinfo['enable_userid']) ? $dbinfo['enable_userid'] : ''){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
						}
					} elseif ((isset($dbinfo['priv_write']) && ($dbinfo['priv_write'] == '회원' or $dbinfo['priv_write'] == '운영자' )) && !isset($_SESSION['seUid'])){
							back("세션이 끊겼습니다. 로그인 후 사용이 가능합니다.");
					}
					break;
				case 'email' :
					if(isset($_POST['email'])){
						$qs['email']	= check_email($_POST['email']);
					} elseif (isset($list['bid']) && $list['bid'] == ($_SESSION['seUid'] ?? 0)){ // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'] ?? '';
					}
					break;
				case 'ip' :	$qs['ip'] = remote_addr(); break; // 정확한 IP 주소
				case 'priv_hidelevel' : // 정수값으로
					$qs['priv_hidelevel'] = (int)(isset($_POST['priv_hidelevel']) ? $_POST['priv_hidelevel'] : 0);
					break;				
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
		} // end foreach
	} // end if
	////////////////////////////////

	///////////////////////////////
	// 파일 업로드 - 변경(04/12/28)
	///////////////////////////////
	$sql_set_file = '';
	if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = $dbinfo['upload_dir'] . '/' . (int)(isset($list['bid']) ? $list['bid'] : 0);

		// 기존 업로드 파일 정보 읽어오기
		if(isset($list['upfiles']) && $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
		} else {
			$upfiles = [];
		}
		$upfiles_totalsize=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key => $value){
				if(isset($_REQUEST["del_{$key}"])){
						// 해당 파일 삭제
						if( isset($upfiles[$key]['name']) && is_file($updir .'/'. $upfiles[$key]['name']) ){
							@unlink($updir .'/'. $upfiles[$key]['name']);
							@unlink($updir .'/'. $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
						} elseif( isset($upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name']) ) { // 상위드렉토리에서
							@unlink($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
						}

						$upfiles_totalsize -= (isset($upfiles[$key]['size']) ? $upfiles[$key]['size'] : 0);
						unset($upfiles[$key]);
						if($upfiles_totalsize > 0){
							$sql_set_file = ", upfiles='".db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
						} else {
							$sql_set_file = ", upfiles='' ";
						}
				}
			}
		}

		// 사용변수 초기화
		$POSTFILE	= array(); // 업로드 폼값 다시 저장
		if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'Y') $POSTFILE['upfile'] = &$_FILES['upfile']; // upfile 하나만 업로드
		else $POSTFILE = &$_FILES; // 모든 업로두 파일
		
		// 업로드 파일 처리
		foreach($POSTFILE as $key => $value){
			if(isset($value['name']) && $value['name']) { // 파일이 업로드 되었다면
				if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($value['name'],'.'), 1)); //확장자
					if(!in_array($ext,$allow_extension)) continue;
				}
				if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image'
					AND !is_array(getimagesize($_FILES[$key]['tmp_name'] ?? '')) )
					continue;

				// 기존 업로드 파일이 있다면 삭제
				if( isset($upfiles[$key]['name']) && is_file($updir .'/'. $upfiles[$key]['name']) ){
					@unlink($updir .'/'. $upfiles[$key]['name']);
					@unlink($updir .'/'. $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
				}
				elseif( isset($upfiles[$key]['name']) && is_file($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name']) ) { // 상위드렉토리에서
					@unlink($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name']);
					@unlink($dbinfo['upload_dir'] . '/' . $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
				}

				// 업로드
				$upfiles_tmp=file_upload($key,$updir);
				$upfiles_totalsize = $upfiles_totalsize - (isset($upfiles[$key]['size']) ? $upfiles[$key]['size'] : 0) + $upfiles_tmp['size'];
				$upfiles[$key]=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} // end foreach
		
		// 업로드 성공 파일이 없을때
		if(isset($dbinfo['enable_uploadmust']) && $dbinfo['enable_uploadmust'] == 'Y' and $upfiles_totalsize == 0){
			if(isset($dbinfo['enable_uploadextension']) && $dbinfo['enable_uploadextension'])
				back('다음의 파일 확장자만 업로드 가능합니다.\\n'.$dbinfo['enable_uploadextension']);
			elseif(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] == 'image')
				back('이미지파일을 선택하여 업로드하여 주시기 바랍니다');
			else back('파일이 업로드 되지 않았습니다');
		}
		
		// $sql_set_file 생성
		if($upfiles_totalsize) $sql_set_file = ', upfiles=\''.db_escape(serialize($upfiles)) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	///////////////////////////////

	$sql = "UPDATE {$dbinfo['table']} SET
				rdate	= " . (isset($list['rdate']) ? $list['rdate'] : 'UNIX_TIMESTAMP()') . "
				{$sql_set_file}
				{$sql_set}
			WHERE
				uid='{$qs['uid']}'
		";

	db_query($sql);

	// 만일 카테고리가 변경되었다면, 그 이하 답변글들 역시 cateuid값 변경함
	if(isset($sw_change_cateuid) && $sw_change_cateuid ){
		db_query("update {$dbinfo['table']} set cateuid='".db_escape($qs['cateuid'])."',catetitle='".db_escape($qs['catetitle'])."' where num='{$list['num']}'");
	} // end if
	
	return true;
} // end func.

// 삭제
function delete_ok(&$dbinfo){
	global $PlayerCateBoard, $db_conn, $SITE, $thisUrl;
	
	$sql_where = ' 1 ' ;

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid' =>  'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
			'passwd' =>  'request,trim'
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *,PASSWORD('". db_escape((isset($qs['passwd']) ? $qs['passwd'] : '')) ."') as pass FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and $sql_where LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');

	//////////////////////////////////////
	// 카테고리 정보 가져와 dbinfo 재 설정
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y' and isset($dbinfo['enable_cateinfo']) && $dbinfo['enable_cateinfo'] == 'Y' and isset($list['cateuid']) && $list['cateuid']){
		//============================================================================
		//	07.10.04 davej............선수 테이블을 임시 카테고리 테이블로 사용...........
		// $PlayerCateBoard => /sinc/config.php 파일에 있음
		if( in_array($dbinfo['db'], $PlayerCateBoard) ){
			$dbinfo['table_cate']	= "`savers_secret`.player";
		}
		//============================================================================

		$sql = "select * from {$dbinfo['table_cate']} where uid='".db_escape($list['cateuid'])."' LIMIT 1";
		$cateinfo = db_arrayone($sql);

		if(isset($cateinfo['bid']) && $cateinfo['bid']>0) $dbinfo['cid'] = $cateinfo['bid'];
		if(isset($cateinfo['priv_delete']) && $cateinfo['priv_delete'] !==NULL && $cateinfo['priv_delete'] != '0') $dbinfo['priv_delete']	= $cateinfo['priv_delete'];
	} // end if
	//////////////////////////////////////	
	
	// 삭제 권한 체크
	if(!privAuth($dbinfo,'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if( 'nobid' == substr(isset($dbinfo['priv_delete']) ? $dbinfo['priv_delete'] : '',0,5) )
			back('삭제하실 수 없습니다.');
		elseif(isset($list['bid']) && $list['bid']>0){
			if($list['bid'] != ($_SESSION['seUid'] ?? 0))
				back('삭제하실 수 없습니다.');
		} else {
			if(isset($list['passwd']) && $list['passwd'] != $list['pass']){
				if(isset($_SERVER['QUERY_STRING']))
					back('비밀번호를 입력하여 주십시오',$thisUrl.'delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' and  $sql_where ");

	////////////////////////
	// 업로드 파일 삭제 준비
	$del_uploadfile = array(); // init
	$updir = $dbinfo['upload_dir'] . '/' . (int)(isset($list['bid']) ? $list['bid'] : 0);
	if(isset($list['upfiles']) && $list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$list['upfiles'];
			//$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
		}
		foreach($upfiles as $key => $value){
			if(isset($value['name']) && $value['name']){
				if( is_file($updir .'/'. $value['name']) )
					$del_uploadfile[] = $updir .'/'. $value['name'];
				elseif( is_file($dbinfo['upload_dir'] . '/' . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . '/' . $value['name'];
			} // end if
		} // end foreach
	} // end if
	
	// 답변글과 파일도 함께 삭제 준비
	if(isset($list['num']) && $list['num']){
		$rs_subre = db_query("SELECT * FROM {$dbinfo['table']} WHERE num='{$list['num']}' AND length(re) > length('{$list['re']}') AND locate('{$list['re']}',re) = 1");
		while($row=db_array($rs_subre)){
			if(isset($row['upfiles']) && $row['upfiles']){
				$updir = $dbinfo['upload_dir'] . '/' . (int)(isset($row['bid']) ? $row['bid'] : 0);
				$upfiles=unserialize($row['upfiles']);
				if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles['upfile']['name']=$row['upfiles'];
				}
				foreach($upfiles as $key => $value){
					if(isset($value['name']) && $value['name']){
						if( is_file($updir .'/'. $value['name']) )
							$del_uploadfile[] = $updir .'/'. $value['name'];
						elseif( is_file($dbinfo['upload_dir'] . '/' . $value['name']) )
							$del_uploadfile[] = $dbinfo['upload_dir'] . '/' . $value['name'];
					} // end if
				} // end foreach
			} // end if
		} // end while
	
		// 서브그룹도 삭제
		db_query("DELETE FROM {$dbinfo['table']} WHERE num='{$list['num']}' AND length(re) > length('{$list['re']}') AND locate('{$list['re']}',re) = 1");
	} // end if	
	
	// 업로드 파일 삭제
	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value){
			if(@unlink($value)){
				@unlink($value.'.thumb.jpg'); // thumbnail 삭제
				@rmdir(dirname($value)); // 상위드렉토리도 삭제 시도
			}
		}
	} // end if
	////////////////////////
	
	return true;
} // end func delete_ok()

function mode_vote(){
	global $dbinfo, $db_conn, $SITE;

	if(!isset($dbinfo['enable_vote']) || $dbinfo['enable_vote'] != 'Y') back('투표가 진행되고 있지 않습니다.');
	
	$qs=array(
			'vote' =>  'post,trim,notnull=' . urlencode('앨범 점수를 선택하여 주기 바랍니다.'),
			'uid' =>  'post,trim,notnull=' . urlencode('게시물 값이 없습니다.')
		);
	$qs=check_value($qs);

	// 점수 한계선 설정
	$qs['vote'] = (int) $qs['vote'];
	if($qs['vote']>5) $qs['vote']=5;
	if($qs['vote']<-5) $qs['vote']=-5;

	// 조회수 증가
	db_query("UPDATE {$dbinfo['table']} SET
					vote	=vote +{$qs['vote']},
					voteip	='".db_escape($_SERVER['REMOTE_ADDR']) . "'
				WHERE
					uid='{$qs['uid']}'
				AND
					voteip<>'".db_escape($_SERVER['REMOTE_ADDR']) . "'
				LIMIT 1
				");

	if(db_count_affected_rows())
		back('성공적으로 참여하였습니다.');
	else
		back('이미 참여하셨습니다.');

} // end func.

function mode_memowrite(){
	global $dbinfo, $db_conn, $SITE;

	// 권한 검사
	if( !isset($dbinfo['enable_memo']) || $dbinfo['enable_memo'] != 'Y' or !privAuth($dbinfo, 'priv_memowrite') )
		back('메모를 쓸 수 없습니다.');

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'pid' =>  'post,trim,notnull=' . urlencode('잘못된 요청입니다'),
			'title' =>  'post,trim,notnull=' . urlencode('내용 입력하시기 바랍니다.'),
		);
	$qs=check_value($qs);
	
	// 자동 글쓰기 방지 인증 ........ davej.......2014-10-25
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['captcha'])){
		if (empty($_SESSION['cap_code']) || $_POST['captcha'] != $_SESSION['cap_code']){
			back("자동 글쓰기 방지 인증 값이 맞지 않아 내용이 초기화 됩니다.");
		}
	}

	
	// 부모 게시물이 있는지 체크
	$sql = "select uid from {$dbinfo['table']} where uid='{$qs['pid']}'";
	$pid = db_resultone($sql,0,'uid') or back('메모를 남길 해당 게시물이 없습니다. 잘못된 요청입니다.');

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$sql_set = '';
	if($fieldlist = userGetAppendFields($dbinfo['table'].'_memo', $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// slist write (num 제외)
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])){
						$qs['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $qs['content']);
						$qs['content'] = strip_javascript($qs['content']); //davej...........script 태그 삭제
					} elseif (isset($_POST['content'])){
						$_POST['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $_POST['content']);
						$_POST['content'] = strip_javascript($_POST['content']); //davej...........script 태그 삭제
					}
					break;				
				case 'title' : //davej.........모든 태그 삭제
					if(isset($qs['title'])){
						$qs['title'] = strip_tags($qs['title']);
					} elseif (isset($_POST['title'])){
						$_POST['title'] = strip_tags($_POST['title']);
					}
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!isset($_POST['docu_type']) || !$_POST['docu_type']) $_POST['docu_type']=isset($dbinfo['default_docu_type']) ? $dbinfo['default_docu_type'] : 'text';
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html'){
						$_POST['docu_type']='text';
					}
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'] ?? 0;
					break;
				case 'userid' :
					if(isset($_SESSION['seUid'])){
						switch(isset($dbinfo['enable_userid']) ? $dbinfo['enable_userid'] : ''){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
						}
					} elseif ((isset($dbinfo['priv_write']) && ($dbinfo['priv_write'] == '회원' or $dbinfo['priv_write'] == '운영자' )) && !isset($_SESSION['seUid'])){
							back("세션이 끊겼습니다. 로그인 후 사용이 가능합니다.");
					}
					break;
				case 'email' :
					if(isset($_POST['email'])){
						$qs['email'] = check_email($_POST['email']);
					} elseif (isset($_SESSION['seUid'])){
						$qs['email'] = $_SESSION['seEmail'] ?? '';
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($qs['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
				}
			} elseif(isset($_POST[$value])){
				if($value == 'passwd'){
					$sql_set .= ", passwd	=PASSWORD('".db_escape($_POST['passwd']) . "') ";
				} else {
					$sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
				}
			}
		} // end foreach
	} // end if
	////////////////////////////////

	$sql="INSERT INTO {$dbinfo['table']}_memo SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	// 어느 페이지로 이동할 것인지 결정
	if(isset($_REQUEST['goto'])) $goto = $_REQUEST['goto'];
	//elseif($dbinfo['goto_memowrite']) $goto = $dbinfo['goto_memowrite'];
	back('',$goto);
} // end func memoWrite_ok

function mode_memoModify(){
	global $dbinfo, $db_conn, $SITE;

	// $qs 추가, 체크후 값 가져오기
	$qs=array(
			'uid' =>  'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
			'pid' =>  'request,trim,notnull=' . urlencode('고유번호가 넘어오지 않았습니다.')
		);
	$qs=check_value($qs);
	
	// 자동 글쓰기 방지 인증 ........ davej.......2014-10-25
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['captcha'])){
		if (empty($_SESSION['cap_code']) || $_POST['captcha'] != $_SESSION['cap_code']){
			back("자동 글쓰기 방지 인증 값이 맞지 않아 내용이 초기화 됩니다.");
		}
	}

	// 해당 게시물 읽어오기
	$sql = "SELECT *,PASSWORD('".db_escape((isset($_POST['passwd'])?$_POST['passwd']:'')) . "') as pass FROM {$dbinfo['table']}_memo WHERE uid='{$qs['uid']}' and pid='{$qs['pid']}' LIMIT 1";
	$list=db_arrayone($sql) or back('수정할 게시물이 없습니다. 확인 바랍니다.');
	
	// 수정 권한 체크
	if(!privAuth($dbinfo,'priv_delete') ){
		if(isset($list['bid']) && $list['bid']>0){
			if( $list['bid'] != ($_SESSION['seUid'] ?? 0) or 'nobid' == substr(isset($dbinfo['priv_modify'])?$dbinfo['priv_modify']:'',0,5) ){
				back('수정하실 권한이 없습니다.');
			}
		} else {
			if( isset($list['passwd']) && $list['passwd'] != $list['pass']){
				back('정확한 비밀번호를 입력 해 주십시오');
			}
		}
	} // end if

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type',
					'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	$sql_set = '';
	if($fieldlist = userGetAppendFields($dbinfo['table'].'_memo', $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// slist modify
				case 'content' : // <br>테그다음에 꼭 new line 들어가게
					if(isset($qs['content'])){
						$qs['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $qs['content']);
						$qs['content'] = strip_javascript($qs['content']); //davej...........script 태그 삭제
					} elseif (isset($_POST['content'])){
						$_POST['content'] = preg_replace("/<br>([^\r\n])/i", "<br>\n\\1", $_POST['content']);
						$_POST['content'] = strip_javascript($_POST['content']); //davej...........script 태그 삭제
					}
					break;				
				case 'title' : //davej.........모든 태그 삭제
					if(isset($qs['title'])){
						$qs['title'] = strip_tags($qs['title']);
					} elseif (isset($_POST['title'])){
						$_POST['title'] = strip_tags($_POST['title']);
					}
					break;				
				case 'docu_type' : // html값이 아니면 text로
					if(!isset($_POST['docu_type']) || !$_POST['docu_type']) $_POST['docu_type']=isset($dbinfo['default_docu_type']) ? $dbinfo['default_docu_type'] : 'text';
					$_POST['docu_type'] = strtolower($_POST['docu_type']);
					if($_POST['docu_type'] != 'html'){
						$_POST['docu_type']='text';
					}
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'userid' :
					if(isset($list['bid']) && $list['bid'] == ($_SESSION['seUid'] ?? 0)) { // 관리자권한으로 수정했으면 변경불가
						switch(isset($dbinfo['enable_userid']) ? $dbinfo['enable_userid'] : ''){
							case 'name'		: $qs['userid'] = $_SESSION['seName'] ?? ''; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname'] ?? ''; break;
							default			: $qs['userid'] = $_SESSION['seUserid'] ?? ''; break;
						}
					} elseif ((isset($dbinfo['priv_write']) && ($dbinfo['priv_write'] == '회원' or $dbinfo['priv_write'] == '운영자' )) && !isset($_SESSION['seUid'])){
							back("세션이 끊겼습니다. 로그인 후 사용이 가능합니다.");
					}
					break;
				case 'email' :
					if(isset($_POST['email'])){
						$qs['email']	= check_email($_POST['email']);
					} elseif (isset($list['bid']) && $list['bid'] == ($_SESSION['seUid'] ?? 0)) { // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'] ?? '';
					}
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . db_escape($qs[$value]) . '\' ';
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . db_escape($_POST[$value]) . '\' ';
		} // end foreach
	}
	
	$sql = "UPDATE {$dbinfo['table']}_memo SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set}
			WHERE
				uid='{$qs['uid']}'
		";
	db_query($sql);

	// 어느 페이지로 이동할 것인지 결정
	if(isset($_REQUEST['goto'])) $goto = $_REQUEST['goto'];
	//elseif($dbinfo['goto_memowrite']) $goto = $dbinfo['goto_memowrite'];
	back('',$goto);
} // end func.

// 메모 삭제
function mode_memodelete(){
	global $SITE, $dbinfo, $thisUrl, $qs_basic;
	
	$qs=array(
			'uid' =>  'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
			'pid' =>  'request,trim,notnull=' . urlencode('고유번호가 넘어오지 않았습니다.'),
			'passwd' =>  'request,trim'
		);
	$qs=check_value($qs);
	
	// 삭제 권한 체크와 해당 게시물 읽어오기
	$sql = "SELECT *,PASSWORD('".db_escape((isset($qs['passwd'])?$qs['passwd']:'')) . "') as pass FROM {$dbinfo['table']}_memo WHERE uid='{$qs['uid']}' and pid='{$qs['pid']}' LIMIT 1";
	$list = db_arrayone($sql) or back('이미 삭제되었거나 잘못된 요청입니다');
	if(!privAuth($dbinfo,'priv_delete')) {// 게시판 전체 삭제 권한을 가졌다면
		if( 'nobid' == substr(isset($dbinfo['priv_delete']) ? $dbinfo['priv_delete'] : '',0,5) )
			back('수정하실 수 없습니다.');
		elseif(isset($list['bid']) && $list['bid']>0){
			if($list['bid'] != ($_SESSION['seUid'] ?? 0))
				back('수정하실 권한이 없습니다.');
		} else {
			if(isset($list['passwd']) && $list['passwd'] != $list['pass']){
				if(isset($_SERVER['QUERY_STRING']))
					back('비밀번호를 입력하여 주십시오',$thisUrl.'delete.php?'.$_SERVER['QUERY_STRING']);
				else back('비밀번호를 정확히 입력하십시오');
			}
		}
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table']}_memo WHERE uid='{$qs['uid']}' ");

	// 어느 페이지로 이동할 것인지 결정
	if(isset($_REQUEST['goto'])){
		$goto = $_REQUEST['goto'];
	//elseif($dbinfo['goto_memowrite']) $goto = $dbinfo['goto_memowrite'];
	} else {
		$goto = $thisUrl.'read.php?' . href_qs('uid='.$_REQUEST['pid'],$qs_basic);
	}
	back('',$goto);
} // end func memodelete_ok

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

// 카테고리 새서브 RE값 구함
// 05/01/12
function userReplyRe($table, $num, $re){
	global $dbinfo;

	// 한 table에 여러 게시판 생성의 경우
	$sql_where = '';
	if (($dbinfo['table_name'] ?? '') != ($dbinfo['db'] ?? '')) {
		$sql_where = " db='".db_escape($dbinfo['db'])."' ";
	}
	if (($dbinfo['enable_type'] ?? '') == 'Y') {
		$sql_where = $sql_where ? $sql_where . " and type='docu' " : " type='docu' ";
	}
	if (!$sql_where) {
		$sql_where = " 1 ";
	}

	$sql = "SELECT re, right(re,1) FROM {$table} WHERE $sql_where and num='{$num}' AND length(re)=length('{$re}')+1 AND locate('{$re}', re) = 1 ORDER BY re DESC LIMIT 1";
	$row = db_arrayone($sql);

	if ($row) {
		$ord_head = substr($row['re'], 0, -1);
		if (ord($row['right(re,1)']) >= 255) {
			back("더이상 추가하실 수 없습니다");
		}
		$ord_foot = chr(ord($row['right(re,1)']) + 1);
		$re = $ord_head . $ord_foot;
	} else {
		$re .= "1";
	}
	return $re;
} // end func userReplyRe($table, $num, $re)

?>