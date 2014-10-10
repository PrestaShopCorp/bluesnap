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
 * BlueSnap Order Object Model
 *
 *
 */
class BluesnapOrder extends ObjectModel {

	public $id;
	public $id_bluesnap_order;
	public $id_cart;
	public $bluesnap_reference;
	public $refunded = 0;
	public static $definition = array(
		'table' => 'bluesnap_order',
		'primary' => 'id_bluesnap_order',
		'multilang' => false,
		'fields' => array(
			'id_cart' => array('type' => self::TYPE_INT, 'required' => true),
			'bluesnap_reference' => array('type' => self::TYPE_INT, 'required' => true),
			'refunded' => array('type' => self::TYPE_BOOL),
		),
	);

	public static function getByPsCartId($id_cart, $refunded = false)
	{
		return Db::getInstance()->getRow(
			'SELECT * FROM `'._DB_PREFIX_.'bluesnap_order`
			WHERE 1 '.
			($refunded ? 'AND refunded != 1 ' : '').'
			AND id_cart = "'.pSQL($id_cart).'"'
		);
	}

	/**
	 * Get all available order states
	 *
	 * @param integer $id_lang Language id for state name
	 * @return array Order states
	 */
	public static function getOrderStates($id_order, $id_lang)
	{
		$states = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'order_state` os
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state`
				AND osl.`id_lang` = '.(int)$id_lang.')
            WHERE deleted = 0
            ORDER BY `name` ASC
        ');

		$order_obj = new Order($id_order);
		$bluesnap_info = BluesnapOrder::getByPsCartId($order_obj->id_cart);
		if (isset($bluesnap_info['bluesnap_reference']) && !empty($bluesnap_info['bluesnap_reference']))
			return $states;
		else
		{
			foreach ($states as $key => $state)
			{
				if ($state['id_order_state'] == Configuration::get('BS_OS_PAYMENT_VALID'))
					unset($states[$key]);
			}

			return $states;
		}
	}

}
