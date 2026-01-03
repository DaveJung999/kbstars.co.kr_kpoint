<?php
//=======================================================
// 설	명 : 관리자 페이지 : 지불정보, 회원로그정보 검색
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/03/24
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/03/24 박선민 처음
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1,
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	include_once($thisPath.'config.php');

	// 넘어온값 처리
	$mode_get = $_GET['mode'] ?? 'paymentinfo';
	$bid_get = $_GET['bid'] ?? '';
	$userid_get = $_GET['userid'] ?? '';
	$tel_get = $_GET['tel'] ?? '';
	$hp_get = $_GET['hp'] ?? '';
	$order_get = $_GET['order'] ?? '';
	$msc_column_get = $_GET['msc_column'] ?? '';
	$msc_string_get = $_GET['msc_string'] ?? '';
	$startdate_get = $_GET['startdate'] ?? '';
	$enddate_get = $_GET['enddate'] ?? '';

	// table
	$table_logon	= $SITE['th'].'logon';
	$table_groupinfo= $SITE['th'].'groupinfo';
	$table_joininfo	= $SITE['th'].'joininfo';
	$table_payment	= $SITE['th'].'payment';
	$table_service	= $SITE['th'].'service';
	$table_loguser	= $SITE['th'].'log_userinfo';
	$table_log_wtmp	= $SITE['th'].'log_wtmp';
	$table_log_lastlog=$SITE['th'].'log_lastlog';
	
	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($bid_get) { $msc_column_get='logon.uid'; $msc_string_get=$bid_get;}
	elseif($userid_get) { $msc_column_get='logon.userid'; $msc_string_get=$userid_get;}
	elseif($tel_get) { $msc_column_get='logon.tel'; $msc_string_get=$tel_get;}
	elseif($hp_get) { $msc_column_get='logon.hp'; $msc_string_get=$hp_get;}
	elseif($order_get) { $msc_column_get='payment.num'; $msc_string_get=$order_get;}
	elseif(!$msc_column_get) { $msc_column_get='logon.userid'; $msc_string_get='%';}

	/////////////////////////////////
	// 회원 검색 및 회원정보 가져오기
	// - 넘어온값 체크
	$sql_table= explode('.',$msc_column_get);
	if(count($sql_table)!=2 || empty($msc_string_get)) go_url('msearch.php');
	
	// - $sql_where
	$msc_string_safe = db_escape($msc_string_get);
	if( strpos($msc_string_get, '%') !== false ) {
		if($msc_string_get=='%') $msc_string_safe = '%%';
		$sql_where	= " `({$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` like '{$msc_string_safe}') ";
	}
	else $sql_where	= " `({$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` = '{$msc_string_safe}') ";
	
	// - $sql문 완성
	$sql = '';
	switch ($sql_table['0']) {
		case 'logon' :
			$sql="SELECT *, email as msc_column FROM `{$SITE['th']}{$sql_table['0']}` WHERE  $sql_where ";
			break;
		case 'payment':
			$sql="SELECT {$table_logon}.*, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as msc_column FROM {$table_logon}, `{$SITE['th']}{$sql_table['0']}` WHERE {$table_logon}.uid=`{$SITE['th']}{$sql_table['0']}`.bid AND  $sql_where ";
			break;
	} // end switch
	
	$rs_msearch = $sql ? db_query($sql) : false;
	$count_msearch = $rs_msearch ? db_count($rs_msearch) : 0;

	// 결과값이 한명이 아니라면, 서치 페이지로 이동시킴.
	if($count_msearch != 1)
		go_url("msearch.php?mode={$mode_get}&msc_column={$msc_column_get}&msc_string=" . urlencode($msc_string_get));
	$logon = db_array($rs_msearch);
	db_free($rs_msearch);
	/////////////////////////////////

	// 넘오온값 체크
	// - startdate와 enddate가 없다면
	if($startdate_get == "") {
		$startdate_get = date("Y-m-d",time()-3600*24*30); // 한달전
	}
	$starttime = strtotime($startdate_get);

	if($enddate_get == "") {
		$enddate_get = date("Y-m-d");
	}
	$endtime = strtotime($enddate_get)+3600*24-1;
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$logon_uid_safe = (int)($logon['uid'] ?? 0);
$sql_where = " bid='{$logon_uid_safe}' AND rdate>='{$starttime}' AND rdate <='{$endtime}' AND re=''"; // init
$sql = "SELECT rdate, bid, num, totalprice, status FROM {$table_payment} WHERE $sql_where ORDER BY num DESC";
$rs_payment = db_query($sql);
$count_payment = $rs_payment ? db_count($rs_payment) : 0;

