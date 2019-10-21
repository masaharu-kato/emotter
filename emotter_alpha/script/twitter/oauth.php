<?php
namespace Twitter;
require_once __DIR__.'/../base/functions.php';
require_once __DIR__.'/../base/urls.php';
require_once __DIR__.'/../base/consumer_keys.php';

function urlenc($str) {
	return rawurlencode($str);
}

class OAuth {

	const CONSUMER_KEY = OAUTH_CONSUMER_KEY;
	const CONSUMER_SECRET = OAUTH_CONSUMER_SECRET;

	const API_HOST = 'https://api.twitter.com';
	const API_VERSION_URL = '1.1';
	const API_URL_HEAD = self::API_HOST.'/'.self::API_VERSION_URL.'/';
	const API_URL_FOOT = '.json';

	const REQUEST_TOKEN_URL = self::API_HOST.'/oauth/request_token';
	const ACCESS_TOKEN_URL  = self::API_HOST.'/oauth/access_token';

	const API_SIGNATURE_METHOD = 'HMAC-SHA1';
	const PHP_SIGNATURE_METHOD = 'sha1';
	const API_VERSION = '1.0';

	const CALLBACK_URL = SITE_URL.'/request_token.php';

	private $url = '';		//	アクセスするAPIのアドレス
	private $params = [];	//	認証に用いるパラメータ
	private $token  = '';		//	認証に用いるトークン
	private $method = '';	//	GETまたはPOST
	private $options = [];	//	GET取得時に用いるパラメータ

	private function setURL($val) { return $this->url = $val; }
	private function setToken($val) { return $this->token = $val; }
	private function setMethodPOST() { return $this->method = 'POST'; }
	private function setMethodGET () { return $this->method = 'GET';  }
	private function setOptions($val) { return $this->options = $val; } 

//	パラメータの値をURLエンコードし、ソートして整えたものを返す
	private function organizeParams() {
		foreach ($this->params  as $key => $value) $this->params[$key] = $key != 'oauth_callback' ? urlenc($value) : $value;
		foreach ($this->options as $key => $value) $this->params[$key] = $value;
		ksort($this->params);
		return $this->params;
	}

//	パラメータのテキストを取得
	private function getParamsText() {
		return urlenc(
			str_replace(
				['+'  , '%7E'],
				['%20', '~'  ],
				http_build_query($this->organizeParams(),'','&')
			)
		);
	}

//	認証情報から署名を取得する
	private function getSignature() {
		return base64_encode(
			hash_hmac(self::PHP_SIGNATURE_METHOD,
				$this->method.'&'.urlenc($this->url).'&'.$this->getParamsText(),
				urlenc(self::CONSUMER_SECRET).'&'.urlenc($this->token),
				true
			)
		);
	}

//	認証情報に署名を追加したものを返す
	private function getParamsWithSignature() {
		$this->params['oauth_signature'] = $this->getSignature();
		return $this->params;
	}

//	APIへ送信する際のコンテキストを返す
	private function getResponseContext() {
		return [
			'http' => [
				'method' => $this->method ,
				'header' => ['Authorization: OAuth '.http_build_query(self::getParamsWithSignature(), '', ',')]
			]
		];
	}

//	optionsに値が入っていれば、それを付加したURLを返す
	private function getURLWithOptions() {
		return $this->options ? $this->url.'?'.http_build_query($this->options) : $this->url;
	}

//	APIに認証情報を送信し結果を得る
	private function getResponse_Raw() {
		return file_get_contents(
			$this->getURLWithOptions(), 
			false,
			stream_context_create($this->getResponseContext())
		);
	}

	private function getResponse() {
		try {
		//	例外をバッファに読み取る
		    ob_start();
		    $ret = $this->getResponse_Raw();
		    $warning = ob_get_contents();
		    ob_end_clean();

		//	エラー内容があれば例外とする
		    if ($warning) throw new \Exception($warning);

		    return $ret;
		} catch (\Exception $e) {
			echo "Warnings in getResponse():<BR>\n";
			echo $e;
			return null;
		}
	}


//	APIに認証情報を送信し、得た結果を配列変数に直したものを返す
//	$this->url リクエストURL(TWITTER_AUTH_REQUEST_TOKEN_URL or TWITTER_AUTH_ACCESS_TOKEN_URL)
//	$this->params 認証情報の入った連想配列
//	$this->token  認証に用いるトークン
	private function getResponseAsArray() {
		$query = [] ;
		parse_str( $this->getResponse(), $query) ;
		return $query;
	}

//	基本的な認証情報を配列に追加する
	private function addBasicParams($p) {
		$p['oauth_consumer_key' 	] = self::CONSUMER_KEY;
		$p['oauth_signature_method' ] = self::API_SIGNATURE_METHOD;
		$p['oauth_version' 			] = self::API_VERSION;
		$p['oauth_timestamp' 		] = time();
		$p['oauth_nonce' 			] = microtime();
		return $p;
	}

