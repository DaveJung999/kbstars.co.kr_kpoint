<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/17
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/11/10 Gemini AI	PHP 7+ 호환성 업데이트, 사용자 정의 db_* 함수 적용
// 04/08/17 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_value, check_idnum, check_compnum
	'useApp' => 1, // file_upload()
	'useClassSendmail' =>	1, // mime_mail
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $_SERVER['HTTP_HOST']); // $_SERFVER -> $_SERVER

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// $dbinfo 설정
	$dbinfo['table']	= $SITE['th'] . "companyinfo";
	$dbinfo['upload_dir'] = $_SERVER['DOCUMENT_ROOT']."/smember/companytax/upload/{$dbinfo['table']}";
	$dbinfo['enable_uploadextension']	= "gif,jpg";

	// 넘어온값 기본 처리
	$qs=array(
		"c_num1" =>	"post,trim",
		"c_num2" =>	"post,trim",
		"c_num3" =>	"post,trim",
		"c_idnum1" =>	"post,trim",
		"c_idnum2" =>	"post,trim",
		"c_name" =>	"post,trim,notnull=" . urlencode("회사명을 입력하시기 바랍니다."),
		"c_owner" =>	"post,trim,notnull=" . urlencode("대표자성명을 입력하시기 바랍니다."),
		"c_address" =>	"post,trim,notnull=" . urlencode("사업장주소를 입력하시기 바랍니다."),
		"c_kind" =>	"post,trim,notnull=" . urlencode("회사 업태를 입력하시기 바랍니다."),
		"c_detail" =>	"post,trim,notnull=" . urlencode("회사 종목을 입력하시기 바랍니다."),
		"c_tel" =>	"post,trim",
		"c_fax" =>	"post,trim",
		"tax_zip" =>	"post,trim",
		"tax_address" =>	"post,trim",
		"tax_tel" =>	"post,trim",
		"tax_fax" =>	"post,trim",
		"tax_hp" =>	"post,trim",
		"tax_email" =>	"post,trim",
		"comment" =>	"post,trim",
		"tax_name" =>	"post,trim",
		"status" =>	"post,trim",
	);
		
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		back('회사정보를 생성하였습니다','write.php?mode=modify&uid=1');
		break;
	case 'modify':
		modify_ok($dbinfo,$qs,'uid');
		back('수정하였습니다','write.php?mode=modify&uid=1');
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================

