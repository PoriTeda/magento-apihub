<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class RefundStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;
    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\RefundStatus
     */
    protected $_refundStatusSource;

    /**
     * RefundStatus constructor.
     * @param \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        $this->_refundStatusSource = $refundStatusSource;
        $this->_rmaFactory = $rmaFactory;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $model = $this->_rmaFactory->create();
        $model->load($row->getId());

        if (!$model->getId()) {
            return '';
        }

        return $this->_refundStatusSource->getLabel($model->getRefundStatus());
    }
}
