
function emoji_click(self) {
	return emoji_submit(self, {
		tweet_id:self.parentNode.dataset.tweetid,
		code:self.dataset.emojicode,
	});
}

function emoji_submit(self, params) {
	return loadByAjax('add_emote.php', 'POST', params, function(data){
	//	削除した
		let dd;
		switch(data) {
		case 'Deleted':
			self.classList.remove('done');
			dd = self.getElementsByTagName('dd')[0];
			dd.innerHTML--;
			return;

		case 'Added':
			self.classList.add('done');
			dd = self.getElementsByTagName('dd')[0];
			dd.innerHTML++;
			return;
		}

		alert('Error: ' + data);
		return;
	});
}

function jump(url) {
	location.href = url;
}

function tweet_image_switch(self) {
	let elm_tweet = self.parentNode.parentNode;
	let elm_media = elm_tweet.getElementsByClassName('media')[0];
	switch_class(elm_media, 'visible');
}

function new_tab_open(url) {
	window.open(url);
}

function original_twitter_open(tweet_id) {
	new_tab_open('https://twitter.com/-/status/' + tweet_id);
}
