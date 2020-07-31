<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Test\Unit\Model\Rules\Rule;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DaysAgoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attribute;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDate;

    /**
     * Set up instances and mock objects
     */
    protected function setUp()
    {
        $this->attribute = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate->expects($this->once())->method('date')->willReturn($this->currentDate());
    }

    /**
     * Tests the method applyToCollection
     *
     * @param int $value
     * @param string $startOperator
     * @param string $endOperator
     * @param string $result
     * @param string $attribute
     *
     * @dataProvider applyToCollectionDataProvider
     */
    public function testApplyToCollection(
        $value,
        $startOperator,
        $endOperator = null,
        $result = null,
        $attribute = 'attribute'
    ) {
        $model = (new ObjectManager($this))->getObject(
            \Magento\VisualMerchandiser\Model\Rules\Rule\DaysAgo::class,
            [
                'rule' => ['value' => $value, 'operator' => $startOperator, 'attribute' => $attribute],
                'attribute' => $this->attribute
            ]
        );

        $managerHelper = new ObjectManager($this);
        $managerHelper->setBackwardCompatibleProperty($model, 'localeDate', $this->localeDate);

        $collection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $date = $this->currentDate();
        $date = $date->modify('-' . $value . ' days');

        if ($startOperator != 'eq') {
            $dateResult = $date->format($result);

            $this->localeDate
                ->expects($this->once())
                ->method('convertConfigTimeToUtc')
                ->with($dateResult)
                ->willReturn('convertConfigTimeToUtcResult');

            $collection
                ->expects($this->once())
                ->method('addFieldToFilter')
                ->with($attribute, [$endOperator => 'convertConfigTimeToUtcResult']);
        } else {
            $dateResult = $date->format('Y-m-d 00:00:00');

            $this->localeDate
                ->expects($this->at(1))
                ->method('convertConfigTimeToUtc')
                ->with($dateResult)
                ->willReturn('convertConfigTimeToUtcResultStartDate');

            $dateResult = $date->format('Y-m-d 23:59:59');

            $this->localeDate
                ->expects($this->at(2))
                ->method('convertConfigTimeToUtc')
                ->with($dateResult)
                ->willReturn('convertConfigTimeToUtcResultEndDate');

            $collection
                ->expects($this->once())
                ->method('addFieldToFilter')
                ->with(
                    $attribute,
                    [
                        'from' => 'convertConfigTimeToUtcResultStartDate',
                        'to' => 'convertConfigTimeToUtcResultEndDate',
                    ]
                );
        }

        $model->applyToCollection($collection);
    }

    /**
     * @return array
     */
    public function applyToCollectionDataProvider()
    {
        return [
            [1, 'lt', 'gt', 'Y-m-d 23:59:59'],
            [2, 'gt', 'lt', 'Y-m-d 00:00:00'],
            [3, 'gteq', 'lteq', 'Y-m-d 23:59:59'],
            [4, 'lteq', 'gteq', 'Y-m-d 00:00:00'],
            [5, 'eq'],
        ];
    }

    /**
     * @return \DateTime
     */
    private function currentDate()
    {
        return new \DateTime('2000-10-10', new \DateTimeZone(date_default_timezone_get()));
    }
}
