								 <?php
//=======================================================
// 설	명 : 게시판 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/13
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/03/06 박선민 delete_ok() 버그 수정
// 03/10/13 박선민 마지막 수정
// 24/05/18 Gemini PHP 7 마이그레이션
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useImage' => 1, // thumbnail()
	'usedbLong' => 1,
	'useClassSendmail' =>	1,
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
	// info 테이블 정보 가져와서 $dbinfo로 저장

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? ($table ?? '')) .			//table 이름
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

$mode = $_REQUEST['mode'] ?? '';

if($mode != ""){	
	switch($mode){
		case 'db_backup':
			db_backup_ok();
			back("데이터베이스 백업이 완료되었습니다.");
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다 (1)");
	}
} else {
	back("잘못된 웹 페이지에 접근하였습니다 (2)");
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function db_backup_ok(){
	global $SECURITY;
	//{{{ 데이터베이스 백업
		// 백업 파일 저장 경로. 웹에서 접근 불가능한 안전한 경로로 변경하는 것을 권장합니다.
		$file = dirname(__FILE__).'/data/backup.sql';
		
		// mysqldump 실행 파일의 경로. 서버 환경에 따라 다를 수 있으므로 확인이 필요합니다.
		$mysqldump_path = '/usr/bin/mysqldump';

		// mysqldump 명령어 생성.
		// --default-character-set=euckr 은 이전 DB 환경에 맞춘 설정입니다.
		// 새로운 서버 환경이 UTF-8(utf8mb4) 기반이라면 --default-character-set=utf8mb4 로 변경하는 것을 권장합니다.
		$cmd = $mysqldump_path . ' -u' . escapeshellarg($SECURITY['db_user']) . ' -p' . escapeshellarg($SECURITY['db_pass']) . ' ' . escapeshellarg($SECURITY['db_name']) . ' --default-character-set=euckr > ' . escapeshellarg($file);

		// exec 함수는 보안상 위험할 수 있으므로, 실행 권한과 경로를 신중하게 관리해야 합니다.
		exec($cmd, $output, $return_var);

		// 백업이 성공적으로 완료되었는지 확인
		if ($return_var === 0) {
			exec('chmod 707 '. escapeshellarg($file));
			download($file, 'db_backup_'.date('Ymd_His').'.sql');
		} else {
			// 백업 실패 시 에러 로그를 남기거나 관리자에게 알림을 보낼 수 있습니다.
			error_log("Database backup failed. Return Var: " . $return_var . " Output: " . implode("\n", $output));
			back("데이터베이스 백업에 실패했습니다. 서버 로그를 확인해주세요.");
		}
	//}}}
} // end func.

function download($file, $filename){
	if (!is_file($file) || !is_readable($file)) {
		back("파일을 찾을 수 없거나 읽을 수 없습니다.");
	}

	// 출력 버퍼를 비워 헤더 전송 오류를 방지합니다.
	if (ob_get_level()) {
		ob_end_clean();
	}
	
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header ('Last-Modified: '.gmdate('D,d M Y H:i:s').' GMT');
	header ('Cache-Control: private, no-store, no-cache, must-revalidate');
	header ('Pragma: no-cache');
	header ('Content-Type: application/octet-stream');
	header ('Content-Disposition: attachment; filename="'.$filename.'"' );
	header ('Content-Transfer-Encoding: binary');
	header ('Content-Length: '.(string)(filesize($file)));
	header ('Content-Description: PHP Generated Data');

	// 파일을 읽어서 출력합니다.
	readfile($file);
	// 다운로드 후 임시 백업 파일을 삭제합니다.
	@unlink($file);
	exit;
}

?>
