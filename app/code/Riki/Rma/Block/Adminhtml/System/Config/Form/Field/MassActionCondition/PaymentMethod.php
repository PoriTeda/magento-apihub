<?php
namespace Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition;

class PaymentMethod extends FieldAbstract
{
    /**
     * @var \Riki\Rma\Model\Config\Source\Payment\Method
     */
    protected $paymentMethodSource;

    /**
     * PaymentMethod constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Riki\Rma\Model\Config\Source\Payment\Method $paymentMethodSource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Riki\Rma\Model\Config\Source\Payment\Method $paymentMethodSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentMethodSource = $paymentMethodSource;
    }

    /**
     * @return array
     */
    protected function _getOptions()
    {
        if (!$this->options) {
            $this->options = $this->paymentMethodSource->toOptionArray();
        }
        return $this->options;
    }
}
