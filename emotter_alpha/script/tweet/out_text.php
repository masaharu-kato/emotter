<?php
namespace Twitter\Tweet;
	require_once __DIR__.'/../base/urls.php';

	function getIndicies($value) {
		$ret = [];
		if(isset($value->entities)) {
			$entities = $value->entities;
			foreach ($entities as $entity_type => $typed_entities) {
				foreach ($typed_entities as $entity){
					$ret[$entity->indices[0]] = $entity;
					$ret[$entity->indices[0]]->entity_type = $entity_type;
				}
			}
		}
		ksort($ret);
		return $ret;
	}

	function getEntityText($entity) {
		$baseurl = SITE_DIR;

		switch($entity->entity_type) {

		case 'hashtags':
			$ctext = $entity->text;
			$curl = $baseurl.'/search?q=%23'.$ctext;
			return "<span class=\"link hashtag\" onclick=\"jump('$curl')\">#$ctext</span>";

		case 'symbols':
			$ctext = $entity->text;
			$curl = $baseurl.'/search?q=$'.$ctext;
			return "<span class=\"link symbols\" onclick=\"jump('$curl')\">$$ctext</span>";

		case 'user_mentions':
			$ctext = $entity->screen_name;
			$curl = $baseurl.'/'.$ctext;
			return "<span class=\"link user_mentions\" onclick=\"jump('$curl')\">@$ctext</span>";

		case 'urls':
			$ctext = $entity->display_url;
			$curl = $entity->expanded_url;
			return "<span class=\"link urls\" onclick=\"new_tab_open('$curl')\">$ctext</span>";

		case 'media':
			return '';

		}

		return '';
	}

	function getNormalText($text) {
		return nl2br($text);
	}


	function outTweetText($value) {
		$ret = '';
		$i_prev = 0;
		foreach (getIndicies($value) as $i_begin => $entity) {
		//	このエンティティに至るまで通常のテキストを出力
			$ret .= mb_substr($value->full_text, $i_prev, $i_begin - $i_prev);
			$ret .= getEntityText($entity);
			$i_prev = $entity->indices[1] + 1;
		}
		$ret .= getNormalText(mb_substr($value->full_text, $i_prev));
		return $ret;
	}

?>