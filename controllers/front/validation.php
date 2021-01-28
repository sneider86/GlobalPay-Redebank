<?php
/**
 * RedebanGlobalPay
 *
 * Order Validation Controller
 *
 * @author Erick Estrada <sneider86@gmail.com>
 * @license https://opensource.org/licenses/afl-3.0.php
 */
include_once(dirname(__FILE__).'/../../model/ConsumeRest.php');
include_once(dirname(__FILE__).'/../../model/Transaction.php');

class RedebanglobalpayValidationModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {

        $cart = $this->context->cart;
        $authorized = false;

        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'redebanglobalpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Place the order
         */
        $statuOrder        =   Tools::getValue('statepayment', Configuration::get('statepayment'));
        if (!isset($statuOrder) || $statuOrder==null || empty($statuOrder)) {
            $statuOrder=2;
        }
        $this->module->validateOrder(
            (int) $this->context->cart->id,
            $statuOrder,
            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            null,
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );
        if ($cart->OrderExists() == true) {
            $orderId = Order::getOrderByCartId((int)$cart->id);
        }

        /**
         * Redirect the customer to the order confirmation page
         */

        $this->context->smarty->assign(
            array(
                'localredirect' => true // Retrieved from GET vars
            )
        );
        $baseUrl = Context::getContext()->shop->getBaseURL(true);
        // Load current value
        $uri        =   Tools::getValue('enviroment', Configuration::get('enviroment'));
        $appCode    =   Tools::getValue('appcode', Configuration::get('appcode'));
        $appKey     =   Tools::getValue('appkey', Configuration::get('appkey'));
        $expiration =   Tools::getValue('expiration_days', Configuration::get('expiration_days'));
        $method = 'POST';
        $rest = new ConsumeRest($uri, $method);
        $token = $rest->createToken($appCode, $appKey);
        $rest->setContentType('application/json');
        $rest->addHeader('auth-token', $token);
        $successUri = 'index.php?controller=order-confirmation&id_cart='.
        (int)$cart->id.'&id_module='.
        (int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        $order = new Order((int) $this->module->currentOrder);
        $customer = new Customer($order->id_customer);
        $total = number_format($order->total_paid, 0, '', '');
        $data = [
            'user' => [
                'id'=>$order->id_customer,
                'email'=>$customer->email,
                'name'=>$customer->firstname,
                'last_name'=>$customer->lastname
            ],
            'order' => [
                'dev_reference'=>$order->reference,
                'description'=>$this->module->currentOrder,
                'installments_type'=>0,
                'currency'=>'COP',
                "amount"=> $total
            ],
            'configuration' => [
                'partial_payment'=>true,
                'expiration_days'=>$expiration,
                'allowed_payment_methods'=>["All", "Cash", "BankTransfer", "Card"],
                'success_url'=>$baseUrl.$successUri,
                'failure_url'=>$baseUrl.'module/redebanglobalpay/failed',
                'pending_url'=>$baseUrl.'module/redebanglobalpay/failed',
                'review_url'=>$baseUrl.'module/redebanglobalpay/failed'
            ]
        ];
        $rest->setParams($data);
        $result = $rest->execute();
        $transaction = new Transaction();
        $transaction->setOrderId((int) $this->module->currentOrder);
        $transaction->setOrderReference($order->reference);
        $transaction->setRequest(json_encode($data));
        $transaction->setResponse(json_encode($result));
        if (isset($result['success'])
            && $result['success']
        ) {
            $urlPayment = $result['data']->payment->payment_url;
            $transaction->setLinkPago($urlPayment);
            $transaction->create();
            Tools::redirect($urlPayment);
        } else {
            $baseUrl = Context::getContext()->shop->getBaseURL(true);
            $transaction->create();
            Tools::redirect($baseUrl.'module/redebanglobalpay/failed');
        }
    }
}