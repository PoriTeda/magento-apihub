<?php

namespace Riki\SubscriptionCourse\Model\Course\Source\Hanpukai;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class Month implements OptionSourceInterface
{
    const MONTHS_NUMBER = 12;

    protected $_options;

    protected function _getOptions(){
        if(!$this->_options){
            $this->_options = [];

            $date = new \Zend_Date();
            $this->_options[] = $date->get(\Zend_Date::YEAR) . '/' . $date->get(\Zend_Date::MONTH);

            for($i=1; $i<self::MONTHS_NUMBER; $i++){
                $date->addMonth(1);
                $this->_options[] = $date->get(\Zend_Date::YEAR) . '/' . $date->get(\Zend_Date::MONTH);
            }
        }

        return $this->_options;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach($this->_getOptions() as $option){
            $options[] = [
                'label' =>  $option,
                'value' =>  $option
            ];
        }

        return $options;
    }

    public function toArray(){
        $result = [];
        foreach($this->_getOptions() as $option){
            $result[$option] = $option;
        }

        return $result;
    }
}
