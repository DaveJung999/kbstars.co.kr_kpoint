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
//=======================================================
// 앞으로 : 게시물 삭제시 메모로 삭제되도록...
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'useImage' => 1, // thumbnail()
	'useClassSendmail' =>	1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// Ready.. . (변수 초기화 및 넘어온값 필터링)
//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

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
			"&page=" . ($_REQUEST['page'] ?? '');

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

$thisUrl	= "/Admin/member"; // 마지막 "/"이 빠져야함
$qs=array(
			"title" =>	"post,trim",
	);
	
if(empty($sql_where)) $sql_where = 1;

$table_logon = $SITE['th']."logon";
$table_mailmessage = $SITE['th']."board2_mailmessage";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
	// info 테이블 정보 가져와서 $dbinfo로 저장
if(isset($uid) || isset($mode)){
	switch($mode){
		case 'modify':
			modify_ok();
			back("회원정보가 변경되었습니다.");
			break;
		case 'delete':
			delete_ok($uid);
			go_url("index.php?class={$_GET['class']}",0,"회원정보가 완전히 삭제되었습니다.");
			break;
		case 'index_delete':
			index_delete_ok($total_num);
			go_url("index.php?class={$_GET['class']}",0,"회원정보가 완전히 삭제되었습니다.");
			break;
		case 'mail_message':
			mail_message_ok();
			go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "{$thisUrl}/mailmessage.php?" . href_qs("uid={$uid}",$qs_basic),0,"설정이 완료되었습니다.");
			break;
		case 'save':
			save_ok();
			back("포인트가 적립되었습니다.");
			break;
		default :
			back("잘못된 웹 페이지에 접근하였습니다");
	}
} else {
	back("잘못된 웹 페이지에 접근하였습니다");
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function delete_ok($uid){
	global $table_logon;

	if(empty($uid)) back("고유번호가 없습니다.");

	$sql = "DELETE FROM
				{$table_logon}
			WHERE
				uid={$uid}
		";
	db_query($sql);

	return true;
} // end func.

function index_delete_ok($total_num){
	global $table_logon;

	if(empty($total_num)) back("고유번호가 없습니다.");

	$qs2 = explode(";",	$total_num);
	$cnt = count($qs2);
	$sql_add_logon = '';
	
	$uid_list = [];
	for($i= 1;$i<$cnt;$i++){
		if(isset($qs2[$i])) {
			$uid_list[] = (int)$qs2[$i]; // SQL Injection 방지를 위해 정수형으로 변환
		}
	}

	if (isset($uid_list)) {
		$uids = implode(',', $uid_list);
		$sql = "DELETE FROM {$table_logon} WHERE uid IN ({$uids})";
		db_query($sql);
	}

	return true;
} // end func.
function modify_ok(){
	global $table_logon;

	$qs=array(
			"uid" =>	"post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다"),
			"userid" =>	"post,trim",
			"passwd" =>	"post,trim",
			"email" =>	"post,trim",
			"name" =>	"post,trim,notnull=" . urlencode("이름을 입력하시기 바랍니다."),
			"nickname" =>	"post,trim",
			"priv" =>	"post,trim",
			"level" =>	"post,trim",
			"idnum1" =>	"post,trim",
			"idnum2" =>	"post,trim",
			"birth1" =>	"post,trim",
			"birth2" =>	"post,trim",
			"birth3" =>	"post,trim",
			"birth_lunar" =>	"post,trim",
			"yesmail" =>	"post,trim",
			"yessms" =>	"post,trim",
			"hp" =>	"post,trim",
			"zip1" =>	"post,trim",
			"zip2" =>	"post,trim",
			"address" =>	"post,trim",
			"tel" =>	"post,trim",
			"school" =>	"post,trim",
			"school_name" =>	"post,trim",
			"fav_player" =>	"post,trim",
			"host" => "post,trim",
			"homepage" => "post,trim",
			"country" => "post,trim",
		);

	$qs=check_value($qs);
	
	$qs['email']	= check_email($qs['email']);
	if(isset($qs['homepage'])) $qs['homepage']=check_url($qs['homepage']);
	if(empty($qs['nickname'])) $qs['nickname'] = $qs['name'];
	if(empty($qs['country'])) $qs['country'] = "kr";
	
	$sql_passwd = '';
	if(isset($qs['passwd'])) $sql_passwd = ", `passwd`=password('{$qs['passwd']}')";
	
	$sql_userinfo_set = '';
	if(isset($qs['zip1']) && isset($qs['zip2']))
		$qs['zip'] = "{$qs['zip1']}-{$qs['zip2']}";
	
	$qs['idnum'] = "{$qs['idnum1']}-{$qs['idnum2']}";
	
	if(is_array($_POST['priv']) and count($_POST['priv']))
		$qs['priv'] = implode(',',$_POST['priv']);
		
	$pos = strpos($qs['priv'], '회원');

	if ($pos === false){
		$qs['priv'] = $qs['priv'].",회원";
	}

	// 값 추가
	$qs['ip']		= remote_addr();

	// logon 테이블 회원정보변경
	$sql = "UPDATE
						{$table_logon}
					SET
						`email`='{$qs['email']}',
						`nickname`='{$qs['nickname']}',
						`name`='{$qs['name']}',
						`priv`='{$qs['priv']}',
						`level`='{$qs['level']}',
						`idnum`		='{$qs['idnum']}',
						`yesmail`		='{$qs['yesmail']}',
						`yessms`		='{$qs['yessms']}',
						`tel`		='{$qs['tel']}',
						`hp`		='{$qs['hp']}',
						`zip`		='{$qs['zip']}',
						`address`	='{$qs['address']}',
						`school`		='{$qs['school']}',
						`school_name`		='{$qs['school_name']}',
						`fav_player`		='{$qs['fav_player']}',
						`mdate`	= UNIX_TIMESTAMP(),
						`ip`		='{$qs['ip']}',
						`host`		='{$qs['host']}'
						{$sql_passwd}
					WHERE
						`uid`='{$qs['uid']}'
					AND
						`userid`='{$qs['userid']}'
					";
	
	db_query($sql);

	return true;
} // end func.
function mail_message_ok(){
	global $table_mailmessage, $_SESSION;

	$qs=array(
				'uid' =>	"post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
				'content' =>	"post,trim,notnull=" . urlencode("내용을 입력하시기 바랍니다."),
				'docu_type' =>	"post,trim",
				'data1' =>	"post,trim",
				'data2' =>	"post,trim",
				'data3' =>	"post,trim",
				'data4' =>	"post,trim",
				'data5' =>	"post,trim"
		);
		
	// 넘어온값 체크
	$qs=check_value($qs);

	if(isset($qs['docu_type']) and strtolower($qs['docu_type']) != "html") $qs['docu_type']="text";
	else $qs['docu_type'] = 'html';

	// 값 추가
	$qs['ip']		= remote_addr();
	
	$sql_set = ''; // 변수 초기화
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('uid', 'bid', 'userid', 'email', 'passwd', 'db', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
	if($fieldlist = userGetAppendFields($table_mailmessage, $skip_fields)){
		foreach($fieldlist as $value){
			if(isset($_POST[$value])) $sql_set .= ", `{$value}` = '" . $_POST[$value] . "' ";
		}
	}
	////////////////////////////////
	$sql = "UPDATE
				{$table_mailmessage}
			SET
				`content`	='{$qs['content']}',
				`docu_type`='{$qs['docu_type']}',
				`rdate`	=UNIX_TIMESTAMP(),
				`ip`		='{$qs['ip']}'
				{$sql_set}
			WHERE
				uid={$qs['uid']}
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

//포인트 적립
function save_ok(){
	$uid = $_POST['uid'];
	$remark = $_POST['remark'];
	$deposit = (int)($_POST['deposit'] ?? 0);
	//공급사 개수
	$sql = " SELECT count(sup_bid) as cnt FROM `new21_accountinfo` where bid={$uid} ";	
	$cnt_sup = db_arrayone($sql);
	
	if(($cnt_sup['cnt'] ?? 0) > 0) {
		$deposit_per_sup = $deposit / $cnt_sup['cnt'];
	} else {
		$deposit_per_sup = 0;
	}

	//accountinfo table에 insert
	$ins = " UPDATE new21_accountinfo SET balance = (balance + {$deposit_per_sup}) where bid={$uid} ";
	db_query($ins);
	
	$sql_2 = " SELECT * FROM new21_accountinfo where bid={$uid} ";
	$rs = db_query($sql_2);
	$cnt = db_count($rs);

	$remark_text = date('Y-m-d', time()) . " ".$remark;

	for($i=0 ; $i<$cnt ; $i++){
		$info = db_array($rs);

		$ins2 = " INSERT new21_account SET
						`bid`={$uid},
						`userid`='{$info['userid']}',
						`accountno`={$info['accountno']},
						`rdate`=UNIX_TIMESTAMP(),
						`order_rdate` =UNIX_TIMESTAMP(),
						`sup_bid`={$info['sup_bid']},
						`account_supplier`='{$info['account_supplier']}',
						`pay_price`=0,
						`type`='적립-사이트',
						`remark`='{$remark_text}',
						`deposit`={$deposit_per_sup},
						`withdrawal`=0,
						`balance`=(balance + {$deposit_per_sup}),
						`branch`='사이트'
				";		
		db_query($ins2);
	}
		
	return true;
}

?>
