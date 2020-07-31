<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Plugin;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Staging\Plugin\RestPlugin;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Staging\Model\VersionManager;

/**
 * Class RestPluginTest
 */
class RestPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var FrontControllerInterface|MockObject
     */
    private $subject;

    /**
     * @var RequestInterface|MockObject
     */
    private $appRequest;

    /**
     * @var RestPlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCurrentVersionId'])
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequestData'])
            ->getMock();

        $this->subject = $this->getMockForAbstractClass(FrontControllerInterface::class);

        $this->appRequest = $this->getMockForAbstractClass(RequestInterface::class);

        $this->plugin = new RestPlugin($this->versionManager, $this->request);
    }

    /**
     * @covers \Magento\Staging\Plugin\RestPlugin::beforeDispatch
     */
    public function testBeforeDispatchWithoutVersion()
    {
        $this->request->expects(static::once())
            ->method('getRequestData')
            ->willReturn([]);

        $this->versionManager->expects(static::never())
            ->method('setCurrentVersionId');

        $this->plugin->beforeDispatch($this->subject, $this->appRequest);
    }

    /**
     * @covers \Magento\Staging\Plugin\RestPlugin::beforeDispatch
     */
    public function testBeforeDispatch()
    {
        $version = 278328;

        $this->request->expects(static::once())
            ->method('getRequestData')
            ->willReturn([
                VersionManager::PARAM_NAME => $version
            ]);

        $this->versionManager->expects(static::once())
            ->method('setCurrentVersionId')
            ->with($version);

        $this->plugin->beforeDispatch($this->subject, $this->appRequest);
    }
}
