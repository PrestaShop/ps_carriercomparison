{*
* 2007-2015 PrestaShop
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
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div class="js-carrier-compare"
     data-url="{url entity='module' name='ps_carriercomparison' controller='carrier_comparison'}"
     data-item="{$carrierComparisonInfo|@json_encode}"
     data-method="{$refreshMethod}">
  <form method="post" action="#">
    <fieldset>
      <h3>{l s='Estimate the cost of shipping & taxes.' d='Modules.Carriercomparison.Shop'}</h3>
      <label>{l s='Country' d='Modules.Carriercomparison.Shop'}</label>
      <select name="id_country">
        {foreach from=$countries item=country}
          <option value="{$country.id_country}" {if $carrierComparisonInfo['idCountry'] == $country.id_country}selected="selected"{/if}>{$country.name}</option>
        {/foreach}
      </select>
      <div class="js-states">
        <label>{l s='State' d='Modules.Carriercomparison.Shop'}</label>
        <select name="id_state">
          <option></option>
        </select>
      </div>
      <label>{l s='Zip Code' d='Modules.Carriercomparison.Shop'}</label>
      <input type="text" name="zipcode" value="{$carrierComparisonInfo['zipCode']}"/> ({l s='Needed for certain carriers.' d='Modules.Carriercomparison.Shop'})
      <div class="js-carriers" style="display: none;">
        <table>
          <thead>
          <tr>
            <th></th>
            <th>{l s='Carrier' d='Modules.Carriercomparison.Shop'}</th>
            <th>{l s='Information' d='Modules.Carriercomparison.Shop'}</th>
            <th>{l s='Price' d='Modules.Carriercomparison.Shop'}</th>
          </tr>
          </thead>
          <tbody id="carriers_list">

          </tbody>
        </table>
      </div>
      <input class="js-carrier-compare-submit" type="button" value="{l s='Update cart' d='Modules.Carriercomparison.Shop'}"/>
      <input class="js-carrier-compare-estimate" type="button" value="{l s='Estimate Shipping Cost' d='Modules.Carriercomparison.Shop'}" />
    </fieldset>
  </form>
</div>
