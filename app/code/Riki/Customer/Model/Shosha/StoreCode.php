<?php
namespace Riki\Customer\Model\Shosha;

use Magento\Framework\App\RequestInterface;

class StoreCode extends \Magento\Eav\Model\Attribute\Data\AbstractData
{
    const CODE_0003708471 = 1;       const TEXT_0 =  "0003708471: ITOCHU DDM";
    const CODE_0004480688 = 2;       const TEXT_1 =  "0004480688: Mitsubishi DDM";
    const CODE_0004638008 = 3;       const TEXT_2 =  "0004638008: Cedyna";
    const CODE_0005110776 = 4;       const TEXT_11 = "0005110776: Fukuzuen";
    const CODE_005618553  = 5;       const TEXT_5 =  "005618553: LUPICIA";

    /**
     * Shoshacode constructor.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Locale\ResolverInterface $localeResolver)
    {
        parent::__construct($localeDate, $logger, $localeResolver);
    }

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
    public function validateValue($value)
    {
        return parent::validateValue($value);
    }

    /**
     * Extract data from request and return value
     *
     * @param RequestInterface $request
     * @return array|string
     */
    public function extractValue(RequestInterface $request)
    {
        $value = $this->_getRequestValue($request);
        return $this->_applyInputFilter($value);
    }

    /**
     * Export attribute value to entity model
     *
     * @param array|string $value
     * @return $this
     */
    public function compactValue($value)
    {
        if ($value !== false) {
            $this->getEntity()->setDataUsingMethod($this->getAttribute()->getAttributeCode(), $value);
        }
        return $this;
    }

    /**
     * Restore attribute value from SESSION to entity model
     *
     * @param array|string $value
     * @return $this
     */
    public function restoreValue($value)
    {
        return $this->compactValue($value);
    }

    /**
     * Return formated attribute value from entity model
     *
     * @param string $format
     * @return string|array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function outputValue($format = AttributeDataFactory::OUTPUT_FORMAT_TEXT)
    {
        $value = $this->getEntity()
            ->getData($this->getAttribute()->getAttributeCode());
        $value = $this->_applyOutputFilter($value);
        return $value;
    }

    /**
     * @param $code
     * @return int|mixed
     */
    public function convertCodeToValue($code){
        $rs = [
            '0003708471' => 1,
            '0004480688' => 2,
            '0004638008' => 3,
            '0005110776' => 4
        ];

        if( !empty($rs[$code]) ){
            return $rs[$code];
        } else {
            return 0;
        }

    }

}