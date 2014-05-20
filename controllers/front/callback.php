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
		$this->context->smarty->assign(array(
			'bluesnap_order_confirmation_url' => Context::getContext()->link->getPageLink('order-confirmation')
		));

		$this->setTemplate('callback.tpl');
	}

}
