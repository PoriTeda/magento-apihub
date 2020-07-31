<?php
namespace Riki\ArReconciliation\Controller\Adminhtml\Returns;
class Log extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory
     */
    protected $_orderReturnLogCollection;

    /**
     * Log constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory $orderReturnLogCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory $orderReturnLogCollection
    ){
        $this->_helperData = $helper;
        $this->_orderReturnLogCollection = $orderReturnLogCollection;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');

        $model = $this->_orderReturnLogCollection->create();

        $order = $model->addFieldToFilter( 'order_id', $id )
                    ->setOrder( 'id', 'DESC');

        return $this->_helperData->exportChangLog(
            $order, \Riki\ArReconciliation\Helper\Data::RETURN_OBJECT, 'order_increment_id'
        );
    }
}
