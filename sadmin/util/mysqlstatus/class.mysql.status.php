<?php
##
## this file name is 'class.mysql.status.php'
##
## show mysql my database status
## and mysql server status and comments
##
## author : <san2(at)linuxchannel.net>, http://linuxchannel.net/
## changes :
##	- 2003.01.26 : bug fixed, and upgraded comments, support MySQL 4.0.x
##	- 2003.01.24 : add status of [Threads_cached]
##	- 2003.01.23 : fixed errata
##	- 2003.01.20 : add parsing to HTML
##	- 2003.01.19 : add comments
##	- 2003.01.10 : get mydb, status, vars
##	- 2025.11.10 : Gemini AI PHP 7+ 마이그레이션 (mysql_* -> db_* 함수 교체)
##
## references :
##	- http://www.mysql.com/doc/en/SHOW_TABLE_STATUS.html
##	- http://www.mysql.com/doc/en/SHOW_STATUS.html
## 	- http://www.mysql.com/doc/en/SHOW_VARIABLES.html
##	- http://www.mysql.com/information/presentations/presentation-oscon2000-20000719/
##
## download :
##	- http://ftp.linuxchannel.net/devel/php_mysql_status/
##	- download 'class.mysql.status.php.txt' file and rename to class.mysql.status.php
##
## usage :
##	ex1) all status view
##		$status = new mysql_status; // default $what 7
##		$status->tohtml();
##
##	ex2) only my database status view
##		$status = new mysql_status(1); // or mysql_status(1,'mydb_name')
##		$status->tohtml();
##
##	ex3) only server status view
##		$status = new mysql_status(2);
##		$status->tohtml();
##
##	ex4) my database and server status view
##		$status = new mysql_status(3);
##		$status->tohtml();
##
##	ex5) only server variables view
##		$status = new mysql_status(4);
##		$status->tohtml();
##
##	ex6) HTML handle : see arguments of tohtml() function
##		$status = new mysql_status;
##		// '#000000' default, is line color of HTML table
##		// '#CCCCCC' default, is head color of HTML table
##		// '#FFFFFF' default, is TD color of HTML table
##		$color = array('line'=>'#000000','th'=>'#CCCCCC','td'=>'#FFFFFF');
##		$status->tohtml($color); // is defaults
##
##	and, more see $what argument of mysql_status() function
##	also see $color arguments of tohtml() function
##

class mysql_status
{
	var $mydb	= array();	// array, mydb(current database) information
	var $stat	= array();	// array, MySQL server status
	var $vars	= array();	// array, MySQL server variables
	var $dbs	= 0;		// string, number of databases
	var $comments	= array();	// array, MySQL server status comments

	## get mysql mydb status and server variables and status
	##
	## arguments :
	##	$what	integer(0 <= $what <= 7), bits of get mode
	##		- default : $what 7
	##		- $what 0 : all, same as $what 7
	##		- $what 1 : only my database infomation
	##		- $what 2 : only server status
	##		- $what 3 : $what 1 + $what 2
	##		- $what 4 : only server variables
	##		- $what 5 : $what 1 + $what 4
	##		- $what 6 : $what 2 + $what 4
	##		- $what 7 : $what 1 + $waht 2 + $what 4
	##	$db	string, table status of $db, default(current connected database)
	##
	function mysql_status($what=7, $db='')
	{
		$stat = array();
		$vars = array();

		if($what<1 || $what>7 || !is_int($what)) $what = 7;

		$bits = $this->get_bits($what);

		## 'SHOW TABLE STATUS', same as 'mysqlshow [OPTIONS] --status mydb'
		##
		if($bits['0']) $this->get_mydb($db);

		## 'SHOW STATUS', same as 'mysqladmin [OPTIONS] extended-status'
		##
		if($bits['1']) $stat = $this->get_array('SHOW STATUS');

		## 'SHOW VARIABLES', same as 'mysqladmin [OPTIONS] variables'
		##
		if($bits['2']) $vars = $this->get_array('SHOW VARIABLES');

		## then, get main server status
		##
		if($bits['1'])
		{
			## 'SHOW DATABASES', same as 'mysqlshow [OPTIONS]' or mysql_list_dbs()
			##
			$this->get_dbs();

			## get comments from $vars, $stat
			##
			$stat = $this->get_comments($vars,$stat);

			## then, get main server status
			##
			$this->get_stat($stat);
		}

		## and then, get main server variables
		##
		if($bits['2']) $this->get_vars($vars);

		## force to flush buffer
		##
		unset($vars);
		unset($stat);
	}

