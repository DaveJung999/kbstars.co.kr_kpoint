<?php
//=======================================================
// 설	명 :	다운로드 파일(download.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/30
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/07/30 박선민 마지막 수정
//=======================================================
// @param	string $mode	: [NULL|downloand|image|watermark|origin|thumbnail|allimages|mainimage]
//			string $db		: MUST
//			string $uid		: MUST (단, mode=mainimage인 경우 제외)
//			string $userid	: 앨범에서 $mode가 mypicture의 경우 해당 하이디의 기본 사진
//			string $upfile	: [업로드폼이름], 없다면 첫번째 찾은 파일이 됨
//			string $notfound: [any|small|large]
//			int		$imagewidth	: 기본값 100
//			int		$imageheight : 기본값 100
// @return object		file or image or html(이미지리스트)
// 사용방법 :
//	1 . 아무거나 하나 다운로드(업로드폼이름 "upfile"이 우선함)
//		= "download.php?mode=&db={$db}&uid={$list['uid']}";
//	2 . 특정 폼이름 다운로드(<input type=file name=upfile의 경우)
//		= "download.php?mode=&db={$db}&uid={$list['uid']}&upfile=upfile";
//	3 . 특정 폼이름 다운로드, 만일 없다면 아무거나
//		= "download.php?mode=&db={$db}&uid={$list['uid']}&upfile=upfile&notfound=any";
//	4 . 셤네일(100x100)으로 만들어서 전송(가로x세로 높이를 지정안할 경우 100x100기본임)
//		= "download.php?mode=thumbtail&db={$db}&uid={$list['uid']}&imagewidth=100&imageheight=100&upfile=upfile&notfound=any";
//	5 . 어떤 회원의 사진을 받고 싶다면
//		= "download.php?mode=mypicture&userid=????
//	6 . 무조건 다운로드 되도록, 그리고 다운로드히트증가
//	= "download.php?mode=download&db={$db}&uid={$list['uid']}"
//		
//	** mode값 설명(mode값이 있으면 이미지임)
//		mode=			: 이미지가 아닌 것으로 볾
//		mode=mainimage	: 앨범에서 사용되는 것으로 해당 앨범의 대표 이미지 찾음
//	mode=image		: 이미지임을 명확히함
//		mode=thumbnail	: 셤네일 이미지로
//		mode=watermark	: 워터마크 처리함
//		mode=allimages	: 이미지들을 html문서로 모두 보여줌
//						="download.php?mode=allimages&db={$db}&uid={$uid}";
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1, // boardAuth()
	'useImage' => 1	// thumbnail()
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
	$prefix		= "board2"; // board? album? 등의 접두사
$thisPath	= dirname(__FILE__);
$thisUrl	= "/s{$prefix}"; // 마지막 "/"이 빠져야함

	$table_dbinfo	= $SITE['th'] . "{$prefix}info";
	$table_logon	= $SITE['th'] . "logon";

	// dbinfo 테이블 정보 가져와서 $dbinfo로 저장
	if($_GET['db']){
		$sql="SELECT * from {$table_dbinfo} WHERE db='{$_GET['db']}'";
	} else {
		if($_GET['mode']) go_url("/scommon/noparam.gif");
		else back("DB 값이 없습니다 . err 67");
	} // end if
	
	if(!$dbinfo=db_arrayone($sql)){
		if($_GET['mode']) go_url("/scommon/noparam.gif");
		else back("사용하지 않는 DB입니다 . err 72");
		exit;
	}
	$table=$SITE['th'] . "{$prefix}_" . $dbinfo['db'];
	
	// 업로드 기본 디렉토리 설정
	$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) . "/{$SITE['th']}{$prefix}_{$dbinfo['db']}" : "{$thisPath}/upload/{$SITE['th']}{$prefix}_{$dbinfo['db']}";

	// 넘오온 값 필터링
	$upfile = $_GET['upfile'] ? $_GET['upfile'] : "upfile"; // 디폴트 업로드 폼 네임
	if($_GET['mode'] == "mainimage") $_GET['notfound']="any"; 

	//===================
	// SQL문 where절 정리
	//===================
	$sql_where	= " 1 ";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
if($_GET['mode'] == "mainimage") 
	$sql = "SELECT * from {$table} where $sql_where order by primarydocu DESC, rdate LIMIT 1"; // 앨범에서 해당 앨범의 대표 이미지 구할때
else
	$sql = "SELECT * from {$table} where uid='{$_GET['uid']}' and $sql_where LIMIT 1";
if(!$list=db_arrayone($sql)){
	if($_GET['mode']) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 3");
}

// 인증 체크
if($_GET['mode'] == "watermark") { 
	//워터마크 요청이니 무조건 허락
}
elseif( !boardAuth($dbinfo, "priv_download") 
		and !($_SESSION['seUid'] and $list['bid'] == $_SESSION['seUid']) ){
	if($_GET['mode']) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 4");
}
elseif( $dbinfo['enable_level'] == 'Y' and !boardAuth($list,"priv_level") ){
	if($_GET['mode']) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 5");
}

