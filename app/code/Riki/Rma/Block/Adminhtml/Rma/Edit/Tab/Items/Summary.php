<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Summary extends \Magento\Backend\Block\Template
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     *@var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * Summary constructor.
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        $this->dataHelper = $dataHelper;
        $this->refundHelper = $refundHelper;
        $this->amountHelper = $amountHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return false;
        }

        return parent::getTemplate();
    }

    /**
     * Getter
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->dataHelper->getCurrentRma();
    }


    /**
     * Get refund allowed
     *
     * @return bool
     */
    public function getRefundAllowed()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return false;
        }
        return $this->getRma()->getData('refund_allowed');
    }

    /**
     * Get refund methods based on rma
     *
     * @return array
     */
    public function getRefundMethods()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return [];
        }

        if (!$this->getRefundAllowed()) {
            return [];
        }

        return $this->refundHelper->getRefundMethodsByPaymentMethod(
            $this->dataHelper->getRmaOrderPaymentMethodCode($this->getRma()),
            $this->getRma()
        );
    }

    /**
     * Is allowed to update refund_method
     *
     * @return bool
     */
    public function isAllowedUpdateRefundMethod()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_refund_actions_save_method');
    }
}