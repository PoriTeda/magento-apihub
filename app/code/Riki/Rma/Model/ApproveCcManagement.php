<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;

class ApproveCcManagement
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $rmaAmountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rikiRmaHelper;

    /**
     * @var AmountCalculator
     */
    protected $amountCalculator;

    /**
     * @var array
     */
    protected $defaultItemValues = [
        'return_amount_adj' =>  0,
        'return_wrapping_fee_adj' =>  0
    ];

    /**
     * ApproveCcManagement constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param AmountCalculator $amountCalculator
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Rma\Model\AmountCalculator $amountCalculator
    ) {
        $this->request = $request;
        $this->rmaAmountHelper = $amountCalculator->getAmountHelper();
        $this->rikiRmaHelper = $amountCalculator->getDataHelper();
        $this->amountCalculator = $amountCalculator;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return $this
     * @throws LocalizedException
     */
    public function prepareData(\Magento\Rma\Model\Rma $rma)
    {
        $this->request->setPostValue([]);
        $defaultValues['items'] = [];
        foreach ($rma->getItemsForDisplay() as $item) {
            foreach ($this->defaultItemValues as $field => $value) {
                $currentValue = $item->getData($field);

                if (empty($currentValue) && $currentValue != $value) {
                    $defaultValues['items'][$item->getId()][$field] = $value;
                }
            }
            $itemReturnAmount = $this->rmaAmountHelper->getReturnAmountByItem($item);
            $defaultValues['items'][$item->getId()]['return_amount'] = $itemReturnAmount;
            $itemReturnWrapping = $this->rmaAmountHelper->getReturnWrappingByItem($item);
            $defaultValues['items'][$item->getId()]['return_wrapping_fee'] = $itemReturnWrapping;
        }
        $order = $this->rikiRmaHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            throw new LocalizedException(__('Order can not be found'));
        }
        $amountFields = $this->amountCalculator->calculateReturnAmount($rma);
        $result = $defaultValues + $amountFields;
        $this->request->setPostValue($result);
        return $this;
    }
}
