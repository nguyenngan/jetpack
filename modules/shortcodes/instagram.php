<?php
/**
 * Instagram Embeds.
 *
 * Full links: https://www.instagram.com/p/BnMOk_FFsxg/
 * https://www.instagram.com/tv/BkQjCfsBIzi/
 * [instagram url=https://www.instagram.com/p/BnMOk_FFsxg/]
 * [instagram url=https://www.instagram.com/p/BZoonmAHvHf/ width=320]
 * Embeds can be converted to a shortcode when the author does not have unfiltered_html caps:
 * <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="2" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"><div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding-bottom:55%; padding-top:45%; text-align:center; width:100%;"><div style="position:relative;"><div style=" -webkit-animation:dkaXkpbBxI 1s ease-out infinite; animation:dkaXkpbBxI 1s ease-out infinite; background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAAGFBMVEUiIiI9PT0eHh4gIB4hIBkcHBwcHBwcHBydr+JQAAAACHRSTlMABA4YHyQsM5jtaMwAAADfSURBVDjL7ZVBEgMhCAQBAf//42xcNbpAqakcM0ftUmFAAIBE81IqBJdS3lS6zs3bIpB9WED3YYXFPmHRfT8sgyrCP1x8uEUxLMzNWElFOYCV6mHWWwMzdPEKHlhLw7NWJqkHc4uIZphavDzA2JPzUDsBZziNae2S6owH8xPmX8G7zzgKEOPUoYHvGz1TBCxMkd3kwNVbU0gKHkx+iZILf77IofhrY1nYFnB/lQPb79drWOyJVa/DAvg9B/rLB4cC+Nqgdz/TvBbBnr6GBReqn/nRmDgaQEej7WhonozjF+Y2I/fZou/qAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-44px; width:44px;"></div><span style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:12px; font-style:normal; font-weight:bold; position:relative; top:15px;">Loading</span></div></div><p style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin:8px 0 0 0; padding:0 4px; word-wrap:break-word;"> Balloons</p><p style=" line-height:32px; margin-bottom:0; margin-top:8px; padding:0; text-align:center;"> <a href="https://instagram.com/p/r9vfPrmjeB/" style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; text-decoration:none;" target="_top"> View on Instagram</a></p></div><style>@-webkit-keyframes"dkaXkpbBxI"{ 0%{opacity:0.5;} 50%{opacity:1;} 100%{opacity:0.5;} } @keyframes"dkaXkpbBxI"{ 0%{opacity:0.5;} 50%{opacity:1;} 100%{opacity:0.5;} }</style></blockquote>
 * <script async defer src="https://platform.instagram.com/en_US/embeds.js"></script>
 *
 * @package Jetpack
 */

use Automattic\Jetpack\Assets;
use Automattic\Jetpack\Connection\Client;
use Automattic\Jetpack\Constants;

/**
 * Embed Reversal for Instagram
 *
 * Hooked to pre_kses, converts an embed code from Instagram.com to an oEmbeddable URL.
 *
 * @param string $content Post content.
 *
 * @return string The filtered or the original content.
 **/
