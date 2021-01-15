<?php

include_once(_PS_MODULE_DIR_.'redebanglobalpay/model/ConsumeRest.php');

class WebserviceSpecificManagementValidatePayment implements WebserviceSpecificManagementInterface
{

    protected $objOutput;
    protected $output;
    protected $wsObject;

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;
        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }
 
    public function getWsObject()
    {
        return $this->wsObject;
    }
 
    public function getObjectOutput()
    {
        return $this->objOutput;
    }
 
    public function getContent()
    {
        return $this->output;
    }
 
    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        return $this;
    }
 
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;
        return $this;
    }

    public function manage()
    {
        $request = $this->getWsObject();
        $input = $this->getInputData();
        $this->getObjectOutput()
                ->setHeaderParams('Created-By', 'Erick Estrada')
                ->setHeaderParams('E-Mail', 'sneider86@gmail.com');
        if (!is_array($input) || count($input)<=0) {
            $this->getWsObject()->setError(400, 'Debe ingresar un input de tipo JSON', 200);
        }
        
        $this->validateTransaction($input);
        $this->validateUser($input);
        
        $rest       = new ConsumeRest('');
        $transId    = $input['transaction']['id'];
        $appCode    = $input['transaction']['application_code'];
        $userId     = $input['user']['id'];
        $appKey     =   Tools::getValue('appkey', Configuration::get('appkey'));
        $stoken     = $input['transaction']['stoken'];
        $reference  = $input['transaction']['dev_reference'];
        $vToken     = $rest->validateTokenIncoming($transId, $appCode, $userId, $appKey, $stoken);
        if (!$vToken) {
            $this->getWsObject()->setError(200, 'error con el token', 203);
        }
        $order = $this->getLoadOrderHistoryByReference($reference);
        if ($order->current_state == 2) {
            $this->getWsObject()->setError(200, 'La orden \''.$reference.'\' ya se encuentra confirmada.', 200);
        } else {
            $loadOrder = $this->changeOrderHistoryByReference($reference);
            if (!$loadOrder) {
                $this->getWsObject()->setError(400, 'No se pudo cargar Orden ref :'.$reference, 203);
            }
            
        }
        
        $objects_data = [
            'code' => '200',
            'message' => 'La orden ha sido confirmada.'
        ];
        $json = json_encode($objects_data);
        $this->output = $json;
    }

    public function getInputData()
    {
        $putresource = fopen("php://input", "rb");
        $input = '';
        while ($putData = fread($putresource, 1024)) {
            $input .= $putData;
        }
        fclose($putresource);
        $dataArray = [];
        if (isset($input) && !empty($input)) {
            $dataArray = json_decode($input, true);
        }
        return $dataArray;
    }

    protected function validateTransaction($input)
    {
        if (!isset($input['transaction'])) {
            $this->getWsObject()->setError(400, 'Parametro \'transaction\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['status'])) {
            $this->getWsObject()->setError(400, 'Parametro \'status\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['order_description'])) {
            $this->getWsObject()->setError(400, 'Parametro \'order_description\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['authorization_code'])) {
            $this->getWsObject()->setError(400, 'Parametro \'authorization_code\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['status_detail'])) {
            $this->getWsObject()->setError(400, 'Parametro \'status_detail\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['date'])) {
            $this->getWsObject()->setError(400, 'Parametro \'date\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['message'])) {
            $this->getWsObject()->setError(400, 'Parametro \'message\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['id'])) {
            $this->getWsObject()->setError(400, 'Parametro \'id\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['dev_reference'])) {
            $this->getWsObject()->setError(400, 'Parametro \'dev_reference\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['carrier_code'])) {
            $this->getWsObject()->setError(400, 'Parametro \'carrier_code\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['amount'])) {
            $this->getWsObject()->setError(400, 'Parametro \'amount\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['paid_date'])) {
            $this->getWsObject()->setError(400, 'Parametro \'paid_date\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['installments'])) {
            $this->getWsObject()->setError(400, 'Parametro \'paid_date\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['stoken'])) {
            $this->getWsObject()->setError(400, 'Parametro \'stoken\' no existe', 200);
            return true;
        }
        if (!isset($input['transaction']['application_code'])) {
            $this->getWsObject()->setError(400, 'Parametro \'application_code\' no existe', 200);
            return true;
        }
    }

    protected function validateUser($input)
    {
        if (!isset($input['user'])) {
            $this->getWsObject()->setError(400, 'Parametro \'user\' no existe', 200);
            return true;
        }
        if (!isset($input['user']['id'])) {
            $this->getWsObject()->setError(400, 'Parametro \'id\' no existe', 200);
            return true;
        }
        if (!isset($input['user']['email'])) {
            $this->getWsObject()->setError(400, 'Parametro \'email\' no existe', 200);
            return true;
        }
    }

    protected function validateCard($input)
    {
        if (!isset($input['card'])) {
            $this->getWsObject()->setError(400, 'Parametro \'card\' no existe', 200);
            return true;
        }
        if (!isset($input['card']['bin'])) {
            $this->getWsObject()->setError(400, 'Parametro \'bin\' no existe', 200);
            return true;
        }
        if (!isset($input['card']['holder_name'])) {
            $this->getWsObject()->setError(400, 'Parametro \'holder_name\' no existe', 200);
            return true;
        }
        if (!isset($input['card']['type'])) {
            $this->getWsObject()->setError(400, 'Parametro \'type\' no existe', 200);
            return true;
        }
        if (!isset($input['card']['number'])) {
            $this->getWsObject()->setError(400, 'Parametro \'number\' no existe', 200);
            return true;
        }
        if (!isset($input['card']['origin'])) {
            $this->getWsObject()->setError(400, 'Parametro \'origin\' no existe', 200);
            return true;
        }
    }

    protected function getLoadOrderHistoryByReference($reference)
    {
        $sql = new DbQuery();
        $sql->select('id_order');
        $sql->from('orders', 'o');
        $sql->where("o.reference = '".$reference."'");
        $tmp = $sql->__toString();
        $result = Db::getInstance()->executeS($sql);
        $idOrder = 0;
        foreach ($result as $item) {
            $idOrder = $item['id_order'];
        }
        if ($idOrder==0) {
            return false;
        }
        $order = new Order((int)$idOrder);
        return $order;
    }

    protected function changeOrderHistoryByReference($reference)
    {
        $sql = new DbQuery();
        $sql->select('id_order');
        $sql->from('orders', 'o');
        $sql->where("o.reference = '".$reference."'");
        $tmp = $sql->__toString();
        $result = Db::getInstance()->executeS($sql);
        $idOrder = 0;
        foreach ($result as $item) {
            $idOrder = $item['id_order'];
        }
        if ($idOrder==0) {
            return false;
        }
        try {
            $status_id     =   Tools::getValue('statewebhook', Configuration::get('statewebhook'));
            $history = new OrderHistory();
            $history->id_order = (int)$idOrder;
            $history->changeIdOrderState($status_id, (int)$idOrder);
            $history->addWithemail();
            $history->save();
        } catch (Exception $err) {
            $msg = $err->getMessage();
        }
        
        return true;
    }
}
