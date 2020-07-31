<?php

namespace Riki\Rma\Model\Config\Source\Rma;

class MassAction implements \Magento\Framework\Option\ArrayInterface
{
    const CLOSE_REQUEST = 'close_request';
    const REJECT_REQUEST = 'reject_request';
    const DENY_REQUEST = 'deny_request';
    const REVIEW_BY_CC = 'accept_request';
    const APPROVE_BY_CC = 'approve_request';
    const APPROVE_BY_CS = 'approve';
    const REJECT = 'reject';
    const REEXPORT_TO_SAP = 'reexport';

    public function optionList()
    {
        return [
            self::DENY_REQUEST    => [
                'label' =>  __('Rejected by CC'),
                'resource' =>  'Riki_Rma::rma_return_actions_deny_request',
            ],
            self::REVIEW_BY_CC   =>  [
                'label' =>  __('Review By CC'),
                'resource' =>  'Riki_Rma::rma_return_actions_review_cc',
            ],
            self::REJECT_REQUEST    => [
                'label' =>  __('Reject and send back to call center op'),
                'resource' =>  'Riki_Rma::rma_return_actions_reject_request',
            ],
            self::APPROVE_BY_CC   =>  [
                'label' =>  __('Approve by CC'),
                'resource' =>  'Riki_Rma::rma_return_actions_approve_request',
            ],
            self::REJECT   =>  [
                'label' =>  __('CS Feedback - Rejected'),
                'resource' =>  'Riki_Rma::rma_return_actions_reject',
            ],
            self::APPROVE_BY_CS   =>  [
                'label' =>  __('Approve by CS'),
                'resource' =>  'Riki_Rma::rma_return_actions_approve',
            ],
            self::CLOSE_REQUEST    =>  [
                'label' =>  __('Close request'),
                'resource' =>  'Riki_Rma::rma_return_actions_close',
            ],
            self::REEXPORT_TO_SAP   =>  [
                'label' =>  __('Reexport to SAP'),
                'resource' =>  'Riki_Rma::rma_return_actions_export_to_sap',
                'url' =>  'riki_rma/returns/reexport',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $result = [];

        foreach ($this->optionList() as $value => $define) {
            $result[$value] = $define['label'];
        }

        return $result;
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($value)
    {
        $options = $this->optionList();

        if (isset($options[$value])) {
            return $options[$value]['label'];
        }

        return '';
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $result = [];

        foreach ($this->getOptions() as $value => $define) {
            $result[] = [
                'value' =>  $value,
                'label' =>  $define['label']
            ];
        }

        return $result;
    }
}
