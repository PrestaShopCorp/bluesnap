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

{capture name=path}{l s='Place Order' mod='bluesnap'}{/capture}

<h1 class="page-heading">{l s='Place Order' mod='bluesnap'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('bluesnap', 'checkout', [], true)|escape:'htmlall':'UTF-8'}" method="post" class="bluesnap_purchase_form">
    <div class="box cheque-box">
        <h3 class="page-subheading">{l s='BlueSnap payment' mod='bluesnap'}</h3>
        <input type="hidden" name="confirm" value="1" />
        <div class="bluesnap_container">
            <div class="bluesnap_logo_left">
                <img src="{$this_path|escape:'htmlall':'UTF-8'}img/logos.png" alt="{l s='BlueSnap payment' mod='bluesnap'}" />
            </div>
            <div>
                <p>{l s='You have chosen to pay with BlueSnap’s local payment options.' mod='bluesnap'}</p>
                <p>{l s='The total amount of your order is' mod='bluesnap'}
                    <span id="amount_{$currencies.0.id_currency|intval}" class="price bluesnap_price">{convertPrice price=$total}</span>
                    {if $use_taxes == 1}
                        {l s='(tax incl.)' mod='bluesnap'}
                    {/if}
                </p>
            </div>
            <div class="clear"></div>
        </div>
        <p>
            <b>{l s='To complete your purchase, you’ll be directed to a secure order page with local payment options displayed based on your country' mod='bluesnap'}.</b>
        </p>
    </div>

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='bluesnap'}
        </a>
        <button type="submit" class="button btn btn-default button-medium">
            <span>{l s='Continue to secure order form' mod='bluesnap'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
    {*<p class="cart_navigation">
        <a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}?step=3" class="button_large">{l s='Other payment methods' mod='bluesnap'}</a>
        <input type="submit" name="submit" value="{l s='Continue to secure order form' mod='bluesnap'}" class="exclusive_large" />
    </p>*}
</form>