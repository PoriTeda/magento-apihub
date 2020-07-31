<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\Edit;
use Magento\Backend\Block\Widget\Context as WidgetContext;
use Magento\Framework\App\RequestInterface;
use Magento\Rma\Model\Rma;
use Magento\Framework\UrlInterface;

/**
 * Tests Magento\Rma\Block\Adminhtml\Rma\Edit.
 */
class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Edit
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var WidgetContext
     */
    private $context;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Rma
     */
    private $rma;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getServer'])
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();
        $this->context = $this->objectManager->getObject(
            WidgetContext::class,
            [
                'request' => $this->request,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
        $this->model = $this->objectManager->getObject(
            Edit::class,
            [
                'context' => $this->context,
            ]
        );

        $this->rma = $this->createMock(Rma::class);
        $this->rma->expects($this->any())->method('getOrderId')->willReturn(1);
        $this->rma->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->objectManager->setBackwardCompatibleProperty($this->model, '_rma', $this->rma);
    }

    /**
     * @param string $referrer
     * @param string $expectation
     * @return void
     *
     * @dataProvider getBackUrlDataProvider
     */
    public function testGetBackUrlWithReferrer(string $referrer, string $expectation) : void
    {
        $this->request->expects($this->once())->method('getServer')
            ->with('HTTP_REFERER')
            ->willReturn($referrer);

        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->willReturnCallback(
                function (string $route = '', array $params = []) {
                    $routeParams = '';
                    array_walk($params, function ($value, $key) use (&$routeParams) {
                        $routeParams .= (strlen($routeParams) ? '/' : '') . $key . '/' . $value;
                    });

                    return "http://localhost/admin/{$route}{$routeParams}";
                }
            );

        $this->assertContains($expectation, $this->model->getBackUrl());
    }

    /**
     * @return array
     */
    public function getBackUrlDataProvider() : array
    {
        return [
            ['http://localhost/admin/sales/order/view/order_id/1', 'sales/order/view/order_id/1'],
            ['http://localhost/admin/customer/index/edit/id/1', 'customer/index/edit/id/1'],
        ];
    }
}
