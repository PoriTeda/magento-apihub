<?php
namespace Riki\ArReconciliation\Block\Adminhtml\Returns;

class Returns extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry;
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory
     */
    protected $_orderRefundLogCollection;
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * Returns constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory $orderReturnLogCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog\CollectionFactory $orderReturnLogCollection,
        array $data = []
    ) {

        $this->_coreRegistry = $registry;
        $this->_helperData = $helper;
        $this->_orderRefundLogCollection = $orderReturnLogCollection;
        $this->_authorization = $context->getAuthorization();
        parent::__construct($context, $data);
    }
    /**
     * Constructor
     *
     * @return void
     */
    public function _prepareLayout(){

        $this->setTemplate('Riki_ArReconciliation::refund/form.phtml');

        $onclick = "submitAndReloadArea($('order_return_information').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'returns_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Save'), 'class' => 'save', 'onclick' => $onclick]
        );

    }
    /**
     * Retrieve save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        if ($this->_authorization->isAllowed("Riki_Sales::sales_order_reconciliation")) {
            return $this->getChildHtml('returns_button');
        }
        return ;
    }


    /*get order*/
    public function getCurrentOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /*get shipment change log*/
    public function getChangeLog()
    {
        $model = $this->_orderRefundLogCollection->create();

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
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('importpaymentcsv/returns/edit', ['order_id' => $this->getCurrentOrder()->getId()]);
    }

    /**
     * export url
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->getUrl('importpaymentcsv/returns/log', ['order_id' => $this->getCurrentOrder()->getId()]);
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

    /**
     * @param $item
     * @return bool|int|string
     */
    public function getChangeFrom($item){
        return $this->_helperData->getChangeFrom($item, \Riki\ArReconciliation\Helper\Data::RETURN_OBJECT);
    }

    /**
     * @param $item
     * @return bool|int|string
     */
    public function getChangeTo($item){
        return $this->_helperData->getChangeTo($item, \Riki\ArReconciliation\Helper\Data::RETURN_OBJECT);
    }

}