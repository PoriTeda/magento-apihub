<?php
namespace Riki\ArReconciliation\Block\Adminhtml;

class Shipment extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\ShipmentLog\CollectionFactory
     */
    protected $_shipmentLogCollection;
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\ArReconciliation\Model\ResourceModel\ShipmentLog\CollectionFactory $shipmentLogCollection,
        \Riki\ArReconciliation\Helper\Data $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_shipmentLogCollection = $shipmentLogCollection;
        $this->_helperData = $helper;
        parent::__construct($context, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    public function _prepareLayout(){

        $this->setTemplate('Riki_ArReconciliation::shipment/form.phtml');

        $onclick = "submitAndReloadArea($('shipment_received_info').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'save_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Save'), 'class' => 'save removeButtonOnShipment', 'onclick' => $onclick]
        );

    }
    /**
     * Retrieve save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }


    /*get shipment*/
    public function getShipment()
    {
        return $this->_coreRegistry->registry('current_shipment');
    }

    /*get shipment change log*/
    public function getChangeLog()
    {
        $model = $this->_shipmentLogCollection->create();

        $shipmentLog = $model->addFieldToFilter(
            'shipment_id', $this->getShipment()->getId()
        )->setOrder(
            'id', 'DESC'
        );

        if( !empty( $shipmentLog->getData() ) )
        {
            return $shipmentLog;
        }

        return false;
    }

    /**
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('importpaymentcsv/shipment/edit', ['shipment_id' => $this->getShipment()->getId()]);
    }

    /**
     * export url
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->getUrl('importpaymentcsv/shipment/log', ['shipment_id' => $this->getShipment()->getId()]);
    }

    /**
     * @param $time
     * @param $type
     * @return string
     */
    public function getTimeLog($time, $type)
    {
        if( $type == 'date' )
        {
            return $this->_helperData->getWebDate($time);
        }
        else
        {
            return $this->_helperData->getWebTime($time);
        }
    }
    /**
     * @param $item
     * @return bool|int|string
     */
    public function getChangeFrom($item){
        return $this->_helperData->getChangeFrom($item, \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT);
    }

    /**
     * @param $item
     * @return bool|int|string
     */
    public function getChangeTo($item){
        return $this->_helperData->getChangeTo($item, \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT);
    }

}