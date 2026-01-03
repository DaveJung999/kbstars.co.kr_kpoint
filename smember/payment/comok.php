<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/16 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useCheck' => 1, // check_value, check_idnum, check_compnum
		'useApp' => 1, // file_upload()
		'useClassSendmail' =>  1, // mime_mail
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $_SERVER['HTTP_HOST']); // $_SERFVER -> $_SERVER

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	
	// 지금 세금계산서 발행서비스 제공하는지
	if($dbinfo['enable_tax'] != 'Y')
		back_close("지금 세금계산서 조회, 발행을 제공하고 있지 않습니다.");
	// 기본 URL QueryString
	$qs_basic = "mode=";
	
	// table
	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_companyinfo	= $SITE['th'] . "companyinfo";	// 회사정보테이블
	$table_companytax	= $SITE['th'] . "companytax";	// 회사정보테이블
	
	// $dbinfo_cominfo 설정
	$dbinfo_cominfo = $dbinfo;
	$dbinfo_cominfo['table']	= $table_companyinfo;
	$dbinfo_cominfo['upload_dir'] = dirname(__FILE__) . "/upload/{$dbinfo_cominfo['table']}";

	// 넘어온값 기본 처리
	$qs_cominfo=array(
			"c_num1" =>  "post,trim",
			"c_num2" =>  "post,trim",
			"c_num3" =>  "post,trim",
			"c_idnum1" =>  "post,trim",
			"c_idnum2" =>  "post,trim",
			"c_name" =>  "post,trim,notnull=" . urlencode("회사명을 입력하시기 바랍니다."),
			"c_owner" =>  "post,trim,notnull=" . urlencode("대표자성명을 입력하시기 바랍니다."),
			"c_address" =>  "post,trim,notnull=" . urlencode("사업장주소를 입력하시기 바랍니다."),
			"c_kind" =>  "post,trim,notnull=" . urlencode("회사 업태를 입력하시기 바랍니다."),
			"c_detail" =>  "post,trim,notnull=" . urlencode("회사 종목을 입력하시기 바랍니다."),
			"c_tel" =>  "post,trim",
			"c_fax" =>  "post,trim",
			"tax_zip" =>  "post,trim",
			"tax_address" =>  "post,trim",
			"tax_tel" =>  "post,trim",
			"tax_fax" =>  "post,trim",
			"tax_hp" =>  "post,trim",
			"tax_email" =>  "post,trim",
			"comment" =>  "post,trim",
			"tax_name" =>  "post,trim",
			"status" =>  "post,trim",
		);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'comtax_confirm' : // payment rdate값 받아서, 세금계산서 발행
		comtax_publish();
		back('세금계산서가 발행되었습니다 . 인쇄하십시요',"comtax.php?rdate={$_REQUEST['rdate']}");
		break;
	case 'cominfo_write':
		$uid = cominfo_write_ok($dbinfo_cominfo, $qs_cominfo);
		//back_close('',$_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_write'] ? $dbinfo['goto_write'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		go_url("cominfo_confirm.php?cpid={$uid}");
		break;
	case 'cominfo_modify':
		cominfo_modify_ok($dbinfo_cominfo,$qs_cominfo,'uid');
		//back_close('',$_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_modify'] ? $dbinfo['goto_write'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic));
		go_url("cominfo_select.php?rdate={$_REQUEST['rdate']}");
		break;
	case 'cominfo_delete':
		//$goto = $_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_delete'] ? $dbinfo['goto_write'] : "{$thisUrl}/read.php?" . href_qs("uid={$uid}",$qs_basic);
		$goto = "cominfo_select.php?rdate={$_REQUEST['rdate']}";
		cominfo_delete_ok($dbinfo_cominfo,'uid',$goto);
		go_url($goto);
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// 세금계산서 발행 준비
function comtax_publish(){
	global $table_payment, $table_companyinfo, $table_companytax;
	
	// 넘어온값 체크
	// - payment rdate값, 사업자등록번호 넘어왔는지 체크
	if(!$_GET['rdate'] || !$_GET['c_num']) back('중요한 값이 넘어오지 않았습니다.');
	
	// - 세금계산서가 발행되어 있는지 체크
	$sql = "SELECT * from {$table_companytax} where bid='{$_SESSION['seUid']}' and paymentrdate='{$_GET['rdate']}'";
	if(db_arrayone($sql)) back('세금계산서가 이미 발행되어있습니다.','comtax.php');

	// - 회사 정보가 등록되어 있는지 체크
	$sql = "SELECT * from {$table_companyinfo} where c_num ='{$_GET['c_num']}'";
	$tocomp = db_arrayone($sql) or back('등록되어 있는 회사 정보가 없습니다.\\n먼저 회사정보를 입력하여야 합니다.');

	// 세금계산서 발행
	comtax_paymentTopublish($_SESSION['seUid'],1,$tocomp['uid'],$_GET['rdate']);
	
}

