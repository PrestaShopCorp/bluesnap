{*
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
* @category   Belvg
* @package    Belvg_BlueSnap
* @author    Alexander Simonchik <support@belvg.com>
* @site
* @copyright  Copyright (c) 2010 - 2014 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*}
<div class="col-lg-12">
    <div class="panel">
        <fieldset>
            <legend><img src="../img/admin/money.gif">{l s='Full refund via BlueSnap' mod='bluesnap'}</legend>

            {if !$bluesnap_refunded}
                <form method="post" action="" name="refund">
                    <div>{l s='BlueSnap order reference number:' mod='bluesnap'} <b>{$bluesnap_reference_number|escape:'htmlall':'UTF-8'}</b></div>
                    <p></p>
                    <input type="hidden" name="bluesnap_reference_number" value="{$bluesnap_reference_number|escape:'htmlall':'UTF-8'}" />
                    <input type="hidden" name="id_bluesnap_order" value="{$id_bluesnap_order|escape:'htmlall':'UTF-8'}" />
                    <input type="submit" name="process_bluesnap_refund" value ="{l s='Process Full Refund' mod='bluesnap'}" class="btn btn-default" />
                </form>
            {else}
                <div class="alert alert-warning">{l s='Refunded' mod='bluesnap'}</div>
            {/if}
        </fieldset>

        {literal}
        <script type="text/javascript">
            $("input[name=process_bluesnap_refund]").click(function(){
                if (confirm('{/literal}{l s='Are you sure you want to refund this order? This action cannot be undone' mod='bluesnap'}{literal}')) {
                    return true;
                } else {
                    event.stopPropagation();
                    event.preventDefault();
                };
            });
        </script>
        {/literal}
    </div>
</div>