<?php

namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;

abstract class WbsAction extends \Magento\Backend\App\Action
{
    const DEFAULT_TIMEZONE = 'UTC';
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Riki\CatalogRule\Model\WbsConversionFactory
     */
    protected $wbsConversionFactory;

    /**
     * WbsAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\CatalogRule\Model\WbsConversionFactory $wbsConversionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\CatalogRule\Model\WbsConversionFactory $wbsConversionFactory
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry = $registry;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->wbsConversionFactory = $wbsConversionFactory;
    }

    /**
     * {@inheritdoc}
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Magento_Backend::marketing_seo');
        $resultPage->getConfig()->getTitle()->prepend(__('Wbs Conversion Management'));

        return $resultPage;
    }

    /**
     * Current template model
     *
     * @return \Riki\FairAndSeasonalGift\Model\Fair
     */
    public function initModel()
    {
        $model = $this->wbsConversionFactory->create();

        if ($this->getRequest()->getParam('entity_id')) {
            $model->load($this->getRequest()->getParam('entity_id'));
        }

        $this->registry->register('current_wbs_conversion_data', $model);

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Riki_CatalogRule::wbs_conversion_management');
    }
}
