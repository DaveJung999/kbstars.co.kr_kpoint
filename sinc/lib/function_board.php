<?php
//=======================================================
// 설 명 : 게시판 사용에 있어서 사용되는 함수 모음 (function_board.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/17
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인		수정 내용
// -------- ------ --------------------------------------
// 04/01/13 박선민 bugfix - boardCateinfo()
// 04/05/17 박선민 boardSqlSort() 일부 수정
// 24/08/10 Gemini	PHP 7+ 호환성 업데이트 (mysql->mysqli, ereg->preg, session_register 제거)
//=======================================================

/*
 * [참고 - 본문에서 nl2br 적용 후 <table> 태그에 <br> 붙는 것 삭제 처리 로직 수정]
 * 이 로직은 preg_* 함수를 사용하도록 수정되었습니다.
 */
/*
	$memo = nl2br(stripslashes($data['memo'])); // 내용
	// 테이블 태그는 <br> 태그가 붙지 않도록 처리
	$memo_parts = explode("<br />", $memo); // nl2br은 <br />을 생성할 수 있습니다.
	$check_table = 0;
	$check_table2 = 0;
	$processed_memo = "";

	foreach ($memo_parts as $part){
		if (preg_match("/<table/i", $part)) $check_table++;
		if (preg_match("/<\/table/i", $part)) $check_table--;

		if (preg_match("/<td/i", $part)) $check_table2++;
		if (preg_match("/<\/td/i", $part)) $check_table2--;

		$processed_memo .= $part;
		if (!$check_table && !$check_table2){
			$processed_memo .= "<br>";
		} elseif($check_table && $check_table2){
			$processed_memo .= "<br>";
		} else {
			$processed_memo = str_replace("&nbsp;", " ", $processed_memo);
		}
	}
	// 마지막에 추가된 불필요한 <br> 제거
	if (substr($processed_memo, -4) === '<br>'){
		$processed_memo = substr($processed_memo, 0, -4);
	}
	$memo = $processed_memo;
*/

/**
 * 게시판 권한 체크 함수
 * @param mysqli $conn DB 커넥션 객체
 * @param array $dbinfo 게시판 정보 배열
 * @param string $auth_priv 체크할 권한 키 (예: 'auth_list')
 * @param int $go_loginpage 로그인 페이지로 이동할지 여부 (1: 이동, 0: 이동 안 함)
 * @return bool 권한이 있으면 true, 없으면 false 반환
 */
function boardAuth(mysqli $conn, array $dbinfo, string $auth_priv, int $go_loginpage = 0): bool
{
	if (!empty($dbinfo[$auth_priv])) { // 권한 설정 유무
		if (isset($_SESSION['seUid'])) { // 로그인 여부
			// 게시판 생성자거나 최고 관리자('root')라면 무조건 통과
			if ($dbinfo['bid'] == $_SESSION['seUid'] || (isset($_SESSION['class']) && $_SESSION['class'] == 'root' && empty($dbinfo['bid']))){
				return true;
			}

			if (!empty($dbinfo['gid'])) { // 그룹 설정 여부
				if (isset($_SESSION['seGroup'][$dbinfo['gid']]) && $_SESSION['seGroup'][$dbinfo['gid']] == "root") { // 그룹장이면 통과
					return true;
				}
				if ($dbinfo[$auth_priv] > ($_SESSION['seGroup'][$dbinfo['gid']] ?? 0) || ($_SESSION['seLevel'] ?? 0) < 1){
					return false;
				}
				return true;
			}

			if ($dbinfo[$auth_priv] > ($_SESSION['seLevel'] ?? 0) || ($_SESSION['seLevel'] ?? 0) < 1){
				return false;
			}
			return true;
		} else { // 비로그인 상태
			if ($go_loginpage) { // 로그인 페이지로 이동이 필요할 경우
				$_SESSION['seREQUEST_URI'] = $_SERVER['REQUEST_URI'];
				// session_register()는 제거됨. $_SESSION에 직접 할당하면 세션에 등록됩니다.
				session_write_close();
				// go_url()은 사용자 정의 함수로 가정합니다.
				go_url("/sjoin/login.php", 0, "로그인이 필요합니다.\\n\\n로그인 페이지로 이동합니다.");
				exit;
			}
			return false;
		}
	}
	return true; // 권한 설정이 없으면 누구나 통과
}


