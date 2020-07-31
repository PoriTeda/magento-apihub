<?php


namespace Riki\Subscription\Controller\Profile;

use \Magento\Customer\Controller\Address\FormPost;
use \Magento\Framework\Exception\InputException;

class SaveAddress extends FormPost
{
    public function execute()
    {
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
        }

        $this->convertPostDataRequest();

        try {
            $address = $this->_extractAddress();
            $this->_addressRepository->save($address);
            $this->messageManager->addSuccess(__('You saved the address.'));
            $url = $this->_buildUrl($this->_redirect->getRefererUrl());
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->success($url));
        } catch (InputException $e) {
            $this->messageManager->addError($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addError($error->getMessage());
            }
        } catch (\Exception $e) {
            $redirectUrl = $this->_redirect->getRefererUrl();
            $this->messageManager->addException($e, __('We can\'t save the address.'));
        }
        $url = $redirectUrl;
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($url));
    }

    /**
     * Extract address from request
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    protected function _extractAddress()
    {
        $addressDataObject = parent::_extractAddress();
        $addressDataObject->setCustomAttribute(
            'riki_type_address',
            \Riki\Customer\Model\Address\AddressType::SHIPPING
        );
        return $addressDataObject;
    }

    /**
     * Convert data post from before process
     */
    protected function convertPostDataRequest()
    {
        $request = $this->getRequest();
        if ($request->getParam('sub_profile_edit_address') == 1) {
            $postcodeData = $request->getParam('postcode_c');
            $regionId = $request->getParam('region_id_c');
            $city = $request->getParam('city_c');
            $street = $request->getParam('street_c');
            $region = $request->getParam('region_c');
            $countryId = $request->getParam('country_id_c');
            $arrParamSet = [
                'postcode' => $postcodeData,
                'region_id' => $regionId,
                'city' => $city,
                'street' => $street,
                'region' => $region,
                'country_id' => $countryId
            ];
            $this->_request->setParams($arrParamSet);
        }
    }
}
