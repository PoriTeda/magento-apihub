<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Observer\Webapi;

use Magento\Framework\Event\ObserverInterface;

class BeforeSubmitObserver
    implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * SubmitObserver constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->_request = $request;
    }
    /**
     * check request from web api
     *
     * @return bool
     */
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

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if(!$order instanceof \Magento\Sales\Model\Order){
            return ;
        }
        if($this->checkRequestWebApi()){
            $order->setCanSendNewEmailFlag(false);
            //set disable send mail when crate order machine api
            $order->setSendEmail(true);
            $order->setEmailSent(true);
        }
        return $this;
    }

}