if(!$count_payment) {
	$tpl->process('LIST','nolist');
}
else {
	while($list = db_array($rs_payment)) {
		/////////////////////////
		// 주문 세부 리스트 처리
		$list_bid_safe = (int)($list['bid'] ?? 0);
		$list_num_safe = (int)($list['num'] ?? 0);
		$sql_cell = "SELECT * FROM {$table_payment} WHERE bid='{$list_bid_safe}' AND num='{$list_num_safe}' ORDER BY re";
		$rs_cell = db_query($sql_cell);
		while($cell = db_array($rs_cell)) {
			// 업로드파일 처리
			if(isset($cell['upfiles'])) {
				$upfiles=unserialize($cell['upfiles']);
				if(!is_array($upfiles)) {
					// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles = [];
					$upfiles['upfile']['name']=$cell['upfiles'];
					$upfiles['upfile']['size']=(int)($cell['upfiles_totalsize'] ?? 0);
				}
				foreach($upfiles as $key => $value) {
					if(isset($value['name']))
						$upfiles[$key]['href']="/smember/payment/paymentdownload.php?".href_qs("uid=".($cell['uid'] ?? 0)."&upfile={$key}","uid=");
				} // end foreach
				$cell['upfiles']=$upfiles;
				unset($upfiles);
			} // end if 업로드파일 처리

			// URL Link..
			$href['uidmodify'] = "inquirymodify.php?bid=".($cell['bid'] ?? 0)."&num=".($cell['num'] ?? 0)."&uid=".($cell['uid'] ?? 0)."#uidmodify";

			// 쇼핑몰이라면
			$tpl->drop_var('href.delete');			
			if(($cell['ordertype'] ?? '')=='shop2') {
				// 만약 쿠폰과 적립금 사용한 것이라면, 취소 넣음
				if(($cell['orderdb'] ?? '')=="coupon") {
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_coupon&uid=".($cell['uid'] ?? 0);
					$tpl->set_var('href.delete',$href['delete']);
				}
				elseif(($cell['orderdb'] ?? '')=="account") {
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_point&uid=".($cell['uid'] ?? 0);
					$tpl->set_var('href.delete',$href['delete']);
				}
				// 상품정보 가져오기
				elseif(isset($cell['orderdb']) && $cell['orderdb']!='배송료') {
					$shop_table = $SITE['th'].'shop2_'.db_escape($cell['orderdb']);
					$orderuid_safe = (int)($cell['orderuid'] ?? 0);
					$sql_shop = "SELECT uid,brand,price,code,publiccode FROM `{$shop_table}` WHERE uid='{$orderuid_safe}'";
					
					// db_istable 함수를 mysqli로 대체
					$res_istable = db_query("SHOW TABLES LIKE '".db_escape($shop_table) . "'");
					if($res_istable && db_count($res_istable) > 0) {
						$res_shop = db_query($sql_shop);
						$cell['shop'] = $res_shop ? db_array($res_shop) : null;
					}

					// URL Link..
					$href['shop'] = "/sshop2/read.php?db=".($cell['orderdb'] ?? '')."&uid=".($cell['orderuid'] ?? 0);
				}
				else $href['shop'] = '';
			}
			else $href['shop'] = '';
			
			$tpl->set_var('href.uidmodify'	,$href['uidmodify']);
			$tpl->set_var('href.shop',$href['shop']);
			$tpl->set_var('list'			,$cell);
			$tpl->set_var('list.rdate_date'	,date("Y-m-d [H:i:s]",$cell['rdate'] ?? time()));
			$tpl->set_var('list.price'		,number_format($cell['price'] ?? 0));

			$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
			// $tpl->drop_var('list');
		}
		db_free($rs_cell);
		/////////////////////////

		// URL Link...
		$href['inquirydetail'] = "../money/inquirydetail.php?bid=".($list['bid'] ?? 0)."&num=".($list['num'] ?? 0);
		$href['rdatemodify'] = "../money/inquirymodify.php?bid=".($list['bid'] ?? 0)."&num=".($list['num'] ?? 0);
		// 상태변경부분
		$href['newstatus'] = "../money/paymentok.php?mode=newstatus&bid=".($list['bid'] ?? 0)."&num=".($list['num'] ?? 0)."&status=".urlencode($list['status'] ?? '');

		$tpl->set_var('list',$list);

		$tpl->set_var('href.inquirydetail',$href['inquirydetail']);
		$tpl->set_var('href.rdatemodify',$href['rdatemodify']);
		$tpl->set_var('href.newstatus',$href['newstatus']);
		$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$list['rdate'] ?? time()));
		$tpl->set_var('list.totalprice'		,number_format($list['totalprice'] ?? 0));
		
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		$tpl->drop_var('CELL');
	} // end for
	db_free($rs_payment);
} // end if.. else..

