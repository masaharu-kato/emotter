<?php
namespace Twitter\Tweet;

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
		switch($entity->entity_type) {
		case 'hashtags':
			return "<span class=\"link hashtag\"
						onclick=\"jump('/search?q=%23{$entity->text}')\">#{$entity->text}</span>";
		case 'symbols':
			return "<span class=\"link symbols\"
						onclick=\"jump('/search?q=${$entity->text}')\">${$entity->text}</span>";
		case 'user_mentions':
			return "<span class=\"link user_mentions\"
						onclick=\"jump('/{$entity->screen_name}')\">@{$entity->screen_name}</span>";
		case 'urls':
			return "<span class=\"link urls\"
						onclick=\"new_tab_open('{$entity->expanded_url}')\">{$entity->display_url}</span>";
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