function jetpack_instagram_embed_reversal( $content ) {
	if ( ! is_string( $content ) || false === stripos( $content, 'instagram.com' ) ) {
		return $content;
	}

	/*
	 * Sample embed code:
	 * <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="2" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"><div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding-bottom:55%; padding-top:45%; text-align:center; width:100%;"><div style="position:relative;"><div style=" -webkit-animation:dkaXkpbBxI 1s ease-out infinite; animation:dkaXkpbBxI 1s ease-out infinite; background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAAGFBMVEUiIiI9PT0eHh4gIB4hIBkcHBwcHBwcHBydr+JQAAAACHRSTlMABA4YHyQsM5jtaMwAAADfSURBVDjL7ZVBEgMhCAQBAf//42xcNbpAqakcM0ftUmFAAIBE81IqBJdS3lS6zs3bIpB9WED3YYXFPmHRfT8sgyrCP1x8uEUxLMzNWElFOYCV6mHWWwMzdPEKHlhLw7NWJqkHc4uIZphavDzA2JPzUDsBZziNae2S6owH8xPmX8G7zzgKEOPUoYHvGz1TBCxMkd3kwNVbU0gKHkx+iZILf77IofhrY1nYFnB/lQPb79drWOyJVa/DAvg9B/rLB4cC+Nqgdz/TvBbBnr6GBReqn/nRmDgaQEej7WhonozjF+Y2I/fZou/qAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-44px; width:44px;"></div><span style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:12px; font-style:normal; font-weight:bold; position:relative; top:15px;">Loading</span></div></div><p style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin:8px 0 0 0; padding:0 4px; word-wrap:break-word;"> Balloons</p><p style=" line-height:32px; margin-bottom:0; margin-top:8px; padding:0; text-align:center;"> <a href="https://instagram.com/p/r9vfPrmjeB/" style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; text-decoration:none;" target="_top"> View on Instagram</a></p></div><style>@-webkit-keyframes"dkaXkpbBxI"{ 0%{opacity:0.5;} 50%{opacity:1;} 100%{opacity:0.5;} } @keyframes"dkaXkpbBxI"{ 0%{opacity:0.5;} 50%{opacity:1;} 100%{opacity:0.5;} }</style></blockquote>
	 * <script async defer src="https://platform.instagram.com/en_US/embeds.js"></script>
	*/

	$regexes = array();

	// new style js.
	$regexes[] = '#<blockquote[^>]+?class="instagram-media"[^>].+?>(.+?)</blockquote><script[^>]+?src="(https?:)?//platform\.instagram\.com/(.+?)/embeds\.js"></script>#ix';

	// Let's play nice with the visual editor too.
	$regexes[] = '#&lt;blockquote(?:[^&]|&(?!gt;))+?class="instagram-media"(?:[^&]|&(?!gt;)).+?&gt;(.+?)&lt;/blockquote&gt;&lt;script(?:[^&]|&(?!gt;))+?src="(https?:)?//platform\.instagram\.com/(.+?)/embeds\.js"(?:[^&]|&(?!gt;))*+&gt;&lt;/script&gt;#ix';

	// old style iframe.
	$regexes[] = '#<iframe[^>]+?src="((?:https?:)?//(?:www\.)?instagram\.com/p/([^"\'/]++)[^"\']*?)"[^>]*+>\s*?</iframe>#i';

	// Let's play nice with the visual editor too.
	$regexes[] = '#&lt;iframe(?:[^&]|&(?!gt;))+?src="((?:https?:)?//(?:www\.)instagram\.com/p/([^"\'/]++)[^"\']*?)"(?:[^&]|&(?!gt;))*+&gt;\s*?&lt;/iframe&gt;#i';

	foreach ( $regexes as $regex ) {
		if ( ! preg_match_all( $regex, $content, $matches, PREG_SET_ORDER ) ) {
			continue;
		}

		foreach ( $matches as $match ) {
			if ( ! preg_match( '#(https?:)?//(?:www\.)?instagr(\.am|am\.com)/p/([^/]*)#i', $match[1], $url_matches ) ) {
				continue;
			}

			// Since we support Instagram via oEmbed, we simply leave a link on a line by itself.
			$replace_regex = sprintf( '#\s*%s\s*#', preg_quote( $match[0], '#' ) );
			$url           = esc_url( $url_matches[0] );

			$content = preg_replace( $replace_regex, sprintf( "\n\n%s\n\n", $url ), $content );
			/** This action is documented in modules/shortcodes/youtube.php */
			do_action( 'jetpack_embed_to_shortcode', 'instagram', $url );
		}
	}

	return $content;
}

add_filter( 'pre_kses', 'jetpack_instagram_embed_reversal' );

/**
 * Instagram's custom Embed provider.
 * We first remove the embed provider that's registered by Core; then, we declare our own.
 *
 * We can drop the `wp_oembed_remove_provider` line once Core stops adding its own Instagram provider:
 * https://core.trac.wordpress.org/ticket/50861.
 */
wp_oembed_remove_provider( '#https?://(www\.)?instagr(\.am|am\.com)/(p|tv)/.*#i' );

wp_oembed_add_provider(
	'#https?://(www\.)?instagr(\.am|am\.com)/(p|tv)/.*#i',
	'https://graph.facebook.com/v5.0/instagram_oembed/',
	true
);

/**
 * Handle an alternate Instagram URL format, where the username is also part of the URL.
 */
wp_oembed_add_provider(
	'#https?://(?:www\.)?instagr(?:\.am|am\.com)/(?:[^/]*)/(p|tv)/([^\/]*)#i',
	'https://graph.facebook.com/v5.0/instagram_oembed/',
	true
);

/**
 * Add auth token required by Instagram's oEmbed REST API.
 *
 * @since 9.1.0
 *
 * @param string $provider URL of the oEmbed provider.
 * @param string $url      URL of the content to be embedded.
 * @param array  $args     arguments, usually passed from a shortcode.
 *
 * @return string
 */
function jetpack_instagram_oembed_auth_token( $provider, $url, $args ) {
	if ( ! wp_startswith( $provider, 'https://graph.facebook.com/v5.0/instagram_oembed/' ) ) {
		return $provider;
	}

	$access_token = jetpack_instagram_get_access_token();

	// We handle the case where we _don't_ have an access token in `jetpack_instagram_oembed_result`,
	// which comes before this filter (and skips it if successful).
	if ( empty( $access_token ) ) {
		return $provider;
	}

	return add_query_arg(
		array(
			'access_token' => $access_token,
		),
		$provider
	);
}
add_filter( 'oembed_fetch_url', 'jetpack_instagram_oembed_auth_token', 10, 3 );

