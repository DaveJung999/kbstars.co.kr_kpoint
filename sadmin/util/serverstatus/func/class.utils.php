<?php
##  시스템 정보 출력 #############################################
##
##  작성자	: 김칠봉[닉:산이] <san2(at)linuxchannel.net>
##  스크립트 명 : PHP를 이용한 시스템 정보를 출력하는 스크립트
##
#############################################################
##
## 주)
## 사용상 부주의로 인한 피해는
## 본 작성자에게 어떠한 보증이나 책임이 없습니다.
##
###############################################################

class utils
{
  ## check $TMPL['config']
  ##
  function check_config($tmpl, $config, $post)
  {
	global $_SERVER;

	if($config == 'select') $key = $post ? $post : 'default';
	else $key = $config;

	if(!trim($tmpl[$key])) $key = 'default';

	$c['config'] = $key;
	if($config == 'select')
	{
		$select[$key] = ' SELECTED';
		foreach($tmpl AS $k=>$v) $form .= "<OPTION VALUE='$k'{$select[$k]}>$v\n";
		$c['form'] = "\n".
		'<FONT SIZE=2>'.
		"<FORM METHOD='post' ACTION={$_SERVER['_PHP_SELF']}>\n".
		"<SELECT NAME='utmpl'>\n$form".
		'</SELECT>'."\n".
		"&nbsp;<INPUT TYPE='submit' VALUE='테마적용'>\n".
		'</FORM>'.
		'</FONT>'."\n";
	}

	return $c;
  }


  ## get tmplates file
  ##
  function get_tmpl($file)
  {
	global $TMPL, $_SERVER;

	$content = $this->get_file($_SERVER['_PWD']."/templates/{$TMPL['config']}/$file");
	$content = str_replace('"','\"',$content);
	eval('$content = "'.$content.'";');

	return $content;
  }


  ## get block tmplates file
  ## $from = array(), $to = array();
  ##
  function get_block($from,$to,$content)
  {
	global $TMPL;

	$content = str_replace($from,$to,$content);
	$content = str_replace('"','\"',$content);
	eval('$content = "'.$content.'";');

	return $content;
  }


  function get_width($width)
  {
	$width *= 2; // 크기로 2배로 정함
	if(!$width) $width = 1; // 기본값 설정
	if($width == 200) $width = 199;

	return $width;
  }


  ## 자바 스크립트 window.status A 태그
  ##
  function tag_a($str,$link, $target='', $winstatus='')
  {
	if($winstatus)
	{
		$winstatus = str_replace("'","\'",$winstatus);
		$status = ' onMouseOver="window.status=\''.$winstatus.'\'; return true;" '.
		'onMouseOut="window.status=\'\'; return true;"';
	}
	if($target) $target = " target='$target'";

	return "<A HREF='$link'$target$status>$str</A>";
  }


  function html_table($args='border="0" spacing="0" padding="0" width="100%"')
  {
	$args = strtoupper($args);
	return "\n<TABLE $args>\n";
  }


  ## if you using the PHP/4.0.x
  ## http://www.php.net/ChangeLog-4.php
  ##
  function _globals()
  {
	global $_SERVER; // add user vars for 4.0.x

	if(preg_match('/^4\.0/',PHP_VERSION))
	{
		$GLOBALS['_SERVER']  = array_merge($GLOBALS['HTTP_SERVER_VARS'],$_SERVER);
		$GLOBALS['_GET']	 = $GLOBALS['HTTP_GET_VARS'];
		$GLOBALS['_POST']	= $GLOBALS['_POST'];
		$GLOBALS['_COOKIE']  = $GLOBALS['HTTP_COOKIE_VARS'];
		$GLOBALS['_SESSION'] = $GLOBALS['_SESSION'];
		$GLOBALS['_ENV']	 = $GLOBALS['HTTP_ENV_VARS'];
		$GLOBALS['_FILES']	= $GLOBALS['HTTP_POST_FILES']; // ???
		$GLOBALS['_REQUEST'] = array_merge($_GET,$_COOKIE);
	}
  }


