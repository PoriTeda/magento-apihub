<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Decorator for DecisionValidator.
 *
 * This validator includes special case when actual authorization transaction was
 * declined by Cybersource decision manager but due to issues from Cybersource side
 * it is not clear for the merchant.
 */
class CancelValidator extends AbstractValidator
{
    /**
     * Unacceptable decision
     *
     * @var String
     */
    private static $declineDecision = 'REJECT';

    /**
     * General reason code for invalid field data
     *
     * @var int
     */
    private static $invalidDataCode = 102;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param ValidatorInterface $validator
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ValidatorInterface $validator
    ) {
        $this->validator = $validator;
        parent::__construct($resultFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $validationSubject)
    {
        $result = $this->validator->validate($validationSubject);

        if (!$result->isValid()) {
            $response = SubjectReader::readResponse($validationSubject);

            $result = $this->createResult(
                isset($response[DecisionValidator::DECISION], $response[DecisionValidator::REASON_CODE]) &&
                $response[DecisionValidator::DECISION] === self::$declineDecision &&
                $response[DecisionValidator::REASON_CODE] === self::$invalidDataCode,
                $result->getFailsDescription()
            );
        }

        return $result;
    }
}
