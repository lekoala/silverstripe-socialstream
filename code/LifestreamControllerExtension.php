<?php

/**
 * Description of SocialStreamControllerExtension
 *
 * @author Koala
 */
class LifestreamControllerExtension extends Extension
{

    public function onAfterInit()
    {
        $config   = SiteConfig::current_site_config();
        $services = array();

        $whitelist = explode(',',$config->LifestreamWhitelist);
        
        if ($config->TwitterUsername && (empty($whitelist) || in_array('twitter',$whitelist))) {
            $services[] = '{ service : "twitter", user: "'.$config->TwitterUsername.'" }';
        }
        if ($config->TwitterHashtag && (empty($whitelist) || in_array('twitter_hashtag',$whitelist))) {
            $services[] = '{ service : "twitter_hashtag", user: "'.$config->TwitterHashtag.'" }';
        }
        if ($config->FacebookPage && (empty($whitelist) || in_array('facebook_page',$whitelist))) {
            $services[] = '{ service : "facebook_page", user: "'.$config->FacebookPage.'" }';
        }
        if ($config->VimeoUsername && (empty($whitelist) || in_array('vimeo',$whitelist))) {
            $services[] = '{ service : "vimeo", user: "'.$config->VimeoUsername.'" }';
        }
        if ($config->YoutubeUsername && (empty($whitelist) || in_array('youtube',$whitelist))) {
            $services[] = '{ service : "youtube", user: "'.$config->YoutubeUsername.'" }';
        }

        $script = 'var lifestreamList = ['.implode(',', $services).'];$("#lifestream").lifestream({classname: "lifestream",limit: 5,list: lifestreamList});';
        Requirements::customScript($script);
        Requirements::css('socialstream/javascript/lifestream/lifestream.css');
        Requirements::javascript('socialstream/javascript/lifestream/jquery.lifestream.min.js');
        Requirements::javascript('socialstream/javascript/lifestream/jquery.lifestream.twitter_hashtag.js');
    }

    public function Lifestream()
    {
        return '<div id="lifestream"></div>';
    }
}