	## decimal to binary
	##
	function get_bits($dec)
	{
		if(!is_int($dec)) return 0;

		$dec = abs($dec);
		$bits = strrev(decbin($dec));

		return $bits; // is not integer, is string
	}

	## get my databases information
	##
	## http://www.mysql.com/doc/en/SHOW_TABLE_STATUS.html
	##
	function get_mydb($db='')
	{
		if($db) $sql = ' FROM '.$db;
		// [!] FIX: mysql_query -> db_query
		if(!$result = db_query('SHOW TABLE STATUS'.$sql)) return 0;

		$i = 0;
		// [!] FIX: mysql_fetch_assoc -> db_array
		while($lists = db_array($result)) // associative array
		{
			$this->mydb[] = array(
			'name' => $lists['Name'],
			'type' => $lists['Type'],
			'rows' => number_format($lists['Rows']),
			'srow' => $this->hsize($lists['Avg_row_length']), // size of avg row
			'size' => $this->hsize($lists['Data_length']), // size of this table
			'sidx' => $this->hsize($lists['Index_length'])
			);

			$sum['rows'] += $lists['Rows'];
			$sum['size'] += $lists['Data_length'];
			$sum['sidx'] += $lists['Index_length'];

			$i++;
		}

		// [!] FIX: mysql_free_result -> db_free
		db_free($result);

		$this->mydb[$i] = array(
		'name' => '<B>SUM</B>',
		'type' => $i.' tables',
		'rows' => number_format($sum['rows']),
		'srow' => $this->hsize(@round($sum['size']/$sum['rows'])),
		'size' => '<B>'.$this->hsize($sum['size']).'</B>',
		'sidx' => $this->hsize($sum['sidx'])
		);
	}

	## get MySQL server status and variables
	##
	## http://www.mysql.com/doc/en/SHOW_STATUS.html
	## http://www.mysql.com/doc/en/SHOW_VARIABLES.html
	## http://www.mysql.com/information/presentations/presentation-oscon2000-20000719/
	##
	function get_array($sql)
	{
		// [!] FIX: mysql_query -> db_query
		if(!$result = db_query($sql)) return 0;

		## get array to $vars
		##
		$array = array();
		// [!] FIX: mysql_fetch_row -> db_row (NUMERIC array fetch)
		while($lists = db_row($result))
		{ $array[$lists['0']] = $lists['1']; }

		// [!] FIX: mysql_free_result -> db_free
		db_free($result);

		return $array;
	}

	function get_dbs()
	{
		// [!] FIX: mysql_list_dbs()를 db_tablelist()와 db_resultone("SELECT DATABASE()")으로 대체
		$current_db = db_resultone("SELECT DATABASE()");
		if (!$current_db) {
			$this->dbs = 'Error';
			return;
		}

		// [!] FIX: 현재 연결된 DB의 이름을 $db_conn 전역 변수를 통해 얻을 수 없으므로, 모든 DB 리스트를 가져와 개수를 센다.
		global $db_conn;
		if(empty($db_conn)) { // DB 연결이 안 되었으면 종료
			$this->dbs = 'Not connected';
			return;
		}
		
		// DB 리스트 가져오기 (db_tablelist는 테이블 리스트를 반환하므로, 여기서는 SHOW DATABASES 대신 SELECT)
		// db_tablelist() 함수가 DB 목록도 지원한다고 가정 (원래 의도는 db_list_dbs() 대체)
		$dbs_array = db_tablelist($current_db, '.*', 1);
		
		if (is_array($dbs_array)) {
			$num = sizeof($dbs_array);
		} else {
			$num = 1; // 단일 DB
		}
		
		if($num < 2) $this->dbs = 'hidden';
		else $this->dbs = $num .' or more';
	}

