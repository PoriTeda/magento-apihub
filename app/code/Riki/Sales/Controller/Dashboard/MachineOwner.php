<?php
namespace Riki\Sales\Controller\Dashboard;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemcollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class MachineOwner extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Riki\Sales\Model\ProductMachineOwner
     */
    protected $_productMachineOwner;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Sales\Model\ProductMachineOwner $productMachineOwner,
        Context $context
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_productMachineOwner =$productMachineOwner;
        parent::__construct($context);
    }


    /**
     *
     * @return $this
     */
    public function execute(){

        $dataJson = $this->_productMachineOwner->getListProductPurchaseHistory();
        if ($this->getRequest()->isAjax()){
            $result = $this->resultJsonFactory->create();
            return $result->setData($dataJson);
        }
    }




}