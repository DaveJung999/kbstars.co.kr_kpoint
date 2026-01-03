<?php
##  시스템 정보 출력 #############################################
##
##  작성자 : 김칠봉[닉:산이] <san2(at)linuxchannel.net>
##  스크립트 명 : PHP를 이용한 시스템 정보를 출력하는 스크립트
##
#############################################################
##
## 주)
## 사용상 부주의로 인한 피해는
## 본 작성자에게 어떠한 보증이나 책임이 없습니다.
##
##
##############################################################

## force to $GLOBALS, don't delete this line
##
global $_SERVER, $_ENV, $_PHPA, $ARCH, $TMPL, $_time;

$_start = microtime();
$_time = time();

$_SERVER['_PWD'] = dirname(__FILE__); // real path of this file

require $_SERVER['_PWD'].'/func/config.php'; // 설정파일
require $_SERVER['_PWD'].'/func/class.utils.php'; // class of utils

$utils = new utils; // object of group functions

## check $GLOBALS and matching PHP/4.0.x == PHP/4.[12].x
##
$utils->_globals();

## get $_SERVER['_URI'], $_SERVER['_PHP_SELF']
##
$utils->realuri($_SERVER['_PWD']);

$TMPL = $utils->check_config($tmpl,$tmpl_config,$_POST['utmpl']);
$TMPL['year'] = date('Y',$_time);

## check support OS and get class type(arch, machine)
##
$ARCH = $utils->get_machine(); // add 2002.10.23

require $_SERVER['_PWD'].'/func/class.sysinfo.common.php';
require $_SERVER['_PWD'].'/func/class.sysinfo.'.$ARCH['t'].'.php';
require $_SERVER['_PWD'].'/templates/'.$TMPL['config'].'/config.php';

##############################################################
##############################################################

## create 'sysinfo' object
##
$sysinfo = new sysinfo;

$_phpa = $utils->get_phpa();

$TMPL['title'] = $sysinfo->title . ' sysinfo(v.'._SYSINFO_VERSION_.')';
$TMPL['to_base'] = $utils->get_addr_to_base();
$TMPL['curr_time'] = $utils->my_date($_time).'의 운영상황 : '.$utils->hday_12z($_time);
$TMPL['OS'] = $sysinfo->OS;
$TMPL['sys_uptime'] = '약 ' . $utils->system_uptime();
$TMPL['apache_uptime'] = '약 ' . $utils->apache_uptime() . ', ' .
	$_SERVER['SERVER_SOFTWARE'] . ' ' . $_phpa['version'];
$TMPL['swap'] = $sysinfo->swap;

## object -> var
## extract(get_object_vars($sysinfo)); // good idea, but very bad speed !!!
##
$pci = $sysinfo->pci;
$cpu = $sysinfo->cpu;
$mem = $sysinfo->mem;
$part = $sysinfo->part;
$eth = $sysinfo->eth;
$sd = $sysinfo->sd;
$hd = $sysinfo->hd;
$nstat = $sysinfo->nstat;
$od = $sysinfo->od; // other HDD

## get block templates
##
foreach(array('basic','partitions','netstat','scsihdd','eidehdd') AS $v)
{ ${$v} = $utils->get_file($_SERVER['_PWD']."/templates/{$TMPL['config']}/block/$v.tmpl"); }

$from['basic'] = array('$STR','$VALUES');
$from['partitions'] = array('$DEV','$MOUNT','$FS','$SIZE',
  '$USED_W','$FREE_W','$USED_S','$USED_P','$FREE_S','$FREE_P');
$from['netstat'] = array('$IFACE','$R_SIZE','$R_PACKETS','$R_ERRORS','$R_DROPS',
  '$T_SIZE','$T_PACKETS','$T_ERRORS','$T_DROPS','$COLLS');
$from['scsihdd'] = array('$MODEL','$ID','$SYNC','$SIZE','$PART');
$from['eidehdd'] = array('$MODEL','$BUFFER','$SIZE','$PART');


##############################################################
## parsing basic hardware
##############################################################

if($pci['agp'][0])
{
  $TMPL['basic_agp'] = $utils->get_block
  ($from['basic'],array('PCI Bus',$pci['agp'][0]),$basic);
}

if($pci['scsi'][0])
{
  $TMPL['basic_scsi'] = $utils->get_block
  ($from['basic'],array('SCSI Host Adapter',$pci['scsi'][0]),$basic);
}

##############################################################
## parsing basic, cpu, memory
##############################################################