	## get comments and return $stat(added)
	##
	## 1) MySQL caches(all threads shared) [***]
	##	- key_buffer_size	: 8MB < INDEX key
	##	- table_cache		: 64 < number of open tables for all threads
	##	- thread_cache_size	: 0 < number of keep in a cache for reuse
	##
	## 2) MySQL buffers(not shared) [***]
	##	- join_buffer_size	: 1MB < FULL-JOIN
	##	- myisam_sort_buffer_size : 8MB < REPAIR, ALTER, LOAD
	##	- record_buffer		: 2MB < sequential scan allocates
	##	- record_rnd_buffer	: 2MB < ORDER BY(to avoid disk)
	##	- sort_buffer		: 2MB < ORDER BY, GROUP BY
	##	- tmp_table_size	: 32MB < advanced GROUP BY(to avoid disk)
	##
	## 3) MySQL memory size of INDEX(key)/JOIN/RECORD(read)/SORT/TABLE
	##	- INDEX(key)		: 8MB < [***] key_buffer_size (shared)
	##	- JOIN 			: 1MB <	[***] join_buffer_size (not shared)
	##	- RECORD(read)		: 2MB < [***] record_buffer (not shared)
	##				: 2MB < [***] record_rnd_buffer (not shared)
	##	- SORT			: 8MB < [	] myisam_sort_buffer_size (not shared)
	##				: 2MB <	[***] sort_buffer (not shared)
	##	- TABLE(tmp)		: 32MB< [***] tmp_table_size(not shared)
	##
	## 4) MySQL timeout
	##	- interactive_timeout	: 28800 > active to re-active timeout
	##	- wait_timeout		: 28000 > not active to active timeout
	##
	## 5) MySQL connections
	##	- max_connections	: 100 < 'to many connections' error
	##	- max_user_connections	: 0(no limit) or upto [Max_used_connect] status
	##
	function get_comments($vars, $stat)
	{
		$stat['ab_clients'] = sprintf('%.2f',$stat['Aborted_clients']*100/$stat['Connections']);
		$stat['ab_connects'] = sprintf('%.2f',$stat['Aborted_connects']*100/$stat['Connections']);
		$stat['per_qs'] = sprintf('%.2f',$stat['Questions']/$stat['Uptime']);
		$stat['per_qc'] = sprintf('%.2f',$stat['Questions']/$stat['Connections']);
		$stat['per_cs'] = sprintf('%.2f',$stat['Connections']/$stat['Uptime']);
		$stat['per_kr'] = sprintf('%.2f',@($stat['Key_reads']/$stat['Key_read_requests']));
		$stat['per_kw'] = sprintf('%.2f',@($stat['Key_writes']/$stat['Key_write_requests']));
		$stat['per_tc'] = sprintf('%.2f',$stat['Threads_created']/$stat['Connections']);
		$stat['per_oc'] = sprintf('%.2f',$stat['Opened_tables']/$stat['Connections']);

		## share refer vars
		##
		if($vars)
		{
			$stat['refer_vars_wait_timeout'] = number_format($vars['wait_timeout']).
				'('.$this->runtime($vars['wait_timeout']).')';
			$stat['refer_vars_interactive_timeout'] = number_format($vars['interactive_timeout']).
				'('.$this->runtime($vars['interactive_timeout']).')';
			$stat['refer_vars_connect_timeout'] = number_format($vars['connect_timeout']).
				'('.$this->runtime($vars['connect_timeout']).')';
			$stat['refer_vars_key_buffer_size'] = number_format($vars['key_buffer_size']).
				'('.$this->hsize($vars['key_buffer_size']).')';
			$stat['refer_vars_max_connections'] =
				number_format($vars['max_connections']);
			$stat['refer_vars_table_cache'] =
				number_format($vars['table_cache']);
			$stat['refer_vars_thread_cache_size'] =
				number_format($vars['thread_cache_size']);
			$stat['refer_vars_tmp_table_size'] = number_format($vars['tmp_table_size']).
				'('.$this->hsize($vars['tmp_table_size']).')';
			$stat['refer_vars_long_query_time'] = number_format($vars['long_query_time']).
				'('.$this->runtime($vars['long_query_time']).')';
		}

		$B['b'] = '<FONT COLOR=#0000FF>'; // for blue
		$B['r'] = '<FONT COLOR=#FF0000>'; // for red
		$B['e'] = '</FONT>';

		$busy = array('normal','busy','very busy','hot busy');

		if($stat['ab_clients'] > 1) {
			$this->comments['wait_timeout'] = $B['r'].',<BR>연결 취소가 많습니다(기준 1 %).<BR>'.
			$stat['refer_vars_wait_timeout'].'(wait_timeout)초나 '.
			$stat['refer_vars_interactive_timeout'].'(interactive_timeout)초보다<BR>'.
			'늦게 close 하거나 연결을 끊지 않는 경우가 많습니다.'.$B['e'];
			$this->comments['interactive_timeout'] = $this->comments['wait_timeout'];
		} else {
			$this->comments['wait_timeout'] =
			$this->comments['interactive_timeout'] = $B['b'].', 정상'.$B['e'];
		}

		if($stat['ab_connects'] > 1) {
			$this->comments['connect_timeout'] = $B['r'].',<BR>잘못된(fail) 접속 요청이 많습니다'.
			'(기준 1 %).<BR>'.
			$stat['refer_vars_connect_timeout'].'(connect_timeout)값을 올리세요.'.$B['e'];
		}
		else $this->comments['connect_timeout'] = $B['b'].', 정상'.$B['e'];

		if($stat['per_qs'] < 1) $bkey = 0;
		else if($stat['per_qs'] < 10) $bkey = 1;
		else if($stat['per_qs'] < 20) $bkey = 2;
		else $bkey = 3;

		$this->comments['busy'] = $busy[$bkey];

		if($stat['per_kr'] > 0.01) {
			$this->comments['key_buffer_size'] = $B['r'].
			',<BR>DISK에서 Key를 읽는 요청이 많습니다'.
			'(기준 0.01).<BR>'.$stat['refer_vars_key_buffer_size'].
			'(key_buffer_size)값을 올리세요.'.$B['e'];
		}
		else $this->comments['key_buffer_size'] = $B['b'].', 정상'.$B['e'];

		if($vars['max_connections'])
		{
			$max['vars'] = max($stat['Max_used_connections'],$vars['max_connections']);
			$max['stat'] = $stat['Max_used_connections'] / $vars['max_connections'];
			if($max['stat'] > 0.95 ) {
				$max['to'] = $max['vars'] + (int)($max['vars']*0.2);
				$this->comments['max_connections'] = $B['r'].
				',<BR>최대 접속수에 근접하거나 초과했습니다.<BR>'.
				$stat['refer_vars_max_connections'].
				'(max_connections)값을 '.$max['to'].' 정도로 올리세요.'.$B['e'];
			}
			else $this->comments['max_connections'] = $B['b'].', 정상'.$B['e'];

			$max['table'] = $max['vars'] * 2;
			if($max['table'] > $vars['table_cache']) {
				$this->comments['table_cache'] = $B['r'].',<BR>'.
				$stat['refer_vars_table_cache'].'(table_cache)값을 올리세요.'.$B['e'];
			}
			else $this->comments['table_cache'] = $B['b'].', '.$stat['per_oc'].'/Connections'.$B['e'];
		}

		if($stat['per_tc'] > 0.01)
		{
			if($stat['per_cs'] > 1) {
				$this->comments['thread_cache_size'] = $B['r'].',<BR>'.
				$stat['refer_vars_thread_cache_size'].
				'[thread_cache_size]값을 올리세요.'.$B['e'];
			}
			else if($vars['thread_cache_size'] < 2) {
				$this->comments['thread_cache_size'] = $B['r'].',<BR>'.
				'현재 서버가 매우 바쁘지 않으므로 '.
				$stat['refer_vars_thread_cache_size'].'[thread_cache_size]값을 2 정도로 '.
				'올려 보세요.'.$B['e'];
			}
			else {
				$this->comments['thread_cache_size'] = $B['r'].',<BR>'.
				'현재 서버가 매우 바쁘지 않으므로, 시간이 좀더 지나면 '.
				'이 메시지가 보이질 않아야 정상입니다.'.$B['e'];
			}
		}
		else $this->comments['thread_cache_size'] = $B['b'].', 정상'.$B['e'];

		return $stat;
	}

