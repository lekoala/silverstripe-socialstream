<?php

/**
 * Description of SocialstreamController
 *
 * @author Koala
 */
class SocialstreamController extends Controller
{
    private static $allowed_actions = array(
        'search',
    );
    protected static $_apiInstance;

    /**
     *
     * @return TwitterAPIExchange
     */
    public static function getTwitterApi()
    {
        if (self::$_apiInstance) {
            return self::$_apiInstance;
        }
        self::$_apiInstance = new TwitterAPIExchange($settings);
        return self::$_apiInstance;
    }

    /**
     *
     * @param string $getfield
     * @param string $url
     * @param string $requestMethod
     * @return SS_HTTPResponse
     */
    public static function doTwitterRequest($getfield, $url,
                                            $requestMethod = 'GET')
    {
        $twitter  = self::getTwitterApi();
        $response = $twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        return $this->buildJsonResponse($response);
    }

    /**
     *
     * @param type $response
     * @return SS_HTTPResponse
     */
    protected function buildJsonResponse($response)
    {
        $resp = $this->getResponse();

        var_dump(json_decode($response));

        return $resp;
    }

    /**
     * @link https://dev.twitter.com/rest/reference/get/search/tweets
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function search(SS_HTTPRequest $request)
    {
        $url           = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield      = '?q='.urldecode($request->getVar('query'));

        return $this->doTwitterRequest($getfield, $url);
    }
}