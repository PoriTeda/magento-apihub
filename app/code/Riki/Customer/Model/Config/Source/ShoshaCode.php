<?php
namespace Riki\Customer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ShoshaCode implements OptionSourceInterface
{
    const ITOCHU = 1;       const TEXT_0 =  "ITOCHU";
    const MC = 2;           const TEXT_1 =  "MC";
    const CEDYNA = 3;       const TEXT_2 =  "CEDYNA";
    const FKJEN = 4;       const TEXT_11 =  "FKJEN";
    const LUPICIA = 5;     const TEXT_5 =  "LUPICIA";

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::ITOCHU => __(self::TEXT_0),
            self::MC => __(self::TEXT_1),
            self::CEDYNA => __(self::TEXT_2),
            self::FKJEN => __(self::TEXT_11),
            self::LUPICIA => __(self::TEXT_5)
        ];
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }
    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOptions()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}