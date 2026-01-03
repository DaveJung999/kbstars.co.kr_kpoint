<?php
//=======================================================
// 설	명 :	다운로드 파일(download.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/07/12 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1, // privAuth()
	'useImage' => 1	// thumbnail()
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

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
$db = $_GET['db'] ?? '';
$uid = (int)($_GET['uid'] ?? 0);
$mode = $_GET['mode'] ?? '';
$upfile_key = $_GET['upfile'] ?? 'upfile';
$notfound = $_GET['notfound'] ?? '';

include_once("./dbinfo_pop.php"); // $dbinfo, $table 값 정의

$prefix		= "board2"; // board? album? 등의 접두사
$thisPath	= dirname(__FILE__);
$thisUrl	= "/s{$prefix}"; // 마지막 "/"이 빠져야함

// 업로드 기본 디렉토리 설정
$upload_path = !empty(trim($dbinfo['upload_dir'])) ? trim($dbinfo['upload_dir']) : dirname(__FILE__) . "/../../sboard2/upload";
$dbinfo['upload_dir'] = $upload_path . "/{$SITE['th']}{$prefix}_{$dbinfo['db']}";

if($mode == "mainimage") $notfound="any";

//===================
// SQL문 where절 정리
//===================
$sql_where	= " 1 ";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
if($mode == "mainimage")
	$sql = "select * from {$table} where $sql_where order by `primarydocu` DESC, `rdate` LIMIT 1"; // 앨범에서 해당 앨범의 대표 이미지 구할때
else
	$sql = "select * from {$table} where `uid`={$uid} and $sql_where LIMIT 1";

if(!$list=db_arrayone($sql)){
	if($mode) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 3");
}

// 인증 체크
if($mode == "watermark") {
	//워터마크 요청이니 무조건 허락
}
elseif( !privAuth($dbinfo, "priv_download")
		and !(isset($_SESSION['seUid']) && ($list['bid'] ?? 0) == $_SESSION['seUid']) ){
	if($mode) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 4");
}
elseif( ($dbinfo['enable_level'] ?? '') == 'Y' and !privAuth($list,"priv_level") ){
	if($mode) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 5");
}

// 다운로드 파일 구하기($filepath, $filename)
$filepath = null;
$filename = null;
if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($list['upfiles'])){
	$upfiles=@unserialize($list['upfiles']);
	if( !is_array($upfiles) ){
		if(strlen($list['upfiles'])>0) {
			$upfiles = [];
			$upfiles[$upfile_key]['name']=$list['upfiles'];
		} else {
			if($mode) go_url("/scommon/noimage.gif");
			else back("해당 파일이 없습니다 . errno: 6");
		}
	}

	// 파일이 있는지 체크
	if( isset($upfiles[$upfile_key]['name']) ){
		$filename = $upfiles[$upfile_key]['name'];
		$temp_filepath = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $filename;
		if( is_file($temp_filepath) ) {
			$filepath = $temp_filepath;
			$notfound = "";
		} else {
			$temp_filepath = $dbinfo['upload_dir'] . "/" . $filename;
			if( is_file($temp_filepath) ) {
				$filepath = $temp_filepath;
				$notfound = "";
			}
		}
	}

	// 모든 이미지 보기거나 파일을 못찾았다면 찾아내기
	if($notfound || $mode == "allimages"){
		$tmp_filesize=0;
		$html = ['zoomimage' => ''];
		foreach($upfiles as $key =>	$value){
			if(isset($value['name'])){
				$value['filepath'] = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name'];
				if( !is_file($value['filepath']) ){
					$value['filepath'] = $dbinfo['upload_dir'] . "/" . $value['name'];
					if( !is_file($value['filepath']) ) continue;
				}

				if( $mode == "allimages"){
					if( is_array(@getimagesize($value['filepath'])) ){
						$href	= "{$thisUrl}/download.php?mode=image&db={$dbinfo['db']}&uid={$list['uid']}&upfile={$key}";
						$html['zoomimage'] .= "<a href='javascript: self.close();'><img src='" . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . "' border=0><br>";
					}
				}
				elseif( $notfound == "any" ){
					$filepath = $value['filepath'];
					$filename = $value['name'];
					break;
				}
				elseif( $notfound == "small" ) {
					if( $tmp_filesize == 0 or $tmp_filesize > filesize($value['filepath']) ){
						$filepath = $value['filepath'];
						$filename = $value['name'];
						$tmp_filesize = filesize($value['filepath']);
					}
				}
				elseif( $notfound == "large" ){
					if( $tmp_filesize == 0 or $tmp_filesize < filesize($value['filepath']) ){
						$filepath = $value['filepath'];
						$filename = $value['name'];
						$tmp_filesize = filesize($value['filepath']);
					}
				}
			}
		}
	}
	unset($upfiles);
}
else {
	if($mode) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 6");
}

