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

/**
 * Class bluesnapcallbackModuleFrontController
 *
 * after placing order at BlueSnap payment gateway uses redirect to this controller
 */
class BluesnapCallbackModuleFrontController extends
	ModuleFrontController
{
	/**
	 * flag allow use ssl for this controller
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * hide header and footer
	 */
	public function init()
	{
		$_GET['content_only'] = 1;
		parent::init();
	}

	/**
	 * redirect to order-confirmation (success) page
	 */
	public function initContent()
	{
		parent::initContent();

		//create order
		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		$id_cart = $this->context->cart->id;
		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$this->module->validateOrder((int)$this->context->cart->id,
				Configuration::get('BS_OS_WAITING'), $total, $this->module->displayName, null, array(), null, false,
				$customer->secure_key);

		//change order status if needed
		$order_obj = new Order($this->module->currentOrder);
		$order_state_obj = new OrderState(Configuration::get('BS_OS_PAYMENT_VALID'));
		if ($order_obj->current_state != $order_state_obj->id)
		{
			$bluesnap_info = BluesnapOrder::getByPsCartId($id_cart);
			if (isset($bluesnap_info['bluesnap_reference']) && !empty($bluesnap_info['bluesnap_reference']))
			{
				$ipn_obj = new BluesnapIpn();
				$ipn_obj->changeOrderStatus($order_obj, (int)Configuration::get('BS_OS_PAYMENT_VALID'), $this->errors);
			}
		}
		Configuration::updateValue('BLUESNAP_CONFIGURATION_OK', true);

		$this->context->smarty->assign(array(
			'bluesnap_order_confirmation_url' => Context::getContext()->link->getPageLink('order-confirmation', NULL, NULL, array(
				'id_order' => $this->module->currentOrder, 
				'id_cart' => $id_cart,
				'id_module' => $this->module->id, 
				'key' => $order_obj->secure_key,
				))
		));

		$this->setTemplate('callback.tpl');
	}

}
