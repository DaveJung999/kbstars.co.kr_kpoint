<?php
//=======================================================
// 설	명 :	다운로드 파일(download.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/27
// Project: sitePHPbasic
// @param	string $mode	: [watermark|thumbtail|allimages|mainimage]
//			string $db		: MUST
//			string $uid		: MUST (단, mode=primaryimage인 경우 제외)
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
//		
//	** mode값 설명(mode값이 있으면 이미지임)
//		mode=mainimage	: 앨범에서 사용되는 것으로 해당 앨범의 대표 이미지 찾음
//	mode=image		: 이미지임을 명확히함
//		mode=thumbnail	: 셤네일 이미지로
//		mode=watermark	: 워터마크 처리함
//		mode=allimages	: 이미지들을 html문서로 모두 보여줌
//						="download.php?mode=allimages&db={$db}&uid={$uid}";
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 02/10/27 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1, // privAuth()
	'useImage' => 1, // thumbnail()
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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
include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) : dirname(__FILE__) ;

// 넘오온 값 필터링
if(!$upfile){
	if($mode == "thumbnail" or $mode == "mainimage")
		$upfile		= "thumbnail"; // 셤네일
	else
		$upfile		= "upfile"; // 업로드 폼 네임
}

//===================
// SQL문 where절 정리
//===================
if(!$sql_where) $sql_where	= " 1 ";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
if($mode == "mainimage") $rs_list=db_query("select * from {$table} where $sql_where order by primarydocu DESC, rdate LIMIT 0,1"); // 앨범에서 해당 앨범의 대표 이미지 구할때
else $rs_list=db_query("select * from {$table} where uid='{$uid}' and  $sql_where ");
if(!db_count()){
	if($mode) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 3");
}
$list=db_array($rs_list);

// 인증 체크
if( !privAuth($dbinfo, "priv_download") and !($_SESSION['seUid'] and $list['bid'] == $_SESSION['seUid']) ){
	if($mode) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 4");
}
elseif( $dbinfo['enable_level'] == 'Y' and !privAuth($list,"priv_level") ){
	if($mode) go_url("/scommon/nopriv.gif");
	else back("이용이 제한되었습니다.(레벨부족) errno: 5");
}

// 업로드파일 처리
if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
	$upfiles=unserialize($list['upfiles']);
	if(!is_array($upfiles))
	{ // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
		$upfiles['upfile']['name']=$list['upfiles'];
		$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
	}
	foreach($upfiles as $key =>	$value){
		if($value['name']){
			$upfiles[$key]['href']="download.php?mode=image&db={$db}&uid={$list['uid']}&upfile={$key}";

			// $filename구함(절대디렉토리포함)
			if( !is_file($upfiles[$key]['filename']=$dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name']) ){
				// 한단계 위에 파일이 있다면 그것으로..
				if ( !is_file($upfiles[$key]['filename']=$dbinfo['upload_dir'] . "/" . $value['name']) ){
					unset($upfiles[$key]);
					continue;
				} // end if
			} // end if

			// 이미지들을 html문서로 모두 보여줌
			if( ($mode == "allimages") && (is_array($imagesize=getimagesize($upfiles[$key]['filename']))) ){
				$html['zoomimage'] .= "<a href='javascript: self.close();'><img src='{$upfiles[{$key}]['href']}' {$size['3']} border=0><br>";
			}
		} // end if
	} // end foreach
	$list['upfiles']=$upfiles;
	unset($upfiles);
} else {
	if($mode) go_url("/scommon/noimage.gif");
	else back("해당 파일이 없습니다 . errno: 6");
} // end if 업로드파일 처리

