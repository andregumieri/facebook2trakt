<?php
	define('API_KEY', '123mudar');
	$url = 'http://api.trakt.tv/show/episode/seen/' . API_KEY;


	//open connection
	$ch = curl_init();

	$fields = array();
	$fields['username'] = 'andregumieri';
	$fields['password'] = sha1('123mudar');
	$fields['title'] = 'Homeland';
	$fields['year'] = 2011;
	$fields['episodes'] = array(array(
		"season" => 1,
		"episode" => 1,
		"last_played" => "2013-09-25T03:35:50+0000"
	));


	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	print_r($result);
?>