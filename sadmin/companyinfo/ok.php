<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // file_upload(),remote_addr()
	'useCheck' => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? $table) .			//table 이름
				"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
				"&sc_string=" . urlencode(stripslashes(isset($sc_string) ? $sc_string : '')) . //search string
				"&team=" . ($_REQUEST['team'] ?? '').
				"&html_headtpl=" . (isset($html_headtpl) ? $html_headtpl : '').
				"&pid=" . ($_REQUEST['pid'] ?? '').
				"&pname=" . ($_REQUEST['pname'] ?? '').
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

	$table_companyinfo	= $SITE['th'] . 'companyinfo';	// 회사정보테이블
	$dbinfo	= array(
				'table' =>	$table_companyinfo,
				'enable_upload' =>	'Y',
				'enable_uploadextension' =>	'gif',
				'goto_modify' =>	'write.php'
			);
	$dbinfo['upload_dir'] = $_SERVER['DOCUMENT_ROOT'].'/smember/companytax/upload/'.$dbinfo['table'];

	// 넘어온값 기본 처리
	$qs=array(
			'c_num1' =>	'post,trim,checkNumber',
			'c_num2' =>	'post,trim,checkNumber',
			'c_num3' =>	'post,trim,checkNumber',
			'c_idnum1' =>	'post,trim,checkNumber',
			'c_idnum2' =>	'post,trim,checkNumber',
			'c_name' =>	'post,trim,notnull='	. urlencode('회사명을 입력하시기 바랍니다.'),
			'c_owner' =>	'post,trim,notnull='	. urlencode('대표자성명을 입력하시기 바랍니다.'),
			'c_address' =>	'post,trim,notnull='	. urlencode('사업장주소를 입력하시기 바랍니다.'),
			'c_kind' =>	'post,trim,notnull='	. urlencode('회사 업태를 입력하시기 바랍니다.'),
			'c_detail' =>	'post,trim,notnull='	. urlencode('회사 종목을 입력하시기 바랍니다.'),
			'c_tel' =>	'post,trim',
			'c_fax' =>	'post,trim',
			'tax_zip1' =>	'post,trim,checkNumber',
			'tax_zip2' =>	'post,trim,checkNumber',
			'tax_address' =>	'post,trim',
			'tax_tel' =>	'post,trim',
			'tax_fax' =>	'post,trim',
			'tax_hp' =>	'post,trim',
			'tax_email' =>	'post,trim',
			'comment' =>	'post,trim',
			'status' =>	'post,trim',
		);
	
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'modify':
		modify_ok($dbinfo, $qs);

		// 어느 페이지로 이동할 것인지 결정
		if($_REQUEST['goto']) $goto = $_REQUEST['goto'];
		elseif($dbinfo['goto_modify']) $goto = $dbinfo['goto_modify'];
		else $goto = $thisUrl.'read.php?'	. href_qs('uid='.$_REQUEST['uid'],$qs_basic);
		back('',$goto);
		break;
	default :
		back("잘못된 요청입니다.");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================

