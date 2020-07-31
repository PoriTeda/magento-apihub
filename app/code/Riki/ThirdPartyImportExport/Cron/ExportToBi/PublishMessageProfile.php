<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class PublishMessageProfile
{
    /**
     * @var \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface
     */
    protected $profileItemBuilder;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * PublishMessageProfile constructor.
     * @param \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface $profileItemBuilder
     * @param \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\ProfileItemFactory $profileItemFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryHelper $subProfileNextDeliveryHelper
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface $profileItemBuilder,
        \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\ProfileItemFactory $profileItemFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryHelper $subProfileNextDeliveryHelper
    )
    {
        $this->profileItemBuilder = $profileItemBuilder;
        $this->publisher = $publisher;
        $this->_connectionSales = $resourceConnection->getConnection('sales');
        $this->_dataHelper = $globalHelper;
        $this->_timezone = $timezone;
        $this->filterBuilder =  $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository  = $profileRepository;
        $this->profileItemFactory  = $profileItemFactory;
        $this->subProfileNextDeliveryHelper = $subProfileNextDeliveryHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $lastTimeCronRunConfig = $this->_dataHelper->getConfig(\Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryHelper::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LAST_TIME_RUN);

        $selectProfile = $this->_connectionSales->select()->from([
            'sp' => $this->_connectionSales->getTableName('subscription_profile')
        ])
        ->where(new \Zend_Db_Expr(sprintf("sp.updated_date BETWEEN '%s' AND %s", $lastTimeCronRunConfig, new \Zend_Db_Expr('NOW()'))))
        ->where('next_order_date > ?',  new \Zend_Db_Expr('DATE_FORMAT(NOW(),"%Y-%m-%d")'))
        ->where("type !='tmp' OR type IS NULL");


        $collectionProfileQuery = $this->_connectionSales->query($selectProfile);

        while ($profileItemData = $collectionProfileQuery->fetch()) {
            $profileItem =  $this->profileItemFactory->create();
            $profileItem->setProfileId($profileItemData['profile_id']);
            $profileItemBuilder = $this->profileItemBuilder->setItems([$profileItem]);
            $this->publisher->publish('thirdparty.export.nextdelivery', $profileItemBuilder);
        }

        //export product for hanpukai
//        foreach (array(720,721,722,723,560,719) as $profile_Id) {
//            $profileItem =  $this->profileItemFactory->create();
//            $profileItem->setProfileId($profile_Id);
//            $profileItemBuilder = $this->profileItemBuilder->setItems([$profileItem]);
//            $this->publisher->publish('thirdparty.export.nextdelivery', $profileItemBuilder);
//            $temp[] = $profile_Id;
//        }

        // set last time cron run
        $dateTime = new \DateTime('', new \DateTimeZone('UTC'));
        $this->subProfileNextDeliveryHelper->setLastRunToCron($dateTime->format("Y-m-d H:i:s"));
    }
}
