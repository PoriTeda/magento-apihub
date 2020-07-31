<?php

namespace Riki\MachineApi\Observer;

use Magento\Framework\Event\ObserverInterface;

class DisableSendMailOrderMachine implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->_request = $request;
    }

    public function checkRequestWebApi(){
        $pathInfo =  $this->_request->getPathInfo();
        $patternStep5 ='#V1/mm/carts/order/payment-information#';
        if(preg_match($patternStep5,$pathInfo,$match)){
            return true;
        }
        $pattern ='#/V1/mm/carts/#';
        if(preg_match($pattern,$pathInfo,$match)){
            return true;
        }
        return false;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if(!$order instanceof \Magento\Sales\Model\Order){
            return ;
        }

        /**
         * Disable send mail for machine api,import order csv
         */
        if($this->checkRequestWebApi() || $order->getData('original_unique_id') !=''){
            $order->setCanSendNewEmailFlag(false);
            $order->setEmailSent(true);
            $order->setSendEmail(true);
        }

        return $this;
    }



}