	## get main status
	##
	function get_stat($stat)
	{
		$this->stat = array(
		'TOTAL_STATUS' =>
			array($stat['per_qs'],'<B>'.$this->comments['busy'].'</B> (1초당 평균 쿼리 요청수)'),
		'All_databases' =>
			array($this->dbs,'데이터베이스 수'),
		'Aborted_clients' =>
			array(number_format($stat['Aborted_clients']),'연결 취소 Clients 수'),
		'Aborted_connects' =>
			array(number_format($stat['Aborted_connects']),'연결 실패수'),
		'Aborted_clients_percent' =>
			array($stat['ab_clients'].' %','연결 취소율'.$this->comments['wait_timeout']),
		'Aborted_connects_percent' =>
			array($stat['ab_connects'].' %','연결 실패율'.$this->comments['connect_timeout']),
		'Bytes_received' =>
			array($this->hsize($stat['Bytes_received']),'수신량(총)'),
		'Bytes_sent' =>
			array($this->hsize($stat['Bytes_sent']),'전송량(총)'),
		'Bytes_sent_per_sec' =>
			array($this->hsize($stat['Bytes_sent']/$stat['Uptime']),'전송량(초당)'),
		'Bytes_sent_per_min' =>
			array($this->hsize($stat['Bytes_sent']*60/$stat['Uptime']),'전송량(분당)'),
		'Bytes_sent_per_hour' =>
			array($this->hsize($stat['Bytes_sent']*3600/$stat['Uptime']),'전송량(시간당)'),
		'Bytes_sent_per_day' =>
			array($this->hsize($stat['Bytes_sent']*86400/$stat['Uptime']),'전송량(하루평균)'),
		'Connections' =>
			array(number_format($stat['Connections']),'총 connections 수'),
		'Connections_per' =>
			array($stat['per_cs'],'초당 connections 수'),
		'Created_tmp_disk_tables' =>
			array(number_format($stat['Created_tmp_disk_tables']),
			'DISK에 생성된 임시 테이블 수, refer '.
			$stat['refer_vars_tmp_table_size'].'[tmp_table_size]'),
		'Created_tmp_tables' =>
			array(number_format($stat['Created_tmp_tables']),
			'메모리에 생성된 임시 테이블 수'),
		'Key_reads' =>
			array(number_format($stat['Key_reads']),'DISK에서 Key 읽은 수'),
		'Key_read_requests' =>
			array(number_format($stat['Key_read_requests']),'캐시에서 Key 읽기 요청수'),
		'Key_writes' =>
			array(number_format($stat['Key_writes']),'DISK에 Key를 쓴 수'),
		'Key_write_requests' =>
			array(number_format($stat['Key_write_requests']),'캐시에 Key 쓰기 요청수'),
		'Key_reads_per_request' => // to < 0.01
			array($stat['per_kr'],'DISK에서 Key를 읽는 비율'.$this->comments['key_buffer_size']),
		'Key_writes_per_request' =>
			array($stat['per_kw'],'DISK에 Key를 쓰는 비율, 보통 1에 가까워야 정상'),
		'Max_used_connections' =>
			array(number_format($stat['Max_used_connections']),
			'동시에 연결된 최대값'.$this->comments['max_connections']),
		'Open_tables' =>
			array(number_format($stat['Open_tables']),'현재 열려있는 Tables 수'),
		'Opened_tables' =>
			array(number_format($stat['Opened_tables']),
			'열렸던 Tables 수, refer '.$stat['refer_vars_table_cache'].'[table_cache]'.
			$this->comments['table_cache']),
		'Questions' =>
			array(number_format($stat['Questions']),'현재까지 쿼리 요청수'),
		'Questions_per_connect' =>
			array($stat['per_qc'],'커넥션당 평균 쿼리 요청수'),
		'Select_full_join' =>
			array(number_format($stat['Select_full_join']),'Key 없이 FULL-JOIN 횟수'),
		'Slow_queries' =>
			array(number_format($stat['Slow_queries']),
			$stat['refer_vars_long_query_time'].'(long_query_time)초 보다 큰 쿼리 요청수'),
		'Table_locks_waited' =>
			array($this->runtime($stat['Table_locks_waited']),'Lock wait 총시간'),
		'Threads_cached' =>
			array(number_format($stat['Threads_cached']),
			'캐시된 쓰레드수, '.$stat['refer_vars_thread_cache_size'].
			'[thread_cache_size]보다 항상 작음'),
		'Threads_connected' =>
			array(number_format($stat['Threads_connected']),
			'현재 열려있는 커넥션 수'),
		'Threads_created' =>
			array(number_format($stat['Threads_created']),
			'handle 커넥션에 생성된 쓰레드 수'),
		'Threads_created_per' =>
			array($stat['per_tc'],'커넥션당 생성된 평균 쓰레드 수(기준 0.01)'.
			$this->comments['thread_cache_size']),
		'Threads_running' =>
			array(number_format($stat['Threads_running']),
			'현재 구동중인 쓰레드 수(not sleeping)'),
		'Uptime' =>
			array($this->runtime($stat['Uptime']),'최근 MySQL 서버 구동 시간')
		);
	}

