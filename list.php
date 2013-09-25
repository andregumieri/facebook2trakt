<?php
	require('phpQuery-onefile.php');
	define('TOKEN', 'FACEBOOK_TOKEN');

	function pegaURLDoNetflix($address) {
		$novaURL = null;
		$html = file_get_contents($address);
		phpQuery::newDocumentHTML($html);

		foreach(pq('html>head>meta') as $o) {
			if(pq($o)->attr('property')=='redirect') {
				$url = explode(';', pq($o)->attr('content'));
				$url = $url[1];
				$url = str_replace("url=", "", $url);
				$url = trim($url);
				$url = parse_url($url);
				$query = $url['query'];
				$query = str_replace("&amp;", "___amp;", $query);
				$query = explode("&", $query);

				// Pega o ID do filme
				$filme_id = explode('%2F', $query[1]);
				$filme_id = $filme_id[count($filme_id)-1];

				$movie_id_query = $query[0];
				$episode_id_query = $query[1];

				$novaURL = "http://movies.netflix.com/WiMovie/{$filme_id}";

				
			}
		}

		$html = file_get_contents($novaURL);
		phpQuery::newDocumentHTML($html);
		$name = null;
		$season = null;
		$episode = null;
		$title = null;
		if(count(pq('div.videoImagery'))>0) {
			$name = trim(pq("#odp-body h1")->text());
			$season = trim(pq("#qdd_0 li.option-selected>a>span")->text());
			if(empty($season)) $season = 1;
			$episode = null;
			$title = null;

			foreach(pq('div.videoRow') as $video) {
				$episodeId = pq($video)->children("div.videoImagery")->attr("data-episode-id");
				if($episodeId==$id) {
					$elTitle = pq($video)->children("div.videoDetails")->children("div.title");
					$episode = trim($elTitle->children("span.episodeNumber")->text());
					$title = trim($elTitle->children("span.title-text")->text());
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
		}
		


		return array("url"=>$address, "novaURL"=>$novaURL, "name"=>$name, "title"=>$title, "season"=>intval($season), "episode"=>intval($episode), "year"=>$year);
	}


	function pegaVideo($url) {
		$videos = file_get_contents($url);
		return json_decode($videos);
	}

	$videos = pegaVideo('https://graph.facebook.com/me/video.watches?access_token=' . TOKEN);




	while(count($videos->data)>0 && isset($videos->paging->next)) {
		foreach($videos->data as $video) {
			if(isset($video->application->namespace) && $video->application->namespace=='netflix_social' && isset($video->data->episode)) {
				echo $video->data->episode->title . "\n";
				$serieData = pegaURLDoNetflix($video->data->episode->url);
				$serieData['last_played'] = $video->publish_time;
				print_r($serieData);
			}
		}	
		$videos = pegaVideo($videos->paging->next);
	}
	

	// Troca Token
	// https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={APP_ID}&client_secret={APP_SECRET}&redirect_uri=http://localhost:8888/f2t/get.php&fb_exchange_token={Token Anterior}
?>