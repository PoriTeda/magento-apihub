<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

class Edit
{
    /**
     * @var \Magento\Rma\Model\Rma
     */
    protected $rma;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $dataHelper;
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Framework\UrlInterface $url
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\UrlInterface $url,
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->authorization = $authorization;
        $this->dataHelper = $dataHelper;
        $this->url = $url;
    }

    /**
     * Get current rma
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        if (!$this->rma) {
            $rma = $this->dataHelper->getCurrentRma();
            if ($rma instanceof \Magento\Rma\Model\Rma) {
                $this->setRma($rma);
            }
        }
        return $this->rma;
    }

    /**
     * Set current rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return $this
     */
    public function setRma(\Magento\Rma\Model\Rma $rma)
    {
        $this->rma = $rma;
        return $this;
    }

    /**
     * Get current rma id
     *
     * @return int|mixed
     */
    public function getRmaId()
    {
        if (!$this->getRma()) {
            return 0;
        }

        return $this->getRma()->getId();
    }

    /**
     * Add & remove button
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit $subject
     * @param \Magento\Framework\View\LayoutInterface $layout
     *
     * @return array
     */
    public function beforeSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma\Edit $subject,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $rma = $subject->getRma();
        if (!$rma instanceof \Magento\Rma\Model\Rma) {
            return [$layout];
        }

        $closed = [
            \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED,
            \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED
        ];
        if (in_array($rma->getStatus(), $closed)) {
            return [$layout];
        }

        if ($rma->getData('return_status') == ReturnStatusInterface::COMPLETED) {
            $subject->removeButton('save');
            $subject->removeButton('save_and_edit_button');
            return [$layout];
        }


        $this->setRma($rma);
        $options = [];
        $options += $this->prepareForCallCenterOperator();
        $options += $this->prepareForCallCenterSupervisor();
        $options += $this->prepareForSupplyChain();
        if ($options) {
            $subject->removeButton('save');
            $data['label'] = __('Save');
            $data['class'] = 'save primary';

            if($this->canSave()){
                $data['data_attribute'] = [
                    'mage-init' => [
                        'button' => ['event' => 'save', 'target' => '#edit_form']
                    ]
                ];
            }

            $data['class_name'] = 'Magento\Backend\Block\Widget\Button\SplitButton';
            $data['options'] = $options;
            $subject->addButton(
                'save_extend',
                $data,
                100
            );
        }else{
            if(!$this->canSave()){
                $subject->removeButton('save');
            }
        }

        return [$layout];
    }

    /**
     * Prepare item for Call Center Operator role
     *
     * @return array
     */
    protected function prepareForCallCenterOperator()
    {
        $options = [];
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_deny_request')) {
            $options['deny_request'] = [
                'label' => __('Reject and send back to warehouse'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getDenyRequestUrl(['id' => $this->getRmaId()]) . '").submit();'
            ];
        }
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_accept_request')) {
            $options['accept_request'] = [
                'label' => __('Save and send for approval'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getAcceptRequestUrl(['id' => $this->getRmaId()])  . '").submit();'
            ];
        }

        return $options;
    }

    /**
     * Prepare item for Call Center Supervisor role
     *
     * @return array
     */
    protected function prepareForCallCenterSupervisor()
    {
        $options = [];
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_reject_request')) {
            $options['reject_request'] = [
                'label' => __('Reject and send back to call center op'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getRejectRequestUrl(['id' => $this->getRmaId()])  . '").submit();'
            ];
        }
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_approve_request')) {
            $options['approve_request'] = [
                'label' => __('Approve by CC'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getApproveRequestUrl(['id' => $this->getRmaId()])  . '").submit();',
            ];
        }

        return $options;
    }

    /**
     * Prepare item for Supply Chain role
     *
     * @return array
     */
    protected function prepareForSupplyChain()
    {
        $options = [];
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_reject')) {
            $options['reject'] = [
                'label' => __('Reject by CS'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getRejectUrl(['id' => $this->getRmaId()])  . '").submit();',
            ];
        }
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_approve')) {
            $options['approve'] = [
                'label' => __('Approve by CS'),
                'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->getApproveUrl(['id' => $this->getRmaId()])  . '").submit();',
            ];
        }
        return $options;
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getAcceptRequestUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns_request/accept', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getDenyRequestUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns_request/deny', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getApproveRequestUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns_request/approve', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getRejectRequestUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns_request/reject', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getRejectUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns/reject', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getSaveUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/rma/save', $params);
    }

    /**
     * Get url
     *
     * @param array $params
     * @return string
     */
    public function getApproveUrl($params = [])
    {
        return $this->url->getUrl('riki_rma/returns/approve', $params);
    }

    /**
     * Extend getCloseUrl
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit $subject
     * @param $result
     * @return string
     */
    public function afterGetCloseUrl(\Magento\Rma\Block\Adminhtml\Rma\Edit $subject, $result)
    {
        return $subject->getUrl('riki_rma/returns/close', ['id' => $this->getRmaId()]);
    }

    /**
     * check rma save permission
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit $subject
     * @param $result
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma\Edit $subject,
        $result
    ){
        if(!$this->canSave()){
            $subject->removeButton('save_and_edit_button');
        }

        if(!$this->authorization->isAllowed('Riki_Rma::rma_return_actions_close')){
            $subject->removeButton('close');
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function canSave(){
        return $this->authorization->isAllowed('Riki_Rma::rma_return_actions_save_w') ||
            $this->authorization->isAllowed('Riki_Rma::rma_return_actions_save_cc');
    }
}