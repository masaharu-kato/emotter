<?php
namespace Twitter;
	require_once __DIR__.'/../twitter/oauth_required.php';
	require_once __DIR__.'/../base/urls.php';

	preg_match('@^https?://[^/]+(.*)@i', SITE_URL, $_url_matches);
	$url_dirs_until_app = $_url_matches[1];
	$url_dirs_in_app = substr(strtok($_SERVER["REQUEST_URI"], '?'), strlen($url_dirs_until_app)); 

	$params = explode('/', $url_dirs_in_app);
	if(preg_match('@.*\.php@', $params[1])) $params = array_slice($params, 1);

	if(!isset($params[1]) || $params[1] == '') {
		$ret = $twitter->query(
			'statuses/home_timeline',
			[
				'count' => 200,
				'tweet_mode' => 'extended',
			]
		);
	}
	else if($params[1] == 'search') {
		$ret = $twitter->query(
			'search/tweets',
			[
				'q' 			=> $_GET['q'] ?? '', /* str_replace(['#'], ['%23'], $_GET['q']) */
			/*	'lang' 			=> $_GET['lang'       ] ?? 'ja',
				'count' 		=> $_GET['count'      ] ?? 100,
				'result_type' 	=> $_GET['result_type'] ?? 'recent', */
				'tweet_mode' => 'extended',
			]
		);
	}
	else {
		$ret = $twitter->query(
			'statuses/user_timeline',
			[
				'screen_name' => $params[1],
				'count' => 200,
				'tweet_mode' => 'extended',
			]
		);
	}

	if(isset($ret)) {
		var_dump($ret);
		$data = json_decode($ret);
		if(isset($data->statuses)) $data = $data->statuses;

	}else{
		echo 'Response is empty.<BR>';
	}

?>

<?php require_once __DIR__.'/../common/system/header.php'; ?>
<?php require_once __DIR__.'/../tweet/out.php'; ?>

<style>
<?php
	require_once __DIR__.'/style.css'
?>
</style>

<div class="main">
	<?php if($data ?? 0): ?>
		<div class="tweets">
			<?php foreach ($data as $value) Tweet\outTweet($value) ?>
		</div>
	<?php else: ?>
		&nbsp;
		<div class="none">
			ツイートがありません。
		</div>
	<?php endif; ?>
</div>

<script>
<?php
	require_once __DIR__.'/../base/script.js';
	require_once __DIR__.'/../base/ajax.js';
	require_once __DIR__.'/../tweet/script.js';
?>
</script>

<?php require_once __DIR__.'/../common/system/footer.php'; ?>
