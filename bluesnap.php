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

if (!defined('_PS_VERSION_'))
	exit;

require_once _PS_MODULE_DIR_.'bluesnap/includer.php';

/**
 * Class bluesnap
 *
 *
 * Module class
 */
class Bluesnap extends PaymentModule {

	const PREFIX = 'BLUESNAP_';
	const SANDBOX_CHECKOUT_URL = 'https://sandbox.bluesnap.com/buynow/checkout';
	const CHECKOUT_URL = 'https://checkout.bluesnap.com/buynow/checkout';
	const LOG_FILE = 'log/bluesnap.log';

	/**
	 * hooks uses by module
	 *
	 * @var array
	 */
	protected $hooks = array(
		'displayHeader',
		'payment',
		'adminOrder',
		'BackOfficeHeader',
		'displayOrderConfirmation',
	);

	protected $html = '';

	/**
	 * module settings
	 *
	 * @var array
	 */
	protected $module_params = array(
		'USER' => '',
		'PSWD' => '',
		'SANDBOX_USER' => '',
		'SANDBOX_USER' => '',
		'STORE' => '',
		'SANDBOX' => 0,
		'CONTRACT' => '',
		'PROTECTION_KEY' => '',
		'BUYNOW_DEBUG_MODE' => '',
		'API_DEBUG_MODE' => '',
	);

	/**
	 * Bluesnap waiting status
	 *
	 * @var array
	 */
	private $os_statuses = array(
		'BS_OS_WAITING' => 'Awaiting BlueSnap payment',
	);

	/**
	 * Status for orders with accepted payment
	 *
	 * @var array
	 */
	private $os_payment_green_statuses = array(
		'BS_OS_PAYMENT_VALID' => 'Accepted BlueSnap payment',
	);

	/**
	 * Bluesnap error status
	 *
	 * @var array
	 */
	private $os_payment_red_statuses = array(
		'BS_OS_PAYMENT_ERROR' => 'Error BlueSnap payment',
	);

