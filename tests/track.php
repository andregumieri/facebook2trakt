<?php
	require('../config.php');
	$url = 'http://api.trakt.tv/show/episode/seen/' . TRAKT_API_KEY;

	//open connection
	$ch = curl_init();

	$fields = array();
	$fields['username'] = TRAKT_USERNAME;
	$fields['password'] = TRAKT_PASSWORD;
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
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	print_r($result);
?>