<?php
/**
 * RedebanGlobalPay
 *
 * Order Validation Controller
 *
 * @author Erick Estrada <sneider86@gmail.com>
 * @license https://opensource.org/licenses/afl-3.0.php
 */

class RedebanglobalpayFailedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        // // In the template, we need the vars paymentId & paymentStatus to be defined
        $this->context->smarty->assign(
            array(
                'paymentId' => 'prueba' // Retrieved from GET vars
            )
        );
        $baseUrl = Context::getContext()->shop->getBaseURL(true);
        $this->setTemplate('module:redebanglobalpay/views/templates/front/failed.tpl');
    }
}