<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Test\Unit\Block\Adminhtml\ConfigurableProduct\Edit\Tab\Variations\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PricePermissions\Block\Adminhtml\ConfigurableProduct\Product\Edit\Tab\Variations\Plugin\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\PricePermissions\Block\Adminhtml\ConfigurableProduct\Product\Edit\Tab\Variations\Plugin\Config
     */
    protected $config;

    /**
     * @var \Magento\Backend\Model\Auth\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authSession;

    /**
     * @var \Magento\User\Model\User|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    /**
     * @var \Magento\PricePermissions\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pricePermData;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->authSession = $this->createPartialMock(
            \Magento\Backend\Model\Auth\Session::class,
            ['isLoggedIn', 'getUser']
        );
        $this->pricePermData = $this->createMock(\Magento\PricePermissions\Helper\Data::class);

        $this->user = $this->createMock(\Magento\User\Model\User::class);

        $this->config = $this->objectManager->getObject(
            Config::class,
            [
                'authSession' => $this->authSession,
                'pricePermData' => $this->pricePermData,
            ]
        );
    }

    public function testBeforeToHtmlWithPermissions()
    {
        $subject = $this->createPartialMock(
            \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config::class,
            ['setCanEditPrice', 'setCanReadPrice']
        );
        $this->user->expects($this->any())->method('getRole')->will($this->returnValue('admin'));
        $this->authSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->authSession->expects($this->any())->method('getUser')->willReturn($this->user);
        $this->pricePermData->expects($this->once())->method('getCanAdminReadProductPrice')->willReturn(true);
        $this->pricePermData->expects($this->once())->method('getCanAdminEditProductPrice')->willReturn(true);
        $subject->expects($this->never())->method('setCanEditPrice');
        $subject->expects($this->never())->method('setCanReadPrice');

        $this->config->beforeToHtml($subject);
    }

    public function testBeforeToHtmlWithoutPermissions()
    {
        $subject = $this->createPartialMock(
            \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config::class,
            ['setCanEditPrice', 'setCanReadPrice']
        );
        $this->user->expects($this->any())->method('getRole')->will($this->returnValue('admin'));
        $this->authSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->authSession->expects($this->any())->method('getUser')->willReturn($this->user);
        $this->pricePermData->expects($this->once())->method('getCanAdminReadProductPrice')->willReturn(false);
        $this->pricePermData->expects($this->once())->method('getCanAdminEditProductPrice')->willReturn(false);
        $subject->expects($this->once())->method('setCanEditPrice')->with(false);
        $subject->expects($this->once())->method('setCanReadPrice')->with(false);

        $this->config->beforeToHtml($subject);
    }
}
