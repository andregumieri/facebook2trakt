<?php
	require('config.php');
	define('APP_ID', '408872322546361');
	define('APP_SECRET', '32dd196dee6f717f802cf336c53a1213');
	if(isset($_GET['access_token'])) {
		file_put_contents(".db_token", $_GET['access_token']);
		echo "Token Gravado";
	

	} elseif(isset($_GET['fb_exchange_token'])) {
		if($query = file_get_contents('https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=' . APP_ID . '&client_secret=' . APP_SECRET . '&redirect_uri=' . HTTP_ROOT . '/autenticar.php&fb_exchange_token='.$_GET['fb_exchange_token'])) {
			header("location: autenticar.php?" . $query);
		} else {
			echo "Erro ao trocar o token";
		}
	

	} elseif(!isset($_GET['code'])) {
		$url = 'https://www.facebook.com/dialog/oauth?client_id=' . APP_ID . '&redirect_uri=' . HTTP_ROOT . '/autenticar.php&scope=user_actions.video';
		header("location:{$url}");
	

	} else {
		if($query = file_get_contents('https://graph.facebook.com/oauth/access_token?client_id=' . APP_ID . '&client_secret=' . APP_SECRET . '&code=' . $_GET['code'] . '&redirect_uri=' . HTTP_ROOT . '/autenticar.php')) {
			header("location: autenticar.php?" . $query);
		} else {
			echo "Erro ao pegar um token";
		}
	}
?>