// ===================================
// 회원 로그 파일 출력
// ===================================
	$rs_loguser = db_query("SELECT * FROM {$table_loguser} WHERE userbid='{$logon_uid_safe}' ORDER BY rdate DESC");
	while($row=db_array($rs_loguser)) {
		$row['rdate_date']=date('y-m-d',$row['rdate']);
		
		$logon_bid_safe = (int)($row['bid'] ?? 0);
		$res_logon = db_query("SELECT * FROM {$table_logon} WHERE uid={$logon_bid_safe}");
		$row['logon'] = $res_logon ? db_array($res_logon) : null;
		db_free($res_logon);
		
		// 문서 형식에 맞추어서 내용 변경
		$row['content'] = replace_string($row['content'], (substr($row['content'],0,1)=="<")?"html":"text");	

		// URL Link..
		$href['delete']="./ok.php?mode=loguserdelete&uid=".($row['uid'] ?? 0)."&bid=".($row['bid'] ?? 0);

		$tpl->set_var('href.delete',$href['delete']);
		$tpl->set_var('loguserlist',$row);
		$tpl->process('LOGUSERLIST','loguserlist',TPL_OPTIONAL|TPL_APPEND);
	} // end while
	db_free($rs_loguser);
	$form_loguser = " method='post' action='ok.php'>";
	$form_loguser .= substr(href_qs("mode=loguserwrite&userbid={$logon_uid_safe}",'userbid=',1),0,-1);
	$tpl->set_var('form_loguser',$form_loguser);


// 템플릿 할당
$sql_lastlog = "SELECT from_unixtime(rdate,'%Y-%m-%d [%H:%i]') as `date` FROM {$table_log_lastlog} WHERE bid='{$logon_uid_safe}' AND gid=0 LIMIT 0,1";
$res_lastlog = db_query($sql_lastlog);
$date_lastlog = ($res_lastlog && db_count($res_lastlog) > 0) ? db_array($res_lastlog)['date'] : '';
db_free($res_lastlog);
$tpl->set_var('date_lastlog',$date_lastlog);

$logon_userid_safe = db_escape($logon['userid'] ?? '');
$sql_recmder = "SELECT count(*) as count FROM {$table_logon} WHERE recommender='{$logon_userid_safe}'";
$res_recmder = db_query($sql_recmder);
$count_recmder = ($res_recmder) ? (int)(db_array($res_recmder)['count'] ?? 0) : 0;
db_free($res_recmder);
$tpl->set_var('count_recmder',$count_recmder);

$form_default = " method='get' action='{$_SERVER['PHP_SELF']}'>";
$form_default .= substr(href_qs("msc_column={$msc_column_get}&msc_string=".urlencode($msc_string_get),'msc_column=',1),0,-1);
$tpl->set_var('form_default',$form_default);

// 템플릿 마무리 할당
$tpl->tie_var('dbinfo'			,($dbinfo ?? []));
$tpl->set_var('href'			,($href ?? []));
$tpl->tie_var('get'				,($_GET ?? []));
$tpl->set_var('logon'			,($logon ?? []));

// - 회원전체 서치 부분
$tpl->set_var('count_msearch', $count_msearch ?? 0);
$tpl->set_var('get.msc_string',htmlspecialchars(stripslashes($msc_string_get),ENT_QUOTES));
$form_msearch = " method=get action='{$_SERVER['PHP_SELF']}'> ";
$form_msearch .= substr(href_qs("mode={$mode_get}",'mode=',1),0,-1);
$tpl->set_var('form_msearch',$form_msearch);

$tpl->set_var('startdate', $startdate_get);
$tpl->set_var('enddate', $enddate_get);
$tpl->set_var('status', $_GET['status'] ?? '');
$aTemp = ['status' => $_GET['status'] ?? ''];
userEnumSetFieldsToOptionTag($table_payment, $aTemp);
$tpl->set_var('status_option',$aTemp['status_option']);

// 마무리
// ereg_replace를 preg_replace로 변경
$replacement = '$1' . $thisUrl.'skin/'.($dbinfo['skin'] ?? 'basic').'/images/';
$pattern = '/([="\'])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html',TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================

/**
 * enum,set필드라면, $list['필드_option'] 만들어줌 (Modernized version)
 * @param string $table
 * @param array &$list
 */
function userEnumSetFieldsToOptionTag(string $table, array &$list){
	// SHOW FIELDS는 db_query를 사용하여 여러 행을 가져옵니다.
	$table_safe = db_escape($table);
	$table_def = db_query("SHOW FIELDS FROM {$table_safe}");
	if (!$table_def) {
		return;
	}

	while ($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		// preg_replace 수정: 괄호와 내부 내용만 제거하도록
		$row_table_def['True_Type'] = preg_replace('/\([^\)]*\)/', '', $row_table_def['Type']);

		if ($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field] ?? null);
		} elseif ($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field] ?? '');
		} else {
			continue;
		}

		$return = '';

		// The value column (depends on type)
		// ----------------
		$enum = substr($row_table_def['Type'], strpos($row_table_def['Type'], '(') + 1, -1);
		$enum = explode("','", $enum);

		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace("''", "'", str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . '"';
			if ((isset($list[$field]) && in_array($enum_atom, $aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
					&& $enum_atom == ($row_table_def['Default'] ?? ''))
			) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . "</option>\n";
		} // end for
		
		$list[$field . '_option'] = $return;
	} // end for
	db_free($table_def);
} // end function
?>