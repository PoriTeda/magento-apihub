<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RestrictorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Restrictor
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    protected $urlFactory;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->config = $this->getMockBuilder(\Magento\WebsiteRestriction\Model\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->config->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(\Magento\WebsiteRestriction\Model\Mode::ALLOW_LOGIN));

        $customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();
        $customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(false));

        $this->session = $this->getMockBuilder(\Magento\Framework\Session\Generic::class)
            ->disableOriginalConstructor()
            ->setMethods(['setWebsiteRestrictionAfterLoginUrl'])
            ->getMock();

        $this->urlFactory = $this->getMockBuilder(\Magento\Framework\UrlFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->model = $objectManager->getObject(
            \Magento\WebsiteRestriction\Model\Restrictor::class,
            [
                'config' => $this->config,
                'session' => $this->session,
                'urlFactory' => $this->urlFactory,
                'customerSession' => $customerSession,
            ]
        );
    }

    public function testRestrictRedirectNot302Landing()
    {
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getFullActionName', 'setControllerName'])
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getFullActionName')
            ->will($this->returnValue(''));
        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setRedirect'])
            ->getMockForAbstractClass();
        $isCustomerLoggedIn = false;

        $this->config->expects($this->once())
            ->method('getGenericActions')
            ->will($this->returnValue(['generic_Actions']));
        $this->config->expects($this->once())
            ->method('getHTTPRedirectCode')
            ->will($this->returnValue(0));

        $urlMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->urlFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue($urlMock));

        $urlValue = 'url_value';
        $urlMock->expects($this->once())
            ->method('getUrl')
            ->with('customer/account/login')
            ->will($this->returnValue($urlValue));

        $responseMock->expects($this->once())
            ->method('setRedirect')
            ->will($this->returnValue($urlValue));

        $urlMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
        $this->urlFactory->expects($this->at(1))
            ->method('create')
            ->will($this->returnValue($urlMock));
        $urlValue = 'url_value2';
        $urlMock->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($urlValue));

        $this->session->expects($this->once())
            ->method('setWebsiteRestrictionAfterLoginUrl')
            ->with($urlValue);

        $this->model->restrict($requestMock, $responseMock, $isCustomerLoggedIn);
    }

    public function testRestrictRedirect302Landing()
    {
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getFullActionName', 'setControllerName'])
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getFullActionName')
            ->will($this->returnValue(''));
        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setRedirect'])
            ->getMockForAbstractClass();
        $isCustomerLoggedIn = false;

        $this->config->expects($this->once())
            ->method('getGenericActions')
            ->will($this->returnValue(['generic_Actions']));
        $this->config->expects($this->once())
            ->method('getHTTPRedirectCode')
            ->will($this->returnValue(\Magento\WebsiteRestriction\Model\Mode::HTTP_302_LANDING));

        $landingPageCode = 'landing_page_code';
        $this->config->expects($this->once())
            ->method('getLandingPageCode')
            ->will($this->returnValue($landingPageCode));

        $urlMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->urlFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue($urlMock));

        $urlValue = 'url_value';
        $urlMock->expects($this->once())
            ->method('getUrl')
            ->with('', ['_direct' => $landingPageCode])
            ->will($this->returnValue($urlValue));

        $responseMock->expects($this->once())
            ->method('setRedirect')
            ->will($this->returnValue($urlValue));

        $urlMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
        $this->urlFactory->expects($this->at(1))
            ->method('create')
            ->will($this->returnValue($urlMock));
        $urlValue = 'url_value2';
        $urlMock->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($urlValue));

        $this->session->expects($this->once())
            ->method('setWebsiteRestrictionAfterLoginUrl')
            ->with($urlValue);

        $this->model->restrict($requestMock, $responseMock, $isCustomerLoggedIn);
    }
}
