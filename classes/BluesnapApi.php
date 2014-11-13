<?php
/**
 * 2007-2014 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *         DISCLAIMER   *
 * ***************************************
 * Do not edit or add to this file if you wish to upgrade Prestashop to newer
 * versions in the future.
 * ****************************************************
 *
 * @category	Belvg
 * @package	Belvg_BlueSnap
 * @author    Alexander Simonchik <support@belvg.com>
 * @copyright Copyright (c) 2010 - 2014 BelVG LLC. (http://www.belvg.com)
 * @license   http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 */

require_once _PS_MODULE_DIR_.'bluesnap/includer.php';

/**
 * BlueSnap API Client
 *
 *
 */
class BluesnapApi {

	const API_BASE_URL_SANDBOX = 'https://sandbox.bluesnap.com/services';
	const API_BASE_URL = 'https://www.bluesnap.com/services';
	const XML_NS = 'http://ws.plimus.com';
	const VERSION = '2';
	const HTTP_METHOD_POST = 0;
	const HTTP_METHOD_PUT = 1;

	/**
	 * API username
	 *
	 * @var
	 */
	private $user;

	/**
	 * API password
	 *
	 * @var
	 */
	private $password;

	/**
	 * Debug mode on/off
	 *
	 * @var
	 */
	private $is_debug_mode;

	/**
	 * Log path
	 *
	 * @var string
	 */
	private $debug_log_name = 'log/bluesnap_buynow_api.log';

	/**
	 * Create API object
	 *
	 * @param $user
	 * @param $password
	 */
	public function __construct($user, $password)
	{
		$this->user = $user;
		$this->password = $password;
		$this->is_debug_mode = Configuration::get('BLUESNAP_API_DEBUG_MODE');
	}

	/**
	 * Send Parameter Encryption request,
	 * return URL-encoded (by BlueSnap service) encryption token
	 *
	 * @param array $params
	 * @return string
	 */
	public function paramEncryption(array $params)
	{
		// compose request XML
		$params_xml = '';
		foreach ($params as $key => $value)
		{
			$params_xml .= "<parameter>\n<param-key>{$this->wrapCdata($key)}</param-key>\n
				<param-value>{$this->wrapCdata($value)}</param-value>\n</parameter>\n";
		}

		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<param-encryption xmlns=\"{$this->getXmlNs()}\">\n
			<parameters>\n".$params_xml."  </parameters>\n</param-encryption>\n";

		// send request
		$url = $this->getServiceUrl('tools/param-encryption');
		$xml = $this->request($url, $request_xml);

		// validate response
		if (!isset($xml->{'encrypted-token'}) || is_null($xml->{'encrypted-token'}))
			bluesnap::log('Bluesnap_Buynow: Invalid response data');
		else
			return (string)$xml->{'encrypted-token'};
	}

	/**
	 * Get URL of given API service
	 *
	 * @return string
	 */
	protected function getServiceUrl($service)
	{
		$api_url = Configuration::get('BLUESNAP_SANDBOX') ? self::API_BASE_URL_SANDBOX : self::API_BASE_URL;
		return implode('/', array($api_url, self::VERSION, $service));
	}

	public function refund($reference_number)
	{
		$url = $this->getServiceUrl('orders/refund');
		$url .= '?'.http_build_query(array('invoiceId' => $reference_number));
		$response = $this->request($url, '', self::HTTP_METHOD_PUT);
		if (!empty($response))
		{
			// error
			$response = (array)$response;
			if (isset($response['message']))
				bluesnap::log('API error[refund action] : '.(string)$response['message']->description);
			elseif (isset($response[0]))
				bluesnap::log('API error[refund action] : '.$response[0]);

			return false;
		}

		return true;
	}

