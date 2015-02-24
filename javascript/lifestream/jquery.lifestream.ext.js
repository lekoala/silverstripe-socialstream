/*!
 * jQuery Lifestream Plug-in extensions
 * - Support for twitter hashtags
 * - Custom backend controller
 * - Facebook pages are working again
 */

(function ($) {
	/**
	 * Add links to the facebook feed.
	 * Hashes and regular links are supported.
	 * @param {String} post A string of a post
	 * @return {String} A linkified post
	 */
	var facebookLinkify = function (post) {

		var link = function (t) {
			return t.replace(
					/([a-z]+:\/\/)([-A-Z0-9+&@#\/%?=~_|(\)!:,.;]*[-A-Z0-9+&@#\/%=~_|(\)])/ig,
					function (m, m1, m2) {
						return $("<a></a>").attr('target', '_blank').attr("href", m).text(
								((m2.length > 35) ? m2.substr(0, 34) + '...' : m2)
								)[0].outerHTML;
					}
			);
		},
				hash = function (t) {
					return t.replace(
							/<a.*?<\/a>|(^|\r?\n|\r|\n|)(#|\$)([a-zA-Z0-9ÅåÄäÖöØøÆæÉéÈèÜüÊêÛûÎî_]+)(\r?\n|\r|\n||$)/g,
							function (m, m1, m2, m3, m4) {
								if (typeof m3 == "undefined")
									return m;
								var elem = "";
								if (m2 == "#") {
									elem = ($("<a></a>")
											.attr("href",
													"https://www.facebook.com/hashtag/" + m3)
											.attr('target', '_blank')
											.text("#" + m3))[0].outerHTML;
								}
								return (m1 + elem + m4);
							}
					);
				};

		return hash(link(post));
	};

	/**
	 * Add links to the twitter feed.
	 * Hashes, @ and regular links are supported.
	 * @param {String} tweet A string of a tweet
	 * @return {String} A linkified tweet
	 */
	var twitterLinkify = function (tweet) {

		var link = function (t) {
			return t.replace(
					/([a-z]+:\/\/)([-A-Z0-9+&@#\/%?=~_|(\)!:,.;]*[-A-Z0-9+&@#\/%=~_|(\)])/ig,
					function (m, m1, m2) {
						return $("<a></a>").attr("href", m).attr('target', '_blank').text(
								((m2.length > 35) ? m2.substr(0, 34) + '...' : m2)
								)[0].outerHTML;
					}
			);
		},
				at = function (t) {
					return t.replace(
							/(^|[^\w]+)\@([a-zA-Z0-9_]{1,15})/g,
							function (m, m1, m2) {
								var elem = ($("<a></a>")
										.attr("href", "https://twitter.com/" + m2)
										.attr('target', '_blank')
										.text("@" + m2))[0].outerHTML;
								return m1 + elem;
							}
					);
				},
				hash = function (t) {
					return t.replace(
							/<a.*?<\/a>|(^|\r?\n|\r|\n|)(#|\$)([a-zA-Z0-9ÅåÄäÖöØøÆæÉéÈèÜüÊêÛûÎî_]+)(\r?\n|\r|\n||$)/g,
							function (m, m1, m2, m3, m4) {
								if (typeof m3 == "undefined")
									return m;
								var elem = "";
								if (m2 == "#") {
									elem = ($("<a></a>")
											.attr("href",
													"https://twitter.com/hashtag/" + m3 + "?src=hash")
											.attr('target', '_blank')
											.text("#" + m3))[0].outerHTML;
								} else if (m2 == "$") {
									elem = ($("<a></a>")
											.attr("href",
													"https://twitter.com/search?q=%24" + m3 + "&src=hash")
											.attr('target', '_blank')
											.text("$" + m3))[0].outerHTML;
								}
								return (m1 + elem + m4);
							}
					);
				};

		return hash(at(link(tweet)));
	};

	/**
	 * Facebook page plugin
	 * @param {Object} config
	 * @param {Function} callback
	 * @returns {jquery.lifestream.ext_L8.$.fn.lifestream.feeds.facebook_page.jquery.lifestream.extAnonym$5}
	 */
	$.fn.lifestream.feeds.facebook_page = function (config, callback) {
		var template = $.extend({},
				{
					"facebook_post": '{{html text}}'
				},
		config.template);

		/**
		 * Parse the input from facebook
		 */
		var parseFBPage = function (input) {
			if (!input) {
				return;
			}

			var output = [], list, i = 0, j;

			if (input.posts && input.posts.length > 0) {
				list = input.posts;
				j = list.length;
				for (; i < j; i++) {
					var item = list[i];
					if ($.trim(item.text)) {
						var shortText = item.text;
						if(shortText.length > 255) {
							shortText = shortText.substr(0,255) + '...';
							// Prevent cutting words
							shortText = shortText.substr(0,Math.min(shortText.length, shortText.lastIndexOf(" ")));
						}
						output.push({
							date: new Date(item.createdAt * 1000), //unix time in milli seconds instead of php seconds
							config: config,
							html: $.tmpl(template.facebook_post, {text: facebookLinkify($('<div/>').html(shortText).text())})
						});

					}
				}
			}
			callback(output);
		};

		$.ajax({
			"url": '/socialstream/facebook_page/?query=' + encodeURIComponent(config.user),
			"cache": false
		}).success(parseFBPage);

		// Expose the template.
		// We use this to check which templates are available
		return {
			"template": template
		};

	};

	/**
	 * Twitter feed plugin
	 * @param {Object} config
	 * @param {Function} callback
	 * @returns {jquery.lifestream.ext_L8.$.fn.lifestream.feeds.twitter.jquery.lifestream.extAnonym$11}
	 */
	$.fn.lifestream.feeds.twitter = function (config, callback) {
		var template = $.extend({},
				{
					"posted": '{{html tweet}}'
				},
		config.template);

		/**
		 * Parse the input from twitter
		 * @private
		 * @param  {Object[]} items
		 * @return {Object[]} Array of Twitter status messages.
		 */
		var parseTwitter = function (response) {
			var output = [];

			if (!response.tweets) {
				return output;
			}

			for (var i = 0; i < response.tweets.length; i++) {
				var status = response.tweets[i];
				output.push({
					"date": new Date(status.createdAt * 1000), // unix time
					"config": config, "html": $.tmpl(template.posted, {
						"tweet": twitterLinkify($('<div/>').html(status.text).text()),
						"complete_url": 'https://twitter.com/' + config.user +
								"/status/" + status.id
					}),
					"url": 'https://twitter.com/' + config.user
				});
			}
			callback(output);
		};

		$.ajax({
			"url": '/socialstream/twitter_user/?query=' + encodeURIComponent(config.user),
			"cache": false
		}).success(parseTwitter);

		// Expose the template.
		// We use this to check which templates are available
		return {
			"template": template
		};
	};

	/**
	 * Twitter hashtag plugin
	 * @param {Object} config
	 * @param {Function} callback
	 * @returns {jquery.lifestream.ext_L8.$.fn.lifestream.feeds.twitter_hashtag.jquery.lifestream.extAnonym$17}
	 */
	$.fn.lifestream.feeds.twitter_hashtag = function (config, callback) {
		var template = $.extend({},
				{
					"posted": '{{html tweet}}'
				},
		config.template);


		/**
		 * Parse the input from twitter
		 * @private
		 * @param  {Object[]} items
		 * @return {Object[]} Array of Twitter status messages.
		 */
		var parseTwitter = function (response) {
			var output = [];

			if (!response.tweets) {
				return output;
			}

			for (var i = 0; i < response.tweets.length; i++) {
				var status = response.tweets[i];
				output.push({
					"date": new Date(status.createdAt * 1000), // unix time
					"config": config,
					"html": $.tmpl(template.posted, {
						"tweet": twitterLinkify($('<div/>').html(status.text).text()),
						"complete_url": 'https://twitter.com/' + config.user +
								"/status/" + status.id
					}),
					"url": 'https://twitter.com/' + config.user
				});
			}
			callback(output);
		};

		$.ajax({
			"url": '/socialstream/twitter_search/?query=' + encodeURIComponent('#' + config.user),
			"cache": false
		}).success(parseTwitter);

		// Expose the template.
		// We use this to check which templates are available
		return {
			"template": template
		};
	};
}(jQuery));