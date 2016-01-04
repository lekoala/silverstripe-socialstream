<?php

/**
 * Description of SocialstreamController
 *
 * @author Koala
 */
class SocialstreamController extends Controller
{
    const CACHE_LIFETIME_SECONDS = 120;

    private static $allowed_actions = array(
        'twitter_search',
        'twitter_user',
        'facebook_page',
    );

    /**
     *
     * @var TwitterAPIExchange
     */
    protected static $twitterApiInstance;

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
        if (self::$twitterApiInstance) {
            return self::$twitterApiInstance;
        }
        if (!defined('ST_TWITTER_ACCESS_TOKEN') || !defined('ST_TWITTER_ACCESS_TOKEN_SECRET')
            || !defined('ST_TWITTER_CONSUMER_KEY') || !defined('ST_TWITTER_CONSUMER_SECRET')) {
            throw new Exception('You must define all Twitter api parameters as constants');
        }
        $settings                 = array(
            'oauth_access_token' => ST_TWITTER_ACCESS_TOKEN,
            'oauth_access_token_secret' => ST_TWITTER_ACCESS_TOKEN_SECRET,
            'consumer_key' => ST_TWITTER_CONSUMER_KEY,
            'consumer_secret' => ST_TWITTER_CONSUMER_SECRET
        );
        self::$twitterApiInstance = new TwitterAPIExchange($settings);

        return self::$twitterApiInstance;
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
                return $this->buildTwitterJsonResponse(unserialize($cache_result),
                        true);
            }
        }

        $result = $twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        $response = $this->buildTwitterJsonResponse($result);

        if ($result && self::config()->enable_cache) {
            $cache->save(serialize($result), $cache_key, array($this->action),
                self::CACHE_LIFETIME_SECONDS);
        }

        return $response;
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
     * @param bool $cache
     * @return SS_HTTPResponse
     */
    protected function buildTwitterJsonResponse($response, $cache = false)
    {
        $resp = $this->getResponse();

        $data = json_decode($response);
        if (!$data) {
            throw new Exception($this->jsonError());
        }

        // in case of search
        if (isset($data->statuses)) {
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
            'cache' => $cache,
            'tweets' => $tweets,
        );

        $resp->addHeader('Content-Type', 'application/json');
        $resp->setBody(json_encode($body));

        return $resp;
    }

    /**
     *
     * @param string $app_id
     * @param string $app_secret
     * @return string
     */
    protected static function getFacebookAppToken($app_id, $app_secret)
    {
        $url          = 'https://graph.facebook.com/oauth/access_token';
        $token_params = array(
            "type" => "client_cred",
            "client_id" => $app_id,
            "client_secret" => $app_secret
        );

        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            http_build_query($token_params, null, '&'));
        $ret = curl_exec($ch);
        curl_close($ch);
        return str_replace('access_token=', '', $ret);
    }

    /**
     *
     *
     * @param string $response The json response
     * @param bool $cache
     * @return SS_HTTPResponse
     */
    protected function buildFacebookJsonResponse($response, $cache = false)
    {
        $resp = $this->getResponse();

        $data = json_decode($response);
        if (!$data) {
            throw new Exception($this->jsonError());
        }

        if (isset($data->error)) {
            throw new Exception($data->error->message);
        }

        $posts = array();
        foreach ($data->data as $post) {
            $td            = new stdClass();
            $td->createdAt = strtotime($post->created_time);
            $td->id        = $post->id;
            if (isset($post->story)) {
                $td->text = $post->story;
            } elseif (isset($post->message)) {
                $td->text = $post->message;
            }
            $td->type = $post->type;

            $posts[] = $td;
        }

        $body = array(
            'id' => $this->currentQuery,
            'cache' => $cache,
            'posts' => $posts,
        );

        $resp->addHeader('Content-Type', 'application/json');
        $resp->setBody(json_encode($body));

        return $resp;
    }

    protected function getQueryFromRequest($request)
    {
        $this->currentQuery = filter_var(urldecode($request->getVar('query')),
            FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * @link https://dev.twitter.com/rest/reference/get/search/tweets
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function twitter_search(SS_HTTPRequest $request)
    {
        $this->getQueryFromRequest($request);

        $url      = 'https://api.twitter.com/1.1/search/tweets.json';
        $getfield = '?q='.$this->currentQuery.'&lang='.substr(i18n::get_locale(),
                0, 2);

        return $this->doTwitterRequest($getfield, $url);
    }

    /**
     * @link https://dev.twitter.com/rest/reference/get/search/tweets
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function twitter_user(SS_HTTPRequest $request)
    {
        $this->getQueryFromRequest($request);

        $url      = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield = '?screen_name='.$this->currentQuery;

        return $this->doTwitterRequest($getfield, $url);
    }

    /**
     *
     * @param string $url
     * @return SS_HTTPResponse
     */
    protected function doFacebookRequest($url)
    {
        if (!defined('ST_FACEBOOK_APP_ID') || !defined('ST_FACEBOOK_APP_SECRET')) {
            throw new Exception('You must define all Facebook api parameters as constants');
        }

        $token = self::getFacebookAppToken(ST_FACEBOOK_APP_ID,
                ST_FACEBOOK_APP_SECRET);

        if (self::config()->enable_cache) {
            $cache        = self::getCache();
            $cache_key    = md5('facebook'.i18n::get_locale().$url);
            $cache_result = $cache->load($cache_key);
            if ($cache_result) {
                return $this->buildFacebookJsonResponse(unserialize($cache_result),
                        true);
            }
        }

        $url    = 'https://graph.facebook.com/v2.2/'.$url.'?access_token='.$token.'&locale='.i18n::get_locale().'&format=json&method=get&pretty=0&suppress_http_code=1';
        $result = file_get_contents($url);

        $response = $this->buildFacebookJsonResponse($result);

        if ($result && self::config()->enable_cache) {
            $cache->save(serialize($result), $cache_key, array($this->action),
                self::CACHE_LIFETIME_SECONDS);
        }

        return $response;
    }

    /**
     * @link https://developers.facebook.com/docs/graph-api/quickstart/v2.2
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function facebook_page(SS_HTTPRequest $request)
    {
        $this->getQueryFromRequest($request);

        $url = $this->currentQuery.'/posts';

        return $this->doFacebookRequest($url);
    }
}
