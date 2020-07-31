<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Total extends \Magento\Backend\Block\Template
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * Total constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
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
     * Get return amount
     *
     * @return float|int
     */
    public function getItemsReturnAmount()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return $this->amountHelper->getReturnAmount($this->getRma());
    }

    /**
     * Get return amount
     *
     * @return int|mixed
     */
    public function getReturnAmountAdjusted()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('total_return_amount_adjusted'))) {
            return intval($this->getRma()->getData('total_return_amount_adjusted'));
        }

        return 0;
    }

    /**
     * Get total return amount adj
     *
     * @return int|mixed
     */
    public function getReturnAmountAdj()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('total_return_amount_adj'))) {
            return intval($this->getRma()->getData('total_return_amount_adj'));
        }

        return 0;
    }

    /**
     * Get refund without product
     *
     * @return int|mixed
     */
    public function getRefundWithoutProduct()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if ($this->getRma()->getIsWithoutGoods()
            && intval($this->getRma()->getData('refund_without_product'))
        ) {
            return intval($this->getRma()->getData('refund_without_product'));
        }

        return 0;
    }
}
