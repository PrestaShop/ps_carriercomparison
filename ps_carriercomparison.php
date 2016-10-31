<?php
/*
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
*/

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_CarrierComparison extends Module implements WidgetInterface
{
    public $template_directory = '';

    public function __construct()
    {
        $this->name = 'ps_carriercomparison';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->controllers = array('CarrierComparison');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans(
            'Shipping Estimate',
            array(),
            'Modules.Carriercomparison.Admin'
        );
        $this->description = $this->trans(
            'Compares carrier choices before checkout.',
            array(),
            'Modules.Carriercomparison.Admin'
        );
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('displayHeader');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('setGlobalConfiguration')) {
            if (Configuration::updateValue(
                'SE_RERESH_METHOD',
                (int)Tools::getValue('SE_RERESH_METHOD'))) {
                $output .= $this->displayConfirmation(
                    $this->trans(
                        'Settings updated',
                        array(),
                        'Admin.Global'
                    )
                );
            }
        }

        return $output.$this->renderForm();
    }

    public function hookHeader($params)
    {
        if ($this->isModuleAvailable()
            && isset($this->context->controller->php_self)
            && 'cart' === $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'ps_carriercomparison',
                __DIR__ . '/js/carriercompare.js'
            );
        }
    }

    protected function getIdCountry()
    {
        $idCountry = null;

        if (isset($this->context->cookie->id_country)
            && 0 < $this->context->cookie->id_country) {
            $idCountry = (int)$this->context->cookie->id_country;
        }
        if (!isset($idCountry)) {
            $idCountry = (isset($this->context->customer->geoloc_id_country) ?
                (int)$this->context->customer->geoloc_id_country :
                (int)Configuration::get('PS_COUNTRY_DEFAULT'));
        }
        if (isset($this->context->customer->id)
            && $this->context->customer->id
            && isset($this->context->cart->id_address_delivery)
            && $this->context->cart->id_address_delivery) {
            $address = new Address(
                (int)$this->context->cart->id_address_delivery
            );
            $idCountry = (int)$address->id_country;
        }

        return $idCountry;
    }

    protected function getIdState()
    {
        $idState = null;

        if (isset($this->context->cookie->id_state)
            && 0 < $this->context->cookie->id_state) {
            $idState = (int)$this->context->cookie->id_state;
        }
        if (!isset($idState)) {
            $idState = (isset($this->context->customer->geoloc_id_state) ?
                (int)$this->context->customer->geoloc_id_state :
                0);
        }

        return $idState;
    }

    protected function getZipCode()
    {
        $zipCode = null;

        if (isset($this->context->cookie->postcode)
            && 0 < $this->context->cookie->postcode) {
            $zipCode = Tools::safeOutput($this->context->cookie->postcode);
        }
        if (!isset($zipCode)) {
            $zipCode = (isset($this->context->customer->geoloc_postcode) ?
                $this->context->customer->geoloc_postcode :
                '');
        }

        return $zipCode;
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $carrierInfo = array(
            'idCountry' => $this->getIdCountry(),
            'idState' => $this->getIdState(),
            'zipCode' => $this->getZipCode(),
            'idCarrier' => ($configuration['cart']->id_carrier ?
                $configuration['cart']->id_carrier :
                Configuration::get('PS_CARRIER_DEFAULT')
            ),
            'txtFree' => $this->trans('Free', array(), 'Shop.Theme.Checkout'),
        );

        return array(
            'countries' => Country::getCountries(
                (int)$this->context->cookie->id_lang,
                true
            ),
            'carrierComparisonInfo' => $carrierInfo,
            'currencySign' => $this->context->currency->sign,
            'currencyRate' => $this->context->currency->conversion_rate,
            'currencyFormat' => $this->context->currency->format,
            'currencyBlank' => $this->context->currency->blank,
            'refreshMethod' => (bool)Configuration::get('SE_RERESH_METHOD'),
        );
    }

    public function renderWidget($hookName, array $configuration)
    {
        if (empty($configuration['cart']->getProducts())) {
            return;
        }

        $this->smarty->assign(
            $this->getWidgetVariables(
                $hookName,
                $configuration
            )
        );

        return $this->fetch(
            'module:ps_carriercomparison/template/ps_carriercomparison.tpl'
        );
    }

    /*
    ** Get states by Country id, called by the ajax process
    ** id_state allow to preselect the selection option
    */
    public function getStatesByIdCountry($idCountry, $idState = '')
    {
        $states = State::getStatesByIdCountry($idCountry);

        return (sizeof($states) ? $states : array());
    }

    /*
    ** Get carriers by country id, called by the ajax process
    */
    public function getCarriersListByIdZone(
        $idCountry,
        $idState = 0,
        $zipcode = 0
    ) {
        // cookie saving/updating
        $this->context->cookie->id_country = $idCountry;
        if ($idState != 0) {
            $this->context->cookie->id_state = $idState;
        }
        if ($zipcode != 0) {
            $this->context->cookie->postcode = $zipcode;
        }

        $id_zone = 0;
        if ($idState != 0) {
            $id_zone = State::getIdZone($idState);
        }
        if (!$id_zone) {
            $id_zone = Country::getIdZone($idCountry);
        }

        $carriers = Ps_CarrierComparison::getCarriersByCountry(
            $idCountry,
            $idState,
            $zipcode,
            $this->context->cart,
            $this->context->customer->id
        );

        return (sizeof($carriers) ? $carriers : array());
    }

    /*
     * Get all carriers available for this zon
     */
    public static function getCarriersByCountry(
        $idCountry,
        $idState,
        $zipcode,
        $existingCart,
        $idCustomer
    ) {
        // Create temporary Address
        $addrTemp = new Address();
        $addrTemp->id_customer = $idCustomer;
        $addrTemp->id_country = $idCountry;
        $addrTemp->id_state = $idState;
        $addrTemp->postcode = $zipcode;

        // Populate required attributes
        // Note: Some carrier needs the whole address
        // the '.' will do the job
        $addrTemp->firstname = ".";
        $addrTemp->lastname = ".";
        $addrTemp->address1 = ".";
        $addrTemp->city = ".";
        $addrTemp->alias = "TEMPORARY_ADDRESS_TO_DELETE";
        $addrTemp->save();

        $cart = new Cart();
        $cart->id_currency = $existingCart->id_currency;
        $cart->id_customer = $existingCart->id_customer;
        $cart->id_lang = $existingCart->id_lang;
        $cart->id_address_delivery = $addrTemp->id;
        $cart->add();

        $products = $existingCart->getProducts();
        foreach ($products as $key => $product) {
            $cart->updateQty(
                $product['quantity'],
                $product['id_product'],
                $product['id_product_attribute']
            );
        }

        $carriers = $cart->simulateCarriersOutput(null, true);

        foreach ($carriers as &$carrier) {
            if ($carrier['price']) {
                $carrier['price'] = Tools::displayPrice($carrier['price']);
            }
        }

        //delete temporary objects
        $addrTemp->delete();
        $cart->delete();

        return $carriers;
    }

    public function simulateSelection($carrierId, $idCountry, $idState)
    {
        $idCarrier = (int)Cart::desintifier($carrierId, '');
        $cartData = array();
        $rawCartData = array();
        if ($idState) {
            $idZone = State::getIdZone($idState);
        } else {
            $idZone = Country::getIdZone($idCountry);
        }

        $rawCartData['shipping_total'] =
            (float)$this->context->cart->getPackageShippingCost(
                $idCarrier,
                true,
                null,
                null,
                $idZone);
        $rawCartData['order_total'] =
            (float)$this->context->cart->getOrderTotal()
            - (float)$this->context->cart->getTotalShippingCost();
        $rawCartData['tax_total'] =
            (float)$this->context->cart->getOrderTotal(
                true,
                Cart::ONLY_PRODUCTS
            ) - (float)$this->context->cart->getOrderTotal(
                false,
                Cart::ONLY_PRODUCTS
            ) + (float)$this->context->cart->getPackageShippingCost(
                $idCarrier,
                true,
                new Country($idCountry),
                $this->context->cart->getProducts(),
                $idZone
            ) - (float)$this->context->cart->getPackageShippingCost(
                $idCarrier,
                false,
                new Country($idCountry),
                $this->context->cart->getProducts(),
                $idZone
            );

        $cartData['shipping_total'] =
            $rawCartData['shipping_total'] ?
                Tools::displayPrice($rawCartData['shipping_total']) :
                0;
        $cartData['tax_total'] =
            Tools::displayPrice($rawCartData['tax_total']);
        $cartData['total'] =
            Tools::displayPrice(
                $rawCartData['shipping_total']
                + $rawCartData['order_total']
            );

        return $cartData;
    }

    /**
     * This module is shown on front office, in only some conditions
     * @return bool
     */
    private function isModuleAvailable()
    {
        $fileName = basename($_SERVER['SCRIPT_FILENAME']);
        /**
         * This module is only available on standard order process because
         * on One Page Checkout the carrier list is already available.
         */
        if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
            return false;
        }
        /**
         * If visitor is logged, the module isn't available on Front office,
         * we use the account informations for carrier selection and taxes.
         */
        /*if (Context::getContext()->customer->id)
            return false;*/
        return true;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans(
                        'Settings',
                        array(),
                        'Admin.Global'
                    ),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->trans(
                            'How to refresh the carrier list?',
                            array(),
                            'Modules.Carriercomparison.Admin'
                        ),
                        'name' => 'SE_RERESH_METHOD',
                        'required' => false,
                        'desc' => $this->trans(
                            'This determines when the list of carriers' .
                            ' presented to the customer is updated.',
                            array(),
                            'Modules.Carriercomparison.Admin'
                        ),
                        'default_value' => 1,
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 1,
                                    'name' => $this->trans(
                                        'Automatically with each field change',
                                        array(),
                                        'Modules.Carriercomparison.Admin'
                                    )
                                ),
                                array(
                                    'id' => 0,
                                    'name' =>
                                        $this->trans('When the customer ' .
                                            'clicks on the "Estimate Shipping' .
                                            ' Cost" button',
                                            array(),
                                            'Modules.Carriercomparison.Admin'
                                        )
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions')
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang =
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') :
            0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'setGlobalConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ) .
            '&configure=' . $this->name .
            '&tab_module=' . $this->tab .
            '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'SE_RERESH_METHOD' => Tools::getValue(
                'SE_RERESH_METHOD',
                Configuration::get('SE_RERESH_METHOD')
            ),
        );
    }
}
