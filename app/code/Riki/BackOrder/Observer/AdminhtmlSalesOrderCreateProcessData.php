<?php
namespace Riki\BackOrder\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminhtmlSalesOrderCreateProcessData implements ObserverInterface
{

    protected $_helper;

    protected $_logger;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    public function __construct(
        \Riki\BackOrder\Helper\Admin $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $message
    ){
        $this->_helper = $helper;
        $this->_messageManager = $message;
        $this->_logger = $logger;
    }

    /**
     * validate back-order for hanpukai sequence case
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\AdminOrder\Create $orderCreate */
        $orderCreate = $observer->getEvent()->getOrderCreateModel();
        $postData = $observer->getEvent()->getRequest();

        $quote = $orderCreate->getQuote();

        if(isset($postData['course_id']) && isset($postData['hanpukai_qty'])){
            $isValid = true;

            try{
                $this->_helper->getBackOrderStatusByQuote($quote);
            }catch (\Exception $e){
                $this->_messageManager->addError($e->getMessage());
                $isValid = false;
            }

            if(!$isValid){
                try{
                    $quote->removeAllItems();
                }catch (\Exception $e){
                    $this->_logger->critical($e);
                }
            }
        }
    }
}