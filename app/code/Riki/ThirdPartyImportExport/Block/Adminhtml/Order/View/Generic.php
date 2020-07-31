<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\View;

use Magento\Framework\Stdlib\DateTime\DateTime;

class Generic extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Validator
     */
    protected $_validator;
    protected $_datetime;

    /**
     * Generic constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Validator $validator,
        \Magento\Framework\Registry $registry,
        \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory,
        \Magento\Backend\Block\Template\Context $context,
        DateTime $dateTime,
        array $data = []
    )
    {
        $this->_datetime = $dateTime;
        $this->_validator = $validator;
        $this->_orderFactory = $orderFactory;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return \Riki\ThirdPartyImportExport\Model\Order
     */
    public function getOrder()
    {
        $order = $this->_registry->registry('current_order');
        if ($order) {
            return $order;
        }

        $order = $this->_orderFactory
            ->create()
            ->load($this->getRequest()->getParam('id'));
        $this->_registry->register('current_order', $order);

        return $order;
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

    public function formatDateNotUseTimezone($date)
    {
        return $this->_datetime->date('M d, Y', $date);
    }

}
