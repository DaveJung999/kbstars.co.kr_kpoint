<?php
##  시스템 정보 출력 ########################################
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
##############################################################

##############################################################
##
## speed up `for()'
##  1) not good : for($i=0; $i<count($tmp=@file('file')); $i++)
##  2) good : $tmp=@file('file'); for($i=0; $i<count($tmp); $i++)
##
##############################################################

## sysinfo_common class -> 'class.sysinfo.comm.php' file
##
class sysinfo extends sysinfo_common
{
  var $title;		// get_title();
  var $OS;		// get_os();
  var $swap;		// get_swpart();

  var $dmesg = array();	// get_dmesg();
  var $mod = array();	// get_modfile()
  var $ppart = array();	// get_proc_part()
  var $dd = array();	// get_proc_part() , get the disk block table

  //var $pci = array();	// get_pci() , null as S/390 architecture
  var $cpu = array();	// get_cpu();
  var $mem = array();	// get_mem()
  var $part = array();	// get_part()
  var $eth = array();	// get_ehternet()
  //var $sd = array();	// get_Scsi_hdd() , null as S/390 architecture
  //var $hd = array();	// get_eide_hdd() , null as S/390 architecture
  var $od = array();	// get_ibm_hdd() , imb HDD
  var $nstat = array();	// get_netstat()


  function sysinfo()
  {
	## first loading(IBM S/390), for get the 'network device name'
	##
	$this->nstat	= $this->get_netstat();

	## pre-loading(array)
	##
	$this->dmesg	= $this->get_dmesg();
	$this->mod	= $this->get_modfile();
	$this->ppart	= $this->get_proc_part();

	## basic loading(string)
	##
	$this->title	= $this->get_title();
	$this->OS	= $this->get_os();
	$this->swap	= $this->get_swpart();

	## main loading(array)
	##
	//$this->pci	= $this->get_pci(); // null as S/390 arch
	$this->cpu	= $this->get_cpu();
	$this->mem	= $this->get_mem();
	$this->part	= $this->get_part();
	$this->eth	= $this->get_ethernet();
	//$this->sd	= $this->get_scsi_hdd(); // null as S/390 arch
	//$this->hd	= $this->get_eide_hdd(); // null as S/390 arch
	$this->od	= $this->get_ibm_hdd(); // null as S/390 arch

	## destory this vars
	##
	unset($this->dmesg);
	unset($this->mod);
	unset($this->ppart);
	unset($this->dd);
  }


  function get_dmesg()
  {
	if(!$dmesg = @file('/var/log/dmesg')) return 0;

	$tmp['cpu_num'] = array(0=>'/^Detected ([1-9]+) CPU.*$/',1=>'\\1');
	$tmp['cpu_bogomips'] = array(0=>'/^[^0-9].* ([0-9.]+) BogoMIPS$/',1=>'\\1');
	//$tmp['cpu_cache'] = array(0=>'/^CPU.*: L2 cache: (.+)$/',1=>'\\1');
	//$tmp['cpu_model'] = array(0=>'/^CPU.*: (.+) stepping.*$/',1=>'\\1');

	$d['kernel'] = preg_replace('/^([^(]+) \(.+$/','\\1',$dmesg['0']);

	for($i=1; $i<count($dmesg); $i++)
	{
		foreach(array_keys($tmp) AS $k)
		{
			if(preg_match($tmp[$k][0],$dmesg[$i]))
			{ $d[$k][] = preg_replace($tmp[$k][0],$tmp[$k][1],$dmesg[$i]); }
		}
	}

	return $d;
  }


  function get_modfile()
  {
	## http://www10.software.ibm.com/developerworks/opensource/linux390/index.shtml
	## http://www10.software.ibm.com/developerworks/opensource/linux390/technical-2.2.shtml
	##
	## S/390, net devices : ctc[n], escon[n], eth[n], iucv[n], hsi[n]
	##
	$i = count($this->nstat[iface]) - 1;

	if($i>0)
	{ $iface = preg_replace('/^([a-z]+)[0-9]+$/','\\1',$this->nstat[iface][1]); }
	else
	{ $iface = 'eth'; }

	if(!file_exists($modfile = '/etc/modules.conf'))
	{ $modfile = '/etc/conf.modules'; }

	if($mod = @file($modfile))
	{
		for($i=0; $i<count($mod); $i++)
		{
			if(preg_match("/^[^#]+${iface}[0-9]/",$mod[$i]))
			{ $m['eth'] .= preg_replace("/^.+${iface}[0-9]\s+/",'',chop($mod[$i])).', '; }
		}
	}

	if($m['eth']) $m['eth'] = preg_replace('/, $/','',$m['eth']);

	return $m;
  }


  /*** it's null for S/390 arch
  function get_pci()
  {
  }
  ***/

  function get_cpu()
  {
	$dmesg = $this->dmesg;

	$get['cpu_num'] = $dmesg['cpu_num'];
	$get['bogomips'] = $dmesg['cpu_bogomips'][0];

	if($cpu = @file('/proc/cpuinfo'))
	{
		foreach(array('vendor'=>0,'cpu_num'=>1,'bogomips'=>2) AS $k=>$v)
		{ $get[$k] = preg_replace('/^.+:\s+/','',$cpu[$v]); }
	}

	if($sysinfo = @file('/proc/sysinfo'))
	{
		if(!$get['cpu_num'])
		{
			$rows = count($sysinfo) - 1;
			while(!chop($sysinfo[$rows])) $rows--; // remove empty line
			$rows -= 2; // $rows = $rows -2;
			$tmp['cpu_num'] = $rows;
		}

		if(!$get['vendor']) $tmp['vendor'] = 0;
		$tmp['type'] = 1;
		$tmp['model'] = 2;

		foreach($tmp AS $k=>$v)
		{ $get[$k] = preg_replace('/^.+:\s+/','',$sysinfo[$v]); }

		$get['name'] = $get['type'] . '-' . $get['model'];		
		unset($get['type']);
		unset($get['model']);
	}

	//$get['mhz'] = $get['cache'] = ''; // I guess, NA ??

	return $get;
  }


  function get_ethernet()
  {
	if($this->mod[eth])
	{
		$e['modules'] = $this->mod[eth];
		$pmod = preg_replace('/^([^,]+),.+$/','\\1',$this->mod[eth]);
		$pmod = trim($pmod);
	}
	else $pmod = 'qeth';

	if($qeth = @file("/proc/$module"))
	{
		for($i=2; $i<count($qeth); $i++)
		{ list($null,$null,$devices,$e['pic'][$i-2]) = preg_split('/\s+/',$qeth[$i]); }
	}

	return $e;
  }


  /*** this null
  function get_scsi_hdd()
  {
  }
  ***/


  /*** this null
  function get_eide_hdd()
  {
  }
  ***/


  function get_ibm_hdd()
  {
	$od['partitions'] = $this->ppart[ibm];
	$od['msg'] = $this->dd[ibm];
	$od['num'] = count($od['partitions']);

	if($dasd = @file('/proc/dasd/devices'))
	{
		for($i=0; $i<$od['num']; $i++)
		{
			list($od['model'][],$x,$x,$major,$minor,$x,$od['dev'][],$x,$x,$x,$x,$bs,$blocks)
			= preg_split('/\s+/',trim($dasd[$i]));	
			$od['id'][] = (int)$major .','. (int)$minor;
			$od['size'][] = $this->file_hsize((int)$bs*$blocks); // block size, bs(4096) => 4KB
		}
	}

	return $od;
  }

} // end of class
?>
