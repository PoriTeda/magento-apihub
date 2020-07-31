<?php
namespace Riki\ThirdPartyImportExport\Controller;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

abstract class Order extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Order\Config
     */
    protected $_config;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * Order constructor.
     * @param Registry $registry
     * @param \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory
     * @param \Riki\ThirdPartyImportExport\Helper\Order\Config $config
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory,
        \Riki\ThirdPartyImportExport\Helper\Order\Config $config,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_registry = $registry;
        $this->_config = $config;
        $this->_orderFactory = $orderFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }

    /**
     * @return mixed
     */
    public function initModel()
    {
        $model = $this->_registry->registry('current_order');
        if ($model) {
            return $model;
        }
        $model = $this->_orderFactory->create()
            ->load($this->getRequest()->getParam('id'));

        $this->_registry->register('current_order', $model);

        return $model;
    }
}
