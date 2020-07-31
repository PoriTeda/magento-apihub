<?php
namespace Riki\Customer\Model;

use Magento\Framework\App\RequestInterface;

/** Maping to PETSHOP_CODE
 * Class AmbType
 * @package Riki\Customer\Model
 */
class Petshop extends \Magento\Eav\Model\Attribute\Data\AbstractData
{
    const CODE_1 = 1;       const TEXT_1 =  "I have a cat";
    const CODE_2 = 2;       const TEXT_2 =  "I have  dog";
    const CODE_3 = 3;       const TEXT_3 =  "I have a dog and a cat";
    const CODE_4 = 4;       const TEXT_4 =  "I do not keep pets";
    const CODE_5 = 5;       const TEXT_5 =  "I have other pets";
    const CODE_6 = 6;       const TEXT_6 =  "Do not register data";

    /**
     * Petshop constructor.
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
            self::CODE_1 => __(self::TEXT_1),
            self::CODE_2 => __(self::TEXT_2),
            self::CODE_3 => __(self::TEXT_3),
            self::CODE_4 => __(self::TEXT_4),
            self::CODE_5 => __(self::TEXT_5),
            self::CODE_6 => __(self::TEXT_6)
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

}