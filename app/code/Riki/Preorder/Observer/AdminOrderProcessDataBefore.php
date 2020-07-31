<?php
namespace Riki\Preorder\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminOrderProcessDataBefore implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Backend\Model\Session\Quote $session */
        $session = $observer->getSession();
        $data = $observer->getRequestModel()->getPost();

        if(isset($data['is_preproduct']) && !$session->getQuote()->getItemsCount()){
            $session->setData(\Riki\Preorder\Model\Config\PreOrderType::SESSION_FLAG_NAME, 1);
        }
    }
}