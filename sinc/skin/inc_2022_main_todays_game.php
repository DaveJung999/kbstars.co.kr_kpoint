<?php
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 오늘 경기
$sql = "select * from `savers_secret`.game
		 where from_unixtime(g_start, '%Y-%m-%d') = curdate()";

/*// 테스트
$sql = "select * from `savers_secret`.game
		 where from_unixtime(g_start, '%Y-%m-%d') = '2021-01-21'";
*/

$game = db_arrayone($sql);
$td_game_value = isset($game); // 경기가 있는지 boolean 값으로 확인

if(!$td_game_value){
	$game = []; // $game 변수를 배열로 초기화하여 오류 방지
	$game['href'] = "/stat/index.php?mNum=0301&getinfo=cont&html_skin=2022_d03";
} else {
	// 국민은행이면 순서 뒤집기
	if($game['g_away'] == 13){
		$tmp 					= $game['g_away'];
		$game['g_away']			= $game['g_home'];
		$game['g_home']			= $tmp;

		$tmp 					= $game['away_score'];
		$game['away_score']		= $game['home_score'];
		$game['home_score']		= $tmp;
	}

	//print_r($game);

	//홈팀 정보
	if($game['g_home'] == 13) $game['g_home_name'] = "KB스타즈";

	//어웨이팀 정보
	$sql = " SELECT t_name FROM `savers_secret`.team WHERE tid='{$game['g_away']}' ";
	$game['g_away_name'] = db_resultone($sql,0,'t_name');

	$game['g_start_date'] = date("Y. m. d.",$game['g_start']);
	$game['g_start_time'] = date("H : i",$game['g_start']);
	$game['href'] = "/stat/2-read.php?gid={$game['gid']}&mNum=0301&getinfo=cont&html_skin=2022_d03";

	// 이겼는지 졌는지
	if(isset($game['home_score']) && isset($game['away_score'])){
		if($game['home_score'] < $game['away_score']){
			$game['home_winlose'] = '<img src="/images/2016/new/today_lose.png" width="138" height="50" alt="패"/>';
			$game['away_winlose'] = '<img src="/images/2016/new/today_win.png" width="138" height="50" alt="승"/>';
		} else {
			$game['home_winlose'] = '<img src="/images/2016/new/today_win.png" width="138" height="50" alt="승"/>';
			$game['away_winlose'] = '<img src="/images/2016/new/today_lose.png" width="138" height="50" alt="패"/>';
		}
	}
}

?>

						<div id="today_game_2018" class="clearfix">
							<div id="todaygame_title" class="clearfix"> <a href="<?=htmlspecialchars($game['href'] ?? '#')?>"><img src="/images/2017/main/title_todaygame.png" name="title_todaygame" class="image" id="title_todaygame" alt="오늘의 경기" /></a>
							</div>
							<div id="game_box" class="clearfix">
<?php if(!$td_game_value) { ?>
								<table width="430" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td><img src="/images/team_logo/main/today_nogame.png" width="430" height="185" alt="오늘 경기 정보 없음" /></td>
									</tr>
								</table>
<?php } else { ?>
								<table width="430" border="0" cellspacing="0" cellpadding="0">
									<tbody>
									<tr>
										<td width="138" height="50"><?=$game['home_winlose'] ?? ''?></td>
										<td width="154" height="50" align="center"><p>&nbsp;</p>
										<p id="today_score" style="line-height:1.4em;">
										<?=htmlspecialchars($game['g_start_date'] ?? '')?>
										</p>
										<p id="today_score" style="line-height:1.4em;">
										<?=htmlspecialchars($game['g_start_time'] ?? '')?>
										</p></td>
										<td width="138" height="50"><?=$game['away_winlose'] ?? ''?></td>
									</tr>
									<tr>
										<td width="138" height="95" align="center"><a href="<?=htmlspecialchars($game['href'] ?? '#')?>"><img src="/images/team_logo/today_game/team_<?=htmlspecialchars($game['g_home'] ?? '')?>.png" title="<?=htmlspecialchars($game['g_home_name'] ?? '')?>" width="138" height="95" alt="<?=htmlspecialchars($game['g_home_name'] ?? '')?>" /></a></td>
										<td width="154" height="95" align="center"><p>&nbsp;</p>
										<table width="154" border="0" cellspacing="0" cellpadding="0">
											<tbody>
											<tr>
												<td id="today_score_white" align="center"><?=htmlspecialchars($game['home_score'] ?? '0')?></td>
												<td id="today_score_yellow" width="14" align="center">:</td>
												<td id="today_score_white" align="center"><?=htmlspecialchars($game['away_score'] ?? '0')?></td>
											</tr>
											</tbody>
										</table></td>
										<td width="138" height="95" align="center"><a href="<?=htmlspecialchars($game['href'] ?? '#')?>"><img src="/images/team_logo/today_game/team_<?=htmlspecialchars($game['g_away'] ?? '')?>.png" title="<?=htmlspecialchars($game['g_away_name'] ?? '')?>" width="138" height="95" alt="<?=htmlspecialchars($game['g_away_name'] ?? '')?>" /></a></td>
									</tr>
									<tr>
										<td height="40" colspan="3" align="center"><p id="today_score" style="line-height:1.4em;">
										<?=htmlspecialchars($game['g_ground'] ?? '')?>
										</p></td>
									</tr>
									</tbody>
								</table>
<?php } ?>
							</div>
						</div>