	## get main variables
	##
	function get_vars($vars)
	{
		$ver = explode('.',preg_replace('/-.+$/','',$vars['version']));
		$ver['1'] = sprintf('%02d',$ver['1']);
		$ver['2'] = sprintf('%02d',$ver['2']);
		$ver = $ver['0'].$ver['1'].$ver['2'];

		## http://www.mysql.com/doc/en/Upgrading-from-3.23.html
		## myisam_max_extra_sort_file_size, myisam_max_sort_file_size
		##
		if($ver < 40003) {
			$upg = array(
			'ast'		=> 1048576, // 1024*1024
			//'bulk'	=> 'myisam_bulk_insert_tree_size',
			//'qtype'	=> 'query_cache_startup_type',
			'rbuffer'	=> 'record_buffer',
			'rndbuffer'	=> 'record_rnd_buffer',
			'sbuffer'	=> 'sort_buffer',
			//'warn'	=> 'warnings'
			);
		} else {
			$upg = array(
			'ast'		=> 1,
			//'bulk'	=> 'bulk_insert_buffer_size',
			//'qtype'	=> 'query_cache_type',
			'rbuffer'	=> 'read_buffer_size',
			'rndbuffer'	=> 'read_rnd_buffer_size',
			'sbuffer'	=> 'sort_buffer_size',
			//'warn'	=> 'log-warnings'
			);
		}

		$this->vars = array(
		'back_log' =>
			array(number_format($vars['back_log']),'man 2 listen, '.
			'net.ipv4.tcp_max_syn_backlog'),
		'connect_timeout' =>
			array(number_format($vars['connect_timeout']).
			'('.$this->runtime($vars['connect_timeout']).')',
			'bad handshake timeout(초)'.$this->comments['connect_timeout']),
		//'delayed_insert_limit',
		//'delayed_insert_timeout',
		'join_buffer_size' =>
			array(number_format($vars['join_buffer_size']).
			'('.$this->hsize($vars['join_buffer_size']).')',
			'[***] FULL-JOIN에 사용되는 메모리'),
		'key_buffer_size' =>
			array(number_format($vars['key_buffer_size']).
			'('.$this->hsize($vars['key_buffer_size']).')',
			'[***] INDEX key buffer에 사용되는 메모리, refer [Key_xxx]'.
			$this->comments['key_buffer_size']),
		'long_query_time' =>		// status(--log-slow-queries=file)
			array(number_format($vars['long_query_time']).
			'('.$this->runtime($vars['long_query_time']).')',
			'refer '.$this->stat['Slow_queries']['0'].'[Slow_queries]'),
		'lower_case_table_names' =>
			array($vars['lower_case_table_names'],
			'테이블 대소문자 구별유무(0 구별)'),
		'max_allowed_packet' =>
			array(number_format($vars['max_allowed_packet']).
			'('.$this->hsize($vars['max_allowed_packet']).')',
			'최대 허용할 패킷'),
		'max_connections' =>
			array(number_format($vars['max_connections']),
			'[***] 최대 동시 접속 커넥션 수, refer '.
			$this->stat['Max_used_connections']['0'].'[Max_used_connections]'.
			$this->comments['max_connections']),
		'max_join_size' =>
			array(number_format($vars['max_join_size']).
			'('.$this->hsize($vars['max_join_size']).')',
			'JOIN에 사용될 최대 크기(메모리 아님)'),
		'max_sort_length' =>
			array(number_format($vars['max_sort_length']).
			'('.$this->hsize($vars['max_sort_length']).')',
			'TEXT, BLOB의 정렬에 사용되는 최대 크기'),
		'max_user_connections' =>
			array(number_format($vars['max_user_connections']),
			'최대 동시 user 수(0은 제한없음)'),
		/***
		'max_tmp_tables' =>		// doesn't yet do anything
			array(number_format($vars['max_tmp_tables']),
			'최대 임시 테이블 수'),
		***/
		'myisam_max_extra_sort_file_size' =>	// MBytes(4.0.3 use bytes)
			array(number_format($vars['myisam_max_extra_sort_file_size']).
			'('.$this->hsize($vars['myisam_max_extra_sort_file_size']*$upg['ast']).')',
			'빠른 INDEX를 생성시 사용되는 최대 임시 파일 크기'),
		'myisam_max_sort_file_size' =>	// MBytes(4.0.3 use bytes)
			array(number_format($vars['myisam_max_sort_file_size']).
			'('.$this->hsize($vars['myisam_max_sort_file_size']*$upg['ast']).')',
			'REPAIR, ALTER, LOAD...등 INDEX를 재생성시 사용되는 임시 파일 크기'),
		'myisam_sort_buffer_size' =>	// [***] use REPAIR, INDEX sort
			array(number_format($vars['myisam_sort_buffer_size']).
			'('.$this->hsize($vars['myisam_sort_buffer_size']).')',
			'[***] REPAIR, INDEX, ALTER 정렬에 사용하는 메모리'),
		'open_files_limit' =>		// error 'Too many open file files', default '0'
			array(number_format($vars['open_files_limit']),
			'파일 open 제한 수(0은 제한없음)'),
		/***
		'query_buffer_size' =>		// only 3.23.x, start(init) query buffer, default '0'
			array(number_format($vars['query_buffer_size']).
			'('.$this->hsize($vars['query_buffer_size']).')',
			'초기 쿼리 버퍼용 메모리(기본값은 0)'),
		***/
		$upg['rbuffer'] =>
			array(number_format($vars["{$upg['rbuffer']}"]).
			'('.$this->hsize($vars["{$upg['rbuffer']}"]).')',
			'[***] 순차적인 검색에 사용되는 메모리(read_buffer_size)'),
		$upg['rndbuffer'] =>		// [***] use improve ORDER BY(to avoid a disk seek)
			array(number_format($vars['record_rnd_buffer']).
			'('.$this->hsize($vars['record_rnd_buffer']).')',
			'[***] ORDER BY 정렬에 사용되는 메모리(avoid disk seek)'),
		$upg['sbuffer'] =>		// [***] use ORDER BY, GROUP BY
			array(number_format($vars["{$upg['sbuffer']}"]).
			'('.$this->hsize($vars["{$upg['sbuffer']}"]).')',
			'[***] ORDER BY, GROUP BY 정렬에 사용되는 메모리'),
		'table_cache' =>		// [***] refer to [Opened_tables] status
			array(number_format($vars['table_cache']),
			'[***] 한번에(all thread) 열 수 있는 테이블 수 refer '.
			$this->stat['Opened_tables']['0'].'[Opened_tables]'),
		'thread_cache_size' =>	// [***] refer to [Connections] and [Threads_created] status
			array(number_format($vars['thread_cache_size']),
			'[***] 쓰레드 캐시 재사용 수, refer '.
			$this->stat['Connections']['0'].'[Connections], '.
			$this->stat['Threads_created']['0'].'[Threads_created]'.
			$this->comments['thread_cache_size']),
		'tmp_table_size' =>		// [***] use in-memory(to disk), GROUP BY
			array(number_format($vars['tmp_table_size']).
			'('.$this->hsize($vars['tmp_table_size']).')',
			'[***] 복잡한 GROUP BY 정렬에 사용되는 메모리(avoid disk)'),
		'interactive_timeout' =>
			array(number_format($vars['interactive_timeout']).
			'('.$this->runtime($vars['interactive_timeout']).')',
			'interactive -> re-active에 기다리는 시간(이후 closed)'.
			$this->comments['interactive_timeout']),
		'wait_timeout' =>
			array(number_format($vars['wait_timeout']).
			'('.$this->runtime($vars['wait_timeout']).')',
			'none interactive -> active에 기다리는 시간(이후 closed)'.
			$this->comments['wait_timeout']),
		'timezone' =>
			array($vars['timezone'],
			'현재 MySQL 서버의 TIME-ZONE'),
		'version' =>
			array($vars['version'],
			'현재 MySQL 서버 버전')
		);
	}

