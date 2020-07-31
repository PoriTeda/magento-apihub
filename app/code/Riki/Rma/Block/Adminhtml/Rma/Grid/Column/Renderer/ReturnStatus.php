<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class ReturnStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;
    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\ReturnStatus
     */
    protected $_returnStatusSource;

    /**
     * ReturnStatus constructor.
     * @param \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        $this->_returnStatusSource = $returnStatusSource;
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

        return $this->_returnStatusSource->getLabel($model->getReturnStatus());
    }
}
