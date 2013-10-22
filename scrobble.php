#!/usr/bin/php
<?php
	date_default_timezone_set('America/Sao_Paulo');
	
	define('PID', __DIR__.'/.pid');
	define('PID_WAIT_TIME', 30);

	/* Verifica se ja tem um processo em andamento */
	if(file_exists(PID)) {
		$timepid = file_get_contents(PID);
		$time_diff = mktime()-$timepid;

		if($time_diff<PID_WAIT_TIME) die("Já existe um processo em andamento. Tente novamente em " . (PID_WAIT_TIME-$time_diff) . " segundos.\n");
	}
	file_put_contents(PID, mktime());


	require('config.php');
	require('libs/phpQuery-onefile.php');
	$log = (defined('LOG_PATH') && trim(LOG_PATH)!='');
	if($log) {
		$log = LOG_PATH . '/f2t_' . date('Y-m-d_his') . '.txt'; 
	}

	function do_log($message) {
		global $log;
		echo $message."\n";

		if($log) {
			file_put_contents($log, $message."\n", FILE_APPEND);
		}
	}

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
		$duration = null;
		if(count(pq('div.videoImagery'))>0) { // Originals Netflix
			$name = trim(pq("#odp-body h1")->text());
			$season = trim(pq("#qdd_0 li.option-selected>a>span")->text());
			if(empty($season)) $season = 1;
			$episode = null;
			$title = null;
			$duration = null;

			foreach(pq('div.videoRow') as $video) {
				$episodeId = pq($video)->children("div.videoImagery")->attr("data-episode-id");
				if($episodeId==$id) {
					$elTitle = pq($video)->children("div.videoDetails")->children("div.title");
					$episode = trim($elTitle->children("span.episodeNumber")->text());
					$title = trim($elTitle->children("span.title-text")->text());
					$duration = pq($video)->children(".progress-details")->children(".episode-length.total-time")->children(".time-text");
					$str = 'In My Cart : 11 12 items';
					preg_match('!\d+!', $duration, $matches);
					//print_r($matches);
				}
			}	
		} else { // Comuns
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
		


		return array("url"=>$address, "novaURL"=>$novaURL, "name"=>$name, "title"=>$title, "season"=>intval($season), "episode"=>intval($episode), "year"=>$year, "duration"=>$duration);
	}


	function pegaVideo($url) {
		$videos = file_get_contents($url);
		return json_decode($videos);
	}


	do_log("Iniciado - " . date("d/m/Y H:i:s"));


	// Faz a troca do token
	if(!file_exists(__DIR__.'/.db_token')) die("Autentique-se em " . HTTP_ROOT . '/autenticar.php');
	file_get_contents(HTTP_ROOT . '/autenticar.php?fb_exchange_token='.file_get_contents(__DIR__.'/.db_token'));
	define('FACEBOOK_TOKEN', file_get_contents(__DIR__.'/.db_token'));


	// Pega os primeiros videos
	$videos = pegaVideo('https://graph.facebook.com/me/video.watches?access_token=' . FACEBOOK_TOKEN);


	// Pega o último ID encontrado
	$lastId = 0;
	if(!file_exists(__DIR__.'/.db_fb_lastid') || !$lastId = intval(file_get_contents(__DIR__."/.db_fb_lastid"))) $lastId = 0;

	// Array dos registros do Facebook
	$fbData = array();


	// Passa por todos os registros
	$primeiroID = true;
	$forceStop = false;
	while(count($videos->data)>0 && isset($videos->paging->next) && $forceStop==false) {
		foreach($videos->data as $video) {
			if($forceStop) continue;
			if($video->id<=$lastId) { $forceStop = true; continue; }

			if(isset($video->application->namespace) && $video->application->namespace=='netflix_social' && isset($video->data->episode)) {
				//echo $video->id . "\n";
				if($primeiroID) file_put_contents(__DIR__.'/.db_fb_lastid', $video->id);
				$primeiroID = false;

				$fbData[] = array("id"=>$video->id, "url"=>$video->data->episode->url, "titulo"=>$video->data->episode->title, "last_played"=>$video->publish_time, "movieinfo"=>null, "scrobbled"=>false);
			}
		}	
		if(!$forceStop) $videos = pegaVideo($videos->paging->next);
	}

	// Converte para o formato que sera recuperado do json
	$fbData = json_decode(json_encode($fbData));
	
	// Verifica se já existe um arquivo de cache
	if(file_exists(__DIR__.'/.db_fb_cache')) {
		$merge_cache = array();
		$fb_cache = json_decode(file_get_contents(__DIR__.'/.db_fb_cache'));
		if(is_object($fb_cache)) $fb_cache = get_object_vars($fb_cache);
		if(is_array($fb_cache)) {
			foreach($fb_cache as $item) {
				$merge_cache[] = $item;
			}
		}
		
		if(!is_array($fb_cache)) $fb_cache = array();
		$fbData = array_merge($fbData, $merge_cache);
	}


	// Pega os dados do filme
	$scrobble = array();
	foreach($fbData as $idx=>&$video) {
		if(is_null($video->movieinfo)) {
			$video->movieinfo = json_decode(json_encode(pegaURLDoNetflix($video->url)));
		}
	
		$movieKey = md5($video->movieinfo->name);
		if(!isset($scrobble[$movieKey])) $scrobble[$movieKey] = array();
		$scrobble[$movieKey][] = array("idx"=>$idx, "info"=>$video->movieinfo, "last_played"=>$video->last_played);
	}



	// Marca como visto no trakt
	foreach($scrobble as $episodes) {
		if(empty($episodes[0]['info']->name) || intval($episodes[0]['info']->year)==0) continue;

		$arrEpisodes = array();
		foreach($episodes as $episode) {
			$episodeNum = intval($episode['info']->episode);
			$season = intval($episode['info']->season);
			$duration = intval($episode['info']->duration);
			if($episodeNum<1 || $season<1) continue;

			$arrEpisodes = array("season"=>$season, "episode"=>$episodeNum, "last_played"=>$episode['last_played']);


			$ch = curl_init();

			// SCROBBLE
			/*$url = 'http://api.trakt.tv/show/scrobble/' . TRAKT_API_KEY;
			$fields = array();
			$fields['username'] = TRAKT_USERNAME;
			$fields['password'] = TRAKT_PASSWORD;
			$fields['title'] = $episodes[0]['info']->name;
			$fields['year'] = $episodes[0]['info']->year;
			$fields['season'] = $season;
			$fields['episode'] = $episodeNum;
			$fields['duration'] = $duration;
			$fields['progress'] = 100;
			$fields['plugin_version'] = '0.1';
			$fields['media_center_version'] = '0.1';
			$fields['media_center_version'] = 'Oct 21 2013';*/

			// SEEN
			$url = 'http://api.trakt.tv/show/episode/seen/' . TRAKT_API_KEY;
			$fields = array();
			$fields['username'] = TRAKT_USERNAME;
			$fields['password'] = TRAKT_PASSWORD;
			$fields['title'] = $episodes[0]['info']->name;
			$fields['year'] = $episodes[0]['info']->year;
			$fields['episodes'] = $arrEpisodes;

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

			//execute post
			$result = curl_exec($ch);
			$result = json_decode($result);
			curl_close($ch);

			if(!$result->status || $result->status!='success') {
				do_log("ERRO - " . $episode['info']->url);
				do_log(print_r($url, true));
				do_log(print_r($result, true));
				do_log(print_r($fiedls, true));
				do_log('----');
				continue;
			}
		}

		if(empty($arrEpisodes)) continue;

		foreach($episodes as $episode) {
			unset($fbData[$episode['idx']]);
		}
		do_log("Scrobbled " . $episodes[0]['info']->name);
	}
	

	// Grava o cache modificado
	file_put_contents(__DIR__."/.db_fb_cache", json_encode($fbData));


	do_log("Finalizado - " . date("d/m/Y H:i:s"));

	unlink(PID);
?>