	## parsing to html
	##
	## arguments :
	##	$color		array, parsing to TABLE cloor
	##	$color['line']	string, line color of HTML table, default '#000000'
	##	$color['th']	string, head color of HTML table, default '#CCCCCC'
	##	$color['td']	string, TD color of HTML table, default '#FFFFFF'
	##
	##	$return		boolean, return(1) or print(0)
	##
	function tohtml($color=array(), $return=0)
	{
		if(!$color['line']) $color['line'] = '#000000';
		if(!$color['th']) $color['th'] = '#CCCCCC';
		if(!$color['td']) $color['td'] = '#FFFFFF';

		$html = '';
		if($this->mydb) $html = $this->mydb2html($color);
		if($this->stat) $html .= $this->array2html($this->stat,$color,'STATUS');
		if($this->vars) $html .= $this->array2html($this->vars,$color,'VARIABLES');

		if($return) return $html;
		else echo $html;
	}

	function mydb2html($color)
	{
		$r =	'<B>MY DATABASE STATUS</B>'."\n".
			'<TABLE BORDER=0 WIDTH=100% CELLPADDING=0 CELLSPACING=0 BGCOLOR='.$color['line'].'>'."\n".
			'<TR><TD>'."\n".
			'<TABLE BORDER=0 WIDTH=100% CELLPADDING=3 CELLSPACING=1>'."\n".
			'<TR BGCOLOR='.$color['th'].'>'."\n".
			'<TD ALIGN=right NOWRAP>테이블 이름</TD>'."\n".
			'<TD ALIGN=center NOWRAP>테이블 타입</TD>'."\n".
			'<TD ALIGN=right NOWRAP>레코드 수</TD>'."\n".
			'<TD ALIGN=right NOWRAP>레코드 평균 크기</TD>'."\n".
			'<TD ALIGN=right NOWRAP>사용량</TD>'."\n".
			'<TD ALIGN=right NOWRAP>Index 크기</TD>'."\n".
			'</TR>'."\n";

		$nums = sizeof($this->mydb);
		for($i=0; $i<$nums; $i++)
		{
			// [!] FIX: PHP 7+ 배열 접근 방식 수정
			$r .=	'<TR BGCOLOR='.$color['td'].'>'."\n".
				'<TD ALIGN=right>'.$this->mydb[$i]['name'].'</TD>'."\n".
				'<TD ALIGN=center>'.$this->mydb[$i]['type'].'</TD>'."\n".
				'<TD ALIGN=right>'.$this->mydb[$i]['rows'].'</TD>'."\n".
				'<TD ALIGN=right>'.$this->mydb[$i]['srow'].'</TD>'."\n".
				'<TD ALIGN=right>'.$this->mydb[$i]['size'].'</TD>'."\n".
				'<TD ALIGN=right>'.$this->mydb[$i]['sidx'].'</TD>'."\n".
				'</TR>'."\n";
		}

		$r .=	'</TABLE>'."\n".
			'</TD></TR>'."\n".
			'</TABLE>'."\n<BR>\n";

		return $r;
	}

