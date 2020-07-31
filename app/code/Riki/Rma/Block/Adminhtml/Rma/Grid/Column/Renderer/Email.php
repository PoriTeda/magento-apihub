<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class Email extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $rmaFactory;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * Email constructor.
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Riki\Rma\Helper\Data $rmaHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Riki\Rma\Helper\Data $rmaHelper,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        $this->rmaFactory = $rmaFactory;
        $this->rmaHelper = $rmaHelper;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $model = $this->rmaFactory->create();
        $model->load($row->getId());
        $customer = $this->rmaHelper->getRmaCustomer($model);
        if ($customer) {
            return $customer->getEmail();
        }
        return '';
    }
}
