<?php

/**
 * @package     DeferImages
 *
 * @copyright   Copyright (C) 2022 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;

class plgSystemDeferImages extends CMSPlugin {
	
	public static $site_code;
	public static $pluginParams;

	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);

		$plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('system', 'deferimages');
		$pluginParams = new JRegistry();
		$pluginParams->loadString($plugin->params);
		self::$pluginParams = $pluginParams;
	}
	
	public static function onAfterRender() {
		if(isset($_GET['unoptimize'])) return;
		if(isset($_GET['format'])
			|| strpos($_SERVER['REQUEST_URI'], 'rss')
		) {
			return;
		}
		$app = JFactory::getApplication();
		if (\Joomla\CMS\Factory::getApplication()->isClient('administrator')) return;
		
		//Allowed pages (any words in the url separated by | character), use \/$ for index
		//E.g. \/$|uncache|products
		$allowed_pages = self::$pluginParams->get("allowed_pages", "");
		if(preg_match("({$allowed_pages})", $_SERVER['REQUEST_URI']) !== 1) { 
			return; 
		}
		
		self::$site_code = JFactory::getApplication()->getBody();
		self::DeferImages();
		JFactory::getApplication()->setBody(self::$site_code);
	}
	
	public static function DeferImages() {
		$exclude = array('skipDefer');
		foreach(explode(",", self::$pluginParams->get("exclude")) as $val) {
			$val = preg_replace("/[\s\.#]/smix", "", $val);
			if($val != "") {
				$val = preg_quote($val);
				$val = str_replace("/", "\/", $val);
				$exclude[] = $val;
			}
		}
		
		if(isset($_GET['debug'])) {
			$exclude = array('disabled-for-debug');
		}
		
		$base64_image = self::$pluginParams->get('LoadingImage', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAEr0lEQVRoQ+1XT2gjVRj/3ps0SXfT3cpucUF0exB3IWKSzpupdAMaL0JBXDzoRVgX0cOeFBTPe1QR2YMH9WAFD+JB3EvAg0Qh9M/MvKS1Blosugi7LKiQmG2ZNJn3yRcyJdQomWnSrSW5zLzJ9+/3/b73vu8xOCY/dkxwwAjIUWNyxMiIkSFlYFRaQ0psaLMjRkKnbkiKI0b6SSwicinlBMnqul5njKl+9MLIDIyRcrmc9jwvJYT43A+kUqlEG43GaVrHYrFaMpnc9f9zHOd0vV5v5HI5N0zg+3UGAqRcLk96nvcrAEwCwA0hxBvkyHGcE5zzk/SulNoWQuzQ+/r6+oPNZvOM53meEOLnQTA1ECCdoKsA0M4+IuYMw/i+F5DOt2mS0zTNS6VSm0eGkQ6QBQC40gnqphDicq/Ssizr4Ugk0t43AFCdmZm5c1+BUI1TAEKIGj3L5fK053mrPiuapj2QTqf/6t7sHbmLPhvNZvMXIUST1oVCIULPXC7XCgMsVGnZtv04Y+y7jsMvXdf9IJvN/uY4zmUAWEDEVcMwnu4VUKlUOq9pWrxer9/JZrP1QqEQTyQS5xHxHMlvb2/LXC53LyiYsEAuMca+7nJWQ8QXDMP4KUgAhUIhEY/HU5qmtdmgH+d81Wc5iK1QQMiBbdtvM8be6gYjhHgsiHPbti8h4h6IaDS6lU6nbwex4cuGBtIBQ8wQmDlErBiG8UyQIJaXl3XOeQIRq5qm3QrDxECA+EaKxeIjtEeCgPBlaY8MoikGZmRxcfGhWCx2ARHbpw9jbKPRaGzOzc2FKgnqK67rTkYiEWqm0Gq1qvF4vOo3z36TExiIlPIzRDT2ObCFEFf7ddotZ1nWU4yxqe5viPi7aZo/BLE3ECCMMVvX9f8XECqtSCRykXN+gTKmlNpstVobByktzvnk7u5uu7Si0WhVKTX80upFt2VZ50zTvBukFLoOiglqjGF0u3UCl9a++k4zxq4wxtIAsCWEeC1IQFLKFxHxLCLe1jTNPsjcFRqIlPIVRPSHRDq97um6/lwQIKVS6VWlVMzXUUrZpmnaQWwcqI9YlpXmnH+4Z4Qxmo3e1HV9a21t7WQqldr+r2D83uE4zlnO+fPdYDjn34RhJhQjUspHEfHTTrDfKqUWaI+Uy2XD87xrjLFbuq5f7wXGcRxikQZEGvU3isXixPj4uOH3JQD4SgjxR1BWQgEhJ5VKJUHPZDLZnlRLpdKUUuo9AGjfCMfGxq66rhtnjL1Ma0T8YmdnZ/vUqVPvdNaupmkfZzIZupBBPp9vl9j8/HwjKAiSDw1kvzPHca4BQHt07/SV96WUTyilXqdvnPNPdF3/UUr5EgC0pwKadDOZzM0wge/XGSQQuiGeIAdKqeumaVZs256nJHec5g3DyC8tLU1Ho9H2IYGIrhDi3SMDhDZ4s9n8iIAwxvK6rhMoGvX/AYS+SymfBYAnGWNurVa7cV+Gxn/LHl11AWAqk8nsHZ+9SsvXp5MPAO6GbaRDK61eAFdWVs50b/bZ2dk/B1FGvWwMbI8MK8B+7Y6A9Jupw5IbMXJYme7Xz4iRfjN1WHIjRg4r0/36GTHSb6YOS+7YMPI38ygAUTFkTyAAAAAASUVORK5CYII=');
		
		// Replace images
		$pattern = "/<img(?![^>]*(?:".implode("|", $exclude).")[^>]*)([^<]*?)\s+src=['\"](?!data:)(.*?)['\"]([^<]*?)>/smix";
		$replace = "<img src='{$base64_image}' data-image='$2'$1$3>";
		self::$site_code = preg_replace_callback($pattern, function($matches) use ($base64_image) {
			//check if image has no leading slash (/)
			if($matches[2][0] != '/'
				&& strpos($matches[2], "http") === false
			) {
				$matches[2] = '/' . $matches[2];
			}
			
			$result = "<img src='{$base64_image}' data-image='{$matches[2]}'{$matches[1]}{$matches[3]}>";
			return $result;
		}, self::$site_code);
		
		// Replace styles backgrounds
		$pattern = "/style=(['\"])?\s*(background(?:-image)?):\s*[^;]*url\(['\"]?([^'\";]*)['\"]?\)/smix";
		$replace = "data-background='$3' style=$1$2: url({$base64_image})";
		self::$site_code = preg_replace($pattern, $replace, self::$site_code);
		
		$extra = "
				<script>
					var DeferType = '".self::$pluginParams->get('DeferType', 'Lazy')."';				
					if(DeferType == 'OnLoad') {
						if (window.addEventListener) { // For all major browsers, except IE 8 and earlier 
							window.addEventListener('load', function() {
								setTimeout(function() {
									showOptimizedImages();	
								}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
							});      
						} else if (window.attachEvent) { // For IE 8 and earlier versions
							window.attachEvent('onload', function() {
								setTimeout(function() {
									showOptimizedImages();
								}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
							});      
						} else { // Last resort if all else fails
							window.onload = function() { 
								setTimeout(function() {
									showOptimizedImages();
								}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
							};      
						}
					}
					else {
						setTimeout(function() {
							showOptimizedImages();
						}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
					}
					
					if(DeferType == 'Lazy') {
						window.addEventListener('scroll', function(e) {
							setTimeout(function() {
								showOptimizedImages();
							}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
						}, false);
						window.addEventListener('mousedown', function(e) {
							setTimeout(function() {
								showOptimizedImages();
							}, ".self::$pluginParams->get('ImagesLoadDelay', 200).");
						}, false);
					}
					
					function showOptimizedImages() {
						var imgDefer = document.getElementsByTagName('img');
						var LazyImages = 1;
						if(DeferType == 'OnLoad') { 
							LazyImages = 0;
						}
						for (var i=0; i < imgDefer.length; i++) {
							if(imgDefer[i].getAttribute('data-image')) {
								var src = imgDefer[i].getAttribute('data-image');
								if(LazyImages) {
									if(isVisible(imgDefer[i])) {
										imgDefer[i].setAttribute('src', src);
										imgDefer[i].setAttribute('data-image', '');
									}
								}
								else {
									imgDefer[i].setAttribute('src', src);
									imgDefer[i].setAttribute('data-image', '');
								}
							}
						}
						
						// Load backgrounds
						var blocks = document.querySelectorAll('[data-background]');
						for (var i=0; i < blocks.length; i++) {
							if(isVisible(blocks[i]) || !LazyImages) {
								var url = blocks[i].getAttribute('data-background');
								var newStyle = blocks[i].getAttribute('style').replace(/url(['\"]?[^)]*['\"]?)/gi, 'url('+url);
								blocks[i].setAttribute('style', newStyle);
								blocks[i].removeAttribute('data-background');
							}
						}
					};				
					function isVisible(el) {
						var rect = el.getBoundingClientRect();
						var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
						return !(rect.bottom < 0 || rect.top - viewHeight >= 0);
					};
				</script>";
				
			self::$site_code = str_replace("</body>", $extra . "</body>", self::$site_code);
	}
}

?>
