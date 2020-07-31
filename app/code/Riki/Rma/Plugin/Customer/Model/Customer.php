<?php
namespace Riki\Rma\Plugin\Customer\Model;

class Customer
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
     * Customer constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Model\Repository\Rma\GridRepository $gridRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Model\Repository\Rma\GridRepository $gridRepository
    ) {
        $this->searchHelper = $searchHelper;
        $this->gridRepository = $gridRepository;
    }

    /**
     * Sync data into rma grid
     *
     * @param \Magento\Customer\Model\Customer $subject
     * @param \Magento\Customer\Model\Customer $result
     * @return mixed
     */
    public function afterAfterSave(
        \Magento\Customer\Model\Customer $subject,
        \Magento\Customer\Model\Customer $result
    ) {
        if (!$result->getId()) {
            return $result;
        }

        $needUpdate = [
            'membership' => 'customer_type'
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
            ->getByCustomerId($result->getId())
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