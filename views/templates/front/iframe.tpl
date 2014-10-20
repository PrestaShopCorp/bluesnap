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

{literal}
<script type="text/javascript">
    $('body').click(function(){
        $('html, body').animate({
            scrollTop: $("iframe").offset().top
        }, 1000);
        return false;
    })

    $( document ).ready(function() {
        $("#footer, .sf-contener, #header").hide();
    });
</script>
{/literal}


<div id="bluesnap_header" class="grid_9 alpha omega">
    <a id="bluesnap_logo" href="{$base_dir}" title="{$shop_name|escape:'htmlall':'UTF-8'}">
        <img class="logo" src="{$logo_url}" alt="{$shop_name|escape:'htmlall':'UTF-8'}" {if $logo_image_width}width="{$logo_image_width}"{/if} {if $logo_image_height}height="{$logo_image_height}" {/if}/>
    </a>
    <div id="bluesnap_header_right" class="grid_9 omega"></div>
</div>

{include file="./shopping-cart.tpl"}
{if $bluesnap_iframe_url}
<iframe width="100%" height="1000px" src="{$bluesnap_iframe_url|escape:'htmlall':'UTF-8'}"></iframe>
{else}
    <div class="alert alert-warning">
        {l s='We can\'t process your payment. Please, contact the store owner.' mod='bluesnap'} {*<a href="{$link->getPageLink('contact')}">{l s='Contact us' mod='bluesnap'}</a>*}
    </div>
{/if}