<?php
//=======================================================
// 설	명 : 메인페이지(/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/03/28
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/03/28 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, 
		'useSkin' => 1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$dbinfo	= array(
			'skin' => 'basic',
			'html_type' => 'ht', 
			'html_skin' => '2015_d12'
		);

	$table_logon	= $SITE['th'] . "logon";
	$table_seller	= $SITE['th'] . "seller";

	$form_changepasswd=" action=$Action_domain/smember/profileok.php method=post>
				<input type=hidden name=mode value=changepasswd
			";
	$form_changeprofile=" action=$Action_domain/smember/profileok.php method=post>
				<input type=hidden name=mode value=changeprofile
			";
	
	$sql = "SELECT * from {$table_logon} WHERE uid='{$_SESSION['seUid']}' and userid='{$_SESSION['seUserid']}'";
	$logon		= db_arrayone($sql)	or back("회원님의 회원 정보를 읽지 못하였습니다.\\n로그아웃되며 다시 로그인하여 이용하여주시기 바랍니다.\\n\\n계속 문제 발생시 종합질문페이지에 문의 바랍니다.","/sjoin/logout.php");

	$logon['zip'] = explode("-",$logon['zip']);

	// 생일 파싱
	$logon['birth_mm']	= substr($logon['birth'],0,2);
	$logon['birth_dd']	= substr($logon['birth'],2,2);
	$logon['birth_lunar']= substr($logon['birth'],4,1);
	$logon['birth_yyyy'] = substr($logon['birth'],5,4);
	if(strlen($logon['birth_yyyy']) == 4){
		$logon['birth_date'] = $logon['birth_yyyy'] . "-" . $logon['birth_mm'] . "-" . $logon['birth_dd'];
		if($logon['birth_lunar'] == "-") $logon['birth_lunar_c'][1]=" selected ";
		else $logon['birth_lunar_c'][0]=" selected ";
	}
	
	// 전화 파싱
	$tmp = explode('-',$logon['tel']);
	$logon['tel1'] = $tmp[0];
	$logon['tel2'] = $tmp[1];
	$logon['tel3'] = $tmp[2];
	
	// 핸드폰 파싱
	$tmp = explode('-',$logon['hp']);
	$logon['hp1'] = $tmp[0];
	$logon['hp2'] = $tmp[1];
	$logon['hp3'] = $tmp[2];

	// 메일 수신여부 파싱
	$logon['yesmail_c']["{$logon['yesmail']}"]=" checked ";

	// SMS 수신여부 파싱
	$logon['yessms_c']["{$logon['yessms']}"]=" checked ";

	// 국가선택
	$select_option_country_{$logon['country']} = " selected ";
	$select_option_country="
				<option value='kr' {$select_option_country_kr}>한국 
				<option value='us' {$select_option_country_us}>미국 
				<option value='jp' {$select_option_country_jp}>일본 
				<option value='gh' {$select_option_country_gh}>가나 
				<option value='ga' {$select_option_country_ga}>가봉 
				<option value='gm' {$select_option_country_gm}>감비아 
				<option value='ge' {$select_option_country_ge}>그루지야 
				<option value='gr' {$select_option_country_gr}>그리스 
				<option value='na' {$select_option_country_na}>나미비아 
				<option value='aq' {$select_option_country_aq}>남극대륙 
				<option value='ni' {$select_option_country_ni}>니카라과 
				<option value='de' {$select_option_country_de}>독일 
				<option value='bm' {$select_option_country_bm}>버뮤다 
				<option value='mk' {$select_option_country_mk}>마케도니아 
				<option value='ht' {$select_option_country_ht}>아이티 
				<option value='kz' {$select_option_country_kz}>카자흐스탄 
				<option value='bz' {$select_option_country_bz}>벨리즈 
				<option value='cy' {$select_option_country_cy}>키프로스 
				<option value='pe' {$select_option_country_pe}>페루 
				<option value='cu' {$select_option_country_cu}>쿠바 
				<option value='hu' {$select_option_country_hu}>헝가리 
				<option value='bb' {$select_option_country_bb}>바베이도스 
				<option value='il' {$select_option_country_il}>이스라엘 
				<option value='io' {$select_option_country_io}>영국령 인도양 
				<option value='ml' {$select_option_country_ml}>말리 
				<option value='ec' {$select_option_country_ec}>에콰도르 
				<option value='cl' {$select_option_country_cl}>칠레 
				<option value='ro' {$select_option_country_ro}>루마니아 
				<option value='mr' {$select_option_country_mr}>모리타니아 
				<option value='la' {$select_option_country_la}>라오스 
				<option value='sc' {$select_option_country_sc}>세이셀 제도 
				<option value='tz' {$select_option_country_tz}>탄자니아 
				<option value='ru' {$select_option_country_ru}>러시아 
				<option value='ar' {$select_option_country_ar}>아르헨티나 
				<option value='ck' {$select_option_country_ck}>쿡 섬 
				<option value='dj' {$select_option_country_dj}>지부티 
				<option value='vi' {$select_option_country_vi}>버진 제도 
				<option value='tn' {$select_option_country_tn}>튀니지 
				<option value='kp' {$select_option_country_kp}>북한 
				<option value='tt' {$select_option_country_tt}>트리니나드토바고 
				<option value='ye' {$select_option_country_ye}>예멘 
				<option value='fj' {$select_option_country_fj}>피지 제도 
				<option value='dk' {$select_option_country_dk}>덴마크 
				<option value='hr' {$select_option_country_hr}>크로아티아 
				<option value='mv' {$select_option_country_mv}>몰디브 
				<option value='th' {$select_option_country_th}>태국 
				<option value='ph' {$select_option_country_ph}>필리핀 
				<option value='se' {$select_option_country_se}>스웨덴 
				<option value='jm' {$select_option_country_jm}>자마이카 
				<option value='au' {$select_option_country_au}>호주 
				<option value='pr' {$select_option_country_pr}>푸에르토리코 
				<option value='ag' {$select_option_country_ag}>앤티가, 바부다 
				<option value='ai' {$select_option_country_ai}>안귈라 
				<option value='ve' {$select_option_country_ve}>베네수엘라 
				<option value='mt' {$select_option_country_mt}>몰타 
				<option value='fr' {$select_option_country_fr}>프랑스 
				<option value='ug' {$select_option_country_ug}>우간다 
				<option value='kh' {$select_option_country_kh}>캄보디아 
				<option value='is' {$select_option_country_is}>아이슬랜드 
				<option value='gt' {$select_option_country_gt}>과테말라 
				<option value='mm' {$select_option_country_mm}>버마 
				<option value='gi' {$select_option_country_gi}>지브롤터 
				<option value='sl' {$select_option_country_sl}>시에라리온 
				<option value='at' {$select_option_country_at}>오스트리아 
				<option value='bi' {$select_option_country_bi}>부룬디 
				<option value='fi' {$select_option_country_fi}>핀란드 
				<option value='pl' {$select_option_country_pl}>폴란드 
				<option value='mq' {$select_option_country_mq}>마르티니크 섬 
				<option value='no' {$select_option_country_no}>노르웨이 
				<option value='cn' {$select_option_country_cn}>중국 
				<option value='dm' {$select_option_country_dm}>도미니카 
				<option value='so' {$select_option_country_so}>소말리아 
				<option value='ky' {$select_option_country_ky}>케이만 제도 
				<option value='eg' {$select_option_country_eg}>이집트 
				<option value='bj' {$select_option_country_bj}>베냉 
				<option value='uy' {$select_option_country_uy}>우루과이 
				<option value='ci' {$select_option_country_ci}>아이보리 해안 
				<option value='cg' {$select_option_country_cg}>콩고 
				<option value='sk' {$select_option_country_sk}>슬로바키아 
				<option value='sd' {$select_option_country_sd}>수단 
				<option value='rw' {$select_option_country_rw}>르완다 
				<option value='tv' {$select_option_country_tv}>투발루 
				<option value='re' {$select_option_country_re}>리유니온, 어소우시에티드 제도 
				<option value='lv' {$select_option_country_lv}>라트비아 
				<option value='sr' {$select_option_country_sr}>수리남 
				<option value='co' {$select_option_country_co}>콜롬비아 
				<option value='sy' {$select_option_country_sy}>시리아 
				<option value='gu' {$select_option_country_gu}>괌 
				<option value='ir' {$select_option_country_ir}>이란 
				<option value='tp' {$select_option_country_tp}>티모르 
				<option value='om' {$select_option_country_om}>오만 
				<option value='fk' {$select_option_country_fk}>포클랜드 제도 
				<option value='bs' {$select_option_country_bs}>바하마 
				<option value='iq' {$select_option_country_iq}>이라크 
				<option value='pt' {$select_option_country_pt}>포르투갈 
				<option value='cv' {$select_option_country_cv}>케이프 버드 
				<option value='zw' {$select_option_country_zw}>짐바브웨 
				<option value='my' {$select_option_country_my}>말레이시아 
				<option value='ba' {$select_option_country_ba}>보스니아, 헤르째고비나 
				<option value='by' {$select_option_country_by}>벨로루시아(전 USSR) 
				<option value='zm' {$select_option_country_zm}>잠비아 
				<option value='vn' {$select_option_country_vn}>베트남 
				<option value='cm' {$select_option_country_cm}>카메룬 
				<option value='bf' {$select_option_country_bf}>부르키나파소 
				<option value='ca' {$select_option_country_ca}>캐나다 
				<option value='cr' {$select_option_country_cr}>코스타리카 
				<option value='mz' {$select_option_country_mz}>모잠비크 
				<option value='pk' {$select_option_country_pk}>파키스탄 
				<option value='mw' {$select_option_country_mw}>말라위 
				<option value='fo' {$select_option_country_fo}>파로에 제도 
				<option value='lb' {$select_option_country_lb}>레바논 
				<option value='vc' {$select_option_country_vc}>세인트빈센트그레나딘즈 
				<option value='bt' {$select_option_country_bt}>부탄 
				<option value='cz' {$select_option_country_cz}>체코 
				<option value='tw' {$select_option_country_tw}>타이완 
				<option value='tr' {$select_option_country_tr}>터키 
				<option value='vu' {$select_option_country_vu}>바누아투 
				<option value='br' {$select_option_country_br}>브라질 
				<option value='af' {$select_option_country_af}>아프가니스탄 
				<option value='mg' {$select_option_country_mg}>마다가스카르 
				<option value='tm' {$select_option_country_tm}>투르크메니스탄 
				<option value='mx' {$select_option_country_mx}>멕시코 
				<option value='bn' {$select_option_country_bn}>브루나이 
				<option value='gy' {$select_option_country_gy}>가이아나 
				<option value='bo' {$select_option_country_bo}>볼리비아 
				<option value='mo' {$select_option_country_mo}>마카오 
				<option value='bg' {$select_option_country_bg}>불가리아 
				<option value='pf' {$select_option_country_pf}>폴리네시아 제도(프랑스령) 
				<option value='nc' {$select_option_country_nc}>뉴칼레도니아 섬 
				<option value='lu' {$select_option_country_lu}>룩셈부르크 
				<option value='aw' {$select_option_country_aw}>아루바 
				<option value='gw' {$select_option_country_gw}>기니-비쏘 
				<option value='td' {$select_option_country_td}>차드 
				<option value='gl' {$select_option_country_gl}>그린란드 
				<option value='tj' {$select_option_country_tj}>타지키스탄 
				<option value='gd' {$select_option_country_gd}>그라나다 
				<option value='ma' {$select_option_country_ma}>모로코 
				<option value='cf' {$select_option_country_cf}>중앙아프리카공화국 
				<option value='ee' {$select_option_country_ee}>에스토니아 
				<option value='sh' {$select_option_country_sh}>세인트헬레나 섬 
				<option value='ne' {$select_option_country_ne}>니제르 
				<option value='az' {$select_option_country_az}>아제르바이잔 
				<option value='tg' {$select_option_country_tg}>토고 
				<option value='ae' {$select_option_country_ae}>아랍에미레이트공화국 
				<option value='gn' {$select_option_country_gn}>기니 
				<option value='py' {$select_option_country_py}>파라과이 
				<option value='am' {$select_option_country_am}>아르메니아 
				<option value='ie' {$select_option_country_ie}>아일랜드 
				<option value='mh' {$select_option_country_mh}>마샬 군도 
				<option value='it' {$select_option_country_it}>이탈리아 
				<option value='hn' {$select_option_country_hn}>온두라스 
				<option value='in' {$select_option_country_in}>인도 
				<option value='do' {$select_option_country_do}>도미니카 공화국 
				<option value='yu' {$select_option_country_yu}>유고슬라비아 
				<option value='nz' {$select_option_country_nz}>뉴질랜드 
				<option value='bd' {$select_option_country_bd}>방글라데시 
				<option value='gf' {$select_option_country_gf}>기아나(프랑스령) 
				<option value='pm' {$select_option_country_pm}>세인트 피에르, 미퀠론 
				<option value='gp' {$select_option_country_gp}>과델루프 
				<option value='sz' {$select_option_country_sz}>스와질랜드 
				<option value='ua' {$select_option_country_ua}>우크라이나 
				<option value='ki' {$select_option_country_ki}>키리바시 
				<option value='ms' {$select_option_country_ms}>몽트세라 
				<option value='uk' {$select_option_country_uk}>영국 
				<option value='ao' {$select_option_country_ao}>앙골라 
				<option value='et' {$select_option_country_et}>에디오피아 
				<option value='kw' {$select_option_country_kw}>쿠웨이트 
				<option value='sa' {$select_option_country_sa}>사우디 아라비아 
				<option value='mn' {$select_option_country_mn}>몰골리아 
				<option value='ch' {$select_option_country_ch}>스위스 
				<option value='lr' {$select_option_country_lr}>라이베리아 
				<option value='zr' {$select_option_country_zr}>자이르 
				<option value='es' {$select_option_country_es}>스페인 
				<option value='hk' {$select_option_country_hk}>홍콩 
				<option value='mc' {$select_option_country_mc}>모나코 
				<option value='sn' {$select_option_country_sn}>세네갈 
				<option value='ng' {$select_option_country_ng}>나이지리아 
				<option value='bw' {$select_option_country_bw}>보츠와나 
				<option value='uz' {$select_option_country_uz}>우즈베키스탄 
				<option value='be' {$select_option_country_be}>벨기에 
				<option value='pg' {$select_option_country_pg}>파푸아뉴기니아 
				<option value='sg' {$select_option_country_sg}>싱가포르 
				<option value='al' {$select_option_country_al}>알바니아 
				<option value='fm' {$select_option_country_fm}>마이크로네시어 
				<option value='nr' {$select_option_country_nr}>나우루 공화국 
				<option value='tc' {$select_option_country_tc}>터키, 카이코스 제도 
				<option value='sv' {$select_option_country_sv}>엘살바도르 
				<option value='lc' {$select_option_country_lc}>세인트 루시아 
				<option value='pa' {$select_option_country_pa}>파나마 
				<option value='np' {$select_option_country_np}>네팔 
				<option value='gq' {$select_option_country_gq}>적도 기니 
				<option value='ws' {$select_option_country_ws}>서사모아 
				<option value='ly' {$select_option_country_ly}>리비아 
				<option value='lk' {$select_option_country_lk}>스리랑카 
				<option value='dz' {$select_option_country_dz}>알제리 
				<option value='bh' {$select_option_country_bh}>바레인 
				<option value='nl' {$select_option_country_nl}>네덜란드 
				<option value='ke' {$select_option_country_ke}>케냐 
				<option value='za' {$select_option_country_za}>남아프리카 공화국 
				<option value='to' {$select_option_country_to}>통가 
				<option value='qa' {$select_option_country_qa}>카타르 
				<option value='id' {$select_option_country_id}>인도네시아 
				<option value='jo' {$select_option_country_jo}>요르단 
				<option value='lt' {$select_option_country_lt}>리투아니아 
				<option value='xx' {$select_option_country_xx}>기타나라/우주거주/우주인 
			";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->set_var("logon"				,$logon);
$tpl->set_var("form_changepasswd"	,$form_changepasswd);
$tpl->set_var("form_changeprofile"	,$form_changeprofile);

$tpl->process('ADDRESS'		,'noforeign');
/*
switch($logon["class"]){
	case "person" :
		$tpl->process('ADDRESS'		,'noforeign');
		break;
	case "root" : // root도 다 보이게...
	case "chain" : // enjoyprint.co.kr에서 가맹점을 위해
	case "company" :
		$tpl->set_var("select_option_country",$select_option_country);
		$tpl->process('ADDRESS'		,'noforeign');
		$tpl->process('COMPANYINFO'	,'companyinfo');
		break;
	case "foreign" :
		$tpl->process('ADDRESS'		,'foreign');
		break;
} // end switch
*/

// seller정보
$sql="SELECT * from {$table_seller} where bid='{$_SESSION['seUid']}'";
if($seller = db_arrayone($sql)){
	$tpl->tie_var('seller',$seller);
}
// 템플릿 마무리 할당
// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