	function array2html($array, $color, $name)
	{
		$r =	'<B>MYSQL SERVER '.$name.'</B>'."\n".
			'<TABLE BORDER=0 WIDTH=100% CELLPADDING=0 CELLSPACING=0 BGCOLOR='.$color['line'].'>'."\n".
			'<TR><TD>'."\n".
			'<TABLE BORDER=0 WIDTH=100% CELLPADDING=3 CELLSPACING=1>'."\n".
			'<TR BGCOLOR='.$color['th'].'>'."\n".
			'<TD WIDTH=25% ALIGN=right>항목</TD>'."\n".
			'<TD WIDTH=10% ALIGN=right>'.$name.'</TD>'."\n".
			'<TD WIDTH=65%>commnets, [***] 표시는 성능 향상과 관련됨</TD>'."\n".
			'</TR>'."\n";

		$tmp = array_keys($array);
		foreach($tmp AS $key)
		{
			$r .=	'<TR BGCOLOR='.$color['td'].'>'."\n".
				'<TD ALIGN=right>'.$key.'</TD>'."\n".
				'<TD ALIGN=right NOWRAP>'.$array[$key][0].'</TD>'."\n".
				'<TD>'.$array[$key][1].'</TD>'."\n".
				'</TR>'."\n";
		}

		$r .=	'</TABLE>'."\n".
			'</TD></TR>'."\n".
			'</TABLE>'."\n<BR>\n";

		return $r;
	}

