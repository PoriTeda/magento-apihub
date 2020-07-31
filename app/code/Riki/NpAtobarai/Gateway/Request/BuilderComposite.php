<?php

namespace Riki\NpAtobarai\Gateway\Request;

use Magento\Framework\ObjectManager\TMap;
use Magento\Framework\ObjectManager\TMapFactory;
use \Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class BuilderComposite
 */
class BuilderComposite implements BuilderInterface
{
    /**
     * @var BuilderInterface[] | TMap
     */
    private $builders;

    /**
     * @param TMapFactory $tmapFactory
     * @param array $builders
     */
    public function __construct(
        TMapFactory $tmapFactory,
        array $builders = []
    ) {
        $this->builders = $tmapFactory->create(
            [
                'array' => $builders,
                'type' => BuilderInterface::class
            ]
        );
    }

    /**
     * Builds ENV request
     *
     * @param array $transactions
     * @return array
     */
    public function build(array $transactions)
    {
        $result = [];

        foreach ($transactions as $transaction) {
            $transactionResult = [];
            foreach ($this->builders as $builder) {
                // @TODO implement exceptions catching
                $transactionResult = $this->merge($transactionResult, $builder->build(['transaction' => $transaction]));
            }

            $result[] = $transactionResult;
        }
        return ['transactions' => $result];
    }

    /**
     * Merge function for builders
     *
     * @param array $result
     * @param array $builder
     * @return array
     */
    protected function merge(array $result, array $builder)
    {
        return array_merge($result, $builder);
    }
}
