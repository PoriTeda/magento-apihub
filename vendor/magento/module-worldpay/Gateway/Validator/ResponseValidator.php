<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Framework\App\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Validate responses from WorldPay after successful payment
 */
class ResponseValidator extends AbstractValidator
{
    /**
     * The Payment Responses password, if you have set it in our database via
     * the Merchant Interface. This is only available in the parameters sent in
     * the Payment Responses message. It is not available for substitution
     * into the page sent to the shopper.
     */
    const RESPONSE_PASSWORD = 'callbackPW';

    /**
     * @var Request\Http
     */
    private $request;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RemoteAddress
     * @deprecated Unused dependency that will be removed in future release
     */
    private $remoteAddress;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param Request\Http $request
     * @param RemoteAddress $remoteAddress
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Request\Http $request,
        RemoteAddress $remoteAddress,
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($resultFactory);

        $this->request = $request;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(array $validationSubject)
    {
        $orderIsNotFound = function () {
            $result = true;
            try {
                $this->orderRepository->get($this->request->getPost(OrderDataBuilder::ORDER_ID));
            } catch (NotFoundException $e) {
                $result = false;
            }

            return [
                $result,
                'Order is not found.'
            ];
        };

        $statements = [
            function () {
                return [
                    $this->request->isPost(),
                    'Wrong request type.'
                ];
            },
            function () {
                return [
                    !(empty($this->request->getPost())
                    || empty($this->request->getPost(OrderDataBuilder::ORDER_ID))
                    || empty($this->request->getPost(OrderDataBuilder::STORE_ID))
                    || empty($this->request->getPost(self::RESPONSE_PASSWORD))),
                    'Request doesn\'t contain required fields.'
                ];
            },
            $orderIsNotFound,
            function () {
                return [
                    $this->config->getValue(
                        'response_password',
                        $this->request->getPost(OrderDataBuilder::STORE_ID)
                    ) === $this->request->getPost(self::RESPONSE_PASSWORD),
                    'Transaction password is wrong.'
                ];
            }
        ];

        /** @var \Closure $statement */
        foreach ($statements as $statement) {
            $result = $statement();
            if (!array_shift($result)) {
                return $this->createResult(false, [__(array_shift($result))]);
            }
        }

        return $this->createResult(true);
    }
}
