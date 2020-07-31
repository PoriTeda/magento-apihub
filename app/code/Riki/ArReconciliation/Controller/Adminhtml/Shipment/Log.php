<?php
namespace Riki\ArReconciliation\Controller\Adminhtml\Shipment;
class Log extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\ShipmentLog\CollectionFactory
     */
    protected $_shipmentLogCollection;
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\ArReconciliation\Model\ResourceModel\ShipmentLog\CollectionFactory $shipmentLogCollection,
        \Riki\ArReconciliation\Helper\Data $helper
    ){
        $this->_shipmentLogCollection = $shipmentLogCollection;
        $this->_helperData = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('shipment_id');
        $model = $this->_shipmentLogCollection->create();
        $shipment = $model->addFieldToFilter( 'shipment_id', $id )
                    ->setOrder( 'id', 'DESC');

        return $this->_helperData->exportChangLog(
            $shipment, \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT, 'shipment_increment_id'
        );
    }
}
