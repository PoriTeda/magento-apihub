<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Model;

/**
 * Class ConfigTest
 *
 * @package Magento\Rma\Model
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\Config
     */
    protected $rmaConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\Store | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $store;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    protected function setUp()
    {
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->store = $this->createMock(\Magento\Store\Model\Store::class);

        $this->rmaConfig = new \Magento\Rma\Model\Config($this->scopeConfig, $this->storeManager);
    }

    public function testSetStore()
    {
        $storeId = 5;
        $this->rmaConfig->setStore($this->store);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->will(
                $this->returnValueMap(
                    [
                        [$storeId, $this->store],
                        [null, $this->store],
                    ]
                )
            );
        $this->rmaConfig->setStore($storeId);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->rmaConfig->setStore(null);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
    }

    public function testGetStore()
    {
        $storeId = 5;
        $this->rmaConfig->setStore($this->store);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->will(
                $this->returnValueMap(
                    [
                        [$storeId, $this->store],
                        [null, $this->store],
                    ]
                )
            );
        $this->rmaConfig->setStore($storeId);
        $this->assertEquals($this->store, $this->rmaConfig->getStore($storeId));
        $this->rmaConfig->setStore(null);
        $this->assertEquals($this->store, $this->rmaConfig->getStore(null));
    }

    public function testSetGetRootPath()
    {
        $path = 'path';
        $this->rmaConfig->setRootPath($path);
        $this->assertEquals($path, $this->rmaConfig->getRootPath(''));
    }

    public function testGetRootRmaEmail()
    {
        $this->assertEquals(\Magento\Rma\Model\Config::XML_PATH_RMA_EMAIL, $this->rmaConfig->getRootRmaEmail());
    }

    public function testGetRootAuthEmail()
    {
        $this->assertEquals(\Magento\Rma\Model\Config::XML_PATH_AUTH_EMAIL, $this->rmaConfig->getRootAuthEmail());
    }

    public function testGetRootCommentEmail()
    {
        $this->assertEquals(\Magento\Rma\Model\Config::XML_PATH_COMMENT_EMAIL, $this->rmaConfig->getRootCommentEmail());
    }

    public function testGetRootCustomerCommentEmail()
    {
        $this->assertEquals(
            \Magento\Rma\Model\Config::XML_PATH_CUSTOMER_COMMENT_EMAIL,
            $this->rmaConfig->getRootCustomerCommentEmail()
        );
    }

    public function testIsEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_ENABLED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue(true));
        $this->assertEquals(true, $this->rmaConfig->isEnabled());
    }

    public function testGetCopyTo()
    {
        $data = 'copy1,copy2';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals(explode(',', $data), $this->rmaConfig->getCopyTo('', null));
    }

    public function testGetCopyToFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue(false));
        $this->assertFalse($this->rmaConfig->getCopyTo('', null));
    }

    public function testGetCopyMethod()
    {
        $data = 'bcc';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_METHOD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals($data, $this->rmaConfig->getCopyMethod('', null));
    }

    public function testGetGuestTemplate()
    {
        $data = 'guest tmpl';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_GUEST_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals($data, $this->rmaConfig->getGuestTemplate('', null));
    }

    public function testGetTemplate()
    {
        $data = 'tmpl';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals($data, $this->rmaConfig->getTemplate('', null));
    }

    public function testGetIdentity()
    {
        $data = 'rma';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals($data, $this->rmaConfig->getIdentity('', null));
    }

    public function testGetCustomerEmailRecipient()
    {
        $senderCode = 'rma';
        $data = 'emailRecipient';
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(
                \Magento\Rma\Model\Config::XML_PATH_CUSTOMER_COMMENT_EMAIL_RECIPIENT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($senderCode));
        $this->scopeConfig->expects($this->at(1))
            ->method('getValue')
            ->with(
                'trans_email/ident_' . $senderCode . '/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->will($this->returnValue($data));
        $this->assertEquals($data, $this->rmaConfig->getCustomerEmailRecipient(null));
    }
}