// 세금계산서 발행 
// 04/08/16 박선민
function comtax_paymentTopublish($bid,$from_cpid,$to_cpid,$rdate){
	global $table_payment, $table_companyinfo, $table_companytax, $db_conn;
	
	// 공급자정보 가져오기
	$sql = "SELECT * from {$table_companyinfo} where uid='{$from_cpid}'";
	$from = db_arrayone($sql) or back_close('공급자 정보가 잘못되었습니다 . 발행을 취소합니다.');
	if($from['status'] != 'OK') back('공급자 정보가 OK 상태가 아니여서 세금계산서 발행이 취소되었습니다.');
	
	// 공급자정보 가져오기
	$sql = "SELECT * from {$table_companyinfo} where uid='{$to_cpid}'";
	$to = db_arrayone($sql) or back_close('공급받는자 정보가 잘못되었습니다 . 발행을 취소합니다.');
	if($to['status'] != 'OK') back('공급받는자 정보가 OK 상태가 아니여서 세금계산서 발행이 취소되었습니다.');
	
	// $tax 초기화
	$tax	= array(
				'from_cpid' => $from_cpid,
				'to_cpid' => $to_cpid,
				'bid' => $bid,
				'paymentrdate' => $rdate,
				'from_c_num' => $from['c_num'],
				'from_c_name' => $from['c_name'],
				'from_c_owner' => $from['c_owner'],
				'from_c_address' => $from['c_address'],
				'from_c_kind' => $from['c_kind'],
				'from_c_detail' => $from['c_detail'],
				'to_c_num' => $to['c_num'],
				'to_c_name' => $to['c_name'],
				'to_c_owner' => $to['c_owner'],
				'to_c_address' => $to['c_address'],
				'to_c_kind' => $to['c_kind'],
				'to_c_detail' => $to['c_detail'],
				'total_money' =>  0, // 총 공급가액
				'total_tax' =>  0, // 총 세금
		);
	
	// payment 정보 가져오기
	$sql = "SELECT * from {$table_payment} where bid='{$bid}' and rdate='{$rdate}' order by ordertable";
	$rs_payment = db_query($sql);
	if(!$total = db_count($rs_payment)) back_close('세금계산서 발행할 정보가 없습니다 . 확인 바랍니다.');
	// 초기화
	$lasttax_supply	= 0; // 5건이상 공급가액 합계
	$lasttax_tax	= 0; // 5건이상 세금 합계
	for($i=1;$i<=$total;$i++) { // $i를 1부터 시작함
		$list = db_array($rs_payment);

		// 세금계산서는 내역일 5건만 넣기에, 
		// 5건이 넘으면 "상품명 외"로 포함하여 합계액을 넣음
		if($total>4 and $i>=4){
			if($total == $i) { // 마지막건이니
				// $tax 함수에 넣음
				$i = 4;
				$tax["date{$i}"]		= date('m/d',$list['rdate']);
				$tax["item{$i}"]		= $list['title'] . '외'.($total-4).'건';
				$tax["standard{$i}"]	= '';
				$tax["quantity{$i}"]	= '';
				$tax["price{$i}"]		= ''; // 단가인데 넣지 않음
				$tax["supply_money{$i}"]= $lasttax_supply;
				$tax["tax_money{$i}"]	= $lasttax_tax;
				$tax["etc{$i}"]			= $list['bank'];
				
				$tax['total_supply']	+= $tax["supply_money{$i}"]; // 총 공급가액
				$tax['total_tax']		+= $tax["tax_money{$i}"]; // 총 세금				
				$i = $total;
			} else {
				$lasttax_supply += round($list['price']*10/11); // 반올림
				$lasttax_tax	+= $list['price'] - round($list['price']*10/11);
			}
			continue;
		}
		
		// $tax 함수에 넣음
		$tax["date{$i}"]		= date('m/d',$list['rdate']);
		$tax["item{$i}"]		= $list['title'];
		$tax["standard{$i}"]	= $list['options'];
		$tax["quantity{$i}"]	= $list['quantity'] ?	$list['quantity']:1;
		$tax["price{$i}"]		= ''; // 단가인데 넣지 않음
		$tax["supply_money{$i}"]= round($list['price']*10/11); // 반올림
		$tax["tax_money{$i}"]	= $list['price'] - $tax["supply_money{$i}"];
		$tax["etc{$i}"]			= $list['bank'];
		
		$tax['total_supply']	+= $tax["supply_money{$i}"]; // 총 공급가액
		$tax['total_tax']		+= $tax["tax_money{$i}"]; // 총 세금				
	} // end for

	// $tax 마무리
	$tax['total_money']		= $tax['total_supply'] + $tax['total_tax'];
	$tax['total_date']		= date('y-m-d',$rdate);
	$tax['total_space']		= 11 - strlen((string)$tax['total_supply']);
	$tax['total_etc']		= "ID:{$list['userid']}";
	$tax['total_gubun']		= "영수";
	$tax['status']			= "승인";
	
	$sql="INSERT INTO $table_companytax SET
				`from_cpid`		='{$tax['from_cpid']}',
				`to_cpid`		='{$tax['to_cpid']}',
				`bid`			='{$tax['bid']}',
				`paymentrdate`	='{$tax['paymentrdate']}',
				`from_c_num`	='{$tax['from_c_num']}',
				`from_c_name`	='{$tax['from_c_name']}',
				`from_c_owner`	='{$tax['from_c_owner']}',
				`from_c_address`='{$tax['from_c_address']}',
				`from_c_kind`	='{$tax['from_c_kind']}',
				`from_c_detail`	='{$tax['from_c_detail']}',
				`to_c_num`		='{$tax['to_c_num']}',
				`to_c_name`		='{$tax['to_c_name']}',
				`to_c_owner`	='{$tax['to_c_owner']}',
				`to_c_address`	='{$tax['to_c_address']}',
				`to_c_kind`		='{$tax['to_c_kind']}',
				`to_c_detail`	='{$tax['to_c_detail']}',
				`date1`			='{$tax['date1']}',
				`item1`			='{$tax['item1']}',
				`standard1`		='{$tax['standard1']}',
				`quantity1`		='{$tax['quantity1']}',
				`price1`		='{$tax['price1']}',
				`supply_money1`	='{$tax['supply_money1']}',
				`tax_money1`	='{$tax['tax_money1']}',
				`etc1`			='{$tax['etc1']}',
				`date2`			='{$tax['date2']}',
				`item2`			='{$tax['item2']}',
				`standard2`		='{$tax['standard2']}',
				`quantity2`		='{$tax['quantity2']}',
				`price2`		='{$tax['price2']}',
				`supply_money2`	='{$tax['supply_money2']}',
				`tax_money2`	='{$tax['tax_money2']}',
				`etc2`			='{$tax['etc2']}',
				`date3`			='{$tax['date3']}',
				`item3`			='{$tax['item3']}',
				`standard3`		='{$tax['standard3']}',
				`quantity3`		='{$tax['quantity3']}',
				`price3`		='{$tax['price3']}',
				`supply_money3`	='{$tax['supply_money3']}',
				`tax_money3`	='{$tax['tax_money3']}',
				`etc3`			='{$tax['etc3']}',
				`date4`			='{$tax['date4']}',
				`item4`			='{$tax['item4']}',
				`standard4`		='{$tax['standard4']}',
				`quantity4`		='{$tax['quantity4']}',
				`price4`		='{$tax['price4']}',
				`supply_money4`	='{$tax['supply_money4']}',
				`tax_money4`	='{$tax['tax_money4']}',
				`etc4`			='{$tax['etc4']}',
				`total_supply`	='{$tax['total_supply']}',
				`total_tax`		='{$tax['total_tax']}',
				`total_money`	='{$tax['total_money']}',
				`total_cash`	='{$tax['total_cash']}',
				`total_check`	='{$tax['total_check']}',
				`total_bill`	='{$tax['total_bill']}',
				`total_credit`	='{$tax['total_credit']}',
				`total_date`	='{$tax['total_date']}',
				`total_space`	='{$tax['total_space']}',
				`total_etc`		='{$tax['total_etc']}',
				`total_gubun`	='{$tax['total_gubun']}',
				`rdate`			=UNIX_TIMESTAMP(),
				`rdate_send`	='{$tax['rdate_send']}',
				`rdate_receive`	='{$tax['rdate_receive']}',
				`rdate_reject`	='{$tax['rdate_reject']}',
				`rdate_resend`	='{$tax['rdate_resend']}',
				`rdate_cancle`	='{$tax['rdate_cancle']}',
				`status`	='{$tax['status']}'
		";
	db_query($sql);

	return db_insert_id();
}
function cominfo_write_ok(&$dbinfo, $qs){
	global $db_conn; // mysqli를 위해 추가

	// $qs 추가, 체크후 값 가져오기
	$qs=check_value($qs);
	
	// 사업자등록번호가 정확한지 체크
	if($qs['c_num1']){
		if(!$qs['c_num'] = check_compnum($qs['c_num1'],$qs['c_num2'], $qs['c_num3']))
			back('사업자등록번호를 정확히 입력해 주세요.');
		
		$qs['c_idnum'] = $qs['c_idnum1'] .'-'.	$qs['c_idnum2'];
	} else { // 주민번호로 입력되었다면
		$qs['c_idnum'] = check_idnum($qs['c_idnum1'],$qs['c_idnum2']);
		$qs['c_num']	= preg_replace('/[^0-9]/','',$qs['c_idnum']); // ereg_replace -> preg_replace
	}
	
	// 동일 사업자등록번호가 이미 등록되어 있으면 등록 불가
	$sql = "select * from {$dbinfo['table']} where bid='{$_SESSION['seUid']}' and c_num='{$qs['c_num']}'";
	if(db_arrayone($sql)) back('이미 등록된 사업자등록번호입니다.');
	
	// uid=1 즉 사이트 회사 정보와 동일한 사업자등록번호 입력 불가
	$sql = "select c_num from {$dbinfo['table']} where bid=1";
	if($qs['c_num'] == db_resultone($sql,0,'c_num'))
		back('당사 사이트 회사정보와 동일한 사업자등록번호를 입력하셨습니다 . 입력불가입니다.');
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('status', 'uid', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');

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
			foreach($_FILES as $key =>  $value){
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
				rdate	= UNIX_TIMESTAMP()
				{$sql_set_file}
				{$sql_set}
		";
	db_query($sql);
	$uid = db_insert_id();

	return $uid;
} // end func

function cominfo_modify_ok(&$dbinfo,$qs,$field){
	// $qs 추가, 체크후 값 가져오기
	$qs["{$field}"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	$qs['c_num'] = $qs['c_num1'].$qs['c_num2'].$qs['c_num3'];
	
	// 수정 권한 체크와 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and bid='{$_SESSION['seUid']}'";
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
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우

			$upfiles['upfile']['name']=$list['upfiles'];
			$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
		}
		$upfiles_totalsize=$list['upfiles_totalsize'];

		// 파일을 올리지 않고, 해당 파일을 삭제하고자 하였을때
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>  $value){
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
			foreach($_FILES as $key =>  $value){
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
// 삭제
function cominfo_delete_ok(&$dbinfo,$field,$goto){
	global $SITE, $qs_basic, $thisUrl;
	$qs=array(
			"{$field}" =>  "request,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
			'passwd' =>  "request,trim"
		);
	$qs=check_value($qs);

	// 삭제 권한 체크
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and bid='{$_SESSION['seUid']}'";
	$list=db_arrayone($sql) or back("삭제할 권한이 없습니다");
	
	// 세금계산서가 발행되어 있으면 삭제 불가
	$table_companytax = $SITE['th'] . 'companytax';
	$sql = "SELECT * from {$table_companytax} where from_c_num = '{$list['c_num']}' or to_c_num = '{$list['c_num']}'";
	if(db_arrayone($sql)) back('세금계산서가 발행된 사업자정보여서 삭제가 안됩니다');

	// 업로드 파일 삭제 준비
	$del_uploadfile = array(); // init
	if($list['upfiles']){
		$upfiles=unserialize($list['upfiles']);
		if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name']=$list['upfiles'];
			$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
		}
		foreach($upfiles as $key =>  $value){
			if($value['name']){
				if( is_file($dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/{$list['bid']}/" . $value['name'];
				elseif( is_file($dbinfo['upload_dir'] . "/" . $value['name']) )
					$del_uploadfile[] = $dbinfo['upload_dir'] . "/" . $value['name'];
			} // end if
		} // end foreach
	} // end if
	
	// 삭제
	db_query("DELETE FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}'");

	if(is_array($del_uploadfile)){
		foreach ( $del_uploadfile as $value){
			@unlink($value);
			@unlink($value.".thumb.jpg"); // thumbnail 삭제
		}
	} // end if

	return true;
} // end func delete_ok()

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
