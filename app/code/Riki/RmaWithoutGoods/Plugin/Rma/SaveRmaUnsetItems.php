<?php

namespace Riki\RmaWithoutGoods\Plugin\Rma;

class SaveRmaUnsetItems
{
    /**
     * @param $subject
     * @param $data
     *
     * @return array
     */
    public function beforeSaveRma($subject, $data)
    {
        if (is_array($data) && !empty($data['is_without_goods'])) {
            $data['items'] = [];
        }
        return [$data];
    }
}