if(!$cpu['cpu_num']) $cpu['cpu_num'] = '(hidden)';
if(!$cpu['mhz']) $cpu['mhz'] = 'NA (or unknown)';
if(!$cpu['cache']) $cpu['cache'] = 'NA (or unknown)';

$TMPL['basic_chip'] = $pci['chip'][0] ? $pci['chip'][0] : 'NA (or unknown)';
$TMPL['basic_cpu'] = "{$cpu['vendor']} {$cpu['name']} {$cpu['mhz']} {$cpu['cpu_num']} Processor";
$TMPL['basic_mem'] = $mem['total'];
$TMPL['basic_vga'] = $pci['vga'][0] ? $pci['vga'][0] : 'NA (or unknown)';


foreach(array('free','used','totalfree','totalused','buffered',
  'cached','swapfree','swapused') AS $k)
{
  $mem["${k}width"] = $utils->get_width($mem["${k}percent"]);
  $mem["_${k}width"] = 200 - $mem["${k}width"];
}

$TMPL = array_merge($TMPL,$cpu,$mem);


##############################################################
## parsing partitions
##############################################################

for($i=0; $i<$part['dev'][num]; $i++)
{
  $TMPL['part'] .= $utils->get_block
  (
	$from['partitions'],
	array(
		$part['dev'][$i],$part['mount'][$i],$part['efs'][$i],
		$part['size'][$i],$utils->get_width($part['percent'][$i]),
		$utils->get_width($part['percent_avail'][$i]),
		$part['used'][$i],$part['percent'][$i],
		$part['avail'][$i],$part['percent_avail'][$i]
	),
	$partitions
  );
}

$TMPL['part_sum'] = $part['size'][sum];
$TMPL['part_used_width'] = $utils->get_width($part['percent'][total]);
$TMPL['part_free_width'] = $utils->get_width($part['percent_avail'][total]);
$TMPL['part_used'] = $part['used'][sum].'('.$part['percent'][total].'%)';
$TMPL['part_free'] = $part['avail'][sum].'('.$part['percent_avail'][total].'%)';
$TMPL['part_fix'] = $part['fix'];


##############################################################
## parsing ethernet
##############################################################

$TMPL['modules'] = $eth['modules'];
for($i=0; $i<count($eth['pci']); $i++)
{
  $TMPL['ethernet'] .= $utils->get_block
  ($from['basic'],array('Ethernet'.$i,$eth['pci'][$i]),$basic);
}


##############################################################
## parsing netstat
##############################################################

$TMPL['netstat_fix'] = $nstat['fix'];
for($i=0; $i<count($nstat['iface']); $i++)
{
  $TMPL['netstat'] .= $utils->get_block
  (
	$from['netstat'],
	array(
		$nstat['iface'][$i],
		$nstat['R_size'][$i],
		number_format($nstat['R_packets'][$i]),
		number_format($nstat['R_errs'][$i]),
		number_format($nstat['R_drop'][$i]),
		$nstat['T_size'][$i],
		number_format($nstat['T_packets'][$i]),
		number_format($nstat['T_errs'][$i]),
		number_format($nstat['T_drop'][$i]),
		number_format($nstat['T_colls'][$i])
	),
	$netstat
  );
}


##############################################################
## parsing scsi-hdd
##############################################################

for($i=0; $i<$sd['num']; $i++)
{
  $TMPL['sd'] .= $utils->get_block
  (
	$from['scsihdd'],
	array(
		$sd['model'][$i].'('.$sd['msg'][$i][major].','.
		$sd['msg'][$i][minor].')',
		$sd['id'][$i],$sd['sync'][$sd['id'][$i]],$sd['size'][$i],
		$sd['partitions'][$i]
	),
	$scsihdd
  );
}


##############################################################
## parsing eide-hdd or other HDD
##############################################################

if(!$hd) { $hd = $od; $hd['cache'] = $hd['dev']; }
$TMPL['hdtitle'] = $hd['msg'][0][name];

for($i=0; $i<$hd['num']; $i++)
{
  $TMPL['hd'] .= $utils->get_block
  (
	$from['eidehdd'],
	array(
		$hd['model'][$i].'('.$hd['msg'][$i][major].','.
		$hd['msg'][$i][minor].')',
		$hd['cache'][$i],$hd['size'][$i],$hd['partitions'][$i]
	),
	$eidehdd
  );
}


##############################################################
## parsing print
##############################################################

$TMPL['microtime'] = $_phpa['on'].' '.$utils->get_microtime($_start,microtime());

echo $utils->get_tmpl('header.tmpl');
echo $utils->get_tmpl('main.tmpl') . $TMPL['form'];
echo $utils->get_tmpl('footer.tmpl');
?>
