<?php
namespace Riki\Customer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class StoreCode implements OptionSourceInterface
{
    const CODE_0003708471 = 1;       const TEXT_0 =  "0003708471: ITOCHU DDM";
    const CODE_0004480688 = 2;       const TEXT_1 =  "0004480688: Mitsubishi DDM";
    const CODE_0004638008 = 3;       const TEXT_2 =  "0004638008: Cedyna";
    const CODE_0005110776 = 4;       const TEXT_11 = "0005110776: Fukuzuen";
    const CODE_005618553  = 5;       const TEXT_5 =  "005618553: LUPICIA";
    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::CODE_0003708471 => __(self::TEXT_0),
            self::CODE_0004480688 => __(self::TEXT_1),
            self::CODE_0004638008 => __(self::TEXT_2),
            self::CODE_0005110776 => __(self::TEXT_11),
            self::CODE_005618553  => __(self::TEXT_5)
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