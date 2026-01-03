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
## 본 작성성자에게 어떠한 보증이나 책임이 없습니다.
##
##############################################################

##############################################################
##
## speed up `for()'
##  1) not good : for($i=0; $i<count($tmp=@file('file')); $i++)
##  2) good : $tmp=@file('file'); for($i=0; $i<count($tmp); $i++)
##
##############################################################

class sysinfo_common
{
  ## 파일 크기 출력 함수
  ## $bfsize 변수는 bytes 단위의 크기임
  ##
  function file_hsize($bfsize, $sub=0)
  {
	$BYTES = number_format($bfsize).' Bytes';

	if($bfsize < 1024) return $BYTES;
	else if($bfsize < 1048576) $bfsize = number_format(round($bfsize/1024)).' KB';
	else if($bfsize < 1073741827) $bfsize = number_format(round($bfsize/1048576)).' MB';
	else $bfsize = number_format($bfsize/1073741827,1).' GB';

	if($sub) $bfsize .= "($BYTES)";

	return $bfsize;
  }


  function get_title()
  {
	global $_SERVER, $_ENV; // for PHP/4.0.x

	if(!$hostname['0'] = $_ENV["HOSTNAME"])
	{ $hostname = @file('/proc/sys/kernel/hostname'); }

	return "{$_SERVER['HTTP_HOST']} ({$hostname['0']})";
  }


  function get_mem()
  {
	if(!$mem = @file('/proc/meminfo')) return;

	list($null,$get['total'],$get['used'],$get['free'],$get['shared'],$get['buffered'],$get['cached'])
		= preg_split('/\s+/',$mem['1']);
	list($null,$get['swaptotal'],$get['swapused'],$get['swapfree']) = preg_split('/\s+/',$mem['2']);

	$get['freepercent'] = @round(($get['free']/$get['total'])*100); // 여유 메모리 퍼센트
	$get['usedpercent'] = 100 - $get['freepercent']; // 사용한 메모리 퍼센트

	// 2001.03.18일 추가(실제 사용량 계산)
	$get['totalfree'] = $get['free'] + $get['buffered'] + $get['cached']; // 실제 남은 메모리
	$get['totalused'] = $get['total'] - $get['totalfree']; // 실제로 사용한 메모리
	$get['totalfreepercent'] = @round(($get['totalfree']/$get['total'])*100); // 실제 남은 메모리 퍼센트
	$get['totalusedpercent'] = 100 - $get['totalfreepercent'];  // 실제 사용한 메모리 퍼센트

	//2001.02.26일 추가
	$get['bufferedpercent'] = @round(($get['buffered']/$get['total'])*100); // 버퍼메모리 퍼센트
	$get['cachedpercent'] = @round(($get['cached']/$get['total'])*100); // 캐쉬메모리 퍼센트

	$get['swapfreepercent'] = @round(($get['swapfree']/$get['swaptotal'])*100); // 스왑 여유 공간 퍼센트
	$get['swapusedpercent'] = 100 - $get['swapfreepercent']; // 사용한 스왑 퍼센트

	// 보정
	foreach(array('total','free','used','buffered','cached','totalfree','totalused',
		'swaptotal','swapfree','swapused') AS $k)
	{
		$get[$k] = $this->file_hsize($get[$k]);
	}

	return $get;
  }


