<?php
namespace Twitter\Tweet;
	require_once __DIR__.'/out_text.php';
	require_once __DIR__.'/../base/connect.php';
	require_once __DIR__.'/../twitter/oauth_required.php';
	require_once __DIR__.'/../emote/emojies.php';

	$default_time_zone = new \DateTimeZone('Asia/Tokyo');

	function outTweet($value) {
		if(isset($value->retweeted_status)) return _outTweet($value->retweeted_status, $value);
		return _outTweet($value, null);
	}

	function outDateTime($value) {
		global $default_time_zone;
		return (new \DateTime($value->created_at))->setTimeZone($default_time_zone)->format('Y/m/d H:i:s');
	}

	function getEmoteNumbers($tweet_id) {
		global $pdo;
		$st = $pdo->query("SELECT * FROM emotes WHERE tweet_id = $tweet_id");

		$ret = [];
		while($row = $st->fetch()) $ret[intval($row['code'])][intval($row['user_id'])] = $row['datetime'];

		return $ret;
	}

	function _outTweet($value, $origin) {
		global $emojies, $twitter;
		$emotes = getEmoteNumbers($value->id);
?>
		<div class="<?=$origin ? 'rt' : ''?>">

			<?php if($origin): ?>
				<div class="date" onclick="original_twitter_open('<?=$origin->id?>')"><?=outDateTime($origin)?></div>
				<div class="rt-user">
					<span class="name" onclick="jump('/<?=$origin->user->screen_name?>')">
						<i class="fas fa-retweet"></i>
						<?=$origin->user->name?>
						がRT
					</span>
				</div>
				<div class="float-clear"></div>
			<?php endif ?>

			<div class="head">
				<div class="date" onclick="original_twitter_open('<?=$value->id?>')"><?=outDateTime($value)?></div>
				<div class="user" onclick="jump('/<?=$value->user->screen_name?>')">
					<img src="<?=$value->user->profile_image_url_https?>" />
					<span class="name"><?=$value->user->name?></span>
					<span class="id">
						@<?=$value->user->screen_name?>
					</span>
				</div>
				<div class="float-clear"></div>
			</div>

			<div class="body">
				<?php if($value->extended_entities->media ?? 0): ?>
					<i class="far fa-image" onclick="tweet_image_switch(this)"></i>
				<?php endif ?>
				<span class="text"><?=outTweetText($value)?></span>
			</div>

			<div class="media">
				<?php if($value->extended_entities->media ?? 0): ?>
					<?php foreach($value->extended_entities->media as $m): ?>
						<img src="<?=$m->media_url_https?>" style="
							width:<?=$m->sizes->small->w?>px;height:<?=$m->sizes->small->h?>px;
						"/>
					<?php endforeach ?>
				<?php endif ?>
			</div>

			<div data-tweetid="<?=$value->id?>" class="emotes">
			<?php foreach($emojies as $key => $value): ?><!--
			 --><dl data-emojicode="<?=$key?>" onclick="emoji_click(this)"
					class="
						<?php if(isset($emotes[$key][$twitter->getUserID()])): ?>done<?php endif ?>
					"
				>
					<dt><?=$value?></dt><!--
				 --><dd><?=count($emotes[$key] ?? [])?></dd>
				</dl><!--
		 --><?php endforeach ?>
			</div>

		</div>
<?php
	}

?>