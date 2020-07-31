<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class CustomerType extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * CustomerType constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        $this->scopeHelper = $scopeHelper;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $typeSelection = explode(',',$row->getCustomerType());
        $html = '';
        if(count($typeSelection)>0) {
            $options = $this->getColumn()->getOptions();
            foreach($typeSelection as $value) {
                if(array_key_exists($value, $options))
                    $html .= $options[$value].'</br>';
            }
        }

        $func = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        if ($this->scopeHelper->isInFunction($func)) {
            return trim(str_replace('</br>', ',', $html), ',');
        }

        return $html ;
    }
}
