<?php
namespace Riki\Customer\Model;

use Magento\Framework\App\RequestInterface;

/** Maping to AMB_TYPE
 * Class AmbType
 * @package Riki\Customer\Model
 */
class AmbType extends \Magento\Eav\Model\Attribute\Data\AbstractData
{
    const CODE_0 = 0;       const TEXT_0 =  "Not ambassador";
    const CODE_1 = 1;       const TEXT_1 =  "Ambassador";
    const CODE_9 = 9;       const TEXT_9 =  "Resigned";

    /**
     * AmbType constructor.
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
            self::CODE_0 => __(self::TEXT_0),
            self::CODE_1 => __(self::TEXT_1),
            self::CODE_9 => __(self::TEXT_9)
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
     * @param $value
     * @return mixed|string
     */
    public function getTitle($value) {
        $options = self::getOptionArray();

        if (isset($options[$value])) {
            return $options[$value];
        }

        return '';
    }
}