<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class EmailManagementTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\GiftCardAccount\Model\EmailManagement
     */
    private $model;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeCurrency;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transportBuilder;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $senderResolver;

    /**
     * @var array
     */
    private $account = [
        'name' => 'recipient_name',
        'email' => 'recipient_email@magento.com',
        'website_id' => '1',
        'store' => '2',
        'store_name' => 'Store Name',
        'store_code' => 'store_code_1',
        'balance' => '10',
        'code' => 'GCCODE'
    ];

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->localeCurrency = $this->getMockBuilder(\Magento\Framework\Locale\CurrencyInterface::class)
            ->getMockForAbstractClass();
        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMockForAbstractClass();
        $this->transportBuilder = $this->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->senderResolver = $this->getMockBuilder(\Magento\Framework\Mail\Template\SenderResolverInterface::class)
            ->getMockForAbstractClass();
        $this->model = $this->objectManager->getObject(
            \Magento\GiftCardAccount\Model\EmailManagement::class,
            [
                'storeManager' => $this->storeManager,
                'localeCurrency' => $this->localeCurrency,
                'scopeConfig' => $this->scopeConfig,
                'transportBuilder' => $this->transportBuilder,
                'senderResolver' => $this->senderResolver
            ]
        );
    }

    /**
     * @dataProvider sendEmailDataProvider
     * @param bool $sendEmail
     */
    public function testSendEmail($sendEmail)
    {
        $currencyCode = 'USD';
        $giftcardAccount = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->setMethods(
                [
                    'getRecipientName',
                    'getRecipientEmail',
                    'getRecipientStore',
                    'getWebsiteId',
                    'getBalance',
                    'getCode',
                    'setHistoryAction',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $giftcardAccount->expects($this->any())->method('getRecipientName')->willReturn($this->account['name']);
        $giftcardAccount->expects($this->any())->method('getRecipientEmail')->willReturn($this->account['email']);
        $giftcardAccount->expects($this->any())->method('getRecipientStore')->willReturn($this->account['store']);
        $giftcardAccount->expects($this->any())->method('getWebsiteId')->willReturn($this->account['website_id']);
        $giftcardAccount->expects($this->any())->method('getBalance')->willReturn($this->account['balance']);
        $giftcardAccount->expects($this->any())->method('getCode')->willReturn($this->account['code']);
        $giftcardAccount->expects($this->any())->method('setHistoryAction')
            ->with(\Magento\GiftCardAccount\Model\History::ACTION_SENT)
            ->willReturnSelf();
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->any())->method('getStore')->with($this->account['store'])
            ->willReturn($store);
        $store->expects($this->any())->method('getId')->willReturn($this->account['store']);
        $store->expects($this->any())->method('getBaseCurrencyCode')->willReturn($currencyCode);
        $store->expects($this->any())->method('getName')->willReturn($this->account['store_name']);
        $store->expects($this->any())->method('getCode')->willReturn($this->account['store_code']);
        $currency = $this->getMockBuilder(\Magento\Framework\Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeCurrency->expects($this->any())->method('getCurrency')->with($currencyCode)->willReturn($currency);
        $currency->expects($this->any())->method('toCurrency')->with($this->account['balance'])
            ->willReturn($this->account['balance']);
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturnMap(
            [
                [
                    'giftcard/giftcardaccount_email/template',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->account['store'],
                    'scope\config\template'
                ],
                [
                    'giftcard/giftcardaccount_email/identity',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->account['store'],
                    'giftcard/giftcardaccount_email/identity'
                ]
            ]
        );
        $this->transportBuilder->expects($this->any())->method('setTemplateIdentifier')->with('scope\config\template')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateOptions')
            ->with(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->account['store']])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateVars')
            ->with(
                [
                    'name' => $this->account['name'],
                    'code' => $this->account['code'],
                    'balance' => $this->account['balance'],
                    'store' => $store,
                    'store_name' => $this->account['store_name'],
                ]
            )->willReturnSelf();
        $this->senderResolver->expects($this->any())->method('resolve')->with(
            'giftcard/giftcardaccount_email/identity',
            $this->account['store_code']
        )->willReturn(['name' => 'Store Name', 'email' => 'store_email@magento.com']);
        $this->transportBuilder->expects($this->any())->method('setFrom')->with(
            ['name' => 'Store Name', 'email' => 'store_email@magento.com']
        )->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addTo')
            ->with($this->account['email'], $this->account['name'])
            ->willReturnSelf();
        $transport = $this->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)
            ->getMockForAbstractClass();
        $this->transportBuilder->expects($this->any())->method('getTransport')->willReturn($transport);
        if ($sendEmail) {
            $transport->expects($this->atLeastOnce())->method('sendMessage')->willReturnSelf();
            $giftcardAccount->expects($this->atLeastOnce())->method('setHistoryAction')
                ->with(\Magento\GiftCardAccount\Model\History::ACTION_SENT)->willReturnSelf();
            $giftcardAccount->expects($this->atLeastOnce())->method('save')->willReturnSelf();
        } else {
            $transport->expects($this->atLeastOnce())->method('sendMessage')
                ->willThrowException(new \Magento\Framework\Exception\MailException(__('test message')));
        }
        $this->assertEquals($sendEmail, $this->model->sendEmail($giftcardAccount));
    }

    /**
     * @return array
     */
    public function sendEmailDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
