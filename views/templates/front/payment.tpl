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
* *************************************** */
/* Do not edit or add to this file if you wish to upgrade Prestashop to newer
* versions in the future.
* ****************************************************
* @category   Belvg
* @package    Belvg_BlueSnap
* @author    Alexander Simonchik <support@belvg.com>
* @site
* @copyright  Copyright (c) 2010 - 2014 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt 
*}

<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="payment_module" id="bluesnap_payment_module">
            <a href="{$bluesnap_url|escape:'htmlall':'UTF-8'}" title="{l s='Local credit cards and payment options (by BlueSnap) based on your country.' mod='bluesnap'}" class="bluesnap">
                <p>{l s='Local credit cards and payment options (by BlueSnap) based on your country.' mod='bluesnap'}</p>
                <p><img src="{$bluesnap_path|escape:'htmlall':'UTF-8'}img/logos.png" alt="{l s='Local credit cards and payment options (by BlueSnap) based on your country.' mod='bluesnap'}" id="bluesnap_img" /></p>
                <br class="clearfix" />
            </a>
        </div>
    </div>
</div>