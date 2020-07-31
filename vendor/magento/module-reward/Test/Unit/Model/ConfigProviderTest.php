<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\ConfigProvider
     */
    protected $model;

    /**
     * @var \Magento\Customer\Model\Session | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\Store | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $store;

    /**
     * @var \Magento\Store\Model\Website | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $website;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Session | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Reward\Model\Reward | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $reward;

    /**
     * @var \Magento\Reward\Model\RewardFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactory;

    /**
     * @var \Magento\Reward\Helper\Data | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardHelper;

    /**
     * @var \Magento\Framework\UrlInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Quote\Model\Quote | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quote;

    /**
     * @var \Magento\Reward\Model\Action\OrderExtra | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderExtra;

    protected function setUp()
    {
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->website = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reward = $this->getMockBuilder(\Magento\Reward\Model\Reward::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setWebsiteId',
                'loadByCustomer',
                'getPointsBalance',
                'getCurrencyAmount',
                'getActionInstance',
                'estimateRewardPoints',
                'estimateRewardAmount',
            ])
            ->getMock();

        $this->rewardFactory = $this->getMockBuilder(\Magento\Reward\Model\RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->rewardFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->reward);

        $this->rewardHelper = $this->getMockBuilder(\Magento\Reward\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUseRewardPoints',
                'getBaseRewardCurrencyAmount'
            ])
            ->getMock();

        $this->orderExtra = $this->getMockBuilder(\Magento\Reward\Model\Action\OrderExtra::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\Reward\Model\ConfigProvider(
            $this->customerSession,
            $this->storeManager,
            $this->checkoutSession,
            $this->rewardFactory,
            $this->rewardHelper,
            $this->urlBuilder
        );
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @param bool $hasRates
     * @param bool $isEnabledOnFront
     * @param bool $isOrderAllowed
     * @param string $landingPageUrl
     * @param float $estimateRewardPoints
     * @param float $estimateRewardAmount
     * @param string $rewardMessage
     * @param float $currencyAmount
     * @param float $pointsBalance
     * @param float $minPointsToUse
     * @param bool $useRewardPoints
     * @param float $baseRewardCurrencyAmount
     * @param bool $isAvailable
     * @dataProvider providerGetConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetConfig(
        $customerId,
        $websiteId,
        $hasRates,
        $isEnabledOnFront,
        $isOrderAllowed,
        $landingPageUrl,
        $estimateRewardPoints,
        $estimateRewardAmount,
        $rewardMessage,
        $currencyAmount,
        $pointsBalance,
        $minPointsToUse,
        $useRewardPoints,
        $baseRewardCurrencyAmount,
        $isAvailable
    ) {
        $this->website->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $this->store->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->website);

        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->rewardHelper->expects($this->any())
            ->method('getHasRates')
            ->willReturn($hasRates);
        $this->rewardHelper->expects($this->any())
            ->method('isEnabledOnFront')
            ->willReturn($isEnabledOnFront);
        $this->rewardHelper->expects($this->any())
            ->method('isOrderAllowed')
            ->willReturn($isOrderAllowed);
        $this->rewardHelper->expects($this->any())
            ->method('getLandingPageUrl')
            ->willReturn($landingPageUrl);
        $this->rewardHelper->expects($this->any())
            ->method('getGeneralConfig')
            ->with('min_points_balance', $websiteId)
            ->willReturn($minPointsToUse);
        $this->rewardHelper->expects($this->any())
            ->method('formatReward')
            ->willReturnMap([
                [$pointsBalance, $currencyAmount, null, '%s', '%s', $rewardMessage],
                [$estimateRewardPoints, $estimateRewardAmount, null, '%s', '%s', $rewardMessage],
            ]);

        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->orderExtra->expects($this->any())
            ->method('setQuote')
            ->willReturn($this->quote);

        $this->reward->expects($this->any())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->reward->expects($this->any())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->reward->expects($this->any())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->reward->expects($this->any())
            ->method('getCurrencyAmount')
            ->willReturn($currencyAmount);
        $this->reward->expects($this->any())
            ->method('getPointsBalance')
            ->willReturn($pointsBalance);
        $this->reward->expects($this->any())
            ->method('getActionInstance')
            ->with(\Magento\Reward\Model\Action\OrderExtra::class, true)
            ->willReturn($this->orderExtra);
        $this->reward->expects($this->any())
            ->method('estimateRewardPoints')
            ->with($this->orderExtra)
            ->willReturn($estimateRewardPoints);
        $this->reward->expects($this->any())
            ->method('estimateRewardAmount')
            ->with($this->orderExtra)
            ->willReturn($estimateRewardAmount);

        $this->quote->expects($this->any())
            ->method('getUseRewardPoints')
            ->willReturn($useRewardPoints);
        $this->quote->expects($this->any())
            ->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);

        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('magento_reward/cart/remove')
            ->willReturn('reward_remove');

        if ($currencyAmount !== null && $hasRates) {
            $expectedLabel = __('%1 store reward points available (%2)', $pointsBalance, null);
        } else {
            $expectedLabel = __('%1 store reward points available', $pointsBalance);
        }

        $expected = [
            'authentication' => [
                'reward' => [
                    'isAvailable' => $hasRates && $isEnabledOnFront && $isOrderAllowed,
                    'tooltipLearnMoreUrl' => $landingPageUrl,
                    'tooltipMessage' => sprintf('Sign in now and earn %s for this order.', $rewardMessage),
                ],
            ],
            'payment' => [
                'reward' => [
                    'isAvailable' => $isAvailable,
                    'amountSubstracted' => (bool)$useRewardPoints,
                    'usedAmount' => (float)$baseRewardCurrencyAmount,
                    'balance' => (float)$currencyAmount,
                    'label' => $expectedLabel
                ],
            ],
            'review' => [
                'reward' => [
                    'removeUrl' => 'reward_remove',
                ],
            ],
        ];

        $result = $this->model->getConfig();
        $this->assertEquals($expected, $result);
    }

    /**
     * 1. Customer ID
     * 2. Website ID
     * 3. Is reward has rates flag
     * 4. Is reward is enabled on front flag
     * 5. Is reward enabled for order
     * 5. Landing page URL
     * 6. Estimate reward points
     * 7. Estimate reward amount
     * 8. Reward message text
     * 9. Currency amount
     * 10. Reward Poins balance
     * 11. Minimum value of reward poins to use
     * 12. The Quote use the reward points flag
     * 13. The Quote base reward currency amount
     * 14. Is reward points available (RESULT)
     *
     * @return array
     */
    public function providerGetConfig()
    {
        return [
            [0, 0, false, false, true, 'landing_page', 1, 1, '1 point', 0., 0., 0.001, false, 0., false],
            [0, 0, true, false, true, 'landing_page', 1, 1, '1 point', 0., 0., 0.001, false, 0., false],
            [1, 1, true, true, true, 'landing_page', 1, 1, '1 point', 0., 0., 0.001, false, 0., false],
            [1, 1, true, true, true, 'landing_page', 1, 1, '1 point', 1., 1., 0.001, false, 1., true],
            [1, 1, true, true, false, 'landing_page', 1, 1, '1 point', 1., 1., 0.001, false, 1., true],
        ];
    }
}
