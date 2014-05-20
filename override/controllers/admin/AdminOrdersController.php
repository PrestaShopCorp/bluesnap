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

class AdminOrdersController extends AdminOrdersControllerCore {

	public function initToolbar()
	{
		if ($this->display == 'view')
		{
			$order = new Order((int)Tools::getValue('id_order'));
			if ($order->hasBeenShipped())
				$type = $this->l('Return products');
			elseif ($order->hasBeenPaid())
				$type = $this->l('Standard refund');
			else
				$type = $this->l('Cancel products');

			if (!$order->hasBeenShipped() && !$this->lite_display)
			{
				$this->toolbar_btn['new'] = array(
					'short' => 'Create',
					'href' => '#',
					'desc' => $this->l('Add a product'),
					'class' => 'add_product'
				);
			}

			$bluesnap_info = BluesnapOrder::getByPsOrderReference($order->reference);
			if (isset($bluesnap_info['bluesnap_reference']) && !empty($bluesnap_info['bluesnap_reference']))
				$this->restrictRefund();
			else
			{
				if (Configuration::get('PS_ORDER_RETURN') && !$this->lite_display)
				{
					$this->toolbar_btn['standard_refund'] = array(
						'short' => 'Create',
						'href' => '',
						'desc' => $type,
						'class' => 'process-icon-standardRefund'
					);
				}

				if ($order->hasInvoice() && !$this->lite_display)
				{
					$this->toolbar_btn['partial_refund'] = array(
						'short' => 'Create',
						'href' => '',
						'desc' => $this->l('Partial refund'),
						'class' => 'process-icon-partialRefund'
					);
				}
			}
		}

		$res = AdminController::initToolbar();
		if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->toolbar_btn['new']) &&
					Shop::isFeatureActive())
			unset($this->toolbar_btn['new']);

		return $res;
	}

	protected function restrictRefund()
	{
		//restrict refund for BlueSnap payment
		return false;
	}

	public function renderView()
	{
		$order = new Order(Tools::getValue('id_order'));
		$bluesnap_info = BluesnapOrder::getByPsOrderReference($order->reference);
		if (isset($bluesnap_info['bluesnap_reference']) && !empty($bluesnap_info['bluesnap_reference']))
		{
			//restrict refund for BlueSnap payment
			return parent::renderView();
		}

		if (!Validate::isLoadedObject($order))
			throw new PrestaShopException('object can\'t be loaded');

		$customer = new Customer($order->id_customer);
		$carrier = new Carrier($order->id_carrier);
		$products = $this->getProducts($order);
		$currency = new Currency((int)$order->id_currency);
		// Carrier module call
		$carrier_module_call = null;
		if ($carrier->is_module)
		{
			$module = Module::getInstanceByName($carrier->external_module_name);
			if (method_exists($module, 'displayInfoByCart'))
				$carrier_module_call = call_user_func(array($module, 'displayInfoByCart'), $order->id_cart);
		}

		// Retrieve addresses information
		$address_invoice = new Address($order->id_address_invoice, $this->context->language->id);
		if (Validate::isLoadedObject($address_invoice) && $address_invoice->id_state)
			$invoice_state = new State((int)$address_invoice->id_state);

		if ($order->id_address_invoice == $order->id_address_delivery)
		{
			$address_delivery = $address_invoice;
			if (isset($invoice_state))
				$delivery_state = $invoice_state;
		}
		else
		{
			$address_delivery = new Address($order->id_address_delivery, $this->context->language->id);
			if (Validate::isLoadedObject($address_delivery) && $address_delivery->id_state)
				$delivery_state = new State((int)$address_delivery->id_state);
		}

		$this->toolbar_title = sprintf($this->l('Order #%1$d (%2$s) - %3$s %4$s'), $order->id, $order->reference,
				$customer->firstname, $customer->lastname);
		if (Shop::isFeatureActive())
		{
			$shop = new Shop((int)$order->id_shop);
			$this->toolbar_title .= ' - '.sprintf($this->l('Shop: %s'), $shop->name);
		}

		// gets warehouses to ship products, if and only if advanced stock management is activated
		$warehouse_list = null;

		$order_details = $order->getOrderDetailList();
		foreach ($order_details as $order_detail)
		{
			$product = new Product($order_detail['product_id']);

			if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management)
			{
				$warehouses = Warehouse::getWarehousesByProductId($order_detail['product_id'],
						$order_detail['product_attribute_id']);
				foreach ($warehouses as $warehouse)
				{
					if (!isset($warehouse_list[$warehouse['id_warehouse']]))
						$warehouse_list[$warehouse['id_warehouse']] = $warehouse;
				}
			}
		}

		$payment_methods = array();
		foreach (PaymentModule::getInstalledPaymentModules() as $payment)
		{
			$module = Module::getInstanceByName($payment['name']);
			if (Validate::isLoadedObject($module) && $module->active)
				$payment_methods[] = $module->displayName;
		}

		// display warning if there are products out of stock
		$display_out_of_stock_warning = false;
		$current_order_state = $order->getCurrentOrderState();
		if (Configuration::get('PS_STOCK_MANAGEMENT') && (!Validate::isLoadedObject($current_order_state) ||
				($current_order_state->delivery != 1 && $current_order_state->shipped != 1)))
			$display_out_of_stock_warning = true;

		// products current stock (from stock_available)
		foreach ($products as &$product)
		{
			$product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'],
					$product['product_attribute_id'], $product['id_shop']);

			$resume = OrderSlip::getProductSlipResume($product['id_order_detail']);
			$product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
			$product['amount_refundable'] = $product['total_price_tax_incl'] - $resume['amount_tax_incl'];
			$product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl'], $currency);
			$product['refund_history'] = OrderSlip::getProductSlipDetail($product['id_order_detail']);
			$product['return_history'] = OrderReturn::getProductReturnDetail($product['id_order_detail']);

			// if the current stock requires a warning
			if ($product['current_stock'] == 0 && $display_out_of_stock_warning)
				$this->displayWarning($this->l('This product is out of stock: ').' '.$product['product_name']);

			if ($product['id_warehouse'] != 0)
			{
				$warehouse = new Warehouse((int)$product['id_warehouse']);
				$product['warehouse_name'] = $warehouse->name;
			}
			else
				$product['warehouse_name'] = '--';
		}

		$gender = new Gender((int)$customer->id_gender, $this->context->language->id);

		// Smarty assign
		$this->tpl_view_vars = array(
			'order' => $order,
			'cart' => new Cart($order->id_cart),
			'customer' => $customer,
			'gender' => $gender,
			'customer_addresses' => $customer->getAddresses($this->context->language->id),
			'addresses' => array(
				'delivery' => $address_delivery,
				'deliveryState' => isset($delivery_state) ? $delivery_state : null,
				'invoice' => $address_invoice,
				'invoiceState' => isset($invoice_state) ? $invoice_state : null
			),
			'customerStats' => $customer->getStats(),
			'products' => $products,
			'discounts' => $order->getCartRules(),
			'orders_total_paid_tax_incl' => $order->getOrdersTotalPaid(),
			'total_paid' => $order->getTotalPaid(),
			'returns' => OrderReturn::getOrdersReturn($order->id_customer, $order->id),
			'customer_thread_message' => CustomerThread::getCustomerMessages($order->id_customer, 0),
			'orderMessages' => OrderMessage::getOrderMessages($order->id_lang),
			'messages' => Message::getMessagesByOrderId($order->id, true),
			'carrier' => new Carrier($order->id_carrier),
			'history' => $order->getHistory($this->context->language->id),
			'states' => BluesnapOrder::getOrderStates($order->id, $this->context->language->id),
			'warehouse_list' => $warehouse_list,
			'sources' => ConnectionsSource::getOrderSources($order->id),
			'currentState' => $order->getCurrentOrderState(),
			'currency' => new Currency($order->id_currency),
			'currencies' => Currency::getCurrencies(),
			'previousOrder' => $order->getPreviousOrderId(),
			'nextOrder' => $order->getNextOrderId(),
			'current_index' => self::$currentIndex,
			'carrierModuleCall' => $carrier_module_call,
			'iso_code_lang' => $this->context->language->iso_code,
			'id_lang' => $this->context->language->id,
			'can_edit' => ($this->tabAccess['edit'] == 1),
			'current_id_lang' => $this->context->language->id,
			'invoices_collection' => $order->getInvoicesCollection(),
			'not_paid_invoices_collection' => $order->getNotPaidInvoicesCollection(),
			'payment_methods' => $payment_methods,
			'invoice_management_active' => Configuration::get('PS_INVOICE', null, null, $order->id_shop),
			'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
		);

		return AdminController::renderView();
	}

}
