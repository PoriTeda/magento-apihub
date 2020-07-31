<?php

namespace Riki\NpAtobarai\Gateway\Request\TransactionRegistration;

use Magento\Framework\Exception\LocalizedException;
use \Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class GoodsDataBuilder
 */
class GoodsDataBuilder implements BuilderInterface
{
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

        $transactionGoods = json_decode($transaction->getGoods(), true);
        $goods = [];
        foreach ($transactionGoods as $transactionGood) {
            if (!isset($transactionGood['goods_name'])
                || !isset($transactionGood['goods_price'])
                || !isset($transactionGood['quantity'])) {
                throw new LocalizedException(__('goods_name, goods_price, and quantity must be provided'));
            }
            $goods[] = [
                'goods_name' => mb_substr($transactionGood['goods_name'], 0, 150),
                'goods_price' => (float)$transactionGood['goods_price'],
                'quantity' => $transactionGood['quantity']
            ];
        }
        return ['goods' => $goods];
    }
}