	/**
	 * Send request, return response XML object.
	 *
	 * @param $url
	 * @param $request_xml
	 * @return SimpleXMLElement
	 */
	protected function request($url, $request_xml, $http_method = self::HTTP_METHOD_POST)
	{
		$this->logDebug("Sending request to `{$url}':\n$request_xml\n\n");

		if (!extension_loaded('curl'))
			bluesnap::log('cURL extension is not available');

		$username = $this->user;
		$password = $this->password;

		$ch = curl_init();
		curl_setopt_array($ch, $this->getCurlOptions());

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		switch ($http_method)
		{
			case self::HTTP_METHOD_PUT:
				curl_setopt($ch, CURLOPT_PUT, true);
				break;
			case self::HTTP_METHOD_POST:
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request_xml);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		$response_xml = curl_exec($ch);
		if ($response_xml === false)
		{
			curl_close($ch);
			//throw new PrestaShopException('cURL error "'.curl_errno().': '.curl_error().'"');
			bluesnap::log('cURL error "'.curl_errno().': '.curl_error().'"');
		}

		//print_r($responseXml); die;
		curl_close($ch);
		$this->logDebug("Response text:\n{$response_xml}\n\n");
		// create XML object
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($response_xml);

		return $xml;
	}

	/**
	 * Return array of general cURL options
	 * @return array
	 */
	protected function getCurlOptions()
	{
		return array(
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_USERAGENT => 'PRESTASHOP STORE',
			CURLOPT_COOKIESESSION => true,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => array('Content-Type: application/xml'),
			CURLOPT_RETURNTRANSFER => true,
		);
	}

	/**
	 * Get API request XML namespace
	 *
	 * @return string
	 */
	protected function getXmlNs()
	{
		return self::XML_NS;
	}

	/**
	 * Wrap string into <![CDATA[ ]]>
	 *
	 * @param $string
	 * @return string
	 */
	protected function wrapCdata($string)
	{
		return '<![CDATA['
				.str_replace(']]>', ']]><![CDATA[', $string)
				.']]>';
	}

	/**
	 * Save log
	 *
	 * @param $message
	 */
	protected function logDebug($message)
	{
		if ($this->is_debug_mode)
			bluesnap::log($message, $this->debug_log_name);
	}

