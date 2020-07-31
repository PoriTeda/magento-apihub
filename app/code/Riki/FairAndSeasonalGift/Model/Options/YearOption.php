<?php
namespace Riki\FairAndSeasonalGift\Model\Options;

class YearOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Zend\Form\Element\MonthSelect
     */
    protected $_element;

    /**
     * Constructor
     *
     * @param \Zend\Form\ElementInterface
     */
    public function __construct(\Zend\Form\Element\MonthSelect $element)
    {
        $this->_element = $element;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $yearOptions   = $this->getYearsOptions($this->_element->getMinYear(), $this->_element->getMaxYear());
        $options = [];
        $options[] = [
            'label' => __('Select'),
            'value' => ''
        ];
        foreach ($yearOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key
            ];
        }
        return $options;
    }

    /*
     * get year option
     * $minYear, $maxYear: Int
     */
    protected function getYearsOptions($minYear, $maxYear)
    {
        $result = array();
        for ($i = $maxYear; $i >= $minYear; --$i) {
            $result[$i] = $i;
        }

        return $result;
    }
}
