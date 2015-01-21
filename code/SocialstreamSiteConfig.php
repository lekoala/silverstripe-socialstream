<?php

class SocialstreamSiteConfig extends DataExtension
{
    private static $db = array(
        'TwitterUsername' => 'Varchar(255)',
        'TwitterHashtag' => 'Varchar(255)',
        'FacebookPage' => 'Varchar(255)',
        'VimeoUsername' => 'Varchar(255)',
        'YoutubeUsername' => 'Varchar(255)',
        'LifestreamWhitelist' => 'Varchar(255)'
    );

    public function listAvailableMediaServices()
    {
        return array(
            'bitly' => array(),
            'blogger' => array(),
            'citeulike' => array(),
            'dailymotion' => array(),
            'delicious' => array(),
            'deviantart' => array('template' => array('deviationpost' => '')),
            'dribbble' => array(),
            'facebook_page' => array(),
            'flickr' => array(),
            'formspring' => array(),
            'forrst' => array(),
            'github' => array(),
            'gimmebar' => array(),
            'googleplus' => array('key'),
            'instapaper' => array(),
            'iusethis' => array(),
            'lastfm' => array(),
            'librarything' => array(),
            'miso' => array(),
            'mlkshk' => array(),
            'pinboard' => array(),
            'posterous' => array(),
            'reddit' => array(),
            'rss' => array(),
            'slideshare' => array(),
            'snipplr' => array(),
            'stackoverflow' => array(),
            'tumblr' => array(),
            'twitter' => array(),
            'vimeo' => array(),
            'wikipedia' => array('language'),
            'wordpress' => array(),
            'youtube' => array(),
            'zotero' => array(),
            // custom services
            'twitter_hashtag' => array(),
        );
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Social', new TextField('TwitterUsername'));
        $fields->addFieldToTab('Root.Social', new TextField('TwitterHashtag'));
        $fields->addFieldToTab('Root.Social', new TextField('FacebookPage'));
        $fields->addFieldToTab('Root.Social', new TextField('VimeoUsername'));
        $fields->addFieldToTab('Root.Social', new TextField('YoutubeUsername'));

        $services = array_keys(self::listAvailableMediaServices());
        $fields->addFieldToTab('Root.Social', $wl = new ListboxField('LifestreamWhitelist', 'Lifestream Whitelist', array_combine($services,$services)));
        $wl->setMultiple(true);
        $wl->setDescription('A list of services to display on the Lifestream. If left blank, all configured streams will be used.');
        return $fields;
    }

    public function TwitterLink()
    {
        return 'https://twitter.com/'.$this->owner->TwitterUsername;
    }

    public function FacebookLink()
    {
        return 'https://www.facebook.com/'.$this->owner->FacebookPage;
    }
}