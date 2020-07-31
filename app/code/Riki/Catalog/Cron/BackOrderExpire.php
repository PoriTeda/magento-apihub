<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Catalog\Cron;

class BackOrderExpire
{
    const CONFIG_LAST_TIME_CRON = 'catalog/backorder_expire_cron/cron_last_time';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
     */
    protected $stockRepository;

    /**
     * @var \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory
     */
    protected $stockCollectionFactory;

    /**
     * @var \Wyomind\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;

    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilderFactory
     */
    protected $loggerBuilder;

    /**
     * @var \Magento\Framework\Logger\Monolog
     */
    protected $logger;

    /**
     * BackOrderExpire constructor.
     *
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $stockCollectionFactory
     * @param \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $stockCollectionFactory,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->stockRepository = $stockRepository;
        $this->stockCollectionFactory = $stockCollectionFactory;
        $this->stockFactory = $stockFactory;
        $this->loggerBuilder = $loggerBuilder;
    }

    public function execute()
    {
        $this->logger = $this->getLogger();

        $this->setLastTimeCronRun();

        $this->logger->info('Cron to reset back order config for expired item: Start');

        $expiredBackOrderSettingItems = $this->getExpiredBackOrderConfigItems();

        if ($expiredBackOrderSettingItems) {
            foreach ($expiredBackOrderSettingItems as $item) {
                $this->resetBackOrderConfig($item);
            }
        } else {
            $this->logger->info('Do not have any item need to updated.');
        }

        $this->logger->info('Cron to reset back order config for expired item: End');
    }

    /**
     * get logger object
     *
     * @return mixed
     */
    protected function getLogger()
    {
        /** @var \Magento\Framework\Logger\Monolog $logger */
        $logger = $this->loggerBuilder->create()
            ->setName('ResetBackOrderConfig')
            ->setFileName('reset_back_order_config.log')
            ->pushHandlerByAlias(
                \Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER
            )->create();

        $logger->setTimezone($this->timezone->date()->getTimezone());

        return $logger;
    }

    /**
     * get list of item which back order config is expired
     *
     * @return bool|\Magento\Framework\DataObject[]
     */
    protected function getExpiredBackOrderConfigItems()
    {
        $currentDate = $this->timezone->date()->format('Y-m-d');

        /** @var \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\Collection $stockCollection */
        $stockCollection = $this->stockCollectionFactory->create();
        $stockCollection->addFieldToFilter('backorder_allowed', 1)
            ->addFieldToFilter(
                ['backorder_expire', 'backorder_expire'],
                [['lteq' => $currentDate], ['null' => true]]
            );

        if ($stockCollection->getSize()) {
            return $stockCollection->getItems();
        }

        return false;
    }

    /**
     * @param \Magento\Framework\DataObject $item
     */
    protected function resetBackOrderConfig($item)
    {
        $stockItem = $this->getStockItemById($item->getId());

        if (!$stockItem) {
            $this->logger->info('Item id #'.$item->getId().' is invalid.');
            return;
        }

        $this->logger->info('Reset back order config for item #'. $stockItem->getId());

        /*item is not allowed back order*/
        if (!$stockItem->getData('backorder_allowed')) {
            $this->logger->info('Item #'. $stockItem->getId().' is not allowed back order.');
            return;
        }

        /*item is not expired to day*/
        if (!$this->isExpired($stockItem)) {
            $this->logger->info(
                'Item #'. $stockItem->getId().' is not expired today. Expired date is '.
                $stockItem->getData('backorder_expire')
            );
            return;
        }

        $stockItem->setData('backorder_allowed', 0);
        $stockItem->setData('backorder_limit', 0);
        $stockItem->setData('backorder_expire', null);

        try {
            $stockItem->save();
            $this->stockRepository->updateInventory($stockItem->getProductId());
            $this->logger->info('Item #'. $stockItem->getId().' has been changed to normal.');
        } catch (\Exception $e) {
            $this->logger->info('Cannot change item #'. $stockItem->getId().' to normal.');
            $this->logger->critical('Item #'. $stockItem->getId().' error: '.$e->getMessage());
        }
    }

    /**
     * get stock item by id
     *
     * @param $itemId
     * @return bool|\Wyomind\AdvancedInventory\Model\Stock
     */
    protected function getStockItemById($itemId)
    {
        $stockItem = $this->stockFactory->create();
        $stockItem->load($itemId);

        if ($stockItem->getId()) {
            return $stockItem;
        }

        return false;
    }

    /**
     * validate expired date
     *
     * @param $item
     * @return bool
     */
    protected function isExpired($item)
    {
        if (!$item->getData('backorder_expire')) {
            return true;
        }

        $today = $this->dateTime->timestamp($this->timezone->date()->format('Y-m-d'));

        $comparedDate = $this->dateTime->timestamp($item->getData('backorder_expire'));

        if ($today >= $comparedDate) {
            return true;
        }

        return false;
    }

    /**
     * set last time that this cron was run
     */
    protected function setLastTimeCronRun()
    {
        $dateTime = $this->timezone->date(null, null, false);

        $this->resourceConfig->saveConfig(
            self::CONFIG_LAST_TIME_CRON,
            $dateTime->format("Y-m-d H:i:s"),
            'default',
            0
        );
    }
}

