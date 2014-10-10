{*
* 2007-2013 PrestaShop
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
* *************************************** */
/* Do not edit or add to this file if you wish to upgrade Prestashop to newer
* versions in the future.
* ****************************************************
* @category   Belvg
* @package    Belvg_BlueSnap
* @author    Alexander Simonchik <support@belvg.com>
* @site
* @copyright  Copyright (c) 2010 - 2013 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*}

{if $bluesnap_order.valid == 1}
<div class="conf confirmation">
	{l s='Congratulations! Your payment is done, and your order has been saved under' mod='bluesnap'}
	{if isset($bluesnap_order.reference)}
		{l s='the reference' mod='bluesnap'} <b>{$bluesnap_order.reference|escape:html:'UTF-8'}</b>
	{else}
		{l s='the ID' mod='bluesnap'} <b>{$bluesnap_order.id|escape:html:'UTF-8'}</b>
	{/if}.
	<br /><br />
	{l s='The total amount of this order is' mod='bluesnap'} <span class="price">{$bluesnap_order.total_to_pay|escape:'htmlall':'UTF-8'}</span>
</div>
{else}
<div class="error">
	{l s='Unfortunately, an error occurred during the transaction.' mod='bluesnap'}<br /><br />
	{if isset($bluesnap_order.reference)}
		({l s='Your Order\'s Reference:' mod='bluesnap'} <b>{$bluesnap_order.reference|escape:html:'UTF-8'}</b>)
	{else}
		({l s='Your Order\'s ID:' mod='bluesnap'} <b>{$bluesnap_order.id|escape:html:'UTF-8'}</b>)
	{/if}
</div>
{/if}