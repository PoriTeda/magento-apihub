<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request\Shared;

use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class RequestDataBuilder
 */
class RequestDataBuilder implements BuilderInterface
{
    /**
     * This variable is required but currently unused. Any valid URL can be used.
     */
    const REDIRECT_URL = 'RedirectUrl';

    /**
     * This variable is required but currently unused. Any valid URL can be used.
     */
    const CANCEL_URL = 'CancelUrl';

    /**
     * Language code determines the language that the iframe will be displayed in.
     * One of: EN (English, default), ES (Spanish)
     */
    const LANGUAGE = 'Language';

    /**
     * When set to false, cardholders will be able to edit their email &
     * phone number, even if it’s sent through in the server side request.
     * To fetch the details the customer entered,
     * do a Transaction Query once the transaction is complete.
     */
    const CUSTOMER_READONLY = 'CustomerReadOnly';

    /**
     * @var UrlInterface
     */
    private $urlHelper;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @param ResolverInterface $localeResolver
     * @param UrlInterface $urlHelper
     */
    public function __construct(
        ResolverInterface $localeResolver,
        UrlInterface $urlHelper
    ) {
        $this->localeResolver = $localeResolver;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        return [
            self::LANGUAGE => strtoupper(substr($this->localeResolver->getLocale(), 0, 2)),
            self::CUSTOMER_READONLY => true,
            self::REDIRECT_URL => $this->urlHelper->getBaseUrl(),
            self::CANCEL_URL => $this->urlHelper->getBaseUrl()
        ];
    }
}
