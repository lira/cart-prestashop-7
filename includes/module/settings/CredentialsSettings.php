<?php

/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

require_once MP_ROOT_URL . '/includes/module/settings/AbstractSettings.php';

class CredentialsSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoCredentials';
        $this->values = $this->getFormValues();
        $this->form = $this->generateForm();
        $this->process = $this->verifyPostProcess();
    }

    /**
     * Generate inputs form
     *
     * @return void
     */
    public function generateForm()
    {
        $title = $this->module->l('Credentials');
        $fields = array(
            array(
                'col' => 4,
                'type' => 'switch',
                'label' => $this->module->l('Sandbox Mode'),
                'name' => 'MERCADOPAGO_SANDBOX_STATUS',
                'is_bool' => true,
                'desc' => $this->module->l('Choose "YES" to test your store before selling. ') .
                    $this->module->l('Switch to "NO" to disable test mode ') .
                    $this->module->l('and start receiving online payments.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_SANDBOX_STATUS_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_SANDBOX_STATUS_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 8,
                'type' => 'html',
                'name' => '',
                'desc' => '',
                'label' => $this->module->l('Upload credentials'),
                'html_content' => '<a href="https://www.mercadopago.com/'
                    . Configuration::get('MERCADOPAGO_COUNTRY_LINK') .
                    '/account/credentials" target="_blank" class="btn btn-default btn-credenciais">'
                    . $this->module->l('Search my credentials') . '</a>'
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_PUBLIC_KEY',
                'label' => $this->module->l('Public Key'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN',
                'label' => $this->module->l('Access token'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_PUBLIC_KEY',
                'label' => $this->module->l('Public Key'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => ' ',
                'name' => 'MERCADOPAGO_ACCESS_TOKEN',
                'label' => $this->module->l('Access token'),
                'required' => true
            ),
        );

        return $this->buildForm($title, $fields);
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        $this->validate = ([
            'MERCADOPAGO_PUBLIC_KEY' => 'public_key',
            'MERCADOPAGO_ACCESS_TOKEN' => 'access_token',
            'MERCADOPAGO_SANDBOX_PUBLIC_KEY' => 'public_key',
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => 'access_token',
        ]);

        parent::postFormProcess();

        //activate checkout
        if (Mercadopago::$form_alert != 'alert-danger') {
            if (Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT') == '') {
                $payment_methods = $this->mercadopago->getPaymentMethods();
                foreach ($payment_methods as $payment_method) {
                    $pm_name = 'MERCADOPAGO_PAYMENT_' . $payment_method['id'];
                    Configuration::updateValue($pm_name, 'on');
                }

                Configuration::updateValue('MERCADOPAGO_STANDARD_MODAL', true);
                Configuration::updateValue('MERCADOPAGO_STANDARD_CHECKOUT', true);
            }

            Mercadopago::$form_message = $this->module->l('Settings saved successfully. Now you can configure the module.');

            $this->sendSettingsInfo();
            MPLog::generate('Credentials saved successfully');
        }
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return array(
            'MERCADOPAGO_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_PUBLIC_KEY'),
            'MERCADOPAGO_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_ACCESS_TOKEN'),
            'MERCADOPAGO_SANDBOX_STATUS' => Configuration::get('MERCADOPAGO_SANDBOX_STATUS'),
            'MERCADOPAGO_SANDBOX_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY'),
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN')
        );
    }

    /**
     * Validate credentials and save seller information
     *
     * @param string $input
     * @param string $value
     * @return boolean
     */
    public function validateCredentials($input, $value)
    {
        $token_validation = $this->mercadopago->isValidAccessToken($value);
        if (!$token_validation) {
            return false;
        }

        if ($input == 'MERCADOPAGO_ACCESS_TOKEN') {
            $application_id = explode('-', $value);
            Configuration::updateValue('MERCADOPAGO_APPLICATION_ID', $application_id[1]);
            Configuration::updateValue('MERCADOPAGO_SELLER_ID', $token_validation['id']);
            Configuration::updateValue('MERCADOPAGO_SITE_ID', $token_validation['site_id']);
        }

        return true;
    }
}
