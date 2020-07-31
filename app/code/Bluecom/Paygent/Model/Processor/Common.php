<?php

namespace Bluecom\Paygent\Model\Processor;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseDataFactory;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Magento\Framework\Encryption\EncryptorInterface;
abstract class Common extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;
    /**
     * Common constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ScopeConfigInterface
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_encryptor = $encryptor;
    }

    protected $paymentObject;
    protected $validator;

    /**
     * Init function
     *
     * @return $this
     */
    public function init()
    {
        //todo: initalize paygent gateway module instance
        $this->paymentObject = new PaygentB2BModule();

        $this->paymentObject->init();
        $this->setParam("merchant_id", $this->scopeConfig->getValue('payment/paygent/merchant_id'));
        $this->setParam("connect_id", $this->scopeConfig->getValue('payment/paygent/connect_id'));
        $this->setParam("connect_password", $this->_encryptor->decrypt($this->scopeConfig->getValue('payment/paygent/connect_password')));
        $this->setParam("telegram_version", $this->scopeConfig->getValue('payment/paygent/telegram_version'));
        
        return $this;
    }

    /**
     * Set param to object
     *
     * @param string $key   key
     * @param string $value value
     *
     * @return $this
     */
    public function setParam($key , $value)
    {
        $this->paymentObject->reqPut($key, trim($value));
        return $this;
    }

    /**
     * Post Process
     *
     * @return mixed
     */
    public function process()
    {
        return $this->paymentObject->post();
    }

    /**
     * Get Result
     *
     * @return mixed
     */
    abstract public function getResult();

    /**
     * Get Payment Object from Paygent
     * 
     * @return mixed
     */
    public function getPaymentObject()
    {
        return $this->paymentObject;
    }
}
