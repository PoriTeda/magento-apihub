<?php
namespace Riki\ArReconciliation\Controller\Adminhtml\Order;
class Log extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\OrderLog\CollectionFactory
     */
    protected $_orderLogCollection;

    /**
     * Log constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param \Riki\ArReconciliation\Model\ResourceModel\OrderLog\CollectionFactory $orderLogCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\ArReconciliation\Model\ResourceModel\OrderLog\CollectionFactory $orderLogCollection
    ){
        $this->_helperData = $helper;
        $this->_orderLogCollection = $orderLogCollection;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');

        $model = $this->_orderLogCollection->create();

        $order = $model->addFieldToFilter( 'order_id', $id )
                    ->setOrder( 'id', 'DESC');

        return $this->_helperData->exportChangLog(
            $order, \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT, 'order_increment_id'
        );
    }
}