	public static function getLangByIso($iso)
	{
		$language_codes = array(
			'aa' => 'Afar',
			'ab' => 'Abkhazian',
			'ae' => 'Avestan',
			'af' => 'Afrikaans',
			'ak' => 'Akan',
			'am' => 'Amharic',
			'an' => 'Aragonese',
			'ar' => 'Arabic',
			'as' => 'Assamese',
			'av' => 'Avaric',
			'ay' => 'Aymara',
			'az' => 'Azerbaijani',
			'ba' => 'Bashkir',
			'be' => 'Belarusian',
			'bg' => 'Bulgarian',
			'bh' => 'Bihari',
			'bi' => 'Bislama',
			'bm' => 'Bambara',
			'bn' => 'Bengali',
			'bo' => 'Tibetan',
			'br' => 'Breton',
			'bs' => 'Bosnian',
			'ca' => 'Catalan',
			'ce' => 'Chechen',
			'ch' => 'Chamorro',
			'co' => 'Corsican',
			'cr' => 'Cree',
			'cs' => 'Czech',
			'cu' => 'Church Slavic',
			'cv' => 'Chuvash',
			'cy' => 'Welsh',
			'da' => 'Danish',
			'de' => 'German',
			'dv' => 'Divehi',
			'dz' => 'Dzongkha',
			'ee' => 'Ewe',
			'el' => 'Greek',
			'en' => 'English',
			'eo' => 'Esperanto',
			'es' => 'Spanish',
			'et' => 'Estonian',
			'eu' => 'Basque',
			'fa' => 'Persian',
			'ff' => 'Fulah',
			'fi' => 'Finnish',
			'fj' => 'Fijian',
			'fo' => 'Faroese',
			'fr' => 'French',
			'fy' => 'Western Frisian',
			'ga' => 'Irish',
			'gd' => 'Scottish Gaelic',
			'gl' => 'Galician',
			'gn' => 'Guarani',
			'gu' => 'Gujarati',
			'gv' => 'Manx',
			'ha' => 'Hausa',
			'he' => 'Hebrew',
			'hi' => 'Hindi',
			'ho' => 'Hiri Motu',
			'hr' => 'Croatian',
			'ht' => 'Haitian',
			'hu' => 'Hungarian',
			'hy' => 'Armenian',
			'hz' => 'Herero',
			'ia' => 'Interlingua',
			'id' => 'Indonesian',
			'ie' => 'Interlingue',
			'ig' => 'Igbo',
			'ii' => 'Sichuan Yi',
			'ik' => 'Inupiaq',
			'io' => 'Ido',
			'is' => 'Icelandic',
			'it' => 'Italian',
			'iu' => 'Inuktitut',
			'ja' => 'Japanese',
			'jv' => 'Javanese',
			'ka' => 'Georgian',
			'kg' => 'Kongo',
			'ki' => 'Kikuyu',
			'kj' => 'Kwanyama',
			'kk' => 'Kazakh',
			'kl' => 'Kalaallisut',
			'km' => 'Khmer',
			'kn' => 'Kannada',
			'ko' => 'Korean',
			'kr' => 'Kanuri',
			'ks' => 'Kashmiri',
			'ku' => 'Kurdish',
			'kv' => 'Komi',
			'kw' => 'Cornish',
			'ky' => 'Kirghiz',
			'la' => 'Latin',
			'lb' => 'Luxembourgish',
			'lg' => 'Ganda',
			'li' => 'Limburgish',
			'ln' => 'Lingala',
			'lo' => 'Lao',
			'lt' => 'Lithuanian',
			'lu' => 'Luba-Katanga',
			'lv' => 'Latvian',
			'mg' => 'Malagasy',
			'mh' => 'Marshallese',
			'mi' => 'Maori',
			'mk' => 'Macedonian',
			'ml' => 'Malayalam',
			'mn' => 'Mongolian',
			'mr' => 'Marathi',
			'ms' => 'Malay',
			'mt' => 'Maltese',
			'my' => 'Burmese',
			'na' => 'Nauru',
			'nb' => 'Norwegian Bokmal',
			'nd' => 'North Ndebele',
			'ne' => 'Nepali',
			'ng' => 'Ndonga',
			'nl' => 'Dutch',
			'nn' => 'Norwegian Nynorsk',
			'no' => 'Norwegian',
			'nr' => 'South Ndebele',
			'nv' => 'Navajo',
			'ny' => 'Chichewa',
			'oc' => 'Occitan',
			'oj' => 'Ojibwa',
			'om' => 'Oromo',
			'or' => 'Oriya',
			'os' => 'Ossetian',
			'pa' => 'Panjabi',
			'pi' => 'Pali',
			'pl' => 'Polish',
			'ps' => 'Pashto',
			'pt' => 'Portuguese',
			'qu' => 'Quechua',
			'rm' => 'Raeto-Romance',
			'rn' => 'Kirundi',
			'ro' => 'Romanian',
			'ru' => 'Russian',
			'rw' => 'Kinyarwanda',
			'sa' => 'Sanskrit',
			'sc' => 'Sardinian',
			'sd' => 'Sindhi',
			'se' => 'Northern Sami',
			'sg' => 'Sango',
			'si' => 'Sinhala',
			'sk' => 'Slovak',
			'sl' => 'Slovenian',
			'sm' => 'Samoan',
			'sn' => 'Shona',
			'so' => 'Somali',
			'sq' => 'Albanian',
			'sr' => 'Serbian',
			'ss' => 'Swati',
			'st' => 'Southern Sotho',
			'su' => 'Sundanese',
			'sv' => 'Swedish',
			'sw' => 'Swahili',
			'ta' => 'Tamil',
			'te' => 'Telugu',
			'tg' => 'Tajik',
			'th' => 'Thai',
			'ti' => 'Tigrinya',
			'tk' => 'Turkmen',
			'tl' => 'Tagalog',
			'tn' => 'Tswana',
			'to' => 'Tonga',
			'tr' => 'Turkish',
			'ts' => 'Tsonga',
			'tt' => 'Tatar',
			'tw' => 'Twi',
			'ty' => 'Tahitian',
			'ug' => 'Uighur',
			'uk' => 'Ukrainian',
			'ur' => 'Urdu',
			'uz' => 'Uzbek',
			've' => 'Venda',
			'vi' => 'Vietnamese',
			'vo' => 'Volapuk',
			'wa' => 'Walloon',
			'wo' => 'Wolof',
			'xh' => 'Xhosa',
			'yi' => 'Yiddish',
			'yo' => 'Yoruba',
			'za' => 'Zhuang',
			'zh' => 'Chinese',
			'zu' => 'Zulu'
		);

		if (isset($language_codes[$iso]))
			return Tools::strtoupper($language_codes[$iso]);

		return null;
	}

}
