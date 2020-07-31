<?php
namespace Riki\Customer\Model\Shosha;

use Magento\Framework\App\RequestInterface;

class ShoshaCode extends \Magento\Eav\Model\Attribute\Data\AbstractData
{
    const ITOCHU = 1;       const TEXT_0 =  "ITOCHU";
    const MC = 2;           const TEXT_1 =  "MC";
    const CEDYNA = 3;       const TEXT_2 =  "CEDYNA";
    const FKJEN = 4;        const TEXT_11 =  "FKJEN";
    const LUPICIA = 5;      const TEXT_5 = "LUPICIA";

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
            'ITOCHU' => self::ITOCHU,
            'MC' => self::MC,
            'CEDYNA' => self::CEDYNA,
            'FKJEN' => self::FKJEN,
            'LUPICIA' => self::LUPICIA
        ];

        if( !empty($rs[$code]) ){
            return $rs[$code];
        } else {
            return 0;
        }

    }
}