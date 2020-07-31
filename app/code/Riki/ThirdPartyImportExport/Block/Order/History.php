<?php
namespace Riki\ThirdPartyImportExport\Block\Order;

use Riki\ThirdPartyImportExport\Helper\Data as HelperData;
use Magento\Framework\Stdlib\DateTime\DateTime;

class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'order/history.phtml';

    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Collection
     */
    protected $_orders;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Validator
     */
    protected $_validator;

    protected $_stdTimezone;
    protected $_helper;
    protected $_datetime;
    /**
     * History constructor.
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Validator $validator,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        HelperData $helper,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        DateTime $dateTime,
        array $data = []
    )
    {
        $this->_datetime = $dateTime;
        $this->_helper = $helper;
        $this->_stdTimezone = $stdTimezone;
        $this->_validator = $validator;
        $this->_orderCollectionFactory = $collectionFactory;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get orders match consumer id
     *
     * @return array|\Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        if ($this->_orders) {
            return $this->_orders;
        }

        $customer = $this->_customerSession->getCustomer();
        if (!$customer->getId()) {
            return [];
        }

        if (!($consumerId = $customer->getData('consumer_db_id'))) {
            return [];
        }

        $xYear = (int)$this->_helper->getConfig(HelperData::CONFIG_ORDER_IMPORT_X_YEAR);
        $currentDate = $this->_stdTimezone->date();
        $currentDate = $currentDate->sub(date_interval_create_from_date_string($xYear.' years'));

        $this->_orders = $this->_orderCollectionFactory->create();
        $this->_orders->addFieldToFilter('customer_code', $consumerId)
            ->addFieldToFilter('free_shipping_flag', ['neq' => 1])
            ->addFieldToFilter('payment_method_type', ['neq' => '00'])
            ->addFieldToFilter('order_datetime', ['gteq' => $currentDate->format('Y-m-d H:i:s')])
            ->setOrder('created_datetime', 'desc');

        return $this->_orders;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getOrders()) {
            $pager = $this->getLayout()
                ->createBlock(
                    'Magento\Theme\Block\Html\Pager',
                    'thirdpartyimportexport.order.history.pager'
                )
                ->setLimitVarName('limit1')
                ->setPageVarName('p1')
                ->setCollection($this->getOrders());

            $this->setChild('pager', $pager);

            $this->getOrders()->load();
        }

        return $this;
    }

    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
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
        return $this->_datetime->date('Y/m/d', $date);
    }
}
