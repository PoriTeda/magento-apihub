<?php
namespace Bluecom\Paygent\Model\Email;

class ReauthorizeFailure extends \Riki\Framework\Model\Email\AbstractEmail
{
    const CONFIG_SENDER = 'paygent_config/authorisation/identity';

    const CONFIG_TEMPLATE = 'paygent_config/authorisation/template';

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygent;

    /**
     * ReauthorizeFailure constructor.
     *
     * @param \Bluecom\Paygent\Model\Paygent $paygent
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Bluecom\Paygent\Model\Paygent $paygent,
        \Magento\Framework\Url $urlBuilder,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->paygent = $paygent;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($dataObjectFactory, $storeManager, $scopeConfig, $logger, $inlineTranslation, $transportBuilder);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && !parent::getTemplate()) {
            $this->template = 'paygent_config_authorisation_template';
        }

        return $this->template;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $params
     *
     * @return bool
     */
    public function send($params = [])
    {
        if (isset($params['order'])
            && $params['order'] instanceof \Magento\Sales\Model\Order
        ) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $params['order'];
            $params['orderIncrementId'] = $order->getIncrementId();
            $params['orderAmount'] = floatval($order->getGrandTotal());
            $params['orderDate'] = $order->getCreatedAt();
            $billingAddress = $order->getBillingAddress();
            $params['customerName'] = $billingAddress->getLastname() .' '. $billingAddress->getFirstname();
            $params['postCode'] = $billingAddress->getPostcode();
            $street = $billingAddress->getStreet() ? current($billingAddress->getStreet()) : '';
            $params['street'] = $billingAddress->getRegion() . ' ' . $street;
            $params['phone'] = $billingAddress->getTelephone();

            $vars['linkChangeCC'] = $this->urlBuilder->getUrl('subscriptions/profile',['_nosid' => true, '_scope' => $order->getStoreId()]);
            // Fix link to edit subprofile
            if ($order->getSubscriptionProfileId()) {
                $params['linkChangeCC'] = $this->urlBuilder->getUrl('subscriptions/profile/payment_method_edit', ['id' => $order->getSubscriptionProfileId(),'_nosid' => true, '_scope' => $order->getStoreId()]);
            } else {

                //Link from paygent for order spot
                $ccLink = $this->getAuthorizeLink([
                    'trading_id' => $params['orderIncrementId'],
                    'amount' => $params['orderAmount'],
                    'return_url' => $this->urlBuilder->getUrl(null, [
                        '_nosid' => true,
                        '_scope' => $order->getStoreId()
                    ]),
                    'inform_url' => $this->urlBuilder->getUrl('paygent/paygent/response/', [
                        '_nosid' => true,
                        '_scope' => $order->getStoreId()
                    ])
                ]);
                $params['linkChangeCC'] = isset($ccLink['url']) ? $ccLink['url'] : '';
            }
        }

        return parent::send($params);
    }

    /**
     * Get reauthorize link
     *
     * @param $params
     *
     * @return array
     */
    public function getAuthorizeLink($params)
    {
        $retryNumber = isset($params['retryNumber']) ? $params['retryNumber'] : 0;
        $result = $this->paygent->initRedirectLink($params['trading_id'], $params['amount'], $params);
        if (!isset($result['url']) && $retryNumber < 5) { // retry 5 times
            $params['retryNumber'] = $retryNumber + 1;
            $params['trading_id'] = '0' . $params['trading_id'];
            return $this->getAuthorizeLink($params);
        }

        return $result;
    }
}