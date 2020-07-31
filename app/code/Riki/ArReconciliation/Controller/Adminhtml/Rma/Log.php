<?php
namespace Riki\ArReconciliation\Controller\Adminhtml\Rma;
class Log extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory
     */
    protected $_returnLogCollection;

    /**
     * Log constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory $returnLogCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory $returnLogCollection
    ){
        $this->_helperData = $helper;
        $this->_returnLogCollection = $returnLogCollection;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('rma_id');
        $model = $this->_returnLogCollection->create();
        $rma = $model->addFieldToFilter( 'rma_id', $id )
                    ->setOrder( 'id', 'DESC');
        return $this->_helperData->exportChangLog(
            $rma, \Riki\ArReconciliation\Helper\Data::RETURN_OBJECT, 'rma_increment_id'
        );
    }
}