function modify_ok(&$dbinfo,$qs){
	$sql_where = ' 1 '; // init
	$qs=check_value($qs);
	$qs['uid']=1; // companyinfo의 uid는 늘 1임

	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='{$qs['uid']}' LIMIT 1";
	if(!$list=db_arrayone($sql)){
		// companyinfo의 uid는 늘 1이기에, 1인 데이터 삽입
		$sql = "insert into {$dbinfo['table']} set uid='{$qs['uid']}'";
		db_query($sql);
		$sql = "SELECT * FROM {$dbinfo['table']} WHERE uid='1' LIMIT 1";
		$list=db_arrayone($sql) or back('관리자에게 문의바람니다 . err 91');
	}
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	// bid, nume, re, passwd, type는 수정 불가
	$skip_fields = array( 'bid', 'num', 're', 'passwd', 'type',
					'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate', 'fdate');
	if($fieldlist = userGetAppendFields($dbinfo['table'], $skip_fields)){
		foreach($fieldlist as $value){
			// 해당 필드 데이터값 확정
			switch($value){
				case 'c_num' : // 사업자등록번호
					if($qs['c_num1']) // 사업자등록번호이면
						$qs['c_num'] = check_compnum($qs['c_num1'],$qs['c_num2'],$qs['c_num3'],'사업자등록번호가 잘못되었습니다.');
					else // 주민등록번호이면
						$qs['c_num'] = check_idnum($qs['c_idnum1'],$qs['c_idnum2'],'주민등록번호가 잘못되었습니다.');
					break;
				case 'c_idnum' : // 주번번호
					$qs['c_idnum'] = check_idnum($qs['c_idnum1'],$qs['c_idnum2'],'주민등록번호가 잘못되었습니다.');				
					break;
				case 'tax_zip' : // tax 우편번호
					if($qs['tax_zip1'])
						$qs['tax_zip'] = $qs['tax_zip1'].$qs['tax_zip2'];
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
				case 'ip' :	$qs['ip'] = remote_addr(); break; // 정확한 IP 주소
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ', '.$value.' ="'.$qs[$value] . '"';
			elseif(isset($_POST[$value])) $sql_set .= ', '.$value.' ="'.$_POST[$value] . '"';
		} // end foreach
	} // end if
	////////////////////////////////

	///////////////////////////////
	// 파일 업로드 - 변경(04/12/28)
	///////////////////////////////
	if( $dbinfo['enable_upload'] != 'N' and isset($_FILES) ){
		// 파일 업로드 드렉토리
		$updir = $dbinfo['upload_dir'] . '/'	. (int)$list['bid'];

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
						if( is_file($updir .'/' . $upfiles[$key]['name']) ){
							@unlink($updir .'/' . $upfiles[$key]['name']);
							@unlink($updir .'/' . $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
						}
						elseif( is_file($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name']) ) { // 상위드렉토리에서
							@unlink($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name']);
							@unlink($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
						}

						$upfiles_totalsize -= $upfiles[$key]['size'];
						unset($upfiles[$key]);
				}
			}
		}

		// 사용변수 초기화
		$POSTFILE	= array(); // 업로드 폼값 다시 저장
		if($dbinfo['enable_upload'] == 'Y') $POSTFILE['upfile'] = $_FILES['upfile']; // upfile 하나만 업로드
		else $POSTFILE = $_FILES; // 모든 업로두 파일
		
		// 업로드 파일 처리
		foreach($POSTFILE as $key =>	$value){
			if($value['name']) { // 파일이 업로드 되었다면
				if($dbinfo['enable_uploadextension']){
					$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
					$ext = strtolower(substr(strrchr($value['name'],'.'), 1)); //확장자 
					if(!in_array($ext,$allow_extension)) continue;
				}
				if( $dbinfo['enable_upload'] == 'image'
					AND !is_array(getimagesize($_FILES[$key]['tmp_name'])) )
					continue;

				// 기존 업로드 파일이 있다면 삭제
				if( is_file($updir .'/' . $upfiles[$key]['name']) ){
					@unlink($updir .'/' . $upfiles[$key]['name']);
					@unlink($updir .'/' . $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
				}
				elseif( is_file($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name']) ) { // 상위드렉토리에서
					@unlink($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name']);
					@unlink($dbinfo['upload_dir'] . '/'	. $upfiles[$key]['name'].'.thumb.jpg'); // thumbnail 삭제
				}

				// 업로드
				$upfiles_tmp=file_upload($key,$updir);
				$upfiles_totalsize = $upfiles_totalsize - $upfiles[$key]['size'] + $upfiles_tmp['size'];
				$upfiles[$key]=$upfiles_tmp;
				unset($upfiles_tmp);
			}
		} // end foreach
		
		// 업로드 성공 파일이 없을때
		if($dbinfo['enable_uploadmust'] == 'Y' and $upfiles_totalsize == 0){
			if($dbinfo['enable_uploadextension']) 
				back('다음의 파일 확장자만 업로드 가능합니다.\\n'.$dbinfo['enable_uploadextension']);
			elseif( $dbinfo['enable_upload'] == 'image')
				back('이미지파일을 선택하여 업로드하여 주시기 바랍니다');
			else back('파일이 업로드 되지 않았습니다');
		}
		
		// $sql_set_file 생성
		if($upfiles_totalsize) $sql_set_file = ", upfiles='".serialize($upfiles) . "', upfiles_totalsize='{$upfiles_totalsize}' ";
	} // end if
	///////////////////////////////

	$sql = "UPDATE {$dbinfo['table']} SET 
				rdate	=UNIX_TIMESTAMP()
				{$sql_set_file} 
				{$sql_set}
			WHERE 
				uid='{$qs['uid']}'
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
