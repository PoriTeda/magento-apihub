<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Validator\SilentOrder;

use Magento\Cybersource\Gateway\Helper\SilentOrderHelper;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class SignValidator extends AbstractValidator
{
    /**
     * Signed fields key
     */
    const SIGNED_FIELD_NAMES = 'signed_field_names';

    /**
     * Signature field
     */
    const SIGNATURE = 'signature';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config
    ) {
        parent::__construct($resultFactory);

        $this->config = $config;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return null|ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $paymentDO = SubjectReader::readPayment($validationSubject);

        if (!isset(
            $response[static::SIGNED_FIELD_NAMES],
            $response[static::SIGNATURE]
        )
        ) {
            return $this->createResult(false, [__('Gateway validation error')]);
        }

        try {
            return $this->createResult(
                SilentOrderHelper::signFields(
                    $this->getFieldsToSign(
                        $response,
                        $response[static::SIGNED_FIELD_NAMES]
                    ),
                    $this->config->getValue(
                        'secret_key',
                        $paymentDO->getOrder()->getStoreId()
                    )
                ) === $response[static::SIGNATURE]
            );
        } catch (\LogicException $e) {
            return $this->createResult(false, [__('Gateway validation error')]);
        }
    }

    /**
     * Returns signed fields
     *
     * @param array $response
     * @param string $signedList
     * @return array
     */
    private function getFieldsToSign(array $response, $signedList)
    {
        $result = [];
        foreach (explode(',', $signedList) as $key) {
            if (!isset($response[$key])) {
                throw new \LogicException;
            }
            $result[$key] = $response[$key];
        }
        return $result;
    }
}
