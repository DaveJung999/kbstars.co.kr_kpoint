<?php
// 다음 경기일정......................
$sql = "select * from `savers_secret`.game
		where (g_home=13 or g_away=13)
		and from_unixtime(g_start, '%Y%m%d') > from_unixtime(UNIX_TIMESTAMP(), '%Y%m%d')
		order by g_start LIMIT 1";

/*
// 테스트
$sql = "select * from `savers_secret`.game
		where (g_home=13 or g_away=13)
		and from_unixtime(g_start, '%Y%m%d') > '20210119'
		order by g_start LIMIT 1";
*/

$game_next = db_arrayone($sql);

// 경기일정 있을때
if ($game_next != ""){
	// 국민은행이면 순서 뒤집기
	if($game_next['g_away'] == 13){
		$tmp					= $game_next['g_away'];
		$game_next['g_away']	= $game_next['g_home'];
		$game_next['g_home']	= $tmp;
	}

	//홈팀 정보
	if($game_next['g_home'] == 13) $game_next['g_home_name'] = "KB스타즈";

	//어웨이팀 정보
	$sql = " SELECT t_name FROM `savers_secret`.team WHERE tid='{$game_next['g_away']}' ";
	$game_next['g_away_name'] = db_resultone($sql,0,'t_name');

	// 경기날짜 장소
	//$strWeek = array('(일) ','(월) ','(화) ','(수) ','(목) ','(금) ','(토) ');
	$game_next['g_start_date'] = date("Y. m. d H:i", $game_next['g_start']);

/*	if($_SERVER['REMOTE_ADDR'] == '119.204.101.117')
		print_r($game_next); */
		
	//$game_next['href'] = "/stat/2-read.php?gid={$game_next['gid']}&mNum=0301&getinfo=cont&html_skin=2022_d03";

}

//$game_next['href'] = "/stat/index.php?mNum=0301&getinfo=cont&html_skin=2022_d03";
$game_next['href'] = "/kbstars/2022/d03/01.php?mNum=0301";

/*if($_SERVER['REMOTE_ADDR'] == '119.204.101.117')
		print_r($game_next['g_away']);*/

?>
						<div id="next_game" class="clearfix">
							<div id="nextgame_title" class="clearfix"><a href="<?=$game_next['href'] ?? '#'?>"><img id="title_nextgame" src="/images/2017/main/title_nextgame.png" class="image" alt="다음 경기 일정" /></a></div>
							<div id="gamebox" class="clearfix">
							<?php
							if(isset($game_next['g_away'])){
							?>
								<div id="gamebox_score" class="clearfix">
									<table width="430" height="80" border="0" cellspacing="0" cellpadding="0">
										<tbody>
											<tr>
												<td width="105" align="center">
												<p id="nextdate_gameteam" style="font-weight:bold; margin-top:20px;"><?=$game_next['g_home_name'] ?? ''?></p></td>
												<td width="95" align="center" valign="bottom"><img src="/images/team_logo/nextgame/team_<?=$game_next['g_home'] ?? ''?>.png" title="<?=$game_next['g_home_name'] ?? ''?>" width="95" height="74" alt="<?=$game_next['g_home_name'] ?? ''?>"/></td>
												<td width="30" align="center" valign="middle"><p style=" margin-top:20px;"><img src="/images/2016/new/nextgame_vs.png" width="19" height="13" alt="vs"/></p></td>
												<td width="95" align="center" valign="bottom"><img src="/images/team_logo/nextgame/team_<?=$game_next['g_away'] ?? ''?>.png" title="<?=$game_next['g_away_name'] ?? ''?>" width="95" height="74" alt="<?=$game_next['g_away_name'] ?? ''?>"/></td>
												<td width="105" align="center">
												<p id="nextdate_gameteam" style="font-weight:bold; margin-top:20px;"><?=$game_next['g_away_name'] ?? ''?></p></td>
											</tr>
										</tbody>
									</table>
								</div>

								<div id="gamebox_score2" class="clearfix">
									<p id="nextdate">
									<?=$game_next['g_start_date'] ?? ''?>&nbsp;<?=$game_next['g_ground'] ?? ''?><br />
									</p>
								</div>
							<?php
							} else {
							?>
								<div id="gamebox_empty" class="clearfix"><img src="/images/team_logo/main/next_game_empty_1st.jpg" width="430" height="108" alt="다음 경기 정보가 없습니다" /></div>
							<?php
							}
							?>
							</div>
						</div>
