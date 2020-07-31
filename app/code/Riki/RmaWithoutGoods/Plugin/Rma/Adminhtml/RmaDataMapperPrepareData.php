<?php

namespace Riki\RmaWithoutGoods\Plugin\Rma\Adminhtml;

class RmaDataMapperPrepareData
{
    /**
     * @param \Magento\Rma\Model\Rma\RmaDataMapper $subject
     * @param array $saveRequest
     *
     * @return mixed
     */
    public function beforeFilterRmaSaveRequest(
        \Magento\Rma\Model\Rma\RmaDataMapper $subject,
        array $saveRequest
    )
    {
        if (isset($saveRequest['is_without_goods']) && $saveRequest['is_without_goods']) {
            $saveRequest['items'] = [];
        }
        return [$saveRequest];
    }

    /**
     * @param $subject
     * @param $statuses
     *
     * @return array
     */
    public function afterCombineItemStatuses($subject, $statuses)
    {
        return [\Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING];
    }
}
