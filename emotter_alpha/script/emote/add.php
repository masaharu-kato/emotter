<?php
namespace Twitter;
	require_once __DIR__.'/../base/connect.php';
	require_once __DIR__.'/../base/ajax_entry.php';
	require_once __DIR__.'/../twitter/oauth_required.php';

	if(!isset($_POST['tweet_id'], $_POST['code'])) die('Params not set.');

	$tweet_id = intval($_POST['tweet_id']);
	$code     = intval($_POST['code']);
	$user_id  = $twitter->getUserID();

	if(!$st = $pdo->query("SELECT datetime FROM [[table_name]] WHERE [[column_01]] = $tweet_id AND [[column_02]] = $user_id AND [[column_03]] = $code")) die('SQL Error(1)');
	
	if($st->fetch()) {
		if(!$pdo->query("DELETE FROM [[table_name]] WHERE [[column_01]] = $tweet_id AND [[column_02]] = $user_id AND [[column_03]] = $code")) die('SQL Error(2)');
		die('Deleted');
	}else{
		$datetime = (new \DateTime)->format('YmdHis');
		if(!$pdo->query("INSERT INTO [[table_name]] VALUES($tweet_id, $user_id, $code, $datetime)")) die('SQL Error(3)');
		die('Added');
	}

	echo 'OK';
?>