  function get_part()
  {
	## 각 파티션(FS)와 마운트 포인트 계산
	##
	$mounts = @file('/proc/mounts');

	for($i=0; $i<count($mounts); $i++)
	{
		if(preg_match('/ext[23]|vfat|ntfs|reiserfs|xfs|jfs/',$mounts[$i]))
		{
			list($p['dev'][],$p['mount'][],$p['efs'][])
			= preg_split('/\s+/', $mounts[$i]);
		}
	}

	$num = count($p['dev']);

	## 각 파티션 사용량 계산
	##
	@exec('/bin/df -k 2>/dev/null',$df); // KB 단위로 출력, $df는 배열

	if($df)
	{
		unset($p['dev']); // change partition name
		for($i=1; $i<count($df); $i++)
		{
			if(preg_match(';^/dev/;',$df[$i]))
			{
				list($p['dev'][],$size,$used,$avail,$percent)
				= preg_split('/\s+/',$df[$i]);

				## 각 파티션 used %
				##
				$percent = (int)$percent;
				$p['percent'][] = $percent;
				$p['percent_avail'][] = 100 - $percent;

				## 각 파티션 크기
				##
				$size = $size * 1024; // for PHP/4.3.2 bug ?
				$p['size'][sum] += $size; // Byte 단위로 환산
				$p['size'][] = $this->file_hsize($size);

				## 각 파티션 사용한 용량
				##
				$used = $used * 1024; // for PHP/4.3.2 bug ?
				$p['used'][sum] += $used;
				$p['used'][] = $this->file_hsize($used);

				## 각 파티션 여유 용량
				##
				$avail = $avail * 1024; // for PHP/4.3.2 bug ?
				$p['avail'][sum] += $avail;
				$p['avail'][] = $this->file_hsize($avail);
			}
		}

		$p['fix'] = '* 합계가 정확하게 맞지 않은 이유는 약 '.
			$this->file_hsize($p['size'][sum] - $p['used'][sum] - $p['avail'][sum]).
			'(각 파티션 합) 정도를 시스템이 미리 확보했기 때문입니다.';

		$p['percent'][total] = @round($p['used'][sum]*100/$p['size'][sum]);
		$p['percent_avail'][total] = 100 - $p['percent'][total];
		$p['size'][sum] = $this->file_hsize($p['size'][sum]);
		$p['used'][sum] = $this->file_hsize($p['used'][sum]);
		$p['avail'][sum] = $this->file_hsize($p['avail'][sum]);

		## last check partitions number
		##
		$cnt = count($p['dev']);

		/*** good idea, but unnecessary
		## check chroot enviroments
		##
		if($num > $cnt)
		{ for($i=$cnt; $i<$num; $i++) $p['size'][$i] = 'NA'; }
		***/
	}

	## change partitions number
	##
	$p['dev'][num] = $cnt ? $cnt : $num;

	return $p;
  }


