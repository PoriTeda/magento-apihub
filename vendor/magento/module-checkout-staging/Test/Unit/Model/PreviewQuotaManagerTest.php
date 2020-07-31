<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutStaging\Test\Unit\Model;

use Magento\CheckoutStaging\Model\PreviewQuotaManager;
use Magento\CheckoutStaging\Model\ResourceModel\PreviewQuota\Collection;
use Magento\CheckoutStaging\Model\ResourceModel\PreviewQuota\CollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartSearchResultsInterface;
use Magento\Store\Model\StoresConfig;

/**
 * Class PreviewQuotaManagerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewQuotaManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StoresConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storesConfig;

    /**
     * @var CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionFactory;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collection;

    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactory;

    /**
     * @var PreviewQuotaManager
     */
    private $pqm;

    public function setUp()
    {
        $this->storesConfig = $this->getMockBuilder(
            StoresConfig::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->getMockBuilder(
            SearchCriteriaBuilder::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFactory = $this->getMockBuilder(
            DateTimeFactory::class
        )->getMock();

        $this->collectionFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->collection);

        $this->pqm = new PreviewQuotaManager(
            $this->storesConfig,
            $this->cartRepository,
            $this->searchCriteriaBuilder,
            $this->collectionFactory,
            $this->dateTimeFactory
        );
    }

    public function testFlush()
    {
        $previewQuotas = [1, 3, 5, 7, 9];
        $lifetimes = [
            1 => 60
        ];
        $date = "2500-02-30 00:00:00";

        $this->collection->expects(static::once())
            ->method('getAllIds')
            ->willReturn($previewQuotas);
        $nowDate = $this->getMockBuilder(
            \DateTime::class
        )->disableOriginalConstructor()
            ->getMock();
        $nowDate->expects(static::once())
            ->method('format')
            ->with('Y-m-d H:i:s')
            ->willReturn($date);
        $nowDate->expects(static::once())
            ->method('sub')
            ->willReturnSelf();
        $this->dateTimeFactory->expects(static::once())
            ->method('create')
            ->with('now')
            ->willReturn($nowDate);

        $this->storesConfig->expects(static::once())
            ->method('getStoresConfigByPath')
            ->with(PreviewQuotaManager::QUOTA_LIFETIME_CONFIG_KEY)
            ->willReturn($lifetimes);

        $this->searchCriteriaBuilder->expects(static::exactly(3))
            ->method('addFilter')
            ->willReturnMap(
                [
                    ['entity_id', $previewQuotas, 'in', $this->searchCriteriaBuilder],
                    [CartInterface::KEY_STORE_ID, 1, 'eq', $this->searchCriteriaBuilder],
                    [CartInterface::KEY_UPDATED_AT, $date, 'to', $this->searchCriteriaBuilder]
                ]
            );

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->createMock(CartSearchResultsInterface::class);
        $this->searchCriteriaBuilder->expects(static::once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->cartRepository->expects(static::once())
            ->method('getList')
            ->willReturn($result);
        $result->expects(static::once())
            ->method('getItems')
            ->willReturn([]);

        $this->pqm->flushOutdated();
    }
}