/**
 * 페이지네이션 계산 함수
 * @param int $total 전체 게시물 수
 * @param int $nowpage 현재 페이지 번호
 * @param int $pern 페이지당 게시물 수
 * @param int $page_pern 블록당 페이지 수
 * @return array 페이지네이션 관련 정보 배열
 */
function boardCount(int $total, int $nowpage, int $pern = 5, int $page_pern = 5): array
{
	$count = [];
	$count['total'] = $total;
	$count['nowpage'] = $nowpage;
	$count['pern'] = $pern ?: 5; // 페이지당 게시물 수

	// 총 페이지 수
	$count['totalpage'] = ($count['total'] > 0) ? (int)ceil($count['total'] / $count['pern']) : 1;

	// 현재 페이지 유효성 검사
	if ($count['nowpage'] <= 0) $count['nowpage'] = 1;
	if ($count['nowpage'] > $count['totalpage']) $count['nowpage'] = $count['totalpage'];

	// SQL LIMIT 문의 시작 위치
	$count['firstno'] = ($count['nowpage'] - 1) * $count['pern'];

	// 이전, 다음 페이지
	$count['prevpage'] = max(1, $count['nowpage'] - 1);
	$count['nextpage'] = min($count['totalpage'], $count['nowpage'] + 1);

	// 목록의 시작 번호
	$count['lastnum'] = $count['total'] - $count['firstno'];

	// 블록당 페이지 수
	$count['page_pern'] = $page_pern ?: 5;

	// 현재 페이지 블록
	$count['nowblock'] = (int)ceil($count['nowpage'] / $count['page_pern']);

	// 현재 블록의 시작과 끝 페이지
	$count['firstpage'] = ($count['nowblock'] - 1) * $count['page_pern'] + 1;
	$count['lastpage'] = min($count['totalpage'], $count['nowblock'] * $count['page_pern']);

	return $count;
}

/**
 * 카테고리 정보 반환 함수
 * @param mysqli $conn DB 커넥션 객체
 * @param array $dbinfo 게시판 정보
 * @param string $table_cate 카테고리 테이블명
 * @param int $cateuid 현재 카테고리 UID
 * @param string $enable_catelist 카테고리 리스트(<option>) 생성 여부
 * ... (다른 파라미터들은 원본과 동일)
 * @return array 카테고리 정보 배열
 */
