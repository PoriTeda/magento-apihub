<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Test\Unit\Block\Adminhtml\Permissions\Tab\Rolesedit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws testing
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GwsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var  \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws
     */
    protected $block;

    /**
     * @var  \Magento\Backend\Block\Template\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $adminGwsRole;

    /**
     * @var  \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Init mocks for tests
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->context = $this->createPartialMock(
            \Magento\Backend\Block\Template\Context::class,
            ['getBackendSession']
        );
        $this->jsonEncoder = $this->getMockForAbstractClass(\Magento\Framework\Json\EncoderInterface::class);
        $this->adminGwsRole = $this->createPartialMock(
            \Magento\AdminGws\Model\Role::class,
            ['getGwsWebsites', 'getGwsStoreGroups']
        );
        $this->coreRegistry =  $this->createPartialMock(\Magento\Framework\Registry::class, ['registry']);
        $this->backendSession =  $this->createPartialMock(\Magento\Backend\Model\Session::class, ['getData']);

        $this->context->expects($this->any())->method('getBackendSession')->willReturn($this->backendSession);
    }

    /**
     * @return void
     */
    public function testGetGwsWebsitesFromSession()
    {
        $this->backendSession->expects($this->at(1))
            ->method('getData')
            ->with(
                \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY,
                true
            )
            ->willReturn([0 => '1']);

        $this->block = $this->objectManager->getObject(
            \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->assertEquals([0 => '1'], $this->block->getGwsWebsites());
    }

    /**
     * @return void
     */
    public function testGetGwsStoreGroupsFromSession()
    {
        $this->backendSession->expects($this->at(2))
            ->method('getData')
            ->with(
                \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY,
                true
            )
            ->willReturn([0 => '1']);

        $this->block = $this->objectManager->getObject(
            \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );
        $this->assertEquals([0 => '1'], $this->block->getGwsStoreGroups());
    }

    /**
     * @return void
     */
    public function testGetGwsWebsitesFromRegistry()
    {
        $this->backendSession->expects($this->at(1))
            ->method('getData')
            ->with(
                \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY,
                true
            )
            ->willReturn(null);

        $this->block = $this->objectManager->getObject(
            \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->coreRegistry->expects($this->once())->method('registry')->willReturn($this->adminGwsRole);
        $this->adminGwsRole->expects($this->once())->method('getGwsWebsites')->willReturn([0 => '1']);

        $this->assertEquals([0 => '1'], $this->block->getGwsWebsites());
    }

    /**
     * @return void
     */
    public function testGetGwsStoreGroupsFromRegistry()
    {
        $this->backendSession->expects($this->at(2))
            ->method('getData')
            ->with(
                \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY,
                true
            )
            ->willReturn(null);

        $this->block = $this->objectManager->getObject(
            \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->coreRegistry->expects($this->once())->method('registry')->willReturn($this->adminGwsRole);
        $this->adminGwsRole->expects($this->once())->method('getGwsStoreGroups')->willReturn([0 => '1']);

        $this->assertEquals([0 => '1'], $this->block->getGwsStoreGroups());
    }
}