// 다운로드 파일 구하기($filepath, $filename)
if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
	$upfiles=unserialize($list['upfiles']);
	if( !is_array($upfiles) ){
		if(strlen($list['upfiles'])>0) { 
			// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles[$upfile]['name']=$list['upfiles'];
		} else {
			if($_GET['mode']) go_url("/scommon/noimage.gif");
			else back("해당 파일이 없습니다 . errno: 6");
		}
	}

	// 파일이 있는지 체크
	if( isset($upfiles[$upfile]['name']) ){
		$filename = $upfiles[$upfile]['name'];
		$filepath = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $filename;
		if( is_file($filepath) ) $_GET['notfound']="";
		else {
			// 한단계 위에 파일이 있다면 그것으로..
			$filepath = $dbinfo['upload_dir'] . "/" . $filename;
			if( is_file($filepath) ) $_GET['notfound']="";
		} // end if. . else..
	}

	// 모든 이미지 보기거나 파일을 못찾았다면 찾아내기
	if($_GET['notfound'] or $_GET['mode'] == "allimages"){
		$tmp_filesize=0; // 임시 사용 변수 초기화
		foreach($upfiles as $key =>	$value){
			if($value['name']){
				$value['filepath'] = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name'];
				if( !is_file($value['filepath']) ){
					// 한단계 위에 파일이 있다면 그것으로..
					$value['filepath'] = $dbinfo['upload_dir'] . "/" . $value['name'];
					if( !is_file($value['filepath']) ) continue;
				} // end if

				// 이미지들을 html문서로 모두 보여줌
				if( $_GET['mode'] == "allimages"){
					if( is_array($imagesize=@getimagesize($value['filepath'])) ){
						$href	= "{$thisUrl}/download.php?mode=image&db={$dbinfo['db']}&uid={$list['uid']}&upfile={$key}";
						$html['zoomimage'] .= "<a href='javascript: self.close();'><img src='{$href}' {$size['3']} border=0><br>";
					}
				}
				elseif( $_GET['notfound'] == "any" ){
					$filepath = $value['filepath'];
					$filename = $value['name'];
					break;
				}
				elseif( $_GET['notfound'] == "small" ) { //가장 작은 파일을 찾는 거라면
					if( $tmp_filesize == 0 or $tmp_filesize>filesize($value['filename']) ){
						$filepath = $value['filepath'];
						$filename = $value['name'];
					}
				}
				elseif( $_GET['notfound'] == "large" ){
					if( $tmp_filesize == 0 or $tmp_filesize<filesize($value['filename']) ){
						$filepath = $value['filepath'];
						$filename = $value['name'];
					}
				}
			} // end if
		} // end foreach
	} // end if
	unset($upfiles);
	unset($values);
}
else {
	if($_GET['mode']) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 6");
} // end if 업로드파일 처리