function boardCateinfo(mysqli $conn, array &$dbinfo, string $table_cate, int $cateuid = 0, string $enable_catelist = 'Y', bool $tmp_sw_view_topcatetitles = true, bool $tmp_sw_view_cate_notitems = true, bool $tmp_sw_view_cate_itemcount = true, string $string_view_firsttotal = "(전체)"): array
{
	$sql_where_common = "1";
	if (($dbinfo['table_name'] ?? '') != ($dbinfo['db'] ?? '')){
		$sql_where_common = " db='" . db_escape($dbinfo['db'] ?? '') . "' AND ";
	}

	if (($dbinfo['enable_type'] ?? 'N') == 'Y'){
		$sql_where = $sql_where_common . " type='docu' ";
		$sql_where_cate = $sql_where_common . " type='cate' ";
	} else {
		$sql_where = $sql_where_cate = $sql_where_common . " 1 ";
	}

	$cateinfo = [];
	$catelist = '';

	if ($enable_catelist == 'Y'){
		if (empty($dbinfo['uid'])) { // 게시판이 아닌 카테고리 테이블에서 직접 사용할 경우
			$tmp_sw_view_cate_notitems = true;
			$tmp_sw_view_cate_itemcount = false;
		}

		// 카테고리별 아이템 수 계산
		if (!$tmp_sw_view_cate_notitems || $tmp_sw_view_cate_itemcount){
			$table = preg_replace("/_cate$/i", "", $table_cate);
			// db_query 함수를 사용하도록 수정
			$rs_count_per_cate = db_query("SELECT cateuid, COUNT(*) AS count FROM {$table} WHERE $sql_where GROUP BY cateuid");
			$tmp_cate_count = [];
			if ($rs_count_per_cate){
				while ($row = db_array($rs_count_per_cate)){
					$tmp_cate_count[$row['cateuid']] = $row['count'];
				}
				db_free($rs_count_per_cate);
			}
		}

		if ($string_view_firsttotal){
			$catelist = "<option value=''>{$string_view_firsttotal}</option>";
		}
	}

	if ($cateuid){
		// db_query 함수를 사용하도록 수정
		$rs_cateinfo = db_query("SELECT * FROM {$table_cate} WHERE {$sql_where_cate} AND uid = '" . db_escape($cateuid) . "'");
		if (db_count($rs_cateinfo) > 0){
			$cateinfo = db_arrayone($rs_cateinfo);
		} else {
			// back()은 사용자 정의 함수로 가정.
			back("없는 카테고리입니다.");
		}
		db_free($rs_cateinfo);
		
		$cateinfo['subcate_uid'][] = $cateinfo['uid'];

		if ($tmp_len_re = strlen($cateinfo['re'])){
			$cateinfo['re_beforekey'] = $cateinfo['num'];
			for ($i = 1; $i < $tmp_len_re; $i++){
				$cateinfo['re_beforekey'] .= "-" . ord(substr($cateinfo['re'], $i - 1, 1));
			}
			$cateinfo['re_key'] = $cateinfo['re_beforekey'] . "-" . ord(substr($cateinfo['re'], -1));
		} else {
			$cateinfo['re_key'] = $cateinfo['num'];
		}
	}

	// db_query 함수를 사용하도록 수정
	$rs_cate = db_query("SELECT * FROM {$table_cate} WHERE {$sql_where_cate} ORDER BY num, re");
	if ($rs_cate && db_count($rs_cate) > 0){
		$tmp_cate = [];
		$tmp_before_num = -1;
		$tmp_re_key = [];

		while ($list_cate = db_array($rs_cate)){
			if ($tmp_len_re = strlen($list_cate['re'])){
				$list_cate['re_beforekey'] = $list_cate['num'];
				for ($i_tmp_len_re = 1; $i_tmp_len_re < $tmp_len_re; $i_tmp_len_re++){
					$list_cate['re_beforekey'] .= "-" . ord(substr($list_cate['re'], $i_tmp_len_re - 1, 1));
				}
				$list_cate['re_key'] = $list_cate['re_beforekey'] . "-" . ord(substr($list_cate['re'], -1));
			} else {
				$list_cate['re_key'] = $list_cate['num'];
			}

			$tmp_re_key[$list_cate['re_key']] = $list_cate['uid'];
			if (substr_count($list_cate['re_beforekey'], "-") > 0){
				$list_cate['re_beforebeforekey'] = substr($list_cate['re_beforekey'], 0, strrpos($list_cate['re_beforekey'], "-"));
			} else {
				$list_cate['re_beforebeforekey'] = "";
			}

			if (!$cateuid) { // 전체 카테고리
				if (strlen($list_cate['re']) == 0){
					$cateinfo['subcate'][$list_cate['uid']] = $list_cate['title'];
				} elseif(strlen($list_cate['re']) == 1){
					$cateinfo['subsubcate'][$tmp_re_key[$list_cate['re_beforekey']]][$list_cate['uid']] = $list_cate['title'];
				}
			} else { // 특정 카테고리 선택 시
				if ($list_cate['re_beforekey'] == $cateinfo['re_beforekey']){
					$cateinfo['samecate'][$list_cate['uid']] = $list_cate['title'];
				} elseif($list_cate['num'] == $cateinfo['num']){
					if (preg_match('/^' . preg_quote($list_cate['re_key']) . '-/', $cateinfo['re_key'])){
						$cateinfo['highcate'][$list_cate['uid']] = $list_cate['title'];
					} elseif(preg_match('/^' . preg_quote($cateinfo['re_key']) . '-/', $list_cate['re_key'])){
						$cateinfo['subcate_uid'][] = $list_cate['uid'];
						if ($list_cate['re_beforekey'] == $cateinfo['re_key']){
							$cateinfo['subcate'][$list_cate['uid']] = $list_cate['title'];
						} elseif($list_cate['re_beforebeforekey'] == $cateinfo['re_key']){
							$cateinfo['subsubcate'][$tmp_re_key[$list_cate['re_beforekey']]][$list_cate['uid']] = $list_cate['title'];
						}
					}
				}
			}
			
			if ($enable_catelist == 'Y'){
				if ($list_cate['num'] != $tmp_before_num){
					$tmp_before_num = $list_cate['num'];
					unset($tmp_cate);
					$tmp_cate = [];
				}
				$tmp_cate[strlen($list_cate['re'])] = $list_cate['title'];

				if (!$tmp_sw_view_cate_notitems && empty($tmp_cate_count[$list_cate['uid']])) continue;

				if (empty($dbinfo['cate_depth']) || (!empty($dbinfo['cate_depth']) && $dbinfo['cate_depth'] == strlen($list_cate['re']) + 1)){
					$option_title = $list_cate['title'];
					if ($tmp_sw_view_topcatetitles){
						$full_title = [];
						for ($count_title = 0; $count_title < strlen($list_cate['re']); $count_title++){
							if(isset($tmp_cate[$count_title])) $full_title[] = $tmp_cate[$count_title];
						}
						$full_title[] = $list_cate['title'];
						$option_title = implode(" > ", $full_title);
					}
					if ($tmp_sw_view_cate_itemcount && !empty($tmp_cate_count[$list_cate['uid']])){
						$option_title .= " (" . $tmp_cate_count[$list_cate['uid']] . ")";
					}
					
					$selected = ($list_cate['uid'] == $cateuid) ? 'selected' : '';
					$catelist .= "<option value='{$list_cate['uid']}' {$selected}>" . htmlspecialchars($option_title) . "</option>";
				}
			}
		}
		db_free($rs_cate);
	}
	
	if ($enable_catelist == 'Y') $cateinfo['catelist'] = $catelist;
	return $cateinfo;
}