  ## get $_SERVER['_URI'] and $_SERVER['_PHP_SELF'] (for includes)
  ##
  function realuri($_PWD)
  {
	global $_SERVER; // add user vars for 4.0.x

	$GLOBALS['_SERVER'][_PWD] = $_PWD = realpath($_PWD); // it's override
  
	## for system account user
	## 
	if(preg_match(';^/~;',$_SERVER['REQUEST_URI']))
	{
		$_S['_SNAME'] = realpath($_SERVER['SCRIPT_FILENAME']);
		$_S['_FNAME'] = preg_replace(';^/~[^/]+;','',$_SERVER['SCRIPT_NAME']);
		$_S['_UURI'] = str_replace($_S['_FNAME'],'',$_SERVER['SCRIPT_NAME']);
		$_S['_DOCUMENT_ROOT'] = str_replace($_S['_FNAME'],'',$_S['_SNAME']);
		$_S['_DOCUMENT_PATH'] = str_replace($_S['_DOCUMENT_ROOT'],'',$_PWD);
		$_URI = $_S['_UURI'] . $_S['_DOCUMENT_PATH'];
	} else
	{
		if(preg_match(";^{$_SERVER['DOCUMENT_ROOT']};",$_PWD))
		{
			$_URI = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_PWD);
		} else
		{
			$_URI = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PATH_TRANSLATED']);
			$_URI = dirname($_URI);
		}
	}
	if(!$_SERVER['_URI']) $GLOBALS['_SERVER'][_URI] = $_URI; // it's not override