	/**
	 * create module object
	 */
	public function __construct()
	{
		$this->name = 'bluesnap';
		$this->tab = 'payments_gateways';
		$this->version = '1.6.4';
		$this->author = 'BelVG';
		$this->need_instance = 1;
		$this->is_configurable = 1;
		$this->bootstrap = true;
		$this->module_key = '';

		parent::__construct();

		$this->ps_versions_compliancy = array('min' => '1.6.0', 'max' => '1.6.9');
		$this->displayName = $this->l('BlueSnap BuyNow');
		$this->description = $this->l('Accept online payments easily and securely with a smarter payment gateway.
				BlueSnap has helped over 5,000 merchants convert more shoppers to buyers worldwide.');
		if ($this->getConfig('SANDBOX'))
			$this->api = new BluesnapApi($this->getConfig('SANDBOX_USER'), $this->getConfig('SANDBOX_PSWD'));
		else
			$this->api = new BluesnapApi($this->getConfig('USER'), $this->getConfig('PSWD'));

		/* Backward compatibility */
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
	}

	/**
	 * install module, register hooks, set default config values
	 *
	 * @return bool
	 */
	public function install()
	{
		if (parent::install())
		{
			foreach ($this->hooks as $hook)
			{
				if (!$this->registerHook($hook))
					return false;
			}

			if (!$this->installConfiguration())
				return false;

			if (!function_exists('curl_version'))
			{
				$this->_errors[] = $this->l('Unable to install the module (CURL isn\'t installed).');
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * set default config values
	 *
	 * @return bool
	 */
	public function installConfiguration()
	{
		foreach ($this->module_params as $param => $value)
		{
			if (!self::setConfig($param, $value))
				return false;
		}

		if (!Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bluesnap_order` (
                `id_bluesnap_order` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_cart` int(11) unsigned NOT NULL,
                `bluesnap_reference` int(11) NOT NULL,
                `refunded` tinyint(1) NOT NULL,
                PRIMARY KEY (`id_bluesnap_order`)
            ) ENGINE= '._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'))
			return false;

		//waiting payment status creation
		$this->createBluesnapPaymentStatus($this->os_statuses, '#3333FF', '', false, false, '', false);

		//validate green payment status creation
		$this->createBluesnapPaymentStatus($this->os_payment_green_statuses, '#32cd32', 'payment', true, true, true, true);

		//validate red payment status creation
		$this->createBluesnapPaymentStatus($this->os_payment_red_statuses, '#ec2e15', 'payment_error', false, true, false, true);

		return true;
	}

	/**
	 * uninstall module
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (parent::uninstall())
		{
			foreach ($this->hooks as $hook)
			{
				if (!$this->unregisterHook($hook))
					return false;
			}
		}

		return true;
	}

	/**
	 * create new order statuses
	 *
	 * @param $array
	 * @param $color
	 * @param $template
	 * @param $invoice
	 * @param $send_email
	 * @param $paid
	 * @param $logable
	 */
	public function createBluesnapPaymentStatus($array, $color, $template, $invoice, $send_email, $paid, $logable)
	{
		foreach ($array as $key => $value)
		{
			$ow_status = Configuration::get($key);
			if ($ow_status === false)
			{
				$order_state = new OrderState();
				//$order_state->id_order_state = (int)$key;
			}
			else
				$order_state = new OrderState((int)$ow_status);

			$langs = Language::getLanguages();

			foreach ($langs as $lang)
				$order_state->name[$lang['id_lang']] = utf8_encode(html_entity_decode($value));

			$order_state->invoice = $invoice;
			$order_state->send_email = $send_email;

			if ($template != '')
				$order_state->template = $template;

			if ($paid != '')
				$order_state->paid = $paid;

			$order_state->logable = $logable;
			$order_state->color = $color;
			$order_state->save();

			Configuration::updateValue($key, (int)$order_state->id);

			copy(dirname(__FILE__).'/img/statuses/'.$key.'.gif', dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif');
		}
	}

	/**
	 * Return server path for file
	 *
	 * @param string $file
	 * @return string
	 */
	public function getDir($file = '')
	{
		return _PS_MODULE_DIR_.$this->name.DIRECTORY_SEPARATOR.$file;
	}

	/**
	 * return correct path for .tpl file
	 *
	 * @param $area
	 * @param $file
	 * @return string
	 */
	public function getTemplate($area, $file)
	{
		return 'views/templates/'.$area.'/'.$file;
	}

	/**
	 * alias for Configuration::get()
	 *
	 * @param $name
	 * @return mixed
	 */
	public static function getConfig($name)
	{
		return Configuration::get(Tools::strtoupper(self::PREFIX.$name));
	}

	/**
	 * alias for Configuration::updateValue()
	 *
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public static function setConfig($name, $value)
	{
		return Configuration::updateValue(Tools::strtoupper(self::PREFIX.$name), $value);
	}

	/**
	 * return html with configuration
	 *
	 * @return string
	 */
	public function getContent()
	{
		$this->postProcess();
		$helper = $this->initForm();
		foreach ($this->fields_form as $field_form)
		{
			foreach ($field_form['form']['input'] as $input)
				$helper->fields_value[$input['name']] = $this->getConfig(Tools::strtoupper($input['name']));
		}

		$this->html .= $this->generateBrandbook();
		$this->html .= $helper->generateForm($this->fields_form);

		return $this->html;
	}

	public function generateBrandbook()
	{
		$this->smarty->assign(array(
			'bluesnap_img_url' => __PS_BASE_URI__.'modules/bluesnap/',
		));

		return $this->display(__FILE__, $this->getTemplate('admin', 'brandbook.tpl'));
	}

	/**
	 * helper with configuration
	 *
	 * @return HelperForm
	 */
	private function initForm()
	{
		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->toolbar_btn = $this->initToolbar();
		$helper->title = $this->displayName;
		$helper->submit_action = 'submitUpdate';

		$this->fields_form[0]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('BlueSnap API'), 'image' => $this->_path.
				'logo.gif'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Yes'), 'value' => 1, 'id' => 'sandbox_on'),
						array('label' => $this->l('No'), 'value' => 0, 'id' => 'sandbox_off'),
					),
					'is_bool' => true,
					'class' => 't',
					'label' => $this->l('Sandbox mode'),
					'name' => 'sandbox',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Sandbox username'),
					'name' => 'sandbox_user',
					'size' => 64,
				),
				array(
					'type' => 'password',
					'label' => $this->l('Sandbox password'),
					'name' => 'sandbox_pswd',
					'size' => 64,
					'desc' => $this->l('Leave this field blank if you do not want to change your password.')
				),
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Yes'), 'value' => 1, 'id' => 'sandbox_on'),
						array('label' => $this->l('No'), 'value' => 0, 'id' => 'sandbox_off'),
					),
					'is_bool' => true,
					'class' => 't',
					'label' => $this->l('Is Debug Mode Enabled'),
					'name' => 'api_debug_mode',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Username'),
					'name' => 'user',
					'size' => 64,
				),
				array(
					'type' => 'password',
					'label' => $this->l('Password'),
					'name' => 'pswd',
					'size' => 64,
					'desc' => $this->l('Leave this field blank if you do not want to change your password.')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Store ID'),
					'name' => 'store',
					'size' => 64,
				),
			),
		);

		$this->fields_form[1]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('BlueSnap BuyNow'), 'image' => $this->_path.
				'logo.gif'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Yes'), 'value' => 1, 'id' => 'sandbox_on'),
						array('label' => $this->l('No'), 'value' => 0, 'id' => 'sandbox_off'),
					),
					'is_bool' => true,
					'class' => 't',
					'label' => $this->l('Is Debug Mode Enabled'),
					'name' => 'buynow_debug_mode',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Data Protection Key'),
					'name' => 'protection_key',
					'size' => 64,
				),
				array(
					'type' => 'text',
					'label' => $this->l('Contract ID'),
					'name' => 'contract',
					'size' => 64,
				),
			),
		);

		return $helper;
	}

	/**
	 * PrestaShop way save button
	 *
	 * @return mixed
	 */
	private function initToolbar()
	{
		$toolbar_btn = array();
		$toolbar_btn['save'] = array('href' => '#', 'desc' => $this->l('Save'));
		return $toolbar_btn;
	}

	/**
	 * save configuration values
	 */
	protected function postProcess()
	{
		if (Tools::isSubmit('submitUpdate'))
		{
			$data = $_POST;
			if (is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (in_array($key, array('sandbox_pswd', 'pswd')) && empty($value))
						continue;

					self::setConfig($key, $value);
				}
			}

			Tools::redirectAdmin('index.php?tab=AdminModules&conf=4&configure='.$this->name.
			'&token='.Tools::getAdminToken('AdminModules'.
			(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id));
		}
	}

	/**
	 * include css file in frontend
	 *
	 * @param $params
	 */
	public function hookHeader()
	{
		$this->context->controller->addCSS(($this->_path).'css/front.css', 'all');
	}

	/**
	 * show module on payment step
	 *
	 * @param $params
	 * @return mixed
	 */
	public function hookPayment()
	{
		if (!$this->active)
			return;

		$bluesnap_url = $this->context->link->getModuleLink('bluesnap', 'checkout', array(), true);
		$this->smarty->assign(array(
			'bluesnap_url' => $bluesnap_url,
			'bluesnap_path' => $this->_path,
		));

		return $this->display(__FILE__, $this->getTemplate('front', 'payment.tpl'));
	}

	public function hookAdminOrder()
	{
		if (Tools::isSubmit('id_order'))
		{
			$order_obj = new Order(Tools::getValue('id_order'));
			$bluesnap_info = BluesnapOrder::getByPsCartId($order_obj->id_cart);
			if (isset($bluesnap_info['bluesnap_reference']) && !empty($bluesnap_info['bluesnap_reference']))
			{
				$this->context->smarty->assign(array(
					'bluesnap_reference_number' => $bluesnap_info['bluesnap_reference'],
					'bluesnap_refunded' => (int)$bluesnap_info['refunded'],
					'id_bluesnap_order' => (int)$bluesnap_info['id_bluesnap_order']
				));

				return $this->display(__FILE__, $this->getTemplate('admin', 'order.tpl'));
			}
		}
	}

	public function hookBackOfficeHeader()
	{
		$reference_number = Tools::getValue('bluesnap_reference_number');
		$id_order = Tools::getValue('id_order');
		$id_bluesnap_order = Tools::getValue('id_bluesnap_order');
		if ($reference_number && $id_order && $id_bluesnap_order)
		{
			$id_order_state = Configuration::get('PS_OS_REFUND');
			$template_vars = array();

			if ($this->api->refund($reference_number))
			{
				$order_obj = new Order($id_order);
				$orders_collection = Order::getByReference($order_obj->reference);
				foreach ($orders_collection->getResults() as $order)
				{
					// Set new order state
					$new_history = new OrderHistory();
					$new_history->id_order = (int)$order->id;
					$new_history->changeIdOrderState((int)$id_order_state, $order, true);
					// Save all changes
					if ($new_history->addWithemail(true, $template_vars))
					{
						$bluesnap_order = new BluesnapOrder($id_bluesnap_order);
						$bluesnap_order->refunded = 1;
						$bluesnap_order->update();
					}
				}
			}
		}
	}

	/**
	 * prepare url for bluesnap iframe
	 *
	 * @param $id_order
	 * @return string
	 */
	public function getCheckoutUrl()
	{
		if ($this->getConfig('SANDBOX'))
			$bluesnap_url = self::SANDBOX_CHECKOUT_URL;
		else
			$bluesnap_url = self::CHECKOUT_URL;

		$bluesnap_url .= '?';
		$currency = Currency::getCurrency($this->context->cart->id_currency);
		$bluesnap_params = array(
			'storeId' => (int)$this->getConfig('STORE'),
			'currency' => $currency['iso_code'],
			'email' => Context::getContext()->cookie->email,
			//'language' => Context::getContext()->language->name,
			'sku'.$this->getConfig('CONTRACT') => 1,
			'custom1' => $this->context->cart->id,
		);
		if ($api_lang = BluesnapApi::getLangByIso(Context::getContext()->language->iso_code))
			$bluesnap_params['language'] = $api_lang;

		$this->billingAddressParams($this->context->cart, $bluesnap_params);
		//$this->shippingAddressParams($this->context->cart, $bluesnap_params);

		$bluesnap_url .= http_build_query($bluesnap_params, '', '&');
		$enc = $this->api->paramEncryption(
				array(
					"sku{$this->getConfig('CONTRACT')}priceamount" => $this->context->cart->getOrderTotal(),
					"sku{$this->getConfig('CONTRACT')}name" => $this->getCartItemOverrideName($this->context->cart),
					"sku{$this->getConfig('CONTRACT')}pricecurrency" => $currency['iso_code'],
					'expirationInMinutes' => 90,
		));
        if (!$enc) {
            return null;
        }

		$bluesnap_url .= '&enc='.$enc;

		return $bluesnap_url;
	}

	public function billingAddressParams($cart_obj, &$bluesnap_params)
	{
		$invoice_address = new Address($cart_obj->id_address_invoice);
		$country = new Country($invoice_address->id_country);
		$state = new State($invoice_address->id_state);

		$bluesnap_params['firstName'] = $invoice_address->firstname;
		$bluesnap_params['lastName'] = $invoice_address->lastname;
		//$bluesnap_params['address1'] = $invoice_address->address1;
		$bluesnap_params['country'] = $country->iso_code;
		$bluesnap_params['state'] = $state->iso_code;
		//$bluesnap_params['city'] = $invoice_address->city;
		//$bluesnap_params['zipCode'] = $invoice_address->postcode;
		//$bluesnap_params['phone'] = isset($invoice_address->phone_mobile) ? $invoice_address->phone_mobile : $invoice_address->phone;
	}

	public function shippingAddressParams($cart_obj, &$bluesnap_params)
	{
		$delivery_address = new Address($cart_obj->id_address_delivery);
		$country = new Country($delivery_address->id_country);
		$state = new State($delivery_address->id_state);

		$bluesnap_params['shippingFirstName'] = $delivery_address->firstname;
		$bluesnap_params['shippingLastName'] = $delivery_address->lastname;
		$bluesnap_params['shippingAddress1'] = $delivery_address->address1;
		$bluesnap_params['shippingCountry'] = $country->iso_code;
		$bluesnap_params['shippingState'] = $state->iso_code;
		$bluesnap_params['shippingCity'] = $delivery_address->city;
		$bluesnap_params['shippingZipCode'] = $delivery_address->postcode;
		$bluesnap_params['shippingPhone'] = isset($delivery_address->phone_mobile) ? $delivery_address->phone_mobile : $delivery_address->phone;
	}

	/**
	 * return string for custom1 param (prestashop_order_id)
	 *
	 * @param Order $order
	 * @return string
	 */
	/*private function getOrderItemOverrideName(Order $order)
	{
		return $this->l('Order reference #').$order->reference;
	}*/

	/**
	 * return string for custom1 param (prestashop_order_id)
	 *
	 * @param Cart $order
	 * @return string
	 */
	private function getCartItemOverrideName(Cart $cart)
	{
		return $this->l('Cart #').$cart->id;
	}

	/**
	 * return orders amount
	 *
	 * @param Order $order
	 * @return string
	 */
	/*private function getAmountByReference($reference)
	{
		$orders_collection = Order::getByReference($reference);
		$amount = 0;
		foreach ($orders_collection->getResults() as $order)
			$amount += $order->total_paid;

		return $amount;
	}*/

	/**
	 * save log file
	 *
	 * @param $string
	 * @param null $file
	 */
	public static function log($string, $file = null)
	{
		if (empty($file))
			$file = self::LOG_FILE;

		$file = dirname(__FILE__).DS.$file;
		file_put_contents($file, $string.' - '.date('Y-m-d H:i:s')."\n", FILE_APPEND | LOCK_EX);
	}

	public function hookDisplayOrderConfirmation($params)
	{
		if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name))
			return false;
		if (isset($params['objOrder']) && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid) &&
				version_compare(_PS_VERSION_, '1.5', '>=') && isset($params['objOrder']->reference))
		{
			$this->smarty->assign('bluesnap_order', array(
				'id' => $params['objOrder']->id,
				'reference' => $params['objOrder']->reference,
				'valid' => $params['objOrder']->valid,
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false)
				)
			);
			return $this->display(__FILE__, $this->getTemplate('front', 'order-confirmation.tpl'));
		}
	}

}
