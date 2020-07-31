<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Banner\Model\Banner\DataFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * Banner loading
 */
class Load extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var RawFactory
     */
    protected $rawFactory;

    /**
     * @var DataFactory
     */
    protected $dataFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param RawFactory $rawFactory
     * @param DataFactory $dataFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RawFactory $rawFactory,
        DataFactory $dataFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->rawFactory = $rawFactory;
        $this->dataFactory = $dataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRaw = $this->rawFactory->create();
        $dataObject = $this->dataFactory->create();

        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode(400);
        }

        $response = ['data' => $dataObject->getSectionData()];
        $resultJson = $this->jsonFactory->create();

        return $resultJson->setData($response);
    }
}