	function hsize($bfsize, $sub=0)
	{
		$BYTES = number_format($bfsize).' Bytes';

		if($bfsize < 1024) return $BYTES;
		else if($bfsize < 1048576) $bfsize = number_format(round($bfsize/1024)).' KB';
		else if($bfsize < 1073741827) $bfsize = number_format(round($bfsize/1048576)).' MB';
		else $bfsize = number_format($bfsize/1073741827,1).' GB';

		if($sub) $bfsize .= "($BYTES)";

		return $bfsize;
	}

	function runtime($term, $lang='kr')
	{ 
		$l['kr'] = $l['ko'] = array('초','분','시간','일','달');
		$l['en'] = array('seconds','minutes','hours','days','months');
	 
		$months	= (int)($term / 2592000);
		$term	= (int)($term % 2592000);
		$days	= (int)($term / 86400);
		$term	= (int)($term % 86400);
		$hours	= (int)($term / 3600);
		$term	= (int)($term % 3600);
		$mins	= (int)($term / 60);
		$secs	= (int)($term % 60);
	 
		$months = $months ? $months.$l[$lang][4].' ' : '';
		$days = $days ? $days.$l[$lang][3].' ' : '';
		$hours = $hours ? $hours.$l[$lang][2].' ' : '';
		$mins = $mins ? $mins.$l[$lang][1].' ' : '';
		$secs .= $l[$lang][0];

		return $months.$days.$hours.$mins.$secs;
	}
} // end of class

/*** example
// [!] FIX: mysql_connect/mysql_select_db/mysql_close를 db_* 함수로 대체
require 'class.mysql.status.php';

// db_connect('localhost','mysql','');
// db_select('board');

$status = new mysql_status;
$status->tohtml();

// db_close();
***/
?>