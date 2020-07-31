<?php
namespace Riki\Preorder\Plugin\Quote\Model;

use Magento\Framework\Exception\LocalizedException;

class QuoteValidator
{

    /** @var \Riki\Preorder\Model\PreOrderValidator  */
    protected $preOrderValidator;

    /**
     * QuoteValidator constructor.
     * @param \Riki\Preorder\Model\PreOrderValidator $preOrderValidator
     */
    public function __construct(
        \Riki\Preorder\Model\PreOrderValidator $preOrderValidator
    ) {
        $this->preOrderValidator = $preOrderValidator;
    }

    /**
     * @param \Magento\Quote\Model\QuoteValidator $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     * @throws LocalizedException
     */
    public function beforeValidateBeforeSubmit(
        \Magento\Quote\Model\QuoteValidator $subject,
        \Magento\Quote\Model\Quote $quote
    ) {
        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return [$quote];
        }

        try {
            $this->preOrderValidator->validateBeforeSubmit($quote);
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LocalizedException(__('invalid request'));
        }


        return [$quote];
    }
}
