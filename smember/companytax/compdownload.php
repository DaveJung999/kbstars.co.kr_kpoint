<?php
//=======================================================
// 설	명 : 다운로드 - 회사 인감도장 업로드한 파일
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/18
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/01/29 박선민 처음
// 04/08/18 박선민 일부수정
//=======================================================
$HEADER=array(
		'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useBoard2' => 1, // boardAuth()
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_companyinfo	= $SITE['th'] . "companyinfo";
	$table = $table_companyinfo;

	$updir	= dirname(__FILE__) . "/upload/{$table}";
	$upfile	= $_GET['upfile'] ? $_GET['upfile'] : "upfile"; // 디폴트 업로드 폼 네임

	if($_GET['mode'] != 'image') $_GET['mode']="download";
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$sql = "SELECT * from {$table} where uid={$_GET['uid']}";
$list = db_arrayone($sql) or back("파일이 없거나 보실 수 없습니다 . errno: 1");

// 파일을 읽을 수 있는지 체크
$priv = array("bid" => $list['bid'],"priv" => 99);
if(!boardAuth($priv,"priv")) { 
	// uid가 1번이면, 사이트 운영회사이기에 세금계산서 도장 읽을 권한 부여함
	if($list['uid'] != 1) back("파일이 없거나 보실 수 없습니다 . errno: 2");
}

// 파일 unserialize
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
$filename = $upfiles[$upfile]['name'];
$filepath = $updir	. "/" . $filename;

if( !is_file($filepath) ) back("해당 파일이 없습니다 . errno: 7");

$userid=$list['userid'];
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
if($_GET['mode'] == "download"){
	header("Content-Disposition: attachment; filename=\"{$filename}\"");
	header("Content-Transfer-Encoding: binary"); 
}
else header("Content-Disposition: inline; filename=\"{$filename}\"");
header('Content-Description: sitePHPbasic Security Download'); 
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache'); // HTTP/1.0 
header('Expires: 0');

/*
//메모리 문제 발생되면 아래 방식으로
while(!feof($fd)){
	print fread($fd, 4096);
}
fclose($fd);
*/
$fd=fopen($filepath,'rb');
fpassthru($fd); ?>
