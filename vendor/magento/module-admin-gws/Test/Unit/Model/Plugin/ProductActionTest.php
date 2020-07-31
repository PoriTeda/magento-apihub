<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

class ProductActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Plugin\ProductAction
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $roleMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    protected function setUp()
    {
        $this->roleMock = $this->createMock(\Magento\AdminGws\Model\Role::class);
        $this->subjectMock = $this->createMock(\Magento\Catalog\Model\Product\Action::class);
        $this->model = new \Magento\AdminGws\Model\Plugin\ProductAction($this->roleMock);
    }

    public function testBeforeUpdateWebsitesDoesNotCheckWebsiteAccessWhenRoleIsNotRestricted()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(true));
        $this->roleMock->expects($this->never())->method('getIsWebsiteLevel');
        $this->roleMock->expects($this->never())->method('hasWebsiteAccess');
        $this->model->beforeUpdateWebsites($this->subjectMock, [], [], 'type');
    }

    /**
     * @param boolean $isWebsiteLevelRole
     * @param boolean $hasWebsiteAccess
     * @param string $actionType
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage More permissions are needed to save this item.
     * @dataProvider beforeUpdateWebsitesThrowsExceptionWhenAccessIsRestrictedDataProvider
     */
    public function testBeforeUpdateWebsitesThrowsExceptionWhenAccessIsRestricted(
        $isWebsiteLevelRole,
        $hasWebsiteAccess,
        $actionType
    ) {
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(false));
        $this->roleMock->expects(
            $this->any()
        )->method(
            'getIsWebsiteLevel'
        )->will(
            $this->returnValue($isWebsiteLevelRole)
        );
        $websiteIds = [1];
        $this->roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $websiteIds,
            true
        )->will(
            $this->returnValue($hasWebsiteAccess)
        );
        $this->model->beforeUpdateWebsites($this->subjectMock, [], $websiteIds, $actionType);
    }

    public function beforeUpdateWebsitesThrowsExceptionWhenAccessIsRestrictedDataProvider()
    {
        return [
            [true, false, 'remove'],
            [false, true, 'remove'],
            [false, false, 'remove'],
            [true, false, 'add'],
            [false, true, 'add'],
            [false, false, 'add']
        ];
    }

    public function testBeforeUpdateWebsitesDoesNotThrowExceptionWhenUserHasAccessToGivenWebsites()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(false));
        $this->roleMock->expects($this->once())->method('getIsWebsiteLevel')->will($this->returnValue(true));
        $websiteIds = [1];
        $this->roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $websiteIds,
            true
        )->will(
            $this->returnValue(true)
        );
        $this->model->beforeUpdateWebsites($this->subjectMock, [], $websiteIds, 'add');
    }
}
