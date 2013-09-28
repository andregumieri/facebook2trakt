<?php
	require('../libs/phpQuery-onefile.php');
	
	$html = file_get_contents("padrao.html");
	// $html = file_get_contents("originais.html"); // $id = 70133673;
	// $html = file_get_contents("originais2.html"); // $id = 70248291;

phpQuery::newDocumentHTML($html);
$id = 70133673;
//print_r();
	if(count(pq('div.videoImagery'))>0) {
		$name = trim(pq("#odp-body h1")->text());
		$season = trim(pq("#qdd_0 li.option-selected>a>span")->text());
		if(empty($season)) $season = 1;
		$episode = null;
		$title = null;
		$year = trim(pq("#odp-body>div.ShowInfo>div.moduleContent span.showYear")->text());
		$year = explode("-", $year);
		$year = trim($year[0]);
		$duration = null;

		foreach(pq('div.videoRow') as $video) {
			$episodeId = pq($video)->children("div.videoImagery")->attr("data-episode-id");
			if($episodeId==$id) {
				$elTitle = pq($video)->children("div.videoDetails")->children("div.title");
				$episode = trim($elTitle->children("span.episodeNumber")->text());
				$title = trim($elTitle->children("span.title-text")->text());
				$duration = trim(pq($video)->find(".progress-details .episode-length.total-time .time-text")->text());
				preg_match('!\d+!', $duration, $matches);
				if(count($matches)>0) {
					$duration = intval($matches[0]);
				} else {
					$duration = null;
				}
			}
		}
	} else {
		$name = trim(pq("#displaypage-overview-details h1")->text());
		$season = trim(pq("#selectorButton>span")->text());
		$episode = trim(pq("#episodeColumn>ul>li.current>span.seqNum")->text());
		$title = trim(pq("#episodeColumn>ul>li.current>span.episodeTitle")->text());	
		$year = trim(pq("#displaypage-overview-details>div.titleArea>span.year")->text());
		$year = explode("-", $year);
		$year = trim($year[0]);
		$duration = pq("#episodeColumn>ul>li.current h3 .duration")->text();
		preg_match('!\d+!', $duration, $matches);
		if(count($matches)>0) {
			$duration = intval($matches[0]);
		} else {
			$duration = null;
		}
	}


	echo $name . "\n";
	echo $season . "\n";
	echo $episode . "\n";
	echo $title . "\n";
	echo $year . "\n";
	echo $duration . "\n";
	
?>