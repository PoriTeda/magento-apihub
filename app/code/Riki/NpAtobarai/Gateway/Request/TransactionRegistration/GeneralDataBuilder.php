<?php

namespace Riki\NpAtobarai\Gateway\Request\TransactionRegistration;

use Magento\Framework\Exception\LocalizedException;
use \Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class GeneralDataBuilder
 */
class GeneralDataBuilder implements BuilderInterface
{

    const SETTLEMENT_TYPE = '02';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * GeneralDataBuilder constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactions
     * @return mixed
     * @throws LocalizedException
     */
    public function build(array $transactions)
    {
        $transaction = isset($transactions['transaction']) ? $transactions['transaction'] : '';

        if (!$transaction instanceof \Riki\NpAtobarai\Api\Data\TransactionInterface) {
            throw new LocalizedException(__('Transaction must be an instance of NpTransaction'));
        }

        $order = $transaction->getOrder();
        $siteName = $this->getNpAtobaraiConfiguration('site_name');
        $siteUrl = $this->getNpAtobaraiConfiguration('site_url');

        return [
            'shop_transaction_id' => $transaction->getTransactionId(),
            'shop_order_date' => $this->dateTime->date('Y-m-d', $order->getCreatedAt()),
            'settlement_type' => self::SETTLEMENT_TYPE,
            'site_name' => mb_substr($siteName, 0, 50),
            'site_url' => mb_substr($siteUrl, 0, 100),
            'billed_amount' => (float)$transaction->getBilledAmount()
        ];
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function getNpAtobaraiConfiguration($key)
    {
        $configPath = 'npatobarai/general/' . $key;
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
