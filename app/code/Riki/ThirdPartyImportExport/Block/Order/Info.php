<?php
namespace Riki\ThirdPartyImportExport\Block\Order;

use Magento\Framework\Stdlib\DateTime\DateTime;

class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'order/info.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Validator
     */
    protected $_validator;

    protected  $_datetime;
    /**
     * Info constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Validator $validator,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        DateTime $dateTime,
        array $data = []
    )
    {
        $this->_datetime = $dateTime;
        $this->_validator = $validator;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return \Riki\ThirdPartyImportExport\Model\Order|null
     */
    public function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * @inheritdoc
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    )
    {
        if (empty($date)) {
            return '';
        }
        if (!$this->_validator->isDate($date)) {
            if ($this->_validator->isDate($date, 'Y-m-d')) {
                return parent::formatDate($date, $format, $showTime, $timezone);
            }

            return '';
        }

        return parent::formatDate($date, $format, $showTime, $timezone);
    }

    public function formatDateNotUseTimeZone($date)
    {
        return $this->_datetime->date('Y/m/d', $date);
    }
}
