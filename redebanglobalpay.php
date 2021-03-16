<?php
/*
* 2020 Erick Estrada
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
*  @author Erick Estrada <sneider86@gmail.com>
*  @copyright  2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* 
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
include_once(_PS_MODULE_DIR_.'redebanglobalpay/classes/WebserviceSpecificManagementValidatePayment.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

class Redebanglobalpay extends PaymentModule
{

    protected $_html = '';
    protected $_postErrors = [];

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;
    protected $tableTransaction = 'eres_global_transaction';

    public function __construct()
    {

        $this->name                     = 'redebanglobalpay';
        $this->tab                      = 'payments_gateways';
        $this->version                  = '1.0.0';
        $this->author                   = 'Erick Estrada';
        $this->controllers              = ['payment', 'validation'];
        $this->currencies               = true;
        $this->currencies_mode          = 'checkbox';
        $this->bootstrap                = true;
        $this->displayName              = 'GlobalPay';
        $this->description              = 'Global Pay de Redebank.';
        $this->confirmUninstall         = 'Are you sure you want to uninstall this module?';
        $this->ps_versions_compliancy   = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];
        $this->_html = '';
        parent::__construct();
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('addWebserviceResources')
            && $this->installDb();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteDb();
    }

    public function installDb()
    {
        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->tableTransaction.'` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_id` int(11) NOT NULL,
            `order_reference` VARCHAR(99) NOT NULL,
            `url_link_pago` VARCHAR( 255 ) NOT NULL,
            `request` text,
            `response` text,
			INDEX (`order_reference`)
		) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;');
        return true;
    }
    public function deleteDb()
    {
        //Db::getInstance()->execute('DROP TABLE '._DB_PREFIX_.$this->tableTransaction);
        return true;
    }

    public function hookPaymentOptions($params)
    {
        /*
         * Verify if this module is active
         */
        if (!$this->active) {
            return;
        }
 
        /**
         * Form action URL. The form data will be sent to the
         * validation controller when the user finishes
         * the order process.
         */
        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
 
        /**
         * Assign the url form action to the template var $action
         */
        $this->smarty->assign(['action' => $formAction]);
 
        /**
         *  Load form template to be displayed in the checkout step
         */
        $paymentForm = $this->fetch('module:redebanglobalpay/views/templates/hook/payment_options.tpl');
 
        /**
         * Create a PaymentOption object containing the necessary data
         * to display this module in the checkout
         */
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->displayName)
            ->setCallToActionText($this->displayName)
            ->setAction($formAction)
            ->setForm($paymentForm);
 
        $payment_options = array(
            $newOption
        );
 
        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        /**
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }

        return $this->fetch('module:redebanglobalpay/views/templates/hook/payment_return.tpl');
    }

    public function getContent()
    {
        $output = null;
        $fields = [
            'enviroment',
            'appcode',
            'appkey',
            'expiration_days',
            'statepayment',
            'statewebhook'
        ];

        if (Tools::isSubmit('submit'.$this->name)) {
            $validation = true;
            foreach ($fields as $field) {
                $myModuleName = strval(Tools::getValue($field));

                if (!$myModuleName ||
                    empty($myModuleName) ||
                    !Validate::isGenericName($myModuleName)
                ) {
                    $output .= $this->displayError($this->l('Invalid Configuration value'));
                    $validation=false;
                    break;
                } else {
                    Configuration::updateValue($field, $myModuleName);
                }
            }
            if ($validation) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm($fields);
    }

    public function displayForm($fields)
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $options = [
            [
                'id_option' => 'https://noccapi-stg.globalpay.com.co/linktopay/init_order/',       // The value of the 'value' attribute of the <option> tag.
                'name' => 'Integración'    // The value of the text content of the  <option> tag.
            ],
            [
                'id_option' => 'https://noccapi.globalpay.com.co/linktopay/init_order/',       // The value of the 'value' attribute of the <option> tag.
                'name' => 'Producción'    // The value of the text content of the  <option> tag.
            ]
        ];

        $states = new OrderState(1);
        $states2 = $states->getOrderStates(1);
        foreach ($states2 as $statu) {
            $optionsStatus[] = [
                'id_option' => $statu['id_order_state'],
                'name' => $statu['name']
            ];
        }

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Configuración'),
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Ambiente'),
                    'name' => 'enviroment',
                    'size' => 1,
                    'required' => true,
                    'options' => [
                        'query' => $options,                           // $options contains the data itself.
                        'id' => 'id_option',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                        'name' => 'name'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('App Code Server'),
                    'name' => 'appcode',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('App Key Server'),
                    'name' => 'appkey',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Dias de expiración'),
                    'name' => 'expiration_days',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Estado del pedido Inicial'),
                    'name' => 'statepayment',
                    'size' => 1,
                    'required' => true,
                    'options' => [
                        'query' => $optionsStatus,                           // $options contains the data itself.
                        'id' => 'id_option',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                        'name' => 'name'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Estado del pedido Webhook'),
                    'name' => 'statewebhook',
                    'size' => 1,
                    'required' => true,
                    'options' => [
                        'query' => $optionsStatus,                           // $options contains the data itself.
                        'id' => 'id_option',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                        'name' => 'name'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];
        // Load current value
        foreach ($fields as $field) {
            $helper->fields_value[$field] = Tools::getValue($field, Configuration::get($field));
        }

        return $helper->generateForm($fieldsForm);
    }
    public function hookAddWebserviceResources()
    {
        return [
            'validatepayment' => [
                'description' => 'WebHook Globabank',
                'specific_management' => true,
                'class' => 'WebserviceSpecificManagementValidatePayment'
            ]
        ];
    }
}
