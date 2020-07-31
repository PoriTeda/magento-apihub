<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Ui\Component\Plugin\Catalog\Product;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Ui\Component\Plugin\Catalog\Product\MassAction;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class MassActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var MassAction
     */
    private $massAction;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->massAction = $objectManager->getObject(
            MassAction::class,
            [
                'role' => $this->roleMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
            ]
        );
    }

    /**
     * @param bool $expected
     * @param bool $isActionAllowed
     * @param string $actionType
     * @param int $callGetIsAllNum
     * @param bool $isAll
     * @param int $callHasStoreAccessNum
     * @param bool $hasStoreAccess
     * @dataProvider afterIsActionAllowedDataProvider
     */
    public function testAfterIsActionAllowed(
        $expected,
        $isActionAllowed,
        $actionType,
        $callGetIsAllNum = 0,
        $isAll = true,
        $callHasStoreAccessNum = 0,
        $hasStoreAccess = true
    ) {
        /** @var \Magento\Catalog\Ui\Component\Product\MassAction|MockObject $massActionMock */
        $massActionMock = $this->getMockBuilder(\Magento\Catalog\Ui\Component\Product\MassAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleMock->expects($this->exactly($callGetIsAllNum))
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($callHasStoreAccessNum))
            ->method('hasStoreAccess')
            ->willReturn($hasStoreAccess);

        $this->assertEquals(
            $expected,
            $this->massAction->afterIsActionAllowed($massActionMock, $isActionAllowed, $actionType)
        );
    }

    public function afterIsActionAllowedDataProvider() : array
    {
        return [
            'other-allowed' => [true, true, 'other',],
            'other-not-allowed' => [false, false, 'other',],
            'delete-allowed' => [true, true, 'delete', 1,],
            'delete-allowed-rollIsNotAll' => [true, true, 'delete', 1, false, 1],
            'delete-allowed-rollIsNotAll-noStoreAccess' => [false, true, 'delete', 1, false, 1, false],
            'delete-not-allowed' => [false, false, 'delete',],
            'status-allowed' => [true, true, 'status', 1, 'Magento_Catalog::products'],
            'status-allowed-rollIsNotAll' => [true, true, 'status', 1, false, 1],
            'status-allowed-rollIsNotAll-noStoreAccess' => [false, true, 'status', 1, false, 1, false],
            'status-not-allowed' => [false, false, 'status',],
            'attributes-allowed' => [true, true, 'attributes', 1, 'Magento_Catalog::update_attributes'],
            'attributes-allowed-rollIsNotAll' => [true, true, 'attributes', 1, false, 1],
            'attributes-allowed-rollIsNotAll-noStoreAccess' => [false, true, 'attributes', 1, false, 1, false],
            'attributes-not-allowed' => [false, false, 'attributes',],
        ];
    }
}
