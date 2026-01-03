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
  var $dd = array();	// get_proc_part() , $this->dd, disk block table

  var $pci = array();	// get_pci()
  var $cpu = array();	// get_cpu();
  var $mem = array();	// get_mem()
  var $part = array();	// get_part()
  var $eth = array();	// get_ehternet()
  var $sd = array();	// get_Scsi_hdd()
  var $hd = array();	// get_eide_hdd()
  var $nstat = array();	// get_netstat()


  function sysinfo()
  {
	## pre-loading(array)
	##
	$this->dmesg	= $this->get_dmesg();
	$this->mod	= $this->get_modfile();
	$this->ppart	= $this->get_proc_part();
	$this->nstat	= $this->get_netstat(); // for net device name

	## basic loading(string)
	##
	$this->title	= $this->get_title();
	$this->OS	= $this->get_os();
	$this->swap	= $this->get_swpart();

	## main loading(array)
	##
	$this->pci	= $this->get_pci();
	$this->cpu	= $this->get_cpu();
	$this->mem	= $this->get_mem();
	$this->part	= $this->get_part();
	$this->eth	= $this->get_ethernet();
	$this->sd	= $this->get_scsi_hdd();
	$this->hd	= $this->get_eide_hdd();

	## destory this vars
	##
	unset($this->dmesg);
	unset($this->mod);
	unset($this->ppart);
	unset($this->dd);
  }


  function get_dmesg()
  {
	if(!$dmesg = @file('/var/log/dmesg')) return;

	if(!$this->cpu)
	{
		$tmp['cpu_mhz'] = array(0=>'/^Detected (.+) processor\.$/',1=>'\\1');
		$tmp['cpu_bogomips'] = array(0=>'/^[^0-9]* ([0-9.]+) BogoMIPS$/',1=>'\\1');
		$tmp['cpu_cache'] = array(0=>'/^CPU.*: L2 cache: (.+)$/',1=>'\\1');
		$tmp['cpu_model'] = array(0=>'/^CPU.*: (.+) stepping.*$/',1=>'\\1');
	}

	$tmp['eth'] = array(0=>'/^eth[0-9]: ([^,]+),.*$/',1=>'\\1');

	$tmp['scsi_size'] = array(0=>'/^SCSI.*(sd[a-p]):.*hdwr.*\(([^)]+)\)$/',1=>'\\2');
	//$tmp['scsi_sync'] = array(0=>'/^\(scsi[0-9]:[A-Z]:([0-9]+)\): (.+) transfers.*$/',1=>'\\1:\\2');
	//$tmp['scsi_sync_'] = array(0=>'/^\(scsi[0-9]:[0-9]:([0-9]+):[0-9]+\) Synchronous at ([^,]+),.*/',1=>'\\1:\\2');

	//$tmp['eide_vendor'] = array(0=>'/^hd[a-m]: ([^,]+), ATA DISK drive$/',1=>'\\1');
	//$tmp['eide_size'] = array(0=>';^hd[a-m]:.+w/([0-9]+)KiB.+CHS=([^,]+).*$;',1=>'\\1:\\2');
	////$tmp['eide_part'] = array(0=>'/.*hd[a-m].+(hd[a-m]1.*)/',1=>'\\1');
	////$tmp['scsi_part'] = array(0=>'/^[^:]+: (sd[a-p]1.+)$/',1=>'\\1');

	$d['kernel'] = preg_replace('/^([^(]+) \(.+$/','\\1',$dmesg['0']);

	for($i=1; $i<count($dmesg); $i++)
	{
		foreach(array_keys($tmp) AS $k)
		{
			if(preg_match($tmp[$k][0],$dmesg[$i]))
			{ $d[$k][] = preg_replace($tmp[$k][0],$tmp[$k][1],$dmesg[$i]); }
		}
	}

	if($d['cpu_model'][0])
	{
		$d['cpu_vendor'] = preg_replace('/^([^ ]+).+$/','\\1',trim($d['cpu_model'][0]));
		$d['cpu_model'][0] = str_replace($d['cpu_vendor'],'',$d['cpu_model'][0]);
	}

	if(count($d['scsi_size']) > 0)
	{
		foreach($d['scsi_size'] AS $k=>$v)
		{
			//list($k,$m) = explode(':',$v); // $k is sda, sdb, sdc ...
			$d['scsi_size'][$k] = $this->file_hsize((int)$v * 1048576);
		}
	}

	/***
	if($d['scsi_sync_']) {
		$d['scsi_sync'] = $d['scsi_sync_'];
		unset($d['scsi_sync_']);
	}

	if(count($d['scsi_sync'])>0) {
		foreach($d['scsi_sync'] AS $k=>$v) {
			list($id,$value) = preg_split('/:/',$v);
			$d['scsi_sync'][$id] = $value;
		}
	}

	if(count($d['eide_size'])>0) {
		foreach($d['eide_size'] AS $k=>$v) {
			list($cache,$CHS) = preg_split('/:/',$v);
			$d['eide_cache'][$k] = $this->file_hsize($cache*1024);
			list($C,$H,$S) = preg_split(';/;',$CHS);
			$d['eide_size'][$k] = $this->file_hsize($C*$H*$S*512);
		}
	}
	***/

	return $d;
  }


  function get_modfile()
  {
	if(!file_exists($modfile = '/etc/modules.conf'))
	{ $modfile = '/etc/conf.modules'; }

	if($mod = @file($modfile))
	{
		for($i=0; $i<count($mod); $i++)
		{
			if(preg_match('/^[^#]+eth[0-9]/',$mod[$i]))
			{ $m['eth'] .= preg_replace('/^.+eth[0-9]\s+/','',chop($mod[$i])).', '; }

			else if(preg_match('/^[^#]+scsi_hostadapter/i',$mod[$i]))
			{ $m['scsi'] = preg_replace('/^.+scsi_hostadapter\s+/i','',chop($mod[$i])); }
		}
	}

	if($m['eth']) $m['eth'] = preg_replace('/, $/','',$m['eth']);

	return $m;
  }


  function get_pci()
  {
	if(!$pci = @file('/proc/pci')) return;

	$tmp = array('chip'=>'Host bridge','agp'=>'PCI bridge','scsi'=>'SCSI storage controller',
		'addon'=>'Multimedia video controller','ethernet'=>'Ethernet controller',
		'vga'=>'VGA compatible controller');

	for($i=0; $i<count($pci); $i++)
	{
		foreach($tmp AS $k=>$v)
		{
			if(preg_match("/$v/",$pci[$i]))
			{
				$get[$k][] = preg_replace('/^[^:]+: (.+) \(rev.+$/i','\\1',$pci[$i]);
				break;
			}
		}
	}

	if($addon = trim($get['addon'][0])) $get['vga'][0] .= ' / '.$addon;

	return $get;
  }


  function get_cpu()
  {
	$dmesg = $this->dmesg;

	if(!$cpu = @file('/proc/cpuinfo'))
	{
		$get['cpu_num'] = count($dmesg['cpu_cache']);
		$get['vendor'] = $dmesg['cpu_vendor'];
		$get['name'] = $dmesg['cpu_model'][0];
		$get['mhz'] = $dmesg['cpu_mhz'][0];
		$get['bogomips'] = $dmesg['cpu_bogomips'][0];
		$get['cache'] = $dmesg['cpu_cache'][0];

		return $get;
	}

	$get['cpu_num'] = round(count($cpu)/19);

	foreach(array('vendor'=>1,'name'=>4,'mhz'=>6,'cache'=>7,'bogomips'=>17) AS $k=>$v)
	{ $get[$k] = preg_replace('/^.+: /','',$cpu[$v]); }

	$get['mhz'] .= ' MHz';

	if(!preg_match('/^[1-9]/',$get['bogomips']))
	{ $get['bogomips'] = preg_replace('/^.+:/','',$cpu['18']); }

	return $get;
  }


  function get_ethernet()
  {
	if($this->mod[eth]) $e['modules'] = $this->mod[eth];

	if($this->pci[ethernet]) $e['pci'] = $this->pci[ethernet];
	else $e['pci'] = $this->dmesg[eth];

	return $e;
  }


  function get_scsi_hdd()
  {
	$mod = $this->mod;
	$dmesg = $this->dmesg;
	$ppart = $this->ppart;

	$scsi['size'] = $dmesg['scsi_size'];
	$scsi['partitions'] = $ppart['scsi'];
	$scsi['msg'] = $this->dd[scsi]; // major number, HDD type name
	$scsi['num'] = count($scsi['partitions']);

	if($pscsi = @file('/proc/scsi/scsi'))
	{
		$tmp['0'] = '/^.+Id: ([0-9]+).+$/';
		$tmp['1'] = '/^[^:]+: (.+)\s+Model: (.+)\s+Rev:.+$/';

		for($i=3; $i<count($pscsi); $i+=3)
		{
			if(preg_match('/Direct-Access/',$pscsi[$i]))
			{
				$scsi['id'][] = (int) preg_replace($tmp['0'],'\\1',$pscsi[$i-2]);
				$scsi['model'][] = preg_replace($tmp['1'],'\\1 \\2',$pscsi[$i-1]);
			}
		}
	}

	//if(!$scsi['sync'] = $dmesg['scsi_sync']) {
	if(!file_exists($f='/proc/scsi/'.$mod['scsi'].'/0'))
	{
		$tmp = array('aic7xxx','initio','a100u2w','advansys','wd7000','ncr53c8xx');

		foreach($tmp AS $v) if(file_exists($t='/proc/scsi/'.$v.'/0'))
		{ $f = $t; break; }

		if(!file_exists($f))
		{
			if($hdle = @opendir('/proc/scsi'))
			{
				while($file = @readdir($hdle))
				{
					if(!preg_match('/^(\.|\.\.|scsi|sg)$/',$file))
					{
					  if(file_exists($t = '/proc/scsi/'.$file.'/0'))
					  { $f = $t; break; }
					}
				}
				closedir($hdle);
			}
		}
	}

	if($sync = @file($f))
	{
		if($scsi['id']) $tmp_sync = $scsi['id'];
		else $tmp_sync = array(0,1,2,3,4,5,6,8,9,10,11,12,13,14,15); // exclude '7'
		$finish = count($tmp_sync);

		for($i=0; $i<count($sync); $i++)
		{
			foreach($tmp_sync AS $k)
			{
				if(@preg_match("/^\(scsi[0-9]:[0-9]:$k:/",$sync[$i]))
				{
					$i++;
					$scsi['sync'][$k] = preg_replace('/^.+at ([^,]+),.+$/',
						'\\1',$sync[$i]);
				}
				else if(@preg_match("/Target $k Negotiation Settings$/",$sync[$i]))
				{
					$i+=3;
					$scsi['sync'][$k] = preg_replace('/^.+Curr: (.+) transfers.+$/',
						'\\1',$sync[$i]);
				}
			}
			if(count($scsi['sync'])>=$finish) break; // speed up
		}
	}
	//}

	return $scsi;
  }


  function get_eide_hdd()
  {
	$ppart = $this->ppart;
	$hd['partitions'] = $ppart['eide'];
	$hd['num'] = count($hd['partitions']);
	$hd['msg'] = $this->dd[eide]; // major number, HDD type name

	## keys: major . minor
	## values: device name
	##
	$tmp = array(30=>'/proc/ide/ide0/hda',364=>'/proc/ide/ide0/hdb',
		220=>'/proc/ide/ide1/hdc',2264=>'/proc/ide/ide1/hdd',
		330=>'/proc/ide/ide2/hde',3364=>'/proc/ide/ide2/hdf',
		340=>'/proc/ide/ide3/hdg',3464=>'/proc/ide/ide3/hdh',
		560=>'/proc/ide/ide4/hdi',5664=>'/proc/ide/ide4/hdj',
		570=>'/proc/ide/ide5/hdk',5764=>'/proc/ide/ide5/hdl');

	foreach($tmp AS $v)
	{
		if(file_exists($v))
		{
			$disk = @file($v.'/media');
			if(trim($disk['0']) == 'disk')
			{
				$model = @file($v.'/model');
				$hd['model'][] = $model['0'];
				$size = @file($v.'/capacity');
				$hd['size'][] = $this->file_hsize($size['0']*512);
				$cache = @file($v.'/cache');
				$hd['cache'][] = $this->file_hsize($cache['0']*1024);
			}
		}
		if(count($hd['model'])>=$hd['num']) break; // speed up
	}

	/***
	$dmesg = $this->dmesg;
	if(!$hd['model']) $hd['model'] = $dmesg['eide_vendor'];
	if(!$hd['size']) $hd['size'] = $dmesg['eide_size'];
	if(!$hd['cache']) $hd['cache'] = $dmesg['eide_cache'];
	***/

	return $hd;
  }

} // end of class
?>
