<?php
/**
 * RedebanGlobalPay
 *
 * Order Validation Controller
 *
 * @author Erick Estrada <sneider86@gmail.com>
 * @license https://opensource.org/licenses/afl-3.0.php
 */

class ConsumeRest
{
    private $uri;
    private $method;
    private $headers;
    private $contentType;
    private $params;
    public function __construct(
        $uri,
        $method = 'GET'
    ) {
        $this->uri = $uri;
        $this->method = $method;
        $this->headers = [];
        $this->contentType = '';
        $this->params = [];
    }
    public function getHeaders()
    {
        return $this->headers;
    }
    public function addHeader($key,$value)
    {
        $header = $key.":".$value;
        array_push($this->headers,$header);
    }
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        $header = "Content-Type:".$this->contentType;
        array_push($this->headers,$header);
    }
    public function setParams($params)
    {
        $this->params = $params;
    }
    public function execute()
    {
        switch($this->method){
            case 'POST':
                return $this->consumePost();
                break;
        }
    }
    private function consumePost()
    {
        $ch = curl_init($this->uri);
        if(isset($this->headers) && is_array($this->headers) && count($this->headers)>0){
            switch($this->contentType){
                case 'application/json':
                    if(is_array($this->params) &&  count($this->params)>0){
                        $data_string = json_encode($this->params);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    }
                    break;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER,$this->headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        $response = curl_exec($ch);
        curl_close($ch);
        if(!$response) {
            return false;
        }else{
            return (array)json_decode($response);
        }
    }
    public function createToken($appCode,$appKey)
    {
        $API_LOGIN_DEV     = $appCode;
        $API_KEY_DEV       = $appKey;

        $server_application_code = $API_LOGIN_DEV;
        $server_app_key = $API_KEY_DEV ;
        $date = new DateTime();
        $unix_timestamp = $date->getTimestamp();
        $uniq_token_string = $server_app_key.$unix_timestamp;
        $uniq_token_hash = hash('sha256', $uniq_token_string);
        $auth_token = base64_encode($server_application_code.";".$unix_timestamp.";".$uniq_token_hash);
        return $auth_token;
    }
    public function validateTokenIncoming($transId, $appCode, $userId, $appKey, $stoken)
    {
        $token = md5($transId.'_'.$appCode.'_'.$userId.'_'.$appKey);
        if ($token == $stoken) {
            return true;
        } else {
            return false;
        }
    }

}