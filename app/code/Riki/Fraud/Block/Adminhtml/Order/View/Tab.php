<?php

namespace Riki\Fraud\Block\Adminhtml\Order\View;

use Mirasvit\FraudCheck\Model\Config;

class Tab extends \Mirasvit\FraudCheck\Block\Adminhtml\Order\View\Tab
{
    const CUSTOMERLOCATION = 1;
    const SHIPPINGLOCATION = 2;
    const BILLINGLOCATION = 3;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Mirasvit\FraudCheck\Api\Service\RenderServiceInterface
     */
    protected $_inheritRenderService;

    /**
     * Tab constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Mirasvit\FraudCheck\Model\ScoreFactory $scoreFactory
     * @param \Mirasvit\FraudCheck\Model\Context $checkContext
     * @param \Mirasvit\FraudCheck\Api\Service\MatchServiceInterface $matchService
     * @param \Mirasvit\FraudCheck\Api\Service\RenderServiceInterface $renderService
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mirasvit\FraudCheck\Model\ScoreFactory $scoreFactory,
        \Mirasvit\FraudCheck\Model\Context $checkContext,
        \Mirasvit\FraudCheck\Api\Service\MatchServiceInterface $matchService,
        \Mirasvit\FraudCheck\Api\Service\RenderServiceInterface $renderService,
        Config $config
    ) {
        parent::__construct($scoreFactory, $checkContext, $matchService, $renderService, $config, $context, $registry);
        $this->_jsonHelper = $jsonHelper;
        $this->_inheritRenderService = $renderService;
    }

    public function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('approve_button_container').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'approve_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Approve'), 'class' => 'save', 'onclick' => $onclick]
        );
    }

    /**
     * Get label for Fraud tab
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        $tabLabel = __('Fraud Risk Score');

        $order = $this->getOrder();

        if ($order && !empty($order->getFraudStatus())) {
            $tabLabel .= $this->_inheritRenderService->getScoreBadgeHtml($order->getFraudStatus(), $order->getFraudScore());
        }

        return $tabLabel;
    }

    /**
     * Get order location by location type
     *
     * @param $type
     * @return string
     */
    public function getLocation($type)
    {
        $location = [];

        switch ($type) {
            case self::CUSTOMERLOCATION:
                $location = $this->getCustomerLocation();
                break;
            case self::SHIPPINGLOCATION:
                $location = $this->getShippingLocation();
                break;
            case self::BILLINGLOCATION:
                $location = $this->getBillingLocation();
                break;
        }

        return $this->_jsonHelper->jsonEncode($location);
    }

    /**
     * Get fraud status by rule
     *
     * @param $rule
     * @return mixed
     */
    public function getFraudStatus($rule)
    {
        return $this->getScore()->getFraudStatusByRule( $rule, $this->getOrder() );
    }

    /**
     * check order is approved or not
     *
     * @return bool
     */
    public function isApprove()
    {
        if ($this->getOrder()->getData('fraud_status') == \Riki\Fraud\Model\Score::STATUS_REVIEW) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * approve button
     * @return string
     */
    public function approveButton()
    {
        return $this->getChildHtml('approve_button');
    }

    /**
     * get order approve url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('riki_fraud/order/approve', ['order_id' => $this->getOrder()->getId()]);
    }
}
