<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Search;

class Results extends \Magento\GiftRegistry\Controller\Search
{
    /**
     * Get current customer session
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Customer\Model\Session::class);
    }

    /**
     * Validate input search params
     *
     * @param array $params
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _validateSearchParams($params)
    {
        if (empty($params) || !is_array($params) || empty($params['search'])) {
            $this->messageManager->addNotice(__('Please enter correct search options.'));
            return false;
        }

        switch ($params['search']) {
            case 'type':
                if (empty($params['firstname']) || strlen($params['firstname']) < 2) {
                    $this->messageManager->addNotice(__('Please enter at least 2 letters of the first name.'));
                    return false;
                }
                if (empty($params['lastname']) || strlen($params['lastname']) < 2) {
                    $this->messageManager->addNotice(__('Please enter at least 2 letters of the last name.'));
                    return false;
                }
                break;

            case 'email':
                if (empty($params['email']) || !\Zend_Validate::is($params['email'], 'EmailAddress')) {
                    $this->messageManager->addNotice(__('Please enter a valid email address.'));
                    return false;
                }
                break;

            case 'id':
                if (empty($params['id'])) {
                    $this->messageManager->addNotice(__('Please enter a gift registry ID.'));
                    return false;
                }
                break;

            default:
                $this->messageManager->addNotice(__('Please enter correct search options.'));
                return false;
        }
        return true;
    }

    /**
     * Filter input form data
     *
     * @param  array $params
     * @return array
     */
    protected function _filterInputParams($params)
    {
        foreach ($params as $key => $value) {
            $params[$key] = htmlspecialchars($value);
        }
        if (isset($params['type_id'])) {
            $type = $this->_initType($params['type_id']);
            $dateType = $this->_objectManager->get(
                \Magento\GiftRegistry\Model\Attribute\Config::class
            )->getStaticDateType();
            if ($dateType) {
                $attribute = $type->getAttributeByCode($dateType);
                $format = isset($attribute['date_format']) ? $attribute['date_format'] : null;

                $dateFields = [];
                $fromDate = $dateType . '_from';
                $toDate = $dateType . '_to';

                if (isset($params[$fromDate])) {
                    $dateFields[] = $fromDate;
                }
                if (isset($params[$toDate])) {
                    $dateFields[] = $toDate;
                }
                $params = $this->_filterInputDates($params, $dateFields, $format);
            }
        }
        return $params;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $params = $this->getRequest()->getParam('params');
        if ($params) {
            $this->_getSession()->setRegistrySearchData($params);
        } else {
            $params = $this->_getSession()->getRegistrySearchData();
        }

        if ($this->_validateSearchParams($params)) {
            $results = $this->_objectManager->create(
                \Magento\GiftRegistry\Model\Entity::class
            )->getCollection()->applySearchFilters(
                $this->_filterInputParams($params)
            );

            $this->_view->getLayout()->getBlock('giftregistry.search.results')->setSearchResults($results);
        } else {
            $this->_redirect('*/*/index', ['_current' => true]);
            return;
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Gift Registry Search'));
        $this->_view->renderLayout();
    }

    /**
     * Convert dates in array from localized to internal format
     *
     * @param array $array
     * @param string[] $dateFields
     * @param string $format
     * @return array
     */
    protected function _filterInputDates($array, $dateFields, $format = null)
    {
        if (empty($dateFields)) {
            return $array;
        }
        if ($format === null) {
            $format = \IntlDateFormatter::SHORT;
        }

        $filterInput = new \Zend_Filter_LocalizedToNormalized(
            [
                'locale' => $this->_localeResolver->getLocale(),
                'date_format' => $this->_localeDate->getDateFormat($format),
            ]
        );
        $filterInternal = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT]
        );

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }
        return $array;
    }
}
