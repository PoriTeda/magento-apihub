<?php

namespace Riki\CatalogRule\Helper;

class WbsConversionHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Riki\CatalogRule\Model\ResourceModel\WbsConversion\CollectionFactory
     */
    protected $wbsConversionCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\CatalogRule\Model\ResourceModel\WbsConversion\CollectionFactory $wbsConversionCollectionFactory
    ) {
        parent::__construct($context);
        $this->dateTime = $dateTime;
        $this->wbsConversionCollectionFactory = $wbsConversionCollectionFactory;
    }

    /**
     * Convert wbs for SAP exported
     *
     * @param $wbs
     * @return mixed
     */
    public function convertWbsForSapExported($wbs)
    {
        $datetime = $this->dateTime->gmtDate();
        /** @var \Riki\CatalogRule\Model\ResourceModel\WbsConversion\Collection $wbsConversionCollection */
        $wbsConversionCollection = $this->wbsConversionCollectionFactory->create();

        $wbsConversionCollection->addFieldToFilter(
            'old_wbs', $wbs
        )->addFieldToFilter(
            'is_active', \Riki\CatalogRule\Model\WbsConversion::STATUS_ACTIVE
        )->addFieldToFilter(
            'from_datetime', ['lteq' => $datetime]
        )->addFieldToFilter(
            'to_datetime', ['gteq' => $datetime]
        );

        if ($wbsConversionCollection->getSize()) {
            $wbs = $wbsConversionCollection->setPageSize(1)->getFirstItem()->getNewWbs();
        }

        return $wbs;
    }
}