//==================
// mode값에 따라 처리
//==================
if(empty($mode) || $mode == 'download' || $mode == 'origin') {
	if(!is_file($filepath ?? '')){
		if($mode) go_url("/scommon/noimage.gif");
		else back("해당 파일이 없습니다 . errno: 7");
	}
	if(strpos($filepath,$_SERVER['DOCUMENT_ROOT']) !== false && $mode != 'download'){
		$url = substr($filepath,strlen($_SERVER['DOCUMENT_ROOT']));
		$url = dirname($url) . '/'	. rawurlencode(basename($url));
		header("Location: ".$url);
		exit;
	}
}
elseif($mode == "allimages") {
	echo "<html><body><script language=\"JavaScript\">this.focus();</script>";
	if(isset($html['zoomimage'])) echo $html['zoomimage'];
	else
		echo "<center><a href='javascript: self.close();'><font color=red size=2> 이미지가 없습니다.</font></a></center>";
	echo "</body></html>";
	exit;
}
elseif($mode == 'watermark'){
	if(($list['bid'] ?? 0) != ($_SESSION['seUid'] ?? -1)) {
		$im=thumbnail($filepath,"200","300");
		if ($im) {
			$userid = $list['userid'] ?? "This Site";
			$px = (imagesx($im) - 7.5 * strlen("Copyright By " . $userid))/2;
			$text_color = ImageColorAllocate ($im, 0, 0, 255);
			ImageString($im, 3, $px, imagesy($im)/2, "Copyright By {$userid}", $text_color);

			Header("Content-type: image/jpeg");
			ImageJpeg($im);
			ImageDestroy($im);
			exit;
		}
	}
}
else { // 이미지 파일 요청이면
	if(!is_array($imagesize=@getimagesize($filepath)) )
		go_url("/scommon/noimage.gif");

	$imagewidth = (int)($_GET['imagewidth'] ?? 0);
	$imageheight = (int)($_GET['imageheight'] ?? 0);

	$thumbimagesize = explode("x", $dbinfo['imagesize_thumbnail'] ?? '100x100');
	$thumbimagesize['0'] = (int)($thumbimagesize['0'] ?? 100);
	$thumbimagesize['1'] = (int)($thumbimagesize['1'] ?? 100);
	if($thumbimagesize['0'] == 0) $thumbimagesize['0'] = 100;
	if($thumbimagesize['1'] == 0) $thumbimagesize['1'] = 100;

	if( $imagewidth > 0 && $imagewidth < $thumbimagesize['0'] && $imageheight < $thumbimagesize['1'] ){
		$mode = "thumbnail";
	}

	if($mode == "thumbnail" || $mode == "mainimage"){
		$imagewidth	= ($imagewidth == 0) ? $thumbimagesize['0'] : $imagewidth;
		$imageheight = ($imageheight == 0) ? $thumbimagesize['1'] : $imageheight;

		$thumb_path = "{$filepath}.thumb.jpg";
		if( is_file($thumb_path) && ($tmp=@getimagesize($thumb_path)) && $tmp['0'] == $imagewidth && $tmp['1'] == $imageheight){
				$filepath	= $thumb_path;
				$filename	= basename($thumb_path);
		} else {
			$im=thumbnail($filepath, $imagewidth, $imageheight);
			if ($im) {
				ImageJpeg($im, $thumb_path);
				ImageDestroy($im);
				if( is_file($thumb_path) ){
					$filepath	= $thumb_path;
					$filename	= basename($thumb_path);
				}
			}
		}
	}

	if( $imagewidth > 0 && $imageheight > 0 && ($imagesize['0'] > $imagewidth || $imagesize['1'] > $imageheight) ){
		$im=thumbnail($filepath, $imagewidth, $imageheight);
		if ($im) {
			Header("Content-type: image/jpeg");
			ImageJpeg($im);
			ImageDestroy($im);
			exit;
		}
	}
}
//==================================
// 파일 읽어서 브라우저에 바로 보내기
//==================================
if (!is_file($filepath ?? '')) {
	if($mode) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 8");
}

// mime-type 결정
if(function_exists('mime_content_type')) {
	header('Content-type: '.mime_content_type($filepath));
} else {
	$file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	switch($file_ext){
		case 'jpg':
		case 'jpeg':
			header('Content-type: image/jpeg');
			break;
		case 'gif':
			header('Content-type: image/gif');
			break;
		case 'png':
			header('Content-type: image/png');
			break;
		default :
			header('Content-type: application/octet-stream');
	}
}

header('Content-Length: '.(string)(filesize($filepath)));
if($mode == 'download'){
	header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
	header('Content-Transfer-Encoding: binary');
} else {
	header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
}
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$fd=fopen($filepath,'rb');
fpassthru($fd);
fclose($fd);

// hitdownload 증가
if($mode == "download"){
	$sql = "UPDATE LOW_PRIORITY {$table} SET `hitdownload`=`hitdownload`+1 WHERE `uid`='{$uid}' LIMIT 1";
	db_query($sql);
}

exit;
?>
