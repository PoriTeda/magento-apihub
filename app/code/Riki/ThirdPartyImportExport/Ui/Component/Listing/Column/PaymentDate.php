<?php

namespace Riki\ThirdPartyImportExport\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Ui\Component\Listing\Columns\Column;
use Riki\ThirdPartyImportExport\Model\Order;
use Magento\Framework\Stdlib\DateTime\DateTime;

class PaymentDate extends Column
{
    protected $_orderLegacyModel;
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * System store
     *
     * @var SystemStore
     */
    protected $systemStore;

    /** @var DateTime  */
    protected $_datetime;

    /** @var \Magento\Framework\Stdlib\DateTime\Timezone  */
    protected $timezone;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemStore $systemStore
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        Escaper $escaper,
        array $components = [],
        Order $legacyOrderFactory,
        DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        array $data = []
    ) {
        $this->_datetime = $dateTime;
        $this->systemStore = $systemStore;
        $this->escaper = $escaper;
        $this->_orderLegacyModel = $legacyOrderFactory;
        $this->timezone = $timezone;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['payment_date'] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Get data
     *
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        return $this->formatDate($item['payment_date']);
    }

    /**
     * @param $date
     * @return string
     */
    protected function formatDate($date)
    {
        if (date('Y-m-d H:i:s', strtotime($date)) != $date) {
            return '';
        }

        return $this->timezone->formatDate($date, \IntlDateFormatter::MEDIUM);
    }


    public function formatDateNotUseTimezone($date)
    {
        return $this->_datetime->date('M d, Y, H:i:s A', $date);
    }
}