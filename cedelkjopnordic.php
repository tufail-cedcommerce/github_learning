<?php
/**
 * 2007-2017 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicHelper.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProduct.php';
include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicOrder.php';

class CedElkjopnordic extends Module
{
    protected $config_form = false;
    protected $db = false;
    
    public function __construct()
    {
        $this->name = 'cedelkjopnordic';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Cedcommerce';
        $this->need_instance = 1;
        $this->secure_key = Tools::encrypt($this->name);
        $this->db = Db::getInstance();
                
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */

        //$this->module_key = '';
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name); // becd6f721f6a0c8013a9d47035512ce8
        parent::__construct();

        $this->displayName = $this->l('Elkjopnordic  Integration');
        $this->description = $this->l('Elkjopnordic  Integration Module provides facility to upload store products on 
        elkjopnordic and manage order and shipment from store .');

        $this->confirmUninstall = $this->l('Do you want to Uninstall Elkjopnordic  Integration');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        Configuration::updateValue('Elkjopnordic_LIVE_MODE', false);
        if (count($this->db->ExecuteS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "cedelkjopnordic_install'")) == 0) {
            include(dirname(__FILE__) . '/sql/install.php');
        }
        if (!parent::install()
            || !$this->installTab(
                'AdminCedElkjopnordic',
                'Elkjopnordic Integration',
                0
            )
            || !$this->installTab(
                'AdminCedElkjopnordicProfile',
                'Elkjopnordic Profile',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicProducts',
                'Elkjopnordic Products',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicFeed',
                'Elkjopnordic Products Feed',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicReport',
                'Elkjopnordic Offers Feed',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicOrder',
                'Elkjopnordic Order',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicRejected',
                'Elkjopnordic Failed Orders',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicBulk',
                'Elkjopnordic Bulk',
                (int)-1
            )
            || !$this->installTab(
                'AdminCedElkjopnordicLogs',
                'Elkjopnordic Logs',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
            || !$this->installTab(
                'AdminCedElkjopnordicConfig',
                'Elkjopnordic Configuration',
                (int)Tab::getIdFromClassName('AdminCedElkjopnordic')
            )
        ) {
            return false;
        }
        if (!$this->registerHook('actionUpdateQuantity')
            || !$this->registerHook('actionProductUpdate')
            || !$this->registerHook('actionProductDelete')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('displayBackOfficeTop')
            || !ProductSale::fillProductSales()
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');
        Configuration::deleteByName('ELKJOPNORDIC_LIVE_MODE');
        if (!parent::uninstall()
            || !$this->uninstallTab('AdminCedElkjopnordic')
            || !$this->uninstallTab('AdminCedElkjopnordicProfile')
            || !$this->uninstallTab('AdminCedElkjopnordicBulk')
            || !$this->uninstallTab('AdminCedElkjopnordicReport')
            || !$this->uninstallTab('AdminCedElkjopnordicFeed')
            || !$this->uninstallTab('AdminCedElkjopnordicOrder')
            || !$this->uninstallTab('AdminCedElkjopnordicProducts')
            || !$this->uninstallTab('AdminCedElkjopnordicLogs')
            || !$this->uninstallTab('AdminCedElkjopnordicConfig')
            || !$this->uninstallTab('AdminCedElkjopnordicRejected')
            || !$this->unregisterHook('actionUpdateQuantity')
            || !$this->unregisterHook('actionProductUpdate')
            || !$this->unregisterHook('actionProductDelete')
            || !$this->unregisterHook('displayBackOfficeHeader')
            || !$this->unregisterHook('actionOrderStatusPostUpdate')
            || !$this->unregisterHook('displayBackOfficeTop')
        ) {
            return false;
        }
        return true;
    }

    /**
     * install tabs on basis of class name given
     * use tab name in frontend
     * install under the parent tab given
     * @param $class_name
     * @param $tab_name
     * @param $parent
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab($class_name, $tab_name, $parent)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tab_name;
        }
        if ($parent == 0 && _PS_VERSION_ >= '1.7') {
            $tab->id_parent = (int)Tab::getIdFromClassName('SELL');
            $tab->icon = 'CE';
        } else {
            $tab->id_parent = $parent;
        }
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * uninstall tabs created by module
     * @param $class_name
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        } else {
            return false;
        }
    }

    /**
     * Load the configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';

        if (((bool)Tools::isSubmit('submitCedElkjopnordicModule')) == true) {
            $this->postProcess();
            if (Tools::getValue('ELKJOPNORDIC_API_KEY') == '' && Tools::getValue('ELKJOPNORDIC_API_KEY') == null) {
                $output .= $this->displayError('Please Fill Consumer Id');
            }
            if (Tools::getValue('ELKJOPNORDIC_API_URL') == '' && Tools::getValue('ELKJOPNORDIC_API_URL') == null) {
                $output .= $this->displayError('Please Fill ELKJOPNORDIC API URL');
            }

            if (Tools::getValue('ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL') == '' &&
                Tools::getValue('ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL') == null) {
                $output .= $this->displayError('Please Fill ELKJOPNORDIC CUSTOMER ORDER EMAIL');
            }

            if ($output == '') {
                $this->postProcess();
                $output .= $this->displayConfirmation($this->l('Elkjopnordic Configuration saved successfully'));
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path .
            'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCedElkjopnordicModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
        // return $helper->generateForm($fields_form);
    }

    /**
     * Create the structure of your form.
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function getConfigForm()
    {
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $image_types = $this->db->ExecuteS("SELECT `name` FROM `" . _DB_PREFIX_ .
            "image_type` WHERE 1 ");

        $order_states = $this->db->ExecuteS("SELECT `id_order_state`,`name` FROM `" . _DB_PREFIX_ .
            "order_state_lang` WHERE `id_lang` = '" . $id_lang . "'");

        $order_carriers = $this->db->ExecuteS("SELECT `id_carrier`,`name` FROM `" . _DB_PREFIX_ .
            "carrier` WHERE `active` = '1'");

        $store_currencies_list = Currency::getCurrencies();
        
        $languages = $this->context->controller->getLanguages();

        $payment_methods = array();

        $modules_list = Module::getPaymentModules();

        foreach ($modules_list as $module) {
            $module_obj = Module::getInstanceById($module['id_module']);
            array_push($payment_methods, array('id' => $module_obj->name, 'name' => $module_obj->displayName));
        }

        $this->context->smarty->assign(array(
            'base_url' => Context::getContext()->shop->getBaseURL(true),
            'cron_secure_key' => Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')
        ));
        $cron_html = $this->display(
            __FILE__,
            'views/templates/admin/configuration/cron_table.tpl'
        );
        $fieldsForm = array();
        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Api Configuration'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable'),
                    'name' => 'ELKJOPNORDIC_LIVE_MODE',
                    'is_bool' => true,
                    'desc' => $this->l('Use this module in live mode'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'select',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'desc' => $this->l('Elkjopnordic API Mode.'),
                    'name' => 'ELKJOPNORDIC_API_URL',
                    'label' => $this->l('API URL'),
                    'options' => array(
                        'query' => array(
                            array('value' => 'https://partner.elkjopnordic.com/api/', 'label' => 'Sandbox Mode'),
                            array('value' => 'https://partner.elkjopnordic.com/api/', 'label' => 'Live Mode'),
                        ),
                        'id' => 'value',
                        'name' => 'label',
                    )
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'desc' => $this->l('Elkjopnordic SELLER CONSUMER ID.'),
                    'name' => 'ELKJOPNORDIC_API_KEY',
                    'label' => $this->l('CONSUMER API KEY'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Debug'),
                    'name' => 'ELKJOPNORDIC_DEBUG_ENABLE',
                    'is_bool' => true,
                    'desc' => $this->l('Log data while request sends on Elkjopnordic.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('No')
                        )
                    ),
                ),
            )
        );

        $fieldsForm[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Product Configuration'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Image Type'),
                    'desc' => $this->l('Select Image type to export.'),
                    'name' => 'ELKJOPNORDIC_IMAGE_TYPE',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $image_types,
                        'id' => 'name',
                        'name' => 'name',
                    )
                ),

                array(
                    'type' => 'switch',
                    'label' => $this->l('Update on Product Edit'),
                    'name' => 'ELKJOPNORDIC_UPDATE_PRODUCT_EDIT',
                    'is_bool' => true,
                    'desc' => $this->l('Update Inventry on Elkjopnordic when you edit product on store .'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                 array(
                        'type' => 'select',
                        'label' => $this->l('Store Currency'),
                        'desc' => $this->l('Store Currency to be used in this module.'),
                        'name' => 'ELKJOPNORDIC_CURRENCY_STORE',
                        'required' => false,
                        'default_value' => '',
                        'options' => array(
                            'query' => $store_currencies_list,
                            'id' => 'id_currency',
                            'name' => 'name',
                        )
                    ),
                      array(
                        'type' => 'select',
                        'label' => $this->l('Store Language'),
                        'desc' => $this->l('Store Language to be used in this module.'),
                        'name' => 'ELKJOPNORDIC_LANGUAGE_STORE',
                        'required' => false,
                        'default_value' => '',
                        'options' => array(
                            'query' => $languages,
                            'id' => 'id_lang',
                            'name' => 'name',
                        )
                    ),
            )
        );

        $fieldsForm[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Order Configuration'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'desc' => $this->l('Email to create order on store which are imported form elkjopnordic.'),
                    'name' => 'ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL',
                    'label' => $this->l('CUSTOMER ORDER EMAIL'),
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'desc' => $this->l('Customer Id to create order on store which are imported form ELKJOPNORDIC.'),
                    'name' => 'ELKJOPNORDIC_CUSTOMER_ID',
                    'label' => $this->l('CUSTOMER ID'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status'),
                    'desc' => $this->l('Order Status While importing order.'),
                    'name' => 'ELKJOPNORDIC_ORDER_STATE',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status when Acknowledge'),
                    'desc' => $this->l('Order Status after Acknowledge order.'),
                    'name' => 'ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status when Shipped'),
                    'desc' => $this->l('Order Status after order Shipped.'),
                    'name' => 'ELKJOPNORDIC_ORDER_STATE_SHIPPED',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order Carrier'),
                    'desc' => $this->l('Order Carrier While importing order.'),
                    'name' => 'ELKJOPNORDIC_CARRIER_ID',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_carriers,
                        'id' => 'id_carrier',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order Payment'),
                    'desc' => $this->l('Order Payment While importing order.'),
                    'name' => 'ELKJOPNORDIC_ORDER_PAYMENT',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $payment_methods,
                        'id' => 'id',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Auto Order Acknowledge'),
                    'name' => 'ELKJOPNORDIC_AUTO_ORDER_PROCESS',
                    'is_bool' => true,
                    'desc' => $this->l('If enable then order will be automatically Acknowledge 
                        based on fulfillment status .'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Auto Order Reject'),
                    'name' => 'ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS',
                    'is_bool' => true,
                    'desc' => $this->l('If enable then order will be automatically Rejected 
                        based on fulfillment status .'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('No')
                        )
                    ),
                ),
            )
        );

        $fieldsForm[3]['form'] = array(
            'legend' => array(
                'title' => $this->l('Cron Configuration'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'desc' => $this->l('KEY TO USE AS "secure_key" IN CRON FILES LIKE. 
                        http://yourdomain.com/modules/elkjopnordic/cronfilename.php?secure_key=your
                         configuration secure key'),
                    'name' => 'ELKJOPNORDIC_CRON_SECURE_KEY',
                    'label' => $this->l('Cron Secure Key'),
                ),
                array(
                    'col' => 6,
                    'type' => 'html',
                    'label' => $this->l('Elkjopnordic Cron Urls'),
                    'name' => $cron_html,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return $fieldsForm;
    }

    public function getCronInfoForm()
    {
        $this->context->smarty->assign(array(
            'base_url' => Context::getContext()->shop->getBaseURL(true),
            'cron_secure_key' => Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY')
        ));
        $cron_html = $this->display(
            __FILE__,
            'views/templates/admin/configuration/cron_table.tpl'
        );
        return array(
            'legend' => array(
                'title' => $this->l('Cron Info'),
                'icon' => 'icon-info',
            ),
            'input' => array(
                array(
                    'col' => 6,
                    'type' => 'text',
                    'id' => 'ELKJOPNORDIC_CRON_SECURE_KEY',
                    'required' => true,
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'name' => 'ELKJOPNORDIC_CRON_SECURE_KEY',
                    'label' => $this->l(' Cron Secure Key'),
                    'desc' => $this->l('This cron secure key need to set in 
                    the parameters of following cron urls'),
                ),

                array(
                    'col' => 6,
                    'type' => 'html',
                    'label' => $this->l(''),
                    'name' => $cron_html,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ELKJOPNORDIC_LIVE_MODE' => Configuration::get('ELKJOPNORDIC_LIVE_MODE') ?
                Configuration::get('ELKJOPNORDIC_LIVE_MODE') : '',
            'ELKJOPNORDIC_API_URL' => Configuration::get('ELKJOPNORDIC_API_URL') ?
                Configuration::get('ELKJOPNORDIC_API_URL') : '',
            'ELKJOPNORDIC_API_KEY' => Configuration::get('ELKJOPNORDIC_API_KEY') ?
                Configuration::get('ELKJOPNORDIC_API_KEY') : '',
            'ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL' => Configuration::get('ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL') ?
                Configuration::get('ELKJOPNORDIC_CUSTOMER_ORDER_EMAIL') : '',
            'ELKJOPNORDIC_AUTO_ORDER_PROCESS' => Configuration::get('ELKJOPNORDIC_AUTO_ORDER_PROCESS') ?
                Configuration::get('ELKJOPNORDIC_AUTO_ORDER_PROCESS') : '',
            'ELKJOPNORDIC_UPDATE_PRODUCT_EDIT' => Configuration::get('ELKJOPNORDIC_UPDATE_PRODUCT_EDIT') ?
                Configuration::get('ELKJOPNORDIC_UPDATE_PRODUCT_EDIT') : '',
            'ELKJOPNORDIC_DEBUG_ENABLE' => Configuration::get('ELKJOPNORDIC_DEBUG_ENABLE') ?
                Configuration::get('ELKJOPNORDIC_DEBUG_ENABLE') : '',
            'ELKJOPNORDIC_CUSTOMER_ID' => Configuration::get('ELKJOPNORDIC_CUSTOMER_ID') ?
                Configuration::get('ELKJOPNORDIC_CUSTOMER_ID') : '',
            'ELKJOPNORDIC_ORDER_STATE' => Configuration::get('ELKJOPNORDIC_ORDER_STATE') ?
                Configuration::get('ELKJOPNORDIC_ORDER_STATE') : '',
            'ELKJOPNORDIC_CARRIER_ID' => Configuration::get('ELKJOPNORDIC_CARRIER_ID') ?
                Configuration::get('ELKJOPNORDIC_CARRIER_ID') : '',
            'ELKJOPNORDIC_ORDER_PAYMENT' => Configuration::get('ELKJOPNORDIC_ORDER_PAYMENT') ?
                Configuration::get('ELKJOPNORDIC_ORDER_PAYMENT') : '',
            'ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE' => Configuration::get('ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE') ?
                Configuration::get('ELKJOPNORDIC_ORDER_STATE_ACKNOWLEDGE') : '',
            'ELKJOPNORDIC_ORDER_STATE_SHIPPED' => Configuration::get('ELKJOPNORDIC_ORDER_STATE_SHIPPED') ?
                Configuration::get('ELKJOPNORDIC_ORDER_STATE_SHIPPED') : '',
            'ELKJOPNORDIC_CRON_SECURE_KEY' => Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY') ?
                Configuration::get('ELKJOPNORDIC_CRON_SECURE_KEY') : '',
            'ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS' => Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS') ?
                Configuration::get('ELKJOPNORDIC_AUTO_ORDER_REJECT_PROCESS') : '',
            'ELKJOPNORDIC_IMAGE_TYPE' => Configuration::get('ELKJOPNORDIC_IMAGE_TYPE') ?
                Configuration::get('ELKJOPNORDIC_IMAGE_TYPE') : '',
             'ELKJOPNORDIC_CURRENCY_STORE' => Configuration::get('ELKJOPNORDIC_CURRENCY_STORE'),
             'ELKJOPNORDIC_LANGUAGE_STORE' => Configuration::get('ELKJOPNORDIC_LANGUAGE_STORE'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);
            Configuration::updateValue($key, $value);
        }
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCss($this->_path . 'views/css/tab.css');
    }

    /**
     * Update Product on Elkjopnordic when updated on store
     * @param $params
     * @throws PrestaShopDatabaseException
     */
    public function hookActionProductUpdate($params)
    {
        $cwal_lib = new CedElkjopnordicHelper;
        $cwal_prod = new CedElkjopnordicProduct();
        $product_id = (array)$params['id_product'];
        $cwal_lib->log('hookActionProductUpdate');
        $cwal_lib->log($params);

        if (Configuration::get('ELKJOPNORDIC_LIVE_MODE') && Configuration::get('ELKJOPNORDIC_UPDATE_PRODUCT_EDIT')) {
            $cwal_prod->updateOffers($product_id);
        }
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     */
    public function hookActionProductDelete($params)
    {
        $cwal_lib = new CedElkjopnordicHelper;
        $cwal_prod = new CedElkjopnordicProduct();
        $product_id = (array)$params['id_product'];
        $cwal_lib->log('hookActionProductDelete');
        if (Configuration::get('ELKJOPNORDIC_LIVE_MODE') && Configuration::get('ELKJOPNORDIC_UPDATE_PRODUCT_EDIT')) {
            $cwal_prod->updateOffers($product_id);
        }
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     */
    public function hookActionUpdateQuantity($params)
    {
        $cwal_lib = new CedElkjopnordicHelper;
        $cwal_prod = new CedElkjopnordicProduct();
        $product_id = (array)$params['id_product'];
        $cwal_lib->log('hookActionUpdateQuantity');
        if (Configuration::get('ELKJOPNORDIC_LIVE_MODE') && Configuration::get('ELKJOPNORDIC_UPDATE_PRODUCT_EDIT')) {
          //  $cwal_prod->updateOffers($product_id);
        }
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $cedelkjopnordicHelper = new CedElkjopnordicOrder();
        
        $db = Db::getInstance();
        $orderId = isset($params['id_order']) ? $params['id_order'] : 0;
        $newOrderStatus = (int)$params['newOrderStatus']->id;
        $sql = 'select * from ' . _DB_PREFIX_ . 'cedelkjopnordic_order where order_id=' . $orderId;
        $res = $db->executeS($sql);

        if ($newOrderStatus == Configuration::get('ELKJOPNORDIC_ORDER_STATE_SHIPPED') && isset($res[0])) {
            $elkjopnordic_order_id = isset($res[0]['elkjopnordic_order_id']) ? $res[0]['elkjopnordic_order_id'] : 0;
            $carrier_name = '<Sweden> Postnord';
            $carrier_code = 'postnord_sv';
            $orderobject = new Order($orderId);
            $data = (array)$orderobject;
            $trackingNumber = false;
            $id_order_carrier=$orderobject->getIdOrderCarrier();
            if(!empty($id_order_carrier)){
                 $trackingNumber = $db->getValue("SELECT `tracking_number` FROM `"._DB_PREFIX_."order_carrier` 
                 WHERE `id_order` = ".$orderId." AND `id_order_carrier` =".$id_order_carrier);
            }
            $carrier_url = '';
            $carrier_code = trim($carrier_code);
            $trackingNumber = trim($trackingNumber);

            if (isset($carrier_code) && !empty($carrier_code)) {
              
                $result = $cedelkjopnordicHelper->shipCompleteOrder(
                    $elkjopnordic_order_id,
                    $carrier_code,
                    $carrier_name,
                    $carrier_url,
                    $trackingNumber
                );

                if (isset($result['success']) && $result['success']) {
                       $cedelkjopnordicHelper->shipOrder($elkjopnordic_order_id);
                       $cedelkjopnordicHelper->updateElkjopnordicOrderStatus($elkjopnordic_order_id, 'SHIPPED');
                }
                $cedelkjopnordicHelper->sendInvoice($orderId);
            }
        }
    }

    /**
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBackOfficeTop()
    {
        $failed_order_count = 0;
        $db = Db::getInstance();
        $query = "select count(viewed_order) from "._DB_PREFIX_."cedelkjopnordic_order_error WHERE viewed_order=0";
        $count = $db->executeS($query);
        if (isset($count[0]['count(viewed_order)'])) {
            $failed_order_count = $count[0]['count(viewed_order)'];
        }
        $this->context->smarty->assign(
            array(
                'hrefOrderHandle' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicRejected') .
                    '&viewedRejectedOrder=1',
                'hrefFetchOrders' => Context::getContext()->link->getAdminLink('AdminCedElkjopnordicOrder') .
                    '&fetchorder',
                'hrefMarketplace' => "https://partner.elkjopnordic.com/",
                'count' => $failed_order_count
            )
        );
        return $this->context->smarty->fetch($this->local_path .
            'views/templates/admin/hook/failed_order_notify.tpl');
    }
}
