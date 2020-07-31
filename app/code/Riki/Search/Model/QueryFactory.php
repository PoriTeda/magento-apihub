<?php

namespace Riki\Search\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface; // @codingStandardsIgnoreLine
use Magento\Framework\Stdlib\StringUtils as StdlibString;
use Magento\Store\Model\ScopeInterface;
use Magento\Search\Model\Query;

class QueryFactory extends \Magento\Search\Model\QueryFactory
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var StdlibString
     */
    private $string;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Data helper
     *
     * @var \Riki\CatalogSearch\Helper\Data
     */
    protected $dataHelper;

    /**
     * QueryFactory constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StdlibString $string
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager, // @codingStandardsIgnoreLine
        StdlibString $string,
        \Riki\CatalogSearch\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $context,
            $objectManager, // @codingStandardsIgnoreLine
            $string
        );
        $this->request = $context->getRequest();
        $this->string = $string;
        $this->scopeConfig = $context->getScopeConfig();
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (!$this->query) {
            $maxQueryLength = $this->getMaxQueryLength();
            $rawQueryText = $this->dataHelper->clean($this->getRawQueryText()); // Strip data
            $preparedQueryText = $this->getPreparedQueryText($rawQueryText, $maxQueryLength);
            $query = $this->create()->loadByQuery($preparedQueryText);
            if (!$query->getId()) {
                $query->setQueryText($preparedQueryText);
            }
            $query->setIsQueryTextExceeded($this->isQueryTooLong($rawQueryText, $maxQueryLength));
            $this->query = $query;
        }
        return $this->query;
    }

    /**
     * Retrieve search query text
     *
     * @return string
     */
    private function getRawQueryText()
    {
        $queryText = $this->request->getParam(self::QUERY_VAR_NAME);
        return ($queryText === null || is_array($queryText))
            ? ''
            : $this->string->cleanString(trim($queryText));
    }

    /**
     * @param string $queryText
     * @param int|string $maxQueryLength
     * @return string
     */
    private function getPreparedQueryText($queryText, $maxQueryLength)
    {
        if ($this->isQueryTooLong($queryText, $maxQueryLength)) {
            $queryText = $this->string->substr($queryText, 0, $maxQueryLength);
        }
        return $queryText;
    }

    /**
     * Retrieve maximum query length
     *
     * @param mixed $store
     * @return int|string
     */
    private function getMaxQueryLength($store = null)
    {
        return $this->scopeConfig->getValue(
            Query::XML_PATH_MAX_QUERY_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param string $queryText
     * @param int|string $maxQueryLength
     * @return bool
     */
    private function isQueryTooLong($queryText, $maxQueryLength)
    {
        return ($maxQueryLength !== '' && $this->string->strlen($queryText) > $maxQueryLength);
    }
}