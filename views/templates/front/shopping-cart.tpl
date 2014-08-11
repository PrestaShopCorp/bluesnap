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

<div id="order-detail-content" class="table_block table-responsive">
    <table id="cart_summary" class="table table-bordered">
    <thead>
    <tr>
        <th class="cart_product first_item">{l s='Product' mod='bluesnap'}</th>
        <th class="cart_description item">{l s='Description' mod='bluesnap'}</th>
        <th class="cart_ref item">{l s='Ref.' mod='bluesnap'}</th>
        <th class="cart_unit item">{l s='Unit price' mod='bluesnap'}</th>
        <th class="cart_quantity item">{l s='Qty' mod='bluesnap'}</th>
        <th class="cart_total item">{l s='Total' mod='bluesnap'}</th>
        <th class="cart_delete last_item">&nbsp;</th>
    </tr>
    </thead>
    <tfoot>
    {if $use_taxes}
        {if $priceDisplay}
            <tr class="cart_total_price">
                <td colspan="5">{if $display_tax_label}{l s='Total products (tax excl.)' mod='bluesnap'}{else}{l s='Total products' mod='bluesnap'}{/if}</td>
                <td colspan="2" class="price" id="total_product">{displayPrice price=$total_products}</td>
            </tr>
        {else}
            <tr class="cart_total_price">
                <td colspan="5">{if $display_tax_label}{l s='Total products (tax incl.)' mod='bluesnap'}{else}{l s='Total products' mod='bluesnap'}{/if}</td>
                <td colspan="2" class="price" id="total_product">{displayPrice price=$total_products_wt}</td>
            </tr>
        {/if}
    {else}
        <tr class="cart_total_price">
            <td colspan="5">{l s='Total products' mod='bluesnap'}</td>
            <td colspan="2" class="price" id="total_product">{displayPrice price=$total_products}</td>
        </tr>
    {/if}
    <tr{if $total_wrapping == 0} style="display: none;"{/if}>
        <td colspan="5">
            {if $use_taxes}
                {if $display_tax_label}{l s='Total gift wrapping (tax incl.):' mod='bluesnap'}{else}{l s='Total gift-wrapping cost:' mod='bluesnap'}{/if}
            {else}
                {l s='Total gift-wrapping cost:' mod='bluesnap'}
            {/if}
        </td>
        <td colspan="2" class="price-discount price" id="total_wrapping">
            {if $use_taxes}
                {if $priceDisplay}
                    {displayPrice price=$total_wrapping_tax_exc}
                {else}
                    {displayPrice price=$total_wrapping}
                {/if}
            {else}
                {displayPrice price=$total_wrapping_tax_exc}
            {/if}
        </td>
    </tr>
    {if $total_shipping_tax_exc <= 0 && !isset($virtualCart)}
		{if !isset($carrier->id) || is_null($carrier->id)}
			<tr class="cart_total_delivery">
				<td colspan="5">{l s='Shipping' mod='bluesnap'}</td>
				<td colspan="2" class="price" id="total_shipping">{l s='Free Shipping!' mod='bluesnap'}</td>
			</tr>
		{/if}
    {else}
        {if $use_taxes && $total_shipping_tax_exc != $total_shipping}
            {if $priceDisplay}
                <tr class="cart_total_delivery" {if $total_shipping_tax_exc <= 0} style="display:none;"{/if}>
                    <td colspan="5">{if $display_tax_label}{l s='Total shipping (tax excl.)' mod='bluesnap'}{else}{l s='Total shipping' mod='bluesnap'}{/if}</td>
                    <td colspan="2" class="price" id="total_shipping">{displayPrice price=$total_shipping_tax_exc}</td>
                </tr>
            {else}
                <tr class="cart_total_delivery"{if $total_shipping <= 0} style="display:none;"{/if}>
                    <td colspan="5">{if $display_tax_label}{l s='Total shipping (tax incl.)' mod='bluesnap'}{else}{l s='Total shipping' mod='bluesnap'}{/if}</td>
                    <td colspan="2" class="price" id="total_shipping" >{displayPrice price=$total_shipping}</td>
                </tr>
            {/if}
        {else}
            <tr class="cart_total_delivery"{if $total_shipping_tax_exc <= 0} style="display:none;"{/if}>
                <td colspan="5">{l s='Total shipping' mod='bluesnap'}</td>
                <td colspan="2" class="price" id="total_shipping" >{displayPrice price=$total_shipping_tax_exc}</td>
            </tr>
        {/if}
    {/if}
    <tr class="cart_total_voucher" {if $total_discounts == 0}style="display:none"{/if}>
        <td colspan="5">
            {if $display_tax_label}
                {if $use_taxes && $priceDisplay == 0}
                    {l s='Total vouchers (tax incl.):' mod='bluesnap'}
                {else}
                    {l s='Total vouchers (tax excl.)' mod='bluesnap'}
                {/if}
            {else}
                {l s='Total vouchers' mod='bluesnap'}
            {/if}
        </td>
        <td colspan="2" class="price-discount price" id="total_discount">
            {if $use_taxes && $priceDisplay == 0}
                {assign var='total_discounts_negative' value=$total_discounts * -1}
            {else}
                {assign var='total_discounts_negative' value=$total_discounts_tax_exc * -1}
            {/if}
            {displayPrice price=$total_discounts_negative}
        </td>
    </tr>
    {if $use_taxes && $show_taxes}
        <tr class="cart_total_price">
            <td colspan="5">{l s='Total (tax excl.)' mod='bluesnap'}</td>
            <td colspan="2" class="price" id="total_price_without_tax">{displayPrice price=$total_price_without_tax}</td>
        </tr>
        <tr class="cart_total_tax">
            <td colspan="5">{l s='Total tax' mod='bluesnap'}</td>
            <td colspan="2" class="price" id="total_tax">{displayPrice price=$total_tax}</td>
        </tr>
    {/if}
    <tr class="cart_total_price">
        <td colspan="5" id="cart_voucher" class="cart_voucher">
            {if $voucherAllowed}
                {if isset($errors_discount) && $errors_discount}
                    <ul class="error">
                        {foreach $errors_discount as $k=>$error}
                            <li>{$error|escape:'htmlall':'UTF-8'}</li>
                        {/foreach}
                    </ul>
                {/if}
            {/if}
        </td>
        {if $use_taxes}
            <td colspan="2" class="price total_price_container" id="total_price_container">
                <p>{l s='Total' mod='bluesnap'}</p>
                <span id="total_price">{displayPrice price=$total_price}</span>
            </td>
        {else}
            <td colspan="2" class="price total_price_container" id="total_price_container">
                <p>{l s='Total' mod='bluesnap'}</p>
                <span id="total_price">{displayPrice price=$total_price_without_tax}</span>
            </td>
        {/if}
    </tr>
    </tfoot>
    <tbody>
    {assign var='odd' value=0}
    {foreach $products as $product}
        {assign var='productId' value=$product.id_product}
        {assign var='productAttributeId' value=$product.id_product_attribute}
        {assign var='quantityDisplayed' value=0}
        {assign var='odd' value=($odd+1)%2}
        {assign var='ignoreProductLast' value=isset($customizedDatas.$productId.$productAttributeId) || count($gift_products)}
    {* Display the product line *}
        {include file="./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}
    {* Then the customized datas ones*}
        {if isset($customizedDatas.$productId.$productAttributeId)}
            {foreach $customizedDatas.$productId.$productAttributeId[$product.id_address_delivery] as $id_customization=>$customization}
                <tr id="product_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{$id_customization|intval}_{$product.id_address_delivery|intval}" class="product_customization_for_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{$product.id_address_delivery|intval}{if $odd} odd{else} even{/if} customization alternate_item {if $product@last && $customization@last && !count($gift_products)}last_item{/if}">
                    <td></td>
                    <td colspan="3">
                        {foreach $customization.datas as $type => $custom_data}
                            {if $type == $CUSTOMIZE_FILE}
                                <div class="customizationUploaded">
                                    <ul class="customizationUploaded">
                                        {foreach $custom_data as $picture}
                                            <li><img src="{$pic_dir|escape:'htmlall':'UTF-8'}{$picture.value|escape:'htmlall':'UTF-8'}_small" alt="" class="customizationUploaded" /></li>
                                        {/foreach}
                                    </ul>
                                </div>
                            {elseif $type == $CUSTOMIZE_TEXTFIELD}
                                <ul class="typedText">
                                    {foreach $custom_data as $textField}
                                        <li>
                                            {if $textField.name}
                                                {$textField.name|escape:'htmlall':'UTF-8'}
                                            {else}
                                                {l s='Text #' mod='bluesnap'}{$textField@index+1|escape:'htmlall':'UTF-8'}
                                            {/if}
                                            {l s=':' mod='bluesnap'} {$textField.value|escape:'htmlall':'UTF-8'}
                                        </li>
                                    {/foreach}

                                </ul>
                            {/if}

                        {/foreach}
                    </td>
                    <td class="cart_quantity" colspan="3">
                        {if isset($cannotModify) AND $cannotModify == 1}
                            <span class="span-left">{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count|escape:'htmlall':'UTF-8'}{else}{$product.cart_quantity-$quantityDisplayed|escape:'htmlall':'UTF-8'}{/if}</span>
                        {else}
                            <input type="hidden" value="{$customization.quantity|escape:'htmlall':'UTF-8'}" name="quantity_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{$id_customization|intval}_{$product.id_address_delivery|intval}_hidden"/>
                            <input size="2" type="text" value="{$customization.quantity|escape:'htmlall':'UTF-8'}" class="cart_quantity_input" name="quantity_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{$id_customization|intval}_{$product.id_address_delivery|intval}"/>
                        {/if}
                    </td>
                </tr>
                {assign var='quantityDisplayed' value=$quantityDisplayed+$customization.quantity}
            {/foreach}
        {* If it exists also some uncustomized products *}
            {if $product.quantity-$quantityDisplayed > 0}{include file="$tpl_dir./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}{/if}
        {/if}
    {/foreach}
    {assign var='last_was_odd' value=$product@iteration%2}
    {foreach $gift_products as $product}
        {assign var='productId' value=$product.id_product}
        {assign var='productAttributeId' value=$product.id_product_attribute}
        {assign var='quantityDisplayed' value=0}
        {assign var='odd' value=($product@iteration+$last_was_odd)%2}
        {assign var='ignoreProductLast' value=isset($customizedDatas.$productId.$productAttributeId)}
        {assign var='cannotModify' value=1}
    {* Display the gift product line *}
        {include file="$tpl_dir./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}
    {/foreach}
    </tbody>
    {if sizeof($discounts)}
        <tbody>
        {foreach $discounts as $discount}
            <tr class="cart_discount {if $discount@last}last_item{elseif $discount@first}first_item{else}item{/if}" id="cart_discount_{$discount.id_discount|intval}">
                <td class="cart_discount_name" colspan="3">{$discount.name|escape:'htmlall':'UTF-8'}</td>
                <td class="cart_discount_price"><span class="price-discount">
                        {if !$priceDisplay}{displayPrice price=$discount.value_real*-1}{else}{displayPrice price=$discount.value_tax_exc*-1}{/if}
                    </span></td>
                <td class="cart_discount_delete">1</td>
                <td class="cart_discount_price" colspan="2">
                    <span class="price-discount price">{if !$priceDisplay}{displayPrice price=$discount.value_real*-1}{else}{displayPrice price=$discount.value_tax_exc*-1}{/if}</span>
                </td>
            </tr>
        {/foreach}
        </tbody>
    {/if}
    </table>
</div>