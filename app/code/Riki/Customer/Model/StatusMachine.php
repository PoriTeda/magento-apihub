<?php
namespace Riki\Customer\Model;

use Magento\Framework\App\RequestInterface;

class StatusMachine extends \Magento\Eav\Model\Attribute\Data\AbstractData
{
    const MACHINE_STATUS_VALUE_NOT_APPLICABLE = 0;
    const MACHINE_STATUS_LABEL_NOT_APPLICABLE = 'Not applicable';
    const MACHINE_STATUS_VALUE_IN_RENTAL = 1;
    const MACHINE_STATUS_LABEL_IN_RENTAL = 'In rental';
    const MACHINE_STATUS_VALUE_REQUESTED = 2;
    const MACHINE_STATUS_LABEL_REQUESTED = 'Requested';
    const MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE = 3;
    const MACHINE_STATUS_LABEL_PENDING_FOR_MACHINE = 'Need to be Attached with "PENDING_FOR_MACHINE"';
    const MACHINE_STATUS_VALUE_OOS = 11;
    const MACHINE_STATUS_LABEL_OOS = 'Machine attachment error (OOS)';
    const MACHINE_STATUS_VALUE_NOT_APPLICABLE_PRODUCT_PURCHASED = 12;
    const MACHINE_STATUS_LABEL_NOT_APPLICABLE_PRODUCT_PURCHASED =
        'Machine attachment error (not applicable product purchased)';
    const MACHINE_STATUS_VALUE_NOT_PURCHASED = 13;
    const MACHINE_STATUS_LABEL_NOT_PURCHASED = 'Machine attachment (not purchased through applicable subscription)';
    const MACHINE_STATUS_VALUE_RETURN = 91;
    const MACHINE_STATUS_LABEL_RETURN = 'Return';

    /**
     * StatusMachine constructor.
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
            self::MACHINE_STATUS_VALUE_NOT_APPLICABLE => __(self::MACHINE_STATUS_LABEL_NOT_APPLICABLE),
            self::MACHINE_STATUS_VALUE_IN_RENTAL => __(self::MACHINE_STATUS_LABEL_IN_RENTAL),
            self::MACHINE_STATUS_VALUE_REQUESTED => __(self::MACHINE_STATUS_LABEL_REQUESTED),
            self::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE => __(self::MACHINE_STATUS_LABEL_PENDING_FOR_MACHINE),
            self::MACHINE_STATUS_VALUE_OOS => __(self::MACHINE_STATUS_LABEL_OOS),
            self::MACHINE_STATUS_VALUE_NOT_APPLICABLE_PRODUCT_PURCHASED => __(self::MACHINE_STATUS_LABEL_NOT_APPLICABLE_PRODUCT_PURCHASED),
            self::MACHINE_STATUS_VALUE_NOT_PURCHASED => __(self::MACHINE_STATUS_LABEL_NOT_PURCHASED),
            self::MACHINE_STATUS_VALUE_RETURN => __(self::MACHINE_STATUS_LABEL_RETURN)
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