// 최종 파일 구하기(해당 폼이름없으면 다른 있는 폼이름)
if( $mode<>"allimages" and !isset($list['upfiles']['{$upfile}']['filename']) ){
	if($notfound != "any" and $notfound != "small" or $notfound != "large")
		$notfound	= "any";
	$tmp_filesize=0; // 임시 사용 변수 초기화
	foreach($list['upfiles'] as $key =>	$value){
		if( $value['filename'] ){
			if( $notfound == "any" ){
				$list['upfiles']['{$upfile}']=$value;
				break;
			}
			elseif( ($notfound == "small") && ($tmp_filesize == 0 or $tmp_filesize>filesize($value['filename'])) ){
				$tmp_filesize=filesize($value['filename']);
				$list['upfiles']['{$upfile}']=$value;
			}
			elseif( $notfound == "large" && ($tmp_filesize == 0 or $tmp_filesize<filesize($value['filename'])) ){
				$tmp_filesize=filesize($value['filename']);
				$list['upfiles']['{$upfile}']=$value;
			}// end if. . elseif. . elseif..
		} // end if
	} // end foreach
	if(!isset($list['upfiles']['{$upfile}']['filename']) ){
		if($mode) go_url("/scommon/noimage.gif");
		else back("해당 파일이 없습니다 . errno: 7");
	} // end if
} // end if
//================== 
// mode값에 따라 처리
//================== 
if($mode == "allimages") { // 이미지들을 html문서로 모두 보여줌
	if($html['zoomimage'])
		echo $html['zoomimage'];
	else 
		echo "<center><a href='javascript: self.close();'><font color=red size=2> 이미지가 없습니다.</font></a></center>";

	exit; // 종료
}
elseif($mode == "thumbnail" || $mode == "mainimage"){
	// thumbneil 이미지 크기 조정
	if((int)$imagewidth == 0)		$imagewidth=100;
	if((int)$imageheigth == 0)	$imageheigth=100;

	// 이미지 사이트 구함
	if(!$imagesize=getimagesize($list['upfiles']['{$upfile}']['filename']))
		go_url("/scommon/noimage.gif");

	// 요청 사이즈가 이미지 사이즈크다 셤네일 만들어서 전송
	if( ($imagesize['0']>$imagewidth) && ($imagesize['1']>$imageheight) ){
		$im=thumbnail($list['upfiles']['{$upfile}']['filename'],$imagewidth,$imageheigth);
		Header("Content-type: image/jpeg");
		ImageJpeg($im);
		//ImageGif($im);
		ImageDestroy($im);
		exit; // 종료
	} else { // 바로 읽어서 보냄
		// nothing --> 본 if($mode ..)문 끝난 이후 마지막에 코딩 있음
	} // end if . . else ..
}
elseif($mode == "watermark"){
	if($list['bid'] != $_SESSION['seUid']) { // 요청자가 저작권자가 아니면 Copyright By userid 인쇄
		// 해당 파일 읽어서 바로 클라이언트에 전송
		$im=thumbnail($list['upfiles']['{$upfile}']['filename'],"200","300");

		// 이미지에 Copyright By Userid
		$px = (imagesx($im)-7.5*strlen("Copyright By " . $list['userid'] ))/2;
		$text_color = ImageColorAllocate ($im, 0, 0, 255);
		ImageString($im, 3, $px, imagesy($im)-imagesy($im)/2, "Copyright By {$list['userid']}", $text_color);

		ImageGif($im);
		ImageDestroy($im);
		exit;
	} else { // 요청자과 이미지 주인이라면 그대로 보내기
		// nothing --> 본 if($mode ..)문 끝난 이후에 마지막에 코딩 있음
	} // end if
} // end if
//================== //

//================================== 
// 파일 읽어서 브라우저에 바로 보내기
//================================== 
$filepath = $list['upfiles']['{$upfile}']['filename'];
header('Content-type: application/force-download');
header('Content-length:'.(string)(filesize($list['upfiles']['{$upfile}']['filename'])));
header('Content-Disposition: attachment; filename="'.$list['upfiles']['{$upfile}']['name'].'"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache'); // HTTP/1.0 
header('Expires: 0');

/*
		header("Pragma: ");
		header("Cache-Control: ");
위의 두줄입니다.
		header("Content-Type: application/octet-stream");
*/
//메모리 문제 발생되면 아래 방식으로
$fd=fopen($filepath,'rb');

while(!feof($fd)){
	print fread($fd, 4096);
}
fclose($fd);
/*
//메모리 문제 발생되면 아래 방식으로
while(!feof($fd)){
	print fread($fd, 4096);
}
fclose($fd);
*/
?>