	private function setParams($p) {
		return $this->params = $this->addBasicParams($p);
	}


//	APIへ認証を要求する
	private function getAuthRequestQuery() {
		$this->setURL(self::REQUEST_TOKEN_URL);
		$this->setParams(['oauth_callback' => self::CALLBACK_URL]);
		$this->setToken('');
		$this->setMethodPOST();
		
		return $this->getResponseAsArray();
	}

//	認証を行った後、APIへユーザーへのアクセスを要求する
	private function getAuthAccessQuery() {
		
		$this->setURL(self::ACCESS_TOKEN_URL);
		$this->setParams([
			'oauth_token' 		=> $_GET['oauth_token'   ],
			'oauth_verifier' 	=> $_GET['oauth_verifier'],
		]);
		$this->setToken($_SESSION['twitter_oauth_request_token_secret']);
		$this->setMethodPOST();

		return $this->getResponseAsArray();
	}


//	TwitterAPIの認証ページへ飛ばす
//	$f_first_only : true:初回のみ認証する false:毎回認証する
	public function jumpToAuthPage($f_first_only) {

	//	コールバックURLを指定して、APIへ認証を要求する
		$query = $this->getAuthRequestQuery();
		if(!($query['oauth_token'] ?? '')) throw \Exception('Failed to generate oauth token.');

	//	セッションを開始し、トークンを記憶する
		session_regenerate_id(true);
		$_SESSION['twitter_oauth_request_token_secret'] = $query['oauth_token_secret'] ;

	//	認証後、元のページに戻るため、そのURLを記録しておく
		$_SESSION['original_url'] = \getCurrentURL();


	//	認証画面へ飛ばす
		header('Location: '.self::API_HOST.'/oauth/'
			.(!$f_first_only ? 'authorize' : 'authenticate')
			.'?oauth_token='.$query['oauth_token']
		);

	}


	public function setAuthInfoToSession() {

		$query = $this->getAuthAccessQuery();

		if (!file_exists(__DIR__.'/tokens')) mkdir(__DIR__.'/tokens');
		$path = __DIR__.'/tokens/'.$query['user_id'].'.json';
		file_put_contents($path, json_encode($query));

		$_SESSION['twitter_oauth_access_token'       ] = $query['oauth_token'       ];
		$_SESSION['twitter_oauth_access_token_secret'] = $query['oauth_token_secret'];
		$_SESSION['twitter_user_id'    ] =  $query['user_id'    ];
		$_SESSION['twitter_screen_name'] =  $query['screen_name'];

	//	サイト内の元のページに戻る
		header('Location: '.($_SESSION['original_url'] ?? '/'));
	}

//	API名をもとにアクセスURLを取得する
	private function getAPIURL($api_name) {
		return self::API_URL_HEAD.$api_name.self::API_URL_FOOT;
	}

//	認証を行った後、任意のクエリを実行する
	public function query($api_name, $options) {

		$this->setURL($this->getAPIURL($api_name));
		$this->setParams(['oauth_token' => $_SESSION['twitter_oauth_access_token']]);
		$this->setToken($_SESSION['twitter_oauth_access_token_secret']);
		$this->setMethodGET();
		$this->setOptions($options);
		
		return $this->getResponse();
	}

	public function queryAsJSON($api_name, $options) {
		return json_decode($this->query($api_name, $option));
	}

	public function getUserID() {
		return intval($_SESSION['twitter_user_id'] ?? 0);
	}

	public function getUserScreenName() {
		return $_SESSION['twitter_screen_name'] ?? null;
	}
	
	public function __construct() {

	//	セッションを開始
		session_start();

	//	もし既にTwitterで認証済みなら、何もせず戻る
		if (isset($_SESSION['twitter_oauth_access_token']) && isset($_SESSION['twitter_oauth_access_token_secret'])) return;

	//	もし認証ページから戻ってきて、許可されていれば
		if (isset($_GET['oauth_token']) || isset($_GET['oauth_verifier'])) return $this->setAuthInfoToSession();
		
	//	もし認証ページから戻ってきて、拒否されていれば
		if (isset($_GET['denied'])) {
			die('連携が拒否されました。');
		}
		
	//	まだ何もされていない場合
		$this->jumpToAuthPage(true);
	}

};

?>