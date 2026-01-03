<?php
//=======================================================
// 설 명 : 게시판 처리(ok.php)
// 책임자 : 박선민 (), 검수: 04/07/23
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/23 박선민 마지막 수정
// 24/05/21 Gemini PHP 7 마이그레이션
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // check_email()
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp'	 => 1,
	'useImage' => 1, // thumbnail()
	'useClassSendmail' => 1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode'] ?? '') {
	case 'upload':
		$image_filename = upload_ok();
		$qs_basic = ''; // qs_basic 초기화 (필요시 값 재구성)
		$goto = $_REQUEST['goto'] ?? ($dbinfo['goto_write'] ?? "/common/js/htmlarea203/popups/insert_image_real.php?" . href_qs("image_filename={$image_filename}",$qs_basic));
		back("", $goto);
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function upload_ok()
{
	global $dbinfo;
	
	/////////////////////////////////
	// 파일업로드 처리-추가(03/10/20)
	/////////////////////////////////
	$updir = $_SERVER['DOCUMENT_ROOT'] ."/images_upload";

	// 사용변수 초기화
	$upfiles_totalsize=0;
	$dbinfo['enable_uploadextension'] = "gif,jpg,png,tif,jpeg";
	$image_filename = '';

	if(isset($_FILES['upfile']['name'])) { // 파일이 업로드 되었다면
		$allow_extension = explode(',',$dbinfo['enable_uploadextension']);
		$ext = strtolower(pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION)); //확장자
		
		//시간코드로 파일이름 변경
		$filename_tmp = time();
		$image_filename = $filename_tmp.".".$ext;
		$_FILES['upfile']['name'] = $image_filename;

		if(in_array($ext,$allow_extension)) {
			$upfile_info = file_upload("upfile",$updir);
			if ($upfile_info) {
				$upfiles_totalsize = $upfile_info['size'];
				
				//===========================================================
				//davej...............2008/10/16..............이미지 사이즈 줄이기............
				//===========================================================
				$thumbimagesize['0'] = 500;
				//$thumbimagesize['1'] = 500;
				
				$filepath = $updir."/".$image_filename;
				
				$upimage_size=@getimagesize($filepath);
				
				if ($upimage_size && (intval($upimage_size['0']) > intval($thumbimagesize['0']))){

					$new_width	= intval($thumbimagesize['0']);
					// 비율에 따른 높이 계산 (원본 비율 유지)
					$new_height = intval($upimage_size['1'] * ($new_width / $upimage_size['0']));
					
					$im=thumbnail($filepath, $new_width, $new_height);
					if ($im) {
						ImageJpeg($im, $filepath.'.resize.jpg'); // 파일저장
						ImageDestroy($im);
						if( is_file($filepath.'.resize.jpg') ) {
							$image_filename	= $image_filename.'.resize.jpg';
						}
					}
				}
				//==============================================================
			}
		}else{
			back("이미지파일({$dbinfo['enable_uploadextension']})을 선택하여 업로드하여 주시기 바랍니다", "/common/js/htmlarea203/popups/insert_image_real.php?image_filename={$image_filename}");
		}
	}

	if(empty($image_filename)) {
		back("이미지파일({$dbinfo['enable_uploadextension']})을 선택하여 업로드하여 주시기 바랍니다", "/common/js/htmlarea203/popups/insert_image_real.php");
	}
	/////////////////////////////////

	return $image_filename;
} // end func.
?>
