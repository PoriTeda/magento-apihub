<?php
namespace Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition;

class Reason extends FieldAbstract
{
    /**
     * @var \Riki\Rma\Model\Config\Source\Reason
     */
    protected $reasonSource;

    /**
     * Reason constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Riki\Rma\Model\Config\Source\Reason $reasonSource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Riki\Rma\Model\Config\Source\Reason $reasonSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->reasonSource = $reasonSource;
    }

    /**
     * @return array
     */
    protected function _getOptions()
    {
        if (!$this->options) {
            $this->options = $this->reasonSource->toOptionArray();
        }
        return $this->options;
    }
}