function write_ok(&$dbinfo, $qs){
	global $db_conn; // 사용자 정의 db_* 함수가 전역 DB 연결 객체를 사용한다면 필요함

	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);
	
	// 사업자등록번호가 정확한지 체크
	if($qs['c_num1']){
		if(!$qs['c_num'] = check_compnum($qs['c_num1'],$qs['c_num2'], $qs['c_num3']))
			back('사업자등록번호를 정확히 입력해 주세요.');
		
		$qs['c_idnum'] = $qs['c_idnum1'] .'-'.	$qs['c_idnum2'];
	} else { // 주민번호로 입력되었다면
		$qs['c_idnum'] = check_idnum($qs['c_idnum1'],$qs['c_idnum2']);
		// ereg_replace()는 PHP 7에서 제거되었으므로 preg_replace 사용 (이미 소스에 반영됨)
		$qs['c_num']	= preg_replace('/[^0-9]/','',$qs['c_idnum']);
	}
	
	// 동일 사업자등록번호가 등록되어 있으면 등록 불가
	// db_arrayone() 사용
	$sql = "select * from {$dbinfo['table']} where c_num='{$qs['c_num']}'";
	if(db_arrayone($sql)) back('이미 등록된 사업자등록번호 입니다.');
	
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');

	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				// board2 write
				case 'cateuid' : // catelist에서 선택한 값을
					$qs['cateuid'] = $_POST['catelist'];
					break;
				case 'priv_level' : // 정수값으로
					$qs['priv_level'] = (int)$_POST['priv_level'];
					break;
				case 'docu_type' : // html값이 아니면 text로
					if($_POST['docu_type'] and strtolower($_POST['docu_type']) != "html")	
						$_POST['docu_type']="text";
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'num' :
					$sql = "SELECT max(num) FROM {$dbinfo['table']}";
					// db_resultone() 사용
					$qs['num'] = db_resultone($sql,0,"max(num)") + 1;	
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'];
				case 'userid' :
					if($_SESSION['seUid']){
						switch($dbinfo['enable_userid']){
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($_SESSION['seUid']) $qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			} elseif(isset($_POST[$value])){
				if($value == 'passwd') $sql_set .= ", passwd	=password('{$_POST['passwd']}') ";
				else $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
			}
		} // end foreach
	} // end if
	////////////////////////////////
	/////////////////////////////////
	// 파일업로드 처리-추가(03/10/20)
	/////////////////////////////////
	if($dbinfo['enable_upload'] != 'N' and isset($_FILES)){
		$updir = $dbinfo['upload_dir'] . "/" . (int)$_SESSION['seUid'];

		// 사용변수 초기화
		$upfiles=array();
		$upfiles_totalsize=0;
		if($dbinfo['enable_upload'] == 'Y'){
			if($_FILES['upfile']['name']) { // 파일이 업로드 되었다면
				if($dbinfo['enable_uploadextension']) { // 특정 확장자만 사용가능하면
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($_FILES['upfile']['name'],"."), 1)); //확장자	
					if(in_array($ext,$allow_extension)){
						$upfiles['upfile']=file_upload("upfile",$updir);
						$upfiles_totalsize = $upfiles['upfile']['size'];
					}
				} else {
					$upfiles['upfile']=file_upload("upfile",$updir);
					$upfiles_totalsize = $upfiles['upfile']['size'];
				}
			}
		} else {
			foreach($_FILES as $key =>	$value){
				if($value['name']) { // 파일이 업로드 되었다면
					if($dbinfo['enable_uploadextension']){
						$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
						$ext = strtolower(substr(strrchr($value['name'],"."), 1)); //확장자	
						if(!in_array($ext,$allow_extension)) continue;
					}
					if( $dbinfo['enable_upload'] == 'image'	
						AND !is_array(getimagesize($_FILES[$key]['tmp_name'])) )
						continue;
					$upfiles[$key]=file_upload($key,$updir);
					$upfiles_totalsize += $upfiles[$key]['size'];
				}
			} // end foreach
		} // end if . . esle ..
		if($dbinfo['enable_uploadmust'] == 'Y' and sizeof($upfiles) == 0){
			if( $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		if($upfiles) $sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
		unset($upfiles);
	} // end if
	/////////////////////////////////

	$sql="INSERT INTO {$dbinfo['table']} SET
				uid		= 1,
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function modify_ok(&$dbinfo,$qs,$field){
	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);
	$qs['uid'] = 1; // 사이트 회사 정보는 companyinfo uid가 1임.

	// 수정 권한 체크와 해당 게시물 읽어오기
	// db_arrayone() 사용
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'";
	$list=db_arrayone($sql) or back("수정할 권한이 없습니다");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array('status', 'c_num', 'uid', 'bid', 'num', 're', 'passwd', 'type', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value) {	
				// board2 modify
				case 'cateuid' : // catelist에서 선택한 값을
					// 답변이 아닌 경우에만 카테고리 수정 가능
					if( $_POST['catelist'] and strlen($list['re']) == 0 ){
						$qs['cateuid'] =$_POST['catelist'];
						// 해당 카테고리가 있는지 체크
						if($qs['cateuid']){
							$sql="select * from {$dbinfo['table_cate']} where uid='{$qs['cateuid']}'";
							if(!db_arrayone($sql)) back('선택한 카테고리가 없습니다.');
						}
					}
					else $qs['cateuid'] = $list['cateuid'];	
					break;
				case 'priv_level' : // 정수값으로
					$qs['priv_level'] = (int)$_POST['priv_level'];
					break;
				case 'docu_type' : // html값이 아니면 text로
					if($_POST['docu_type'] and strtolower($_POST['docu_type']) != "html")	
						$_POST['docu_type']="text";
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'userid' :
					if($list['bid'] == $_SESSION['seUid']) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid']){
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid'] == $_SESSION['seUid']) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ", {$value} = '" . $qs[$value] . "' ";
			elseif(isset($_POST[$value])) $sql_set .= ", {$value} = '" . $_POST[$value] . "' ";
		} // end foreach
	} // end if
	////////////////////////////////

	///////////////////////////////
	// 파일 업로드 - 변경(03/10/20)
	///////////////////////////////
	if( $dbinfo['enable_upload'] != 'N' and isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = $dbinfo['upload_dir'] . "/" . (int)$list['bid'];

		// 기존 업로드 파일 정보 읽어오기
		if($list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
	
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
			}
		}
		$upfiles_totalsize=(int)$list['upfiles_totalsize'];

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>	$value){
				if($_REQUEST["del_{$key}"]) {	
						// 해당 파일 삭제
						if( is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) ){
							@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
						}
						elseif( is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) ){
							@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
						}

						$upfiles_totalsize -= $upfiles[$key]['size'];
						unset($upfiles[$key]);
				}
			}
		}

		// 업로드 파일 처리
		if($dbinfo['enable_upload'] == 'Y') { // 파일 하나 업로드라면
			if($_FILES['upfile']['name']) {	// 파일이 업로드 되었다면
				$ok_upload =0;
				if($dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($_FILES['upfile']['name'],"."), 1)); //확장자	
					if(in_array($ext,$allow_extension)) $ok_upload = 1;
				}
				else $ok_upload = 1;

				if($ok_upload){
					// 기존 업로드 파일이 있다면 삭제
					if( is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']) ){
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name']);
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles['upfile']['name'].".thumb.jpg"); // thumbnail 삭제
					}
					elseif( is_file($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']) ){
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name']);
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles['upfile']['name'].".thumb.jpg"); // thumbnail 삭제
					}

					// 업로드
					$upfiles_tmp=file_upload("upfile",$updir);
					$upfiles_totalsize	= $upfiles_tmp['size'];
					$upfiles['upfile']	= $upfiles_tmp;
					unset($upfiles_tmp);
				}
			}
		} else { // 복수 업로드라면,
			foreach($_FILES as $key =>	$value){
				if($value['name']) { // 파일이 업로드 되었다면
					if($dbinfo['enable_uploadextension']){
						$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
						$ext = strtolower(substr(strrchr($value['name'],"."), 1)); //확장자	
						if(!in_array($ext,$allow_extension)) continue;
					}
					if( $dbinfo['enable_upload'] == 'image'	
						AND !is_array(getimagesize($_FILES[$key]['tmp_name'])) )
						continue;
	

					// 기존 업로드 파일이 있다면 삭제
					if( is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']) ){
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name']);
						@unlink($dbinfo['upload_dir'] . "/{$list['bid']}/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
					}
					elseif( is_file($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']) ){
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name']);
						@unlink($dbinfo['upload_dir'] . "/" . $upfiles[$key]['name'].".thumb.jpg"); // thumbnail 삭제
					}

					// 업로드
					$upfiles_tmp=file_upload($key,$updir);
					$upfiles_totalsize = $upfiles_totalsize - $upfiles[$key]['size'] + $upfiles_tmp['size'];
					$upfiles[$key]=$upfiles_tmp;
					unset($upfiles_tmp);
				}
			} // end foreach
		} // end if . . else ..
		if($dbinfo['enable_uploadmust'] == 'Y' and sizeof($upfiles) == 0){
			if($dbinfo['enable_uploadextension'])	
				back("다음의 파일 확장자만 업로드 가능합니다.\\n{$dbinfo['enable_uploadextension']}");
			if( $dbinfo['enable_upload'] == 'image')
				back("이미지파일을 선택하여 업로드하여 주시기 바랍니다");
			else back("파일이 업로드 되지 않았습니다");
		}
		if($upfiles) $sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	///////////////////////////////

	$sql = "UPDATE {$dbinfo['table']} SET	
				rdate	=UNIX_TIMESTAMP()
				{$sql_set_file}	
				{$sql_set}
			WHERE	
				{$field}='{$qs[$field]}'
		";
	db_query($sql);

	return true;
} // end func.

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

?>