  ## support S/390, 2002.10.23
  ##
  function get_proc_part()
  {
	if(!$part = @file('/proc/partitions')) return;

	## disk block table
	## /usr/src/linux/Documentations/devices.txt
	## major => 'type','name','minor'
	##
	$ddtable = array(
	3 => array('eide','E-IDE',64),		// First MFM, RLL and IDE hard disk/CD-ROM interface
	8 => array('scsi','SCSI',16),		// SCSI disk devices
	9 => array('raid','RAID',1),		// Metadisk (RAID) devices
	13=> array('ide','XT-IDE',64),		// 8-bit MFM/RLL/IDE controller
	14=> array('bios','BIOS',64),		// BIOS harddrive
	21=> array('acorn','Acorn-MFM',64),	// Acorn MFM hard drive interface
	22=> array('eide','E-IDE',64),		// Second IDE hard disk/CD-ROM interface
	28=> array('acsi','ACSI',16),		// ACSI disk (68k/Atari)
	33=> array('eide','E-IDE',64),		// Third IDE hard disk/CD-ROM interface
	34=> array('eide','E-IDE',64),		// Fourth IDE hard disk/CD-ROM interface
	36=> array('mca','MCA-ESDI',64),	// MCA ESDI hard disk
	45=> array('ide','PP-IDE',16),		// Parallel port IDE disk devices
	47=> array('atapi','PP-ATAPI',1),	// Parallel port ATAPI disk devices
	48=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	49=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	50=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	51=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	52=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	53=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	54=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	55=> array('raid','DAC960-RAID',8),	// Mylex DAC960 PCI RAID controller
	56=> array('eide','E-IDE',64),		// Fifth IDE hard disk/CD-ROM interface
	57=> array('eide','E-IDE',64),		// Sixth IDE hard disk/CD-ROM interface
	65=> array('scsi','SCSI',16),		// SCSI disk devices
	66=> array('scsi','SCSI',16),		// SCSI disk devices
	67=> array('scsi','SCSI',16),		// SCSI disk devices
	68=> array('scsi','SCSI',16),		// SCSI disk devices
	69=> array('scsi','SCSI',16),		// SCSI disk devices
	70=> array('scsi','SCSI',16),		// SCSI disk devices
	71=> array('scsi','SCSI',16),		// SCSI disk devices
	72=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	73=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	74=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	75=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	76=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	77=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	78=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	79=> array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	80=> array('i2o','I2O',16),		// I2O hard disk
	81=> array('i2o','I2O',16),		// I2O hard disk
	82=> array('i2o','I2O',16),		// I2O hard disk
	83=> array('i2o','I2O',16),		// I2O hard disk
	85=> array('i2o','I2O',16),		// I2O hard disk
	86=> array('i2o','I2O',16),		// I2O hard disk
	87=> array('i2o','I2O',16),		// I2O hard disk
	88=> array('eide','E-IDE',64),		// Seventh IDE hard disk/CD-ROM interface
	89=> array('eide','E-IDE',64),		// Eighth IDE hard disk/CD-ROM interface
	90=> array('eide','E-IDE',64),		// Ninth IDE hard disk/CD-ROM interface
	91=> array('eide','E-IDE',64),		// Tenth IDE hard disk/CD-ROM interface
	92=> array('ppdd','PPDD',1),		// PPDD encrypted disk driver
	94=> array('ibm','IBM-S/390-DASD',4),	// IBM S/390 DASD block storage
	95=> array('ibm','IBM-S/390-VM/ESA',1),	// IBM S/390 VM/ESA minidisk
	101=>array('raid','AMI-HD-RAID',16),	// AMI HyperDisk RAID controller
	104=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	105=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	106=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	107=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	108=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	109=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	110=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	111=>array('compaq','Compaq',16),	// Compaq Intelligent Drive Array
	112=>array('ibm','IBM-iSeries',8),	// IBM iSeries virtual disk
	199=>array('vm','VxVM',1)		// Veritas volume manager (VxVM) volumes
	);

	for($i=2; $i<count($part); $i++) // start check $ppart['2'];
	{
		list($major,$minor,$blocks,$name) = preg_split('/\s+/',trim($part[$i]));
		if($d = $ddtable[$major])
		{
			$type = $d['0'];
			if($minor % $d['2'])
			{
				$ppart[$type][$key] .= $name . ' ';
				if($blocks == 1)
				{
					$ppart[$type][$key] .= '&lt; ';
					$check[$type][] = $key;
				}
			} else
			{
				$key = (int) ${$type}++; // it's mean that counts of the HDD number
				//$key = $major.$minor; // HDD uniq ID
				$this->dd[$type][$key] = array('name'=>$d['1'],'major'=>$major,'minor'=>$minor);
			}
		}
	}
	
	if(is_array($ppart))
	{
		foreach(array_keys($ppart) AS $type)
		{
			if($check[$type])
			{
				foreach($check[$type] AS $key)
				{ $ppart[$type][$key] .= '&gt;'; }
			}
			sort($ppart[$type]); // for value sort
			sort($this->dd[$type]);
		}
	}

	return $ppart;
  }


  function get_swpart()
  {
	if(!$swaps = @file('/proc/swaps')) return;

	for($i=1; $i<count($swaps); $i++)
	{ $sw .= preg_replace(';^/dev/([^ ]+).+\n*$;','\\1',$swaps[$i]) . ','; }

	return preg_replace('/,$/','',$sw);
  }


