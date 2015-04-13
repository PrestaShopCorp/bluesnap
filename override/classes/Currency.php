<?php
/**
 * 2007-2015 PrestaShop
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
 * @author    Pavel Novitsky <pavel@belvg.com>
 * @copyright Copyright (c) 2010 - 2015 BelVG LLC. (http://www.belvg.com)
 * @license   http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 */

/**
 * Class Currency
 * Extend default functionality for BlueSnap Exchange Rate API support
 * If rates update fails — falldown to the default PS API request
 */
class Currency extends CurrencyCore
{
	private static $locally_supported = array('USD', 'EUR', 'GBP', 'CAD', 'JPY', 'CHF', 'ARS', 'AUD', 'BRL', 'CLP', 'COP', 'HKD', 'HUF', 'INR', 'ILS', 'MYR', 'MXN', 'NZD', 'NOK', 'PLN', 'RUB', 'SAR', 'SGD', 'ZAR', 'SEK', 'PEN');

	/**
	 * Update currencies conversion rate
	 * @return array|null|string
	 */
	public static function refreshCurrencies()
	{
		if (Configuration::get('BLUESNAP_USE_BS_EXCHANGE'))
		{
			// get shop default currency
			if (!$default_currency = self::getDefaultCurrency())
				return Tools::displayError('No default currency');

			$default_iso_code = $default_currency->iso_code;
			$currencies = self::getCurrencies(true, false, true);
			$errors = array();

			/* @var $currency Currency */
			foreach ($currencies as $currency)
			{
				$conversion_rate = 1;
				if ($currency->id != $default_currency->id)
				{
					if (!$conversion_rate = $currency->getBluesnapConversionRate($default_iso_code))
					{
						$errors[$currency->iso_code] = $currency->id;
						continue;
					}
				}

				$currency->conversion_rate = $conversion_rate;
				$currency->update();
			}

			// try to get conversion rate with the default PrestaShop API
			if (count($errors))
			{
				if (!$feed = Tools::simplexml_load_file('http://api.prestashop.com/xml/currencies.xml'))
					return Tools::displayError('Cannot parse feed.');

				self::getPrestashopConversionRate($feed, $errors, $default_currency);
			}
		}
		else
			parent::refreshCurrencies();

		return null;
	}

	/**
	 * get BlueSnap conversion rate for currency
	 * @param string $default_iso_code
	 * @return float|null
	 */
	public function getBluesnapConversionRate($default_iso_code)
	{
		$api_url = $this->prepareExchangeUrl($default_iso_code, $this->iso_code);
		$result = null;

		if (Configuration::get('BLUESNAP_API_DEBUG_MODE'))
			bluesnap::log('Request: '.$api_url."\r\n", 'log/bluesnap_exchange_api.log');

		// BlueSnap API did not send correct XML or sent 400 header
		if ($xml = Tools::simplexml_load_file($api_url))
		{
			if (Configuration::get('BLUESNAP_API_DEBUG_MODE'))
				bluesnap::log('Response: '.print_r($xml, true)."\r\n", 'log/bluesnap_exchange_api.log');

			if ($xml->value)
				$result = ((float)$xml->value) / 10000;
		}

		return $result;
	}

	/**
	 * get BlueSnap conversion rate for currencies
	 * @param SimpleXMLElement $feed
	 * @param array $currencies
	 * @param Currency $default_currency
	 */
	public static function getPrestashopConversionRate($feed, $currencies, $default_currency)
	{
		// Default feed currency (EUR)
		$iso_code_source = (string)$feed->source['iso_code'];

		foreach ($currencies as $id_currency)
		{
			$currency = self::getCurrencyInstance($id_currency);
			$currency->refreshCurrency($feed->list, $iso_code_source, $default_currency);
		}
	}

	/**
	 * Create URL based on the currencies iso codes
	 * @param string $default_iso_code
	 * @param string $iso_code
	 * @param float $amount
	 * @return string
	 */
	public function prepareExchangeUrl($default_iso_code, $iso_code, $amount = 10000)
	{
		if (Configuration::get('BLUESNAP_SANDBOX'))
		{
			$api_user = Configuration::get('BLUESNAP_SANDBOX_USER');
			$api_pwd = Configuration::get('BLUESNAP_SANDBOX_PSWD');
		}
		else
		{
			$api_user = Configuration::get('BLUESNAP_USER');
			$api_pwd = Configuration::get('BLUESNAP_PSWD');
		}

		require_once _PS_MODULE_DIR_.'bluesnap/classes/BluesnapApi.php';
		$api = new BluesnapApi($api_user, $api_pwd);
		/* @var $api BluesnapApi */

		$url = $api->getServiceUrl('tools/merchant-currency-convertor');
		$query = '?from='.$default_iso_code.'&to='.$iso_code.'&amount='.$amount;

		return str_replace('://', sprintf('://%s:%s@', $api_user, $api_pwd), $url).$query;
	}

	/**
	 * Check if currency is on a BS locally supported currencis list
	 * @param string $currency_code
	 * @return bool
	 */
	public static function isLocallySupported($currency_code)
	{
		return in_array($currency_code, self::$locally_supported);
	}
}