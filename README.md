Silverstripe Socialstream module
==================
This module adds social stream, sharing and links capabilities to your website.

Lifestream
==================
Configure your social media links through the SiteConfig (currently limited to a restricted list)
and apply extension to your Controller

Socialstream extends the Lifestream jquery plugin with custom extensions to handle
Twitter et Facebook in a uniform fashion. To use theses, you will need to define
in your ss_environment.php the following constants

	/* Socialstream Twitter */
	define('ST_TWITTER_CONSUMER_KEY', 'my_consumer_key');
	define('ST_TWITTER_CONSUMER_SECRET', 'my_consumer_secret');
	define('ST_TWITTER_ACCESS_TOKEN', 'my_access_token');
	define('ST_TWITTER_ACCESS_TOKEN_SECRET', 'my_access_token_secret');

	/* Socialstream Facebook */
	define('ST_FACEBOOK_APP_ID','my_app_id');
	define('ST_FACEBOOK_APP_SECRET','my_app_secret');

Compatibility
==================
Tested with 3.1

Maintainer
==================
LeKoala - thomas@lekoala.be