  ## same as $utils->get_file($file)
  ##
  function get_release($file)
  {
	if(!file_exists($file)) return;

	if(function_exists(dio_open))
	{
		if($fp = @dio_open($file,O_RDONLY))
		{
			$contents = dio_read($fp,filesize($file));
			dio_close($fp);
		}
		return $contents;
	}

	if($fp = @fopen($file,'r'))
	{
		$contents = fread($fp,filesize($file));
		fclose($fp);
	}

	return $contents;
  }


  function get_os()
  {
	global $ARCH;

	$ostype = PHP_OS;
	if(!$ARCH['r'] && (!$ARCH['r'] = $this->dmesg[kernel]))
	{
		unset($ostype);
		if(!$kernel = @file('/proc/sys/kernel/osrelease'))
		{
			if($kernel = @file('/proc/version'))
			{ $ARCH['r'] = preg_replace('/^([^(]+) \(.+$/','\\1',$kernel['0']); }
		}
		else $ARCH['r'] = $kernel['0'];
	}

	$tmp = array('/etc/hancom-release','/etc/redhat-release','/etc/debian_release',
		'/etc/version','/etc/Version','/etc/wow-release','/etc/wowlinux-release');

	foreach($tmp AS $v) if($dist = $this->get_release($v)) break;

	if(!$dist)
	{
		if($hdle = @opendir('/etc'))
		{
			while($file = @readdir($hdle))
			{
				if(preg_match('/release|version/i',$file))
				{ $dist = $this->get_release('/etc/'.$file); break; }
			}
			closedir($hdle);
		}
	}

	return "$ostype {$ARCH['r']} {$ARCH['m']}" . ($dist ? ' / '.$dist : '');
  }


  function get_netstat()
  {
	if(!$netstat = @file('/proc/net/dev')) return;

	for($i=2; $i<count($netstat); $i++)
	{
		## ':'과 공백문자를 나누어 배열형태로 만듬
		## R : 받는 패킷
		## T : 보낸 패킷

		$j = $i - 2;
		$tmp = explode(':',$netstat[$i]);
		$stat['iface'][$j] = trim($tmp['0']);

		if(preg_match('/^bond/',$stat['iface'][$j])) $bonding++;
		else if(preg_match('/^eth/',$stat['iface'][$j])) $ethernet++;

		list($bytes['R'],$stat['R_packets'][$j],$stat['R_errs'][$j],$stat['R_drop'][$j],
			$null,$null,$null,$null,$bytes['T'],$stat['T_packets'][$j],
			$stat['T_errs'][$j],$stat['T_drop'][$j],$stat['T_colls'][$j]
		) = preg_split('/\s+/',trim($tmp['1']));

		$stat['R_size'][$j] = $this->file_hsize($bytes['R']);
		$stat['T_size'][$j] = $this->file_hsize($bytes['T']);

		// 충돌률 계산
		if($stat['T_packets'][$j] > 0)
		{
			if((int)$stat['T_colls'][$j]*100/$stat['T_packets'][$j] >= 5)
			{
				$stat['T_colls'][$j] = '<FONT COLOR="FF0000">'.$stat['T_colls'][$j].'</FONT>';
				$chk .= $stat['iface'][$j] . '&nbsp;';
			}
		}
	}

	if($bonding && $ethernet >= 2)
	{
		//$ethernet = $j - $bonding;
		$stat['fix'] = "현재 이 시스템은 ${ethernet}개의 ".
			TAG_A("<font color='#0000FF'>Ethernet Channel에 대해서 ${bonding}개의 ".
			'가상 bonding 장치</font>',
			'http://www.linuxchannel.net/docs/ethernet-channel-bonding.txt',
			'_blank','About the ethernet channel bonding').
			'가 설정되어 있음.<BR>&nbsp;&nbsp;';
	}

	$stat['fix'] = "* {$stat['fix']} TX의 Colls 항목은 collisions을 의미.";
	if($chk)
	{ $stat['fix'] .= "<BR>&nbsp;&nbsp; <FONT COLOR='#FF0000'>$chk</FONT>검점 필요(충돌률 5%이상) !!!"; }

	return $stat;
  }
} // end of class
?>