/**
 * SQL 정렬(ORDER BY) 문자열 생성 함수
 * @param mysqli $conn DB 커넥션 객체
 * @param string $table 테이블명
 * @param string $sort 정렬 기준 문자열 (예: 'name,!date')
 * @return string SQL의 ORDER BY 절에 들어갈 문자열
 */
function boardSqlSort(mysqli $conn, string $table, string $sort): string
{
	if (empty($table) || empty($sort)) return ' 1 ';

	// db_query 함수를 사용하도록 수정
	$rs = db_query("SHOW COLUMNS FROM `" . db_escape($table) . "`");
	if (!$rs) return ' 1 '; // 쿼리 실패 시 기본값 반환

	$columns = [];
	while ($row = db_array($rs)){
		$columns[] = $row['Field'];
	}
	db_free($rs);
	
	if (empty($columns)) return ' 1 ';

	$aSort = explode(',', $sort);
	$aReturn = [];
	foreach ($aSort as $s){
		$s = trim($s);
		$sort_option = ' ASC'; // 기본 오름차순
		if (strpos($s, '!') === 0){
			$s = substr($s, 1);
			$sort_option = ' DESC'; // '!'가 있으면 내림차순
		}

		// 실제 테이블에 존재하는 필드인지 확인 후 정렬 배열에 추가
		if (in_array($s, $columns)){
			$aReturn[] = "`" . db_escape($s) . "`" . $sort_option;
		}
	}

	return count($aReturn) > 0 ? implode(', ', $aReturn) : ' 1 ';
}

///////////////////////
// 만들어봤지만 사용 안함 (PHP 7+ 호환 수정)
// 필드에 대한 input box의 name값, 폼 사이즈, 최대 길이를 자동으로 구함
/*
function inputfield(mysqli $conn, string $table, ?array $list_uid = null): array
{
	$table_def_rs = db_query("SHOW FIELDS FROM `" . db_escape($table) . "`");
	$inputfield = [];

	if ($table_def_rs){
		while ($row_table_def = db_array($table_def_rs)){
			$field = $row_table_def['Field'];
			$type = preg_replace('/\(.* /', '', $row_table_def['Type']);

			if (preg_match('/char|int/i', $type)){
				$len = preg_replace('/.*\(([0-9]+)\).* /', '$1', $row_table_def['Type']);
				$data = '';

				if ($list_uid !== null){
					$data = htmlspecialchars($list_uid[$field] ?? '');
				} else {
					if (isset($row_table_def['Default'])){
						$data = htmlspecialchars($row_table_def['Default']);
					}
				}

				$fieldsize = ($len < 4) ? 4 : min($len, 40);
				$maxlength = $len;

				$inputfield[$field] = " name='{$field}' value='{$data}' size='{$fieldsize}' maxlength='{$maxlength}'";
			}
		}
		db_free($table_def_rs);
	}
	return $inputfield;
}
*/
?>