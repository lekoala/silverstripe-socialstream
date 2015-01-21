<?php

/**
 * Description of SocialstreamController
 *
 * @author Koala
 */
class SocialstreamController extends Controller
{
    const CACHE_LIFETIME_SECONDS = 60;

    private static $allowed_actions = array(
        'search',
        'user',
    );

    /**
     *
     * @var Zend_Cache_Frontend
     */
    protected static $apiInstance;

    /**
     *
     * @var string
     */
    protected $currentQuery;

    /**
     * @var Zend_Cache_Frontend
     */
    protected static $cache;

    /**
     *
     * @return TwitterAPIExchange
     */
    public static function getTwitterApi()
    {
        if (self::$apiInstance) {
//            return self::$apiInstance;
        }
        if (!defined('TWITTER_ACCESS_TOKEN') || !defined('TWITTER_ACCESS_TOKEN_SECRET')
            || !defined('TWITTER_CONSUMER_KEY') || !defined('TWITTER_CONSUMER_SECRET')) {
            throw new Exception('You must define all Twitter api parameters as constants');
        }
        $settings          = array(
            'oauth_access_token' => TWITTER_ACCESS_TOKEN,
            'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET,
            'consumer_key' => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET
        );
        self::$apiInstance = new TwitterAPIExchange($settings);

        return self::$apiInstance;
    }

    /**
     * @return Zend_Cache_Frontend
     */
    public static function getCache()
    {
        if (!self::$cache) {
            self::$cache = SS_Cache::factory('Socialstream');
        }
        return self::$cache;
    }

    /**
     *
     * @param string $getfield
     * @param string $url
     * @param string $requestMethod
     * @return SS_HTTPResponse
     */
    protected function doTwitterRequest($getfield, $url, $requestMethod = 'GET')
    {
        $twitter = self::getTwitterApi();

        if (self::config()->enable_cache) {
            $cache        = self::getCache();
            $cache_key    = md5($getfield.i18n::get_locale().$url.$requestMethod);
            $cache_result = $cache->load($cache_key);
            if ($cache_result) {
                return $this->buildJsonResponse(unserialize($cache_result));
            }
        }

        $result = $twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        if ($result && self::config()->enable_cache) {
            $cache->save(serialize($result), $cache_key, array($this->action),
                self::CACHE_LIFETIME_SECONDS);
        }

        return $this->buildJsonResponse($result);
    }

    protected function jsonError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No errors';
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }

    /**
     *
     * @param string $response The json response
     * @return SS_HTTPResponse
     */
    protected function buildJsonResponse($response)
    {
        $resp = $this->getResponse();

        $data = json_decode($response);
        if (!$data) {
            throw new Exception($this->jsonError());
        }

        // in case of search
        if(isset($data->statuses)) {
            $data = $data->statuses;
        }

        $tweets = array();
        foreach ($data as $t) {
            $td            = new stdClass();
            $td->createdAt = strtotime($t->created_at);
            $td->id        = $t->id_str;
            $td->text      = $t->text;
            $tweets[]      = $td;
        }

        $body = array(
            'id' => $this->currentQuery,
            'tweets' => $tweets
        );

        $resp->addHeader('Content-Type', 'application/json');
        $resp->setBody(json_encode($body));

        return $resp;
    }

    /**
     * @link https://dev.twitter.com/rest/reference/get/search/tweets
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function search(SS_HTTPRequest $request)
    {
        $this->currentQuery = urldecode($request->getVar('query'));

        $url      = 'https://api.twitter.com/1.1/search/tweets.json';
        $getfield = '?q='.$this->currentQuery . '&lang=' . substr(i18n::get_locale(),0,2);

        return $this->doTwitterRequest($getfield, $url);
    }
    /**
     * @link https://dev.twitter.com/rest/reference/get/search/tweets
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function user(SS_HTTPRequest $request)
    {
        $this->currentQuery = urldecode($request->getVar('query'));

        $url      = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield = '?screen_name='.$this->currentQuery;

        return $this->doTwitterRequest($getfield, $url);
    }
}