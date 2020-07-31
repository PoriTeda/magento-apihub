<?php
namespace Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition;

class FieldAbstract extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getOptions() as $option) {
                if (isset($option['value']) && $option['value'] && isset($option['label']) && $option['label']) {
                    $this->addOption($option['value'], $this->escapeQuote($option['label']));
                }
            }
        }
        $this->setClass('cc-type-select');
        $this->setExtraParams('multiple="multiple"');
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    protected function _getOptions()
    {
        if (!$this->options) {
            $this->options = [];
        }
        return $this->options;
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value . '[]');
    }
}
