<?php
namespace Riki\Preorder\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminOrderCreateProcessData implements ObserverInterface
{
    protected $_quoteSession;

    protected $_preorderAdminHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Riki\Preorder\Helper\Admin $preorderHelperAdmin,
        \Magento\Framework\App\Action\Context $context
    ){
        $this->_quoteSession = $quoteSession;
        $this->_preorderAdminHelper = $preorderHelperAdmin;
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * validate cart items with pre-order rule
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $quote = $this->_quoteSession->getQuote();

        if($this->_preorderAdminHelper->isPreOrderCart()){

            $del = 0;
            $hasValidItem = false;
            $productId = null;

            foreach($quote->getAllVisibleItems() as $item){

                if(!$hasValidItem){
                    if($this->_preorderAdminHelper->checkCanPreOrder($item->getProductId())){
                        $hasValidItem = true;
                        $productId = $item->getProductId();
                    }else{
                        $quote->deleteItem($item);
                        $this->messageManager->addError(__('Pre-Order do not allow to add this product, sku: %1', $item->getSku()));
                        $del++;
                    }
                }else{
                    if($item->getProductId() != $productId){
                        $quote->deleteItem($item);
                        $del++;
                    }
                }
            }

            if($del){
                $this->messageManager->addError(__('Pre-Order do not allow to add more product.'));
            }
        }else{
            foreach($quote->getAllItems() as $item){

                if($this->_preorderAdminHelper->checkCanPreOrder($item->getProductId())){
                    $quote->deleteItem($item);
                    $this->messageManager->addError(__('We could not add a pre-product, sku: %1', $item->getSku()));
                }
            }
        }
    }
}