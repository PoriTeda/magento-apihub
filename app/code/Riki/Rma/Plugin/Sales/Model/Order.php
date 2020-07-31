<?php
namespace Riki\Rma\Plugin\Sales\Model;

class Order
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Model\Repository\Rma\GridRepository
     */
    protected $gridRepository;

    /**
     * Order constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Model\Repository\Rma\GridRepository $gridRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Model\Repository\Rma\GridRepository $gridRepository
    )
    {
        $this->searchHelper = $searchHelper;
        $this->gridRepository = $gridRepository;
    }

    /**
     * Sync data into rma grid
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return mixed
     */
    public function afterAfterSave(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        if (!$result->getId() || $result instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $result;
        }

        $needUpdate = [
            'payment_status' => 'payment_status',
            'riki_type' => 'order_type'
        ];
        $originalData = $result->getOrigData();
        foreach ($needUpdate as $key => $value) {
            if (isset($originalData[$key])
                && $originalData[$key] == $result->getData($key)
            ) {
                unset($needUpdate[$key]);
            }
        }

        if (!$needUpdate) {
            return $result;
        }

        $gridEntities = $this->searchHelper
            ->getByOrderId($result->getId())
            ->getAll()
            ->execute($this->gridRepository);

        if (!$gridEntities) {
            return $result;
        }

        /** @var \Riki\Rma\Api\Data\GridInterface $gridEntity */
        foreach ($gridEntities as $gridEntity) {
            $updated = [];
            foreach ($needUpdate as $key => $value) {
                if ($gridEntity->getData($value) != $result->getData($key)) {
                    $updated[$value] = $result->getData($key);
                }
            }
            if (!$updated) {
                continue;
            }
            $gridEntity->addData($updated);
            $this->gridRepository->save($gridEntity);
        }

        return $result;
    }
}