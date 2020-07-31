<?php
namespace Riki\ArReconciliation\Block\Adminhtml\Rma;

class Rma extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory
     */
    protected $_returnLogCollection;
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * Rma constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory $returnLogCollection
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\ArReconciliation\Model\ResourceModel\ReturnLog\CollectionFactory $returnLogCollection,
        \Riki\ArReconciliation\Helper\Data $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_returnLogCollection = $returnLogCollection;
        $this->_helperData = $helper;
        parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }
    /**
     * Constructor
     *
     * @return void
     */
    public function _prepareLayout(){

        $this->setTemplate('Riki_ArReconciliation::return/form.phtml');

        $onclick = "submitAndReloadArea($('rma_refund_information').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'save_button',
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
        if ($this->_authorization->isAllowed('Riki_Rma::rma_return_reconciliation')) {
            return $this->getChildHtml('save_button');
        }
        return ;
    }


    /*get shipment*/
    public function getRma()
    {
        return $this->_coreRegistry->registry('current_rma');
    }

    /*get shipment change log*/
    public function getChangeLog()
    {
        $model = $this->_returnLogCollection->create();

        $rmaLog = $model->addFieldToFilter(
            'rma_id', $this->getRma()->getId()
        )->setOrder(
            'id', 'DESC'
        );

        if( !empty( $rmaLog->getData() ) )
        {
            return $rmaLog;
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
        return $this->getUrl('importpaymentcsv/rma/edit', ['rma_id' => $this->getRma()->getId()]);
    }

    /**
     * export url
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->getUrl('importpaymentcsv/rma/log', ['rma_id' => $this->getRma()->getId()]);
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