/**
 * Use WP.com's oEmbed proxy endpoint if we don't have an auth token.
 *
 * We intercept any HTTP requests to Instagram's oEmbed endpoint before they
 * are made. If we don't have the required auth token, we short-circuit the
 * oEmbed logic by making a request to WP.com's oEmbed proxy and returning its
 * response.
 *
 * @since 9.1.0
 *
 * @param null|string $result The UNSANITIZED (and potentially unsafe) HTML that should be used to embed.
 *                            Default null to continue retrieving the result.
 * @param string      $url    The URL to the content that should be attempted to be embedded.
 * @param array       $args   Optional. Arguments, usually passed from a shortcode. Default empty.
 *
 * @todo Rather than making the actual Jetpack->WP.com HTTP request in here, we could consider
 * adding the required authentication headers via the `oembed_remote_get_args` filter (and dropping
 * this filter). Those headers are currently added by `Client::wpcom_json_api_request_as_blog()` and
 * would need extracting into a helper. Furthermore, that function uses the `Client::_wp_remote_request()`
 * helper that automatically retries upon SSL verification header (for JP sites on hosts with misconfigured
 * SSL), which isn't easy to carry over.
 */
function jetpack_instagram_oembed_result( $result, $url, $args ) {
	if ( ! preg_match( '#https?://(www\.)?instagr(\.am|am\.com)/(p|tv)/.*#i', $url ) ) {
		return $result;
	}

	$access_token = jetpack_instagram_get_access_token();

	// If we _have_ an Instagram oEmbed access token, this has been handled in
	// `jetpack_instagram_oembed_auth_token`.
	if ( ! empty( $access_token ) ) {
		return $result;
	}

	// TODO: Check if $result has an error, clear it.

	// Check if we're JP and connected; if yes, try WP.com's proxy endpoint.
	if ( ! Jetpack::is_active_and_not_offline_mode() ) {
		return $result;
	}

	// @TODO Use Core's /oembed/1.0/proxy endpoint on WP.com
	// (Currently not global but per-site, i.e. /oembed/1.0/sites/1234567/proxy)
	// and deprecate /oembed-proxy/instagram endpoint.
	$response      = Client::wpcom_json_api_request_as_blog(
		add_query_arg(
			array( 'url' => $url ),
			'/oembed-proxy/instagram'
		),
		'2',
		array(),
		null,
		'wpcom'
	);
	$response_body = json_decode( wp_remote_retrieve_body( $response ) );

	if (
		is_wp_error( $response )
		|| 200 !== wp_remote_retrieve_response_code( $response )
		|| empty( $response_body )
	) {
		return $result;
	}

	return $response_body->html;

}
// A potential alternative would be to hook into `pre_oembed_result`,
// as that would require fewer checks in `jetpack_instagram_oembed_result`.
// However, `pre_oembed_result` is not applied by the oEmbed REST API
// controller (see https://core.trac.wordpress.org/ticket/51471) which
// would break the `/oembed/1.0/proxy` endpoint that is used by Gutenberg's
// embed block.
add_filter( 'oembed_result', 'jetpack_instagram_oembed_result', 10, 3 );


/**
 * Fetches a Facebook API access token used for query for Instagram embed information, if one is set.
 *
 * @return string The access token or ''
 */
function jetpack_instagram_get_access_token() {
	/**
	 * Filters the Instagram embed token that is used for querying the Facebook API.
	 *
	 * When this token is set, requests are not proxied through the WordPress.com API. Instead, a request is made directly to the
	 * Facebook API to query for information about the embed which should provide a performance benefit.
	 *
	 * @module shortcodes
	 *
	 * @since  9.0.0
	 *
	 * @param string string The access token set via the JETPACK_INSTAGRAM_EMBED_TOKEN constant.
	 */
	return (string) apply_filters( 'jetpack_instagram_embed_token', (string) Constants::get_constant( 'JETPACK_INSTAGRAM_EMBED_TOKEN' ) );
}

/**
 * Display the Instagram shortcode.
 *
 * @param array $atts Shortcode attributes.
 */
function jetpack_shortcode_instagram( $atts ) {
	global $wp_embed;

	if ( empty( $atts['url'] ) ) {
		return '';
	}

	if ( class_exists( 'Jetpack_AMP_Support' ) && Jetpack_AMP_Support::is_amp_request() ) {
		$url_pattern = '#http(s?)://(www\.)?instagr(\.am|am\.com)/p/([^/?]+)#i';
		preg_match( $url_pattern, $atts['url'], $matches );
		if ( ! $matches ) {
			return sprintf(
				'<a href="%1$s" class="amp-wp-embed-fallback">%1$s</a>',
				esc_url( $atts['url'] )
			);
		}

		$shortcode_id = end( $matches );
		$width        = ! empty( $atts['width'] ) ? $atts['width'] : 600;
		$height       = ! empty( $atts['height'] ) ? $atts['height'] : 600;
		return sprintf(
			'<amp-instagram data-shortcode="%1$s" layout="responsive" width="%2$d" height="%3$d" data-captioned></amp-instagram>',
			esc_attr( $shortcode_id ),
			absint( $width ),
			absint( $height )
		);
	}

	return $wp_embed->shortcode( $atts, $atts['url'] );
}
add_shortcode( 'instagram', 'jetpack_shortcode_instagram' );
