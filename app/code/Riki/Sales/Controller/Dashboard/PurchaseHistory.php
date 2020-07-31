<?php
namespace Riki\Sales\Controller\Dashboard;


class PurchaseHistory extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var
     */
    protected $_productPurchaseHistory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Riki\Sales\Model\ProductPurchaseHistory $productPurchaseHistory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Registry $coreRegistry
    ){
        $this->_coreRegistry           = $coreRegistry;
        $this->_productPurchaseHistory = $productPurchaseHistory;
        $this->_resultJsonFactory      = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     *
     * @return $this
     */
    public function execute(){
        if ($this->getRequest()->isAjax()){
            $page     = 1;
            $pageItem =$value = $this->getRequest()->getParam('page');
            if($pageItem >1){
                $page = $pageItem;
            }
            $dataJson = $this->_productPurchaseHistory->getListProductPurchaseHistory($page);
            $result = $this->_resultJsonFactory->create();
            return $result->setData($dataJson);
        }
    }


}