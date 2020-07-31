<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Customer extends \Magento\Backend\Block\Template
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
     * Customer constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param mixed[] $data
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
     * Get customer balance
     *
     * @return float
     */
    public function getPointBalance()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return $this->amountHelper->getPointsBalance($this->getRma());
    }
}