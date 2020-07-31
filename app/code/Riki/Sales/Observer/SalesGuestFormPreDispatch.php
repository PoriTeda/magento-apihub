<?php
namespace Riki\Sales\Observer;

class SalesGuestFormPreDispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Framework\App\RequestInterface $request
         */
        $request = $observer->getRequest();
        $request->setActionName('noroute');
        $request->setDispatched(false);
    }
}