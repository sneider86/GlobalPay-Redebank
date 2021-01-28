<?php
/**
 * RedebanGlobalPay
 *
 * Order Transaction Controller
 *
 * @author Erick Estrada <sneider86@gmail.com>
 * @license https://opensource.org/licenses/afl-3.0.php
 */

class Transaction
{
    private $id;
    private $order_id;
    private $order_reference;
    private $url_link_pago;
    private $request;
    private $response;
    private $tableTransaction;
    
    public function __construct()
    {
        $this->id = null;
        $this->tableTransaction = 'eres_global_transaction';
    }
    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }
    public function getOrderId()
    {
        return $this->order_id;
    }
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }
    public function getOrderReference()
    {
        return $this->order_reference;
    }
    public function setOrderReference($order_reference)
    {
        $this->order_reference = $order_reference;
    }
    public function getLinkPago()
    {
        return $this->url_link_pago;
    }
    public function setLinkPago($url_link_pago)
    {
        $this->url_link_pago = $url_link_pago;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function setRequest($request)
    {
        $this->request = $request;
    }
    public function getResponse()
    {
        return $this->response;
    }
    public function setResponse($response)
    {
        $this->response = $response;
    }
    public function create()
    {
        Db::getInstance()->execute("
        INSERT INTO "._DB_PREFIX_.$this->tableTransaction." 
        (order_id,order_reference,url_link_pago,request,response) 
        VALUE ("
        .$this->order_id.",'".$this->order_reference."','"
        .$this->url_link_pago."','".$this->request."','".$this->response."')");
    }
}