//================== 
// mode값에 따라 처리
//================== 
if($_GET['mode'] == '' or $_GET['mode'] == 'download' or $_GET['mode'] == 'origin') { // 파일이거나 이미지 원본 그대로
	// 다시한번 파일 유무 체크해봄
	if(!is_file($filepath)){
		if($_GET['mode']) go_url("/scommon/noimage.gif");
		else back("해당 파일이 없습니다 . errno: 6");
	}
	// 웹드렉토리 아래에 있다면. . 바로 해당 파일로 이동
	if(strpos($filepath,$_SERVER['DOCUMENT_ROOT']) !==false and $_GET['mode'] != 'download'){
		$url = substr($filepath,strlen($_SERVER['DOCUMENT_ROOT']));
		$url = dirname($url) . '/'	. urlencode(basename($url));
		header("Location: ".$url);
		exit;
	}
}
elseif($_GET['mode'] == "allimages") { // 이미지들을 html문서로 모두 보여줌
	echo "<html><body><script language=\"JavaScript\">this.focus();</script>";
	if($html['zoomimage']) echo $html['zoomimage'];
	else 
		echo "<center><a href='javascript: self.close();'><font color=red size=2> 이미지가 없습니다.</font></a></center>";
	echo "</body></html>";
	exit; // 종료
}
elseif($_GET['mode'] == 'watermark'){
	if($list['bid'] != $_SESSION['seUid']) { // 요청자가 저작권자가 아니면 Copyright By userid 인쇄
		// 해당 파일 읽어서 바로 클라이언트에 전송
		$im=thumbnail($filepath,"200","300");

		// 이미지에 Copyright By Userid
		if(!$list['userid']) $list['userid'] = "This Site";
		$px = (imagesx($im)-7.5*strlen("Copyright By " . $list['userid']))/2;
		$text_color = ImageColorAllocate ($im, 0, 0, 255);
		ImageString($im, 3, $px, imagesy($im)-imagesy($im)/2, "Copyright By {$list['userid']}", $text_color);

		Header("Content-type: image/jpeg");
		ImageJpeg($im);
		//ImageGif($im);
		ImageDestroy($im);
		exit;
	} else { // 요청자과 이미지 주인이라면 그대로 보내기
		// nothing --> 본 if($_GET['mode'] ..)문 끝난 이후에 마지막에 코딩 있음
	} // end if
} // end if
else { // 이미지 파일 요청이면
	// 이미지 사이즈 구함
	if(!is_array($imagesize=@getimagesize($filepath)) )
		go_url("/scommon/noimage.gif");

	// thumbnail이미지 크기 조정
	$thumbimagesize=explode("x", $dbinfo['imagesize_thumbnail']);
	if(intval($thumbimagesize['0']) == 0) $thumbimagesize['0'] = 100; // 최소 100px
	if(intval($thumbimagesize['1']) == 0) $thumbimagesize['1'] = 100; // 최소 100px
	// 요청 이미지가 셤네일 사이즈보다 작으면 셤네일을 보내도록
	if( $_GET['imagewidth']>0 and $_GET['imagewidth']<$thumbimagesize['0'] 
		and $_GET['imageheight']<$thumbimagesize['1'] ){
		$_GET['mode'] = "thumbnail";
	}

	// 셤네일 이미지를 전송해야 된다면, 셤네일 파일 준비시킴
	if($_GET['mode'] == "thumbnail" || $_GET['mode'] == "mainimage"){
		$_GET['imagewidth']	= (intval($_GET['imagewidth']) == 0) ? intval($thumbimagesize['0']) : intval($_GET['imagewidth']);
		$_GET['imageheight']	= (intval($_GET['imageheight']) == 0) ? intval($thumbimagesize['1']) : intval($_GET['imageheight']);

		// 셤네일 파일이 존재하고, 정상인지 체크
		if( is_file($filepath.".thumb.jpg") and is_array($tmp=@getimagesize($filepath.".thumb.jpg")) 
			and $tmp['0'] == $thumbimagesize['0'] and	$tmp['1'] == $thumbimagesize['1']){
				$imagesize	= $thumbimagesize;
				$filepath	= $filepath.".thumb.jpg";
				$filename	= $filename.".jpg";
				unset($tmp);
		} else { // 셤네일 이미지 생성시킴
			$im=thumbnail($filepath,$thumbimagesize['0'],$thumbimagesize['1']);
			ImageJpeg($im,$filepath.".thumb.jpg"); // 파일저장
			ImageDestroy($im);
			if( is_file($filepath.".thumb.jpg") ){
				$imagesize	= $thumbimagesize;
				$filepath	= $filepath.".thumb.jpg";
				$filename	= $filename.".jpg";
			}
		}
	}

	// 요청한 아미지가 이미지 사이즈보다 작으면, 작게 만들어서 전송함
	if( $_GET['imagewidth']>0 and $_GET['imageheight']>0 and ($imagesize['0']>$_GET['imagewidth'] or $imagesize['1']>$_GET['imageheight']) ){
		$im=thumbnail($filepath,$_GET['imagewidth'],$_GET['imageheight']);
		Header("Content-type: image/jpeg");
		ImageJpeg($im);
		//ImageGif($im);
		ImageDestroy($im);
		exit; // 종료
	}

	// 여기까지 왔으면 아래 fpassthru()로
}
//================== //
//================================== 
// 파일 읽어서 브라우저에 바로 보내기
//================================== 
unset($dbinfo);
unset($list);
// mime-type 결정
if(function_exists('mime_content_type')) header('Content-type: '.mime_content_type($filepath));
else {
	$file_ext = strtolower(substr(strrchr($filename,"."), 1));
	switch($file_ext){
		case 'jpg':
		case 'jpeg':
			header('Content-type: image/jpeg');
			break;
		case 'gif':
			header('Content-type: image/gif');
			break;
		default :
			header('Content-type: application/octet-stream');
	}
}
header('Content-length:'.(string)(filesize($filepath)));
if($_GET['mode'] == 'download'){
	header('Content-Disposition: attachment; filename="'.$filename.'";');
	header('Content-Transfer-Encoding: binary'); 
}
else header('Content-Disposition: inline; filename="'.$filename.'";');
header('Content-Description: sitePHPbasic Security Download'); 
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache'); // HTTP/1.0 
header('Expires: 0');
/*
		header("Pragma: ");
		header("Cache-Control: ");
위의 두줄입니다.
		header("Content-Type: application/octet-stream");

//메모리 문제 발생되면 아래 방식으로
while(!feof($fd)){
	print fread($fd, 4096);
}
fclose($fd);

		// By http://www.phpclasses.org/browse/package/1695.html
		header( "Pragma: public");
		header( "Expires: 0");
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header( "Content-Type: application/force-download");
		header( "Content-Type: application/octet-stream");
		header( "Content-Type: application/download");
		header( "Content-Disposition: attachment; filename=".substr( $file, strrpos( $file, $this->slash) +1) . ";");
		header( "Content-Transfer-Encoding: binary");
		header( "Content-Length: ".filesize( $file));
		header( "Content-Description: File Transfert");
		@readfile( $file);
		exit();
*/
$fd=fopen($filepath,'rb');
fpassthru($fd);

// hitdownload 증가
if($_GET['mode'] == "download"){
	$sql = "UPDATE LOW_PRIORITY {$table} SET hitdownload=hitdownload+1 WHERE uid='{$_GET['uid']}' LIMIT 1";
	@db_query($sql);
} 

?>
