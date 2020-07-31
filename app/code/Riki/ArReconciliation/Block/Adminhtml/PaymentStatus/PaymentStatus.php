<?php
namespace Riki\ArReconciliation\Block\Adminhtml\PaymentStatus;

class PaymentStatus extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /*
     * @var \Riki\Shipment\Model\ResourceModel\Status\Options\Payment
     */
    protected $_paymentStatus = null;

    protected $_orderPaymentStatusLogCollection;

    protected $_helperData;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatus,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\ArReconciliation\Model\ResourceModel\OrderPaymentStatusLog\CollectionFactory $orderPaymentStatusLog,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_paymentStatus = $paymentStatus;
        $this->_orderPaymentStatusLogCollection = $orderPaymentStatusLog;
        $this->_helperData = $helper;
        parent::__construct($context, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    public function _prepareLayout(){

        $this->setTemplate('Riki_ArReconciliation::payment/form.phtml');

        $onclick = "submitAndReloadArea($('payment_status_information').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'payment_status_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Save'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );

    }

    public function getPaymentStatusOption()
    {
        return $this->_paymentStatus->getPaymentStatusByMethod( $this->getCurrentOrder()->getPayment()->getMethodInstance()->getCode() );
    }

    public function getPaymentStatusValue($status)
    {
        return $this->_paymentStatus->getOptionText($status);
    }

    /*get shipment change log*/
    public function getChangeLog()
    {
        $model = $this->_orderPaymentStatusLogCollection->create();

        $orderLog = $model->addFieldToFilter(
            'order_id', $this->getCurrentOrder()->getId()
        )->setOrder(
            'id', 'DESC'
        );

        if( !empty( $orderLog->getData() ) )
        {
            return $orderLog;
        }

        return false;
    }

    /**
     * Retrieve save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('payment_status_button');
    }


    /*get order*/
    public function getCurrentOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('importpaymentcsv/payment/edit', ['order_id' => $this->getCurrentOrder()->getId()]);
    }

    public function getExportUrl()
    {
        return $this->getUrl('importpaymentcsv/payment/log', ['order_id' => $this->getCurrentOrder()->getId()]);
    }

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
}