	//$_SERVER['_PHP_SELF'] = preg_replace('/\?.*$/','',$_SERVER['REQUEST_URI']);
	$GLOBALS['_SERVER'][_PHP_SELF] = $_SERVER['REQUEST_URI']; // includes this file at any path
  }


  ## check support OS and get OS/class type(machine)
  ## reference(archtable): /usr/src/linux/arch
  ##
  function get_machine()
  {
	if(!preg_match('/linux/i',PHP_OS))
	{
		echo '&nbsp;<P><H3>support only GNU/LINUX !!!</H3>';
		exit;
	}

	@exec('/bin/uname -srm 2>/dev/null',$m);
	if($m['0'])
	{ list($arch['s'],$arch['r'],$arch['m']) = preg_split('/\s+/',$m['0']); }
	else
	{
		@exec('/bin/arch 2>/dev/null',$m);
		$arch['m'] = $m['0'];
	}
	if(!$arch['m']) $arch['m'] = 'i686'; // default arch mechine

	## /usr/src/linux/Makefile
	## alpha, arm, cris, i386, ia64, m68k, mips, mips64, parisc,
	## ppc, ppc64, s390, s390x, sh, sparc, sparc64
	##
	$arch['t'] = preg_replace(array('/i.86/','/sun4u/','/(arm.*|sa110)/'),
		array('i386','sparc64','arm','arm'), $arch['m']);
 
	if(!preg_match('/^(i386|s390)$/',$arch['t']))
	{
		echo "&nbsp;<P><H3>not support {$arch['t']} arcitecture !!!</H3>";
		exit;
	}

	return $arch; // array
  }


  ## replace exec()
  ##
  function _exec($cmd, &$var)
  {
	if($fp = @popen($cmd,r))
	{
		while(!feof($fp)) $var[] = fgets($fp,4096); // to array
		pclose($fp);
	}

	return $var; // also return $var array
  }


  ## check PHPA(PHP Accelerator)
  ##
  function get_phpa()
  {
	global $_PHPA;

	if(!$_PHPA) return 0;
	$_phpa['version'] = 'PHPA/' . $_PHPA['VERSION'];
	$_phpa['on'] = $_PHPA['ENABLED'] ? 'PHPA on' : 'PHPA off';

	return $_phpa;
  }


  ## check parsing time
  ##
  function get_microtime($_start, $_end)
  {
	$end = explode(' ', $_end);
	$start = explode(' ', $_start);

	## (A+A')-(B+B') better than (A-B)+(A'-B')
	return sprintf('%.4f', ($end['1'] + $end['0']) - ($start['1'] + $start['0']));
  }


  ## Direct IO access get file
  ## 2002.08.23
  ##
  function get_diofile($file)
  {
	if($fp = @dio_open($file,O_RDONLY))
	{
		$contents = dio_read($fp,filesize($file));
		dio_close($fp);
	}

	return $contents;
  }


  ## common get file
  ## 2002.08.23
  ##
  function get_file($file)
  {
	if(!file_exists($file)) return '';

	if(function_exists(dio_open)) return $this->get_diofile($file);

	if($fp = @fopen($file,'r'))
	{
		$contents = fread($fp,filesize($file));
		fclose($fp);
	}

	return $contents;
  }


  ## 현재 또는 특정한 날짜를 한글로 출력하는 함수
  ## $sub가 있을 경우 시:분:초로 출력한다.
  ##
  function hdate_time($date=0, $sub=0)
  {
	if($sub) $str = ' H:i:s';
	return date("Y년 m월 d일$str",$date ? $date : time());
  }


  ## 각 요일을 한글로 출력하는 함수
  ## $sub 변수가 있을 경우 자세하게(?) 출력
  ## $date 변수는 유닉스 timestamp 값이다.
  ##
  function hday($date=0, $sub=0)
  {
	$hday = array('일','월','화','수','목','금','토');
	$key = date('w', !$date ? time() : $date);

	return $hday[$key] . ($sub ? '요일' : '');
  }


  ## 하루 24시간을 12 시간대로 출력하는 함수
  ##
  function hday_12z($date=0)
  {
	$date = date('G', $date ? $date : time());
	$key = intval($date/2 + 0.5);

	$H12Z = array('丑時(축시)','寅時(인시)','卯時(묘시)','辰時(진시)',
	'巳時(사시)','午時(오시)','未時(미시)','申時(신시)','酉時(유시)',
	'戌時(술시)','亥時(해시)','子時(자시)','丑時(축시)'); 

	return $H12Z[$key];
  }


  ## 하루 24시간을 새벽/오전/오후/저녁의 시간대를 출력하는 함수
  ## $sub 변수가 없다면 단순하게 오전/오후로 출력함.
  ## $date 변수는 유닉스 timestamp 값이다.
  ##
  function hday_ap($date=0, $sub=0)
  {
	if($sub) $H = array('새벽','오전','오후','저녁');
	else $H = array('오전','오전','오후','오후');

	$key = intval(date('G',$date ? $date : time())/6);

	return $H[$key];
  }


  ## 현재 또는 특정한 날짜를 한글로 출력하는 함수
  ## $sub가 있을 경우 시:분:초로 출력한다.
  ##
  function my_date($date=0)
  {
	if(!$date) $date = time();

	return $this->hdate_time($date,1).' '.$this->hday($date).
		'('.$this->hday_ap($date,1).')';
  }


  ## 일정기간의 시간(초)를 휴멘스케일로 변환
  ## 2002.11.14 알고리듬 수정
  ## 2001.02.07 일 추가
  ##
  function runtime($term, $lang='kr')
  {
	$l['kr'] = $l['ko'] = array('초','분','시간','일','달');
	$l['en'] = array('seconds','minutes','hours','days','months');

	$months = (int)($term / 2592000);
	$term	= (int)($term % 2592000);
	$days	= (int)($term / 86400);
	$term	= (int)($term % 86400);
	$hours  = (int)($term / 3600);
	$term	= (int)($term % 3600);
	$mins	= (int)($term / 60);
	//$secs  = (int)($term % 60);

	$months = $months ? $months.$l[$lang][4].' ' : '';
	$days = $days ? $days.$l[$lang][3].' ' : '';
	$hours = $hours ? $hours.$l[$lang][2].' ' : '';
	$mins = $mins ? $mins.$l[$lang][1] : '';
	//$secs .= $l[$lang][0];

	return $months.$days.$hours.$mins;
  }


  ## 특정 파일이 얼마동안 변화되어 있는지 계산하는 함수
  ## $arc는 특정 파일임
  ## 쉘에서 시스템이 얼마동안 작동하고 있는지 알아보는 명령은
  ## /usr/bin/uptime 입니다.
  ## 작성자 : 김칠봉[닉:산이] <san2(at)linxuchannel.net>
  ## 작성일 : 2001.01.11, 함수이동
  ## 2001.02.07 일 추가
  ##
  function arc_runtime($arc, $lang='kr')
  {
	global $_time;

	## 실행되고 있는 총 시간을 초(second)로 환산
	##
	$term = ($_time ? $_time : time()) - filemtime($arc);

	return $this->runtime($term,$lang);
  }


  ## 아파치 데몬이 얼마나 오랫동안 실행되고 있는지 알아보기
  ## win32일 경우, $pid = 'C:/Program Files/Apache Group/Apache/logs/httpd.pid'
  ## 작성자 : 김칠봉[닉:산이] <san2(at)linxuchannel.net>
  ## 작성일 : 2000.12.11
  ## 2001.02.07 일 추가
  ##
  function get_apache_runtime($pid='/usr/local/apache/logs/httpd.pid', $lang='kr')
  {
	if(@file_exists($pid)) return $this->arc_runtime($pid,$lang);

	$httpdpid[] = '/usr/local/apache/logs/httpd.pid'; // 일반적으로 소스 컴파일시
	$httpdpid[] = '/var/run/httpd.pid';
	$httpdpid[] = '/var/run/apache.pid';
	$httpdpid[] = '/var/run/apache2.pid';
	$httpdpid[] = '/usr/local/apache/logs/apache.pid';
	$httpdpid[] = '/usr/local/apache2/logs/httpd.pid'; // apache-2.x
	$httpdpid[] = '/usr/local/apache2/logs/apache.pid'; // apache-2.x
	$httpdpid[] = '/usr/local/httpd/logs/httpd.pid';  // apache-2.x
	$httpdpid[] = '/usr/local/httpd/logs/apache.pid';  // apache-2.x
	$httpdpid[] = '/usr/local/etc/httpd/logs/httpd.pid'; // 예전방식
	$httpdpid[] = '/usr/local/etc/httpd/logs/apache.pid'; // 예전방식

	for($i=0; $i<count($httpdpid); $i++)
	{
		if(@file_exists($httpdpid[$i]))
		{
			return $this->arc_runtime($httpdpid[$i],$lang);
			break;
		}
	}
  }


  ## alias get_apache_runtime()
  ##
  function apache_uptime($pid='/usr/local/apache/logs/httpd.pid', $lang='kr')
  {
	return $this->get_apache_runtime($pid,$lang);
  }


  ## 시스템이 얼마나 오래동안 작동하고 있는지 알아보기
  ## 2002.11.07 add boot time
  ## 2001.02.07 일 추가
  ##
  function system_uptime($uptime='/proc/uptime', $lang='kr')
  {
	global $_time;

	$uptime = @file($uptime);
	$t['term'] = (int)$uptime['0'];

	## boot time (/proc/stat -> btime)
	##
	$t['btime'] = ($_time ? $_time : time()) - $t['term'];

	return $this->runtime($t['term'],$lang) .', '. $this->my_date($t['btime']);
  }


  ## alias system_uptime()
  ##
  function sys_uptime($uptime='/proc/uptime', $lang='kr')
  {
	return $this->system_uptime($uptime,$lang);
  }


  ## 2001.02.07 일 추가
  ##
  function get_addr_to_base()
  {
	global $_SERVER;

	$serv = explode('.',$_SERVER['SERVER_ADDR']);
	return $serv['0']*256*256*256 + $serv['1']*256*256 + $serv['2']*256 + $serv['3'];
  }
} // end of class
?>
