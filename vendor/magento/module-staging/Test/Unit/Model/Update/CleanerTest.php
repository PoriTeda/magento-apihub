<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateSearchResultInterface;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Update\Cleaner;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CleanerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateRepository|MockObject
     */
    private $updateRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var IncludesRetriever|MockObject
     */
    private $includesRetriever;

    /**
     * @var VersionHistoryInterface|MockObject
     */
    private $versionHistory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Cleaner
     */
    private $cleaner;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->updateRepository = $this->getMockBuilder(UpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this->createPartialMock(
            SearchCriteriaBuilder::class,
            ['create', 'addFilter']
        );
        $this->searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
        $this->searchCriteriaBuilder->expects($this->any())->method('addFilter')->willReturnself();

        $this->includesRetriever = $this->getMockBuilder(IncludesRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->versionHistory = $this->getMockBuilder(VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cleaner = $this->objectManager->getObject(Cleaner::class, [
            'updateRepository' => $this->updateRepository,
            'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            'includes' => $this->includesRetriever,
            'versionHistory' => $this->versionHistory
        ]);
    }

    /**
     * Checks a test case, when cleaner removes outdated updates.
     *
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testExecute()
    {
        $this->withCurrentHistoryVersion();
        $update1 = [
            'id' => 1491915120,
            'name' => 'Rule 1',
        ];
        $update2 = [
            'id' => 1491915220,
            'name' => 'Rule 2',
            'rollback_id' => 14919152340
        ];
        $updates = [$update1, $update2];
        $this->withRepositoryItems($updates, 0);

        $rollbacks = [
            [
                'id' => 14919152340,
                'name' => 'Rollback for Rule 2'
            ]
        ];
        $this->withRepositoryItems($rollbacks, 1);

        $updatesWithRollbacks = [$update2];
        $this->withRepositoryItems($updatesWithRollbacks, 2);

        // no moved updates
        $this->withRepositoryItems([], 3);
        // no includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([]);

        $updatesToDelete = [$update1, $update2];
        $items = $this->withRepositoryItems($updatesToDelete, 4);

        $index = 5;
        foreach ($items as $item) {
            $this->updateRepository->expects(self::at($index))
                ->method('delete')
                ->with($item);
            $index ++;
        }

        $this->cleaner->execute();
    }

    /**
     * Checks a test case, when cleaner removes rollbacks in the past without updates.
     *
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testOutdatedRollbacks()
    {
        $this->withCurrentHistoryVersion();
        // no active updates
        $this->withRepositoryItems([], 0);

        $rollback = [
            'id' => 14919152340,
            'name' => 'Rollback in the past'
        ];
        $this->withRepositoryItems([$rollback], 1);

        // no updates with rollbacks
        $this->withRepositoryItems([], 2);
        // no moved updates
        $this->withRepositoryItems([], 3);
        // no includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([]);

        $updatesToDelete = $this->withRepositoryItems([$rollback], 4);

        $this->updateRepository->method('delete')
            ->with(array_pop($updatesToDelete));

        $this->cleaner->execute();
    }

    /**
     * Checks a test case, when cleaner does not remove anything.
     *
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testNothingToRemove()
    {
        $this->withCurrentHistoryVersion();

        $update1 = [
            'id' => 1491915120,
            'name' => 'Rule 1',
        ];
        $update2 = [
            'id' => 1491915220,
            'name' => 'Rule 2',
        ];
        $updates = [$update1, $update2];
        $this->withRepositoryItems($updates, 0);

        // no rollbacks
        $this->withRepositoryItems([], 1);
        // no updates with rollbacks
        $this->withRepositoryItems([], 2);

        // moved to updates
        $this->withRepositoryItems([
            [
                'id' => 1491915236,
                'moved_to' => 1491915120
            ]
        ], 3);

        // includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([
                [
                    'id' => 14919152890,
                    'created_in' => 1491915220
                ]
            ]);

        $this->updateRepository->expects(self::never())
            ->method('delete');

        $this->cleaner->execute();
    }

    /**
     * Imitates behavior of version history manager, which returns version id as current timestamp.
     *
     * @return void
     */
    private function withCurrentHistoryVersion()
    {
        $this->versionHistory->method('getCurrentId')
            ->willReturn(time());
    }

    /**
     * Imitates behavior of UpdateRepository, which returns different set of updates depends on context.
     *
     * @param array $data
     * @param int $index
     * @return Update[]
     */
    private function withRepositoryItems(array $data, $index)
    {
        $items = [];
        foreach ($data as $value) {
            $items[$value['id']] = $this->objectManager->getObject(Update::class, ['data' => $value]);
        }

        /** @var UpdateSearchResultInterface|MockObject $searchResult */
        $searchResult = $this->getMockBuilder(UpdateSearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult
            ->method('getItems')
            ->willReturnCallback(function () use ($items) {
                return $items;
            });

        $this->updateRepository->expects(self::at($index))
            ->method('getList')
            ->willReturn($searchResult);

        return $items;
    }
}
