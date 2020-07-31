<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History as StatusHistory;

/**
 * Class HistoryPluginTest
 * @package Magento\Rma\Block\Adminhtml\Order\View\Tab
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HistoryPlugin
     */
    private $historyPlugin;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\Collection | \PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaCollection;

    /**
     * @var CollectionFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $historyCollectionFactory;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rmaCollection = $this->createMock(\Magento\Rma\Model\ResourceModel\Rma\Collection::class);
        $this->rmaCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->will($this->returnSelf());
        $this->historyCollectionFactory = $this->createPartialMock(
            \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory::class,
            ['create']
        );
        $this->historyPlugin = $objectManager->getObject(
            \Magento\Rma\Block\Adminhtml\Order\View\Tab\HistoryPlugin::class,
            [
                'rmaCollection' => $this->rmaCollection,
                'historyCollectionFactory' => $this->historyCollectionFactory
            ]
        );
    }

    /**
     * @dataProvider afterGetFullHistoryProvider
     * @param array $returnsOptions
     * @param array $originalHistory
     */
    public function testAfterGetFullHistory(array $returnsOptions, array $originalHistory = [])
    {
        list($historyCollection, $returns, $original, $expected) = $this->getCase($returnsOptions, $originalHistory);
        $this->rmaCollection->expects($this->once())
            ->method('load')
            ->will($this->returnValue($returns));

        $this->historyCollectionFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($historyCollection));

        $subject = $this->createPartialMock(\Magento\Sales\Block\Adminhtml\Order\View\Tab\History::class, ['getOrder']);
        $subject->expects($this->once())
            ->method('getOrder')
            ->will($this->returnSelf());

        $this->assertEquals($expected, $this->historyPlugin->afterGetFullHistory($subject, $original));
    }

    public function afterGetFullHistoryProvider()
    {
        return [
                [
                    ['id' => 42, 'is_customer_notified' => true],
                    []
                ],
                [
                    [
                        ['id' => 1, 'is_customer_notified' => false],
                        ['id' => 42, 'is_customer_notified' => true]
                    ],
                    []
                ],
                [
                    [
                        ['id' => 1, 'is_customer_notified' => false],
                        ['id' => 42, 'is_customer_notified' => true],
                    ],
                    [
                        [
                            'title' => 'Shipping #1000007 created',
                            'notified' => false,
                            'comment' => '',
                            'created_at' => (new \DateTime(null, new \DateTimeZone('UTC'))),
                        ]
                    ]
                ]
        ];
    }

    private function getCase($returnsOptions, $originalHistory = [])
    {
        $expected = $originalHistory;
        $returns = [];
        $index = 0;

        $historyCollection = $this->createMock(\Magento\Rma\Model\ResourceModel\Rma\Status\History\Collection::class);

        foreach ($returnsOptions as $returnOption) {
            $rmaId = $returnOption['id'];
            $returns[] = $this->getReturn($rmaId);

            $isCustomerNotified = $returnOption['is_customer_notified'];
            $createdAtDate = (new \DateTime(null, new \DateTimeZone('UTC')));

            $systemComment = $this->getSystemComment($isCustomerNotified, $createdAtDate);
            $customComment = $this->getCustomComment();
            $comments = [$systemComment, $customComment];
            $historyCollection->expects($this->at($index++))
                ->method('getItemsByColumnValue')
                ->with('rma_entity_id', $rmaId)
                ->will($this->returnValue($comments));

            $expected[] = [
                'title' => sprintf('Return #%s created', $rmaId),
                'notified' => $isCustomerNotified,
                'comment' => '',
                'created_at' => $createdAtDate,
            ];
        }

        usort($expected, [\Magento\Sales\Block\Adminhtml\Order\View\Tab\History::class, 'sortHistoryByTimestamp']);

        return [$historyCollection, $returns, $originalHistory, $expected];
    }

    private function getReturn($rmaId)
    {
        $rma = $this->createPartialMock(\Magento\Rma\Model\Rma::class, ['getId', 'getIncrementId', '__wakeup']);
        $rma->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($rmaId));
        $rma->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue($rmaId));
        return $rma;
    }

    private function getSystemComment($isCustomerNotified, $createdAtDate)
    {
        $comment = $this->createPartialMock(
            \Magento\Rma\Model\Rma\Status\History::class,
            ['getComment', 'getIsCustomerNotified', 'getCreatedAtDate', '__wakeup']
        );
        $comment->expects($this->once())
            ->method('getComment')
            ->will($this->returnValue(StatusHistory::getSystemCommentByStatus(Status::STATE_PENDING)));
        $comment->expects($this->once())
            ->method('getIsCustomerNotified')
            ->will($this->returnValue($isCustomerNotified));
        $comment->expects($this->once())
            ->method('getCreatedAtDate')
            ->will($this->returnValue($createdAtDate));
        return $comment;
    }

    private function getCustomComment()
    {
        $comment = $this->createPartialMock(
            \Magento\Rma\Model\Rma\Status\History::class,
            ['getComment', 'getIsCustomerNotified', 'getCreatedAtDate', '__wakeup']
        );
        $comment->expects($this->once())
            ->method('getComment')
            ->will($this->returnValue('another comment'));
        $comment->expects($this->never())
            ->method('getIsCustomerNotified');
        $comment->expects($this->never())
            ->method('getCreatedAtDate');
        return $comment;
    }
}
