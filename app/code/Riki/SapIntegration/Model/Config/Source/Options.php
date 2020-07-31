<?php
namespace Riki\SapIntegration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Options implements ArrayInterface
{

    const SETTINGS_2016 = '2016';
    const SETTINGS_2017 = '2017';

    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    public function toArray()
    {
        return [
            self::SETTINGS_2016 => "2016 setting",
            self::SETTINGS_2017 => "2017 setting",
        ];
    }
}