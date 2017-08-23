<?php
/**
 * Swedol_Jeeves
 *
 * NOTICE OF LICENSE
 *
 * Copyright (C) 2016  Improove
 *
 * Swedol_Jeeves is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Swedol_Jeeves is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Swedol_Jeeves.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category    Improove
 * @package     Swedol_Jeeves
 * @copyright   Copyright (C) 2016 Improove (http://www.improove.se/)
 * @license     http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Class Swedol_Jeeves_Model_Api
 */

namespace Lybe\Jeeves\Model;
use Lybe\Jeeves\Api\ApiInterface;
use Lybe\Jeeves\Api\ApiAbstract;
use Lybe\Jeeves\Helper\Data;
class Api extends ApiAbstract implements ApiInterface
{

    /**
     * Logging instance
     * @var \Lybe\jeeves\Logger\Logger
     */
    protected $_logger;
    protected $_helper;

    public function __construct(
        \Lybe\Jeeves\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Data $helper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    protected function makeApiCall(array $in = array())
    {
        $default = array(
            'mode' => 'off', // off , fake , test , live
            'method' => '',
            'parameters' => array(),
            'debug' => 'false',
            'url' => ''
        );
        $in = $this->_Default($default, $in);

        $answer = 'true';
        $message = 'Nothing to report';
        $method = $in['method'];
        $parameters = $in['parameters'];
        $data = array();

        //Forced log, except getOrderList
        //TODO: Remove forced log
        if($method != 'getOrderList') {
            $in['debug'] = 'true';
        }

        if ($in['mode'] === 'off') {
            $message = 'Off mode. Communication is turned OFF in admin. No calls go to the API.';
            goto leave;
        }

        if ($in['mode'] === 'fake') {
            $message = 'Fake mode. You will get a fake answer. No calls go to the API.';
            goto leave;
        }

        if ($in['debug'] === 'true') {
            $message = 'Sending ' . $in['method'] . ' on ' . $in['url'] . ", parameters:\n" . print_r($parameters, true);
            $this->_log($message);
        }

        try {
            // $auth       = new ChannelAdvisorAuth($devKey, $password);
            // $header     = new SoapHeader("http://www.dacsa.se/webservices/", "APICredentials", $auth, false);

            $connectionOptions = array(
                "soap_version" => 'SOAP_1_2',
                "cache_wsdl" => 0,
                "trace" => 1,
                "exception" => 1,
                "connection_timeout" => 5,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            );

            $client = new \SoapClient($in['url'], $connectionOptions);
            $data = $client->__soapCall($method, array($method => $parameters), NULL, NULL); //$header);

            $data = json_decode(json_encode($data), true);

            if ($in['debug'] === 'true') {
                $message = "Api request:\n" . $client->__getLastRequest();
                $message .= "\n";
                $message .= "Api response:\n" . $client->__getLastResponse();

                $this->_log($message);
            }
            $message = 'Here are the response data from the API';

        } catch (\Exception $e) {
            $message = 'Failed to make api call '. $in['method'] .' on ' . $in['url'] . ", parameters:\n"
                . print_r($parameters, true)
                . "Message: " . $e->getMessage();
            $this->_log($message);

            $answer = 'false';
            $message = 'Error. ' . $e->getMessage();
            $data = array();
        }

        leave:
        return array(
            'answer' => $answer,
            'message' => $message,
            'mode' => $in['mode'],
            'method' => $method,
            'parameters' => $parameters,
            'data' => $data
        );
    }


    protected function _log($message = '', $level = \Zend_Log::DEBUG)
    {
        if (empty($message)) {
            return;
        }

        return $this->_logger->info($message);

    }

    protected function call(array $in = array())
    {
        $mode = $in['mode'];
        if (empty($in['mode']) === true) {
            $mode = $this->_helper->getApiMode();
        }
        unset($in['mode']);

        $method = $in['method'];
        unset($in['method']);

        $parameters = $in;
        $url = $this->_helper->getWsdlUrl();

        $out = array(
            'mode' => $mode,
            'method' => $method,
            'parameters' => $parameters,
            'debug' => 'true',
            'url' => $url
        );

        $response = $this->makeApiCall($out);
        return $response;
    }

    protected function getDefaultIStatus($mode = '') {
        $defaultItem = array(
            'StatusCode' => 0,
            'StatusMessage' => '',
            'ReturnValue' => ''
        );

        if ($mode !== 'fake') {
            return $defaultItem;
        }

        $defaultItem = array(
            'StatusCode' => 1,
            'StatusMessage' => 'abc123',
            'ReturnValue' => 'Return Value'
        );

        return $defaultItem;
    }

    protected function getDefaultIOrderHeader($mode = '') {
        $defaultItem = array(
            'CompanyNo' => 0, // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'CustomerId' => 0, // Magento organization_id
            'CustomerName' => '', // Organization name
            'JeevesOrderNo' => 0,
            'ExternalOrderNo' => '', // Magento order number (not magento order id)
            'OrderDate' => '',
            'OrderReference' => '',
            'OrderStatus' => 0,
            'OrderStatusDescr' => '',
            'CurrencyCode' => '',
            'JeevesFtgNr' => '',
            'OrderTotal' => 0.0,
            'OrderTotalInclVAT' => 0.0,
            'OrderOrigin' => ''
        );
        if ($mode !== 'fake') {
            return $defaultItem;
        }

        $defaultItem = array(
            'CompanyNo' => rand(1,2), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'CustomerId' => rand(1,4), // Magento organization_id
            'CustomerName' => 'Customer name', // Organization name
            'JeevesOrderNo' => rand(2000,3000),
            'ExternalOrderNo' => '10000004', // Magento order number (not magento order id)
            'OrderDate' => date('Y-m-d H:m:s'),
            'OrderReference' => 'Order reference',
            'OrderStatus' => 2,
            'CurrencyCode' => 'SEK',
            'JeevesFtgNr' => 'COMP0013',
            'OrderTotal' => 459.90,
            'OrderTotalInclVAT' => 574.88,
            'OrderOrigin' => 'Web'
        );

        return $defaultItem;
    }

    /**
     * Test with this function call that responds with the classic Hello World.
     * @return mixed|null
     */
    public function getHelloWorld(array $in = array())
    {
        $default = array(
            'method' => 'HelloWorld',
            'mode' => 'test',
            'debug' => 'true'
        );

        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'HelloWorldResult' => ''
        );

        $out = $this->_Default($defaultOut, $response['data']);
        if ($response['mode'] === 'fake') {
            $out['HelloWorldResult'] = 'Fake HelloWorld';
        }

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Create a new ERP user. Returns user ERP id.
     * @param array $in
     * @return array
     */
    public function createUser(array $in = array())
    {
        $default = array(
            'method' => 'createUser',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 20, // Magento customer id
            'CustomerNo' => '34', // Jeeves company Id
            'UserName' => 'sabri',
            'EMail' => 'sabri@test.com',
            'TelNo' => '742738392'
        );

        /**
         * default array


        $default = array(
            'method' => 'createUser',
            'mode' => '',
            'CompanyNo' => (int) $this->config()->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->config()->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 0, // Magento customer id
            'CustomerNo' => '', // Jeeves company Id
            'UserName' => '',
            'EMail' => '',
            'TelNo' => ''
        );*/

        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'createUserResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Update ERP user. Returns user ERP id.
     * @param array $in
     * @return array
     */
    public function updateUser(array $in = array())
    {

        $default = array(
            'method' => 'updateUser',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 20, // Magento customer id
            'CustomerNo' => '34', // Jeeves company Id
            'UserName' => 'sabri updated',
            'EMail' => 'sabri@test.com',
            'TelNo' => '742738392'
        );
        /*
         * $default = array(
            'method' => 'updateUser',
            'mode' => '',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 0, // Magento customer id
            'CustomerNo' => '', // Jeeves company Id
            'UserName' => '',
            'EMail' => '',
            'TelNo' => ''
        );
         */


        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'updateUserResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Remove user role from ERP user in a specific company.
     * If RoleId is left out, all roles for the user in the company will be removed
     * @param array $in
     * @return array
     */
    public function removeUserRole(array $in = array())
    {
        $default = array(
            'method' => 'removeUserRole',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 20, // Magento customer id
            'CustomerNo' => '34', // Jeeves company Id
            'RoleId' => 0 // Today 0=normal user, 1=admin user. Other roles might be introduced in the future.
        );

        /*
         * $default = array(
            'method' => 'removeUserRole',
            'mode' => '',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id
            'CustomerId' => 0, // Magento customer id
            'CustomerNo' => '', // Jeeves company Id
            'RoleId' => 0 // Today 0=normal user, 1=admin user. Other roles might be introduced in the future.
        );
         */
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'removeUserRoleResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Get delivery address(es) for a company.
     * @param array $in
     * @return array
     */
    public function getDeliveryAddresses(array $in = array())
    {
        $default = array(
            'method' => 'getDeliveryAddresses',
            'mode' => '',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id. // Not used in this function. Can be set to 0
            'CustomerNo' => '' // Jeeves company id
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'getDeliveryAddressesResult' => array(
                'IDeliveryAddress' => array()
            ) // Array with addresses
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $defaultItem = array(
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'MagentoAddressId' => 20, // Not all addresses in Jeeves have a MagentoAddressId
            'ERPAddressId' => '', // This is the real identifier
            'CustomerNo' => '', // Jeeves company Id
            'AddressName' => 'my adr name',
            'Street1' => 'my street 1',
            'Street2' => 'my street 2',
            'PostCode' => '23323',
            'City' => 'stockholm',
            'CountryId' => 'SE',
            'Telephone' => '978947398'
        );

        /*$defaultItem = array(
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'MagentoAddressId' => 0, // Not all addresses in Jeeves have a MagentoAddressId
            'ERPAddressId' => '', // This is the real identifier
            'CustomerNo' => '', // Jeeves company Id
            'AddressName' => '',
            'Street1' => '',
            'Street2' => '',
            'PostCode' => '',
            'City' => '',
            'CountryId' => '',
            'Telephone' => ''
        );*/

        if ($response['mode'] === 'fake') {
            $out['getDeliveryAddressesResult'] = array(
                'IDeliveryAddress' => array(
                    $this->getDeliveryAddressesFake(array('MagentoAddressId' => 10)),
                    $this->getDeliveryAddressesFake(array('MagentoAddressId' => 20)),
                    $this->getDeliveryAddressesFake(array('MagentoAddressId' => 30))
                ));
        }

        foreach ($out['getDeliveryAddressesResult']['IDeliveryAddress'] as $number => $item) {
            $item = $this->_Default($defaultItem, $item);
            if($item['Telephone'] === '') $item['Telephone'] = '-';
            $out['getDeliveryAddressesResult']['IDeliveryAddress'][$number] = $item;
        }

        $response['data'] = $out;
        return $response;
    }

    protected function getDeliveryAddressesFake(array $in = array()) {
        $default = array(
            'CompanyNo' => rand(1,2),
            'MagentoAddressId' => rand(1,10),
            'ERPAddressId' => '',
            'CustomerNo' => '', // Jeeves company Id
            'AddressName' => 'My address name',
            'Street1' => 'My street ' . rand(10,30),
            'Street2' => 'Street row 2',
            'PostCode' => '1234' . rand(0,9),
            'City' => 'Stockholm',
            'CountryId' => 'SE',
            'Telephone' => '08123456'
        );
        $in = $this->_Default($default, $in);
        return $in;
    }

    /**
     * Portal: Create a new delivery address for a company. Returns address ERP id.
     * @param array $in
     * @return array
     */
    public function createDeliveryAddress(array $in = array())
    {
        $default = array(
            'method' => 'createDeliveryAddress',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'CustomerId' => 0, // OPTIONAL, Magento organization_id
            'CustomerNo' => '', // Jeeves company Id, you find it in the organization remote_address_id
            'AddressName' => '',
            'Street1' => '',
            'Street2' => '',
            'PostCode' => '',
            'City' => '',
            'CountryId' => '',
            'TelNo' => ''
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'createDeliveryAddressResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Update a delivery address for a company. Returns address ERP id.
     * Identify the address by MagentoAddressId - Not all addresses have this
     * Identify the address by Jeeves ERPAddressId - Set that in "CustomerNo"
     * @param array $in
     * @return array
     */
    public function updateDeliveryAddress(array $in = array())
    {
        $default = array(
            'method' => 'updateDeliveryAddress',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id (OPTIONAL for now)
            'ERPAddressId' => '', // Remote address Id. Unique together with CustomerNo.
            'CustomerNo' => '', // Jeeves company Id
            'AddressName' => '',
            'Street1' => '',
            'Street2' => '',
            'PostCode' => '',
            'City' => '',
            'CountryId' => '',
            'TelNo' => ''
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'updateDeliveryAddressResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Delete a delivery address from a company. Returns company ERP id.
     * Identify the address by MagentoAddressId - Not all addresses have this
     * Identify the address by Jeeves ERPAddressId - Set that in "CustomerNo"
     * @param array $in
     * @return array
     */
    public function deleteDeliveryAddress(array $in = array())
    {
        $default = array(
            'method' => 'deleteDeliveryAddress',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id (OPTIONAL for now)
            'ERPAddressId' => '', // Remote address Id. Unique together with CustomerNo.
            'CustomerNo' => '', // Jeeves company Id
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'deleteDeliveryAddressResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Get list of orders for a company.
     * @param array $in
     * @return array
     */
    public function getOrderList(array $in = array())
    {
        $default = array(
            'method' => 'getOrderList',
            'mode' => '',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id. NOT USED FOR NOW
            'CustomerNo' => '', // Jeeves company Id
            'PageNo' => 1, // Is 0 all or none?
            'PageSize' => 3, // 0 = get 100
            'SortBy' => 'OrderDate DESC',
            'DateFrom' => null,
            'DateTo' => null,
            'AmountFrom' => null,
            'AmountTo' => null,
            'OrderStatus' => null,
            'AddOrderLines' => true,
            'CustomerIds' => null,
            'onlyWebOrders' => false,
        );
        $in = $this->_Default($default, $in);


        $response = $this->call($in);

        $defaultOut = array(
            'getOrderListResult' => array('IOrderList' => array())
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $defaultItem = array(
            'OrderCount' => 0,
            'orderhead' => $this->getDefaultIOrderHeader($response['mode']),
            'orderlines' => array()
        );

        if ($response['mode'] === 'fake') {
            $out['getOrderListResult']['IOrderList'] = array($defaultItem, $defaultItem, $defaultItem);
        }

        foreach ($out['getOrderListResult']['IOrderList'] as $number => $item) {
            if (isset($item['orderhead']['OrderTotal'])) {
                $item['orderhead']['OrderTotal'] = floatval($item['orderhead']['OrderTotal']);
            }
            if (isset($item['orderhead']['OrderTotalInclVAT'])) {
                $item['orderhead']['OrderTotalInclVAT'] = floatval($item['orderhead']['OrderTotalInclVAT']);
            }
            if (isset($item['orderhead']['OrderDate'])) {
                $item['orderhead']['OrderDate'] = substr($item['orderhead']['OrderDate'],0,10);
            }
            $item = $this->_Default($defaultItem, $item);
            $out['getOrderListResult']['IOrderList'][$number] = $item;
        }

        $response['data'] = $out;
        return $response;
    }

    /**
     * Portal: Order price list by mail.
     * @param array $in
     * @return array
     */
    public function orderPricelist(array $in = array())
    {
        $default = array(
            'method' => 'orderPricelist',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'AdminId' => (int) $this->_helper->getAdminId(), // Jeeves web portal login user id. NOT USED FOR NOW
            'CustomerNo' => '', // Jeeves company Id
            'EMail' => '' // The email address that will get the price list
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'orderPricelistResult' => $this->getDefaultIStatus($in['mode'])
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $response['data'] = $out;
        return $response;
    }

    /**
     * Return all orders for a given customer
     * @param array $in
     * @return array
     */
    public function getCustomerOrders(array $in = array())
    {
        $default = array(
            'method' => 'GetCustomerOrders',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'CustomerId' => 0, // Magento customer id
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'GetCustomerOrdersResult' => array()
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $defaultItem = $this->getDefaultIOrderHeader();

        if ($response['mode'] === 'fake') {
            $out['GetCustomerOrdersResult'] = array($defaultItem, $defaultItem, $defaultItem);
        }

        foreach ($out['GetCustomerOrdersResult'] as $number => $item) {
            $item = $this->_Default($defaultItem, $item);
            $out['GetCustomerOrdersResult'][$number] = $item;
        }

        $response['data'] = $out;
        return $response;
    }

    /**
     * Return all order lines for a given order
     * @param array $in
     * @return array
     */
    public function getOrderLines(array $in = array())
    {
        $default = array(
            'method' => 'GetOrderLines',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'OrderNo' => '' // Test server have only one example order, OrderNo = 1
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'GetOrderLinesResult' => array('IOrderLine' => array())
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $defaultItem = $this->defaultOrderLine($response['mode']);

        if ($response['mode'] === 'fake') {
            $out['GetOrderLinesResult']['IOrderLine'] = array($defaultItem, $defaultItem, $defaultItem);
        }

        foreach ($out['GetOrderLinesResult']['IOrderLine'] as $number => $item) {
            $item = $this->_convertToNumber($item);
            $item = $this->_Default($defaultItem, $item);
            $out['GetOrderLinesResult']['IOrderLine'][$number] = $item;
        }

        $response['data'] = $out;
        return $response;
    }

    protected function _convertToNumber($item) {
        $fieldsToFloat = array('OrderQty', 'OrderRestQty', 'Price', 'NetPrice', 'RebatePercent', 'OrderLineTotal', 'OrderLineTotalInclVAT');
        foreach ($fieldsToFloat as $fieldName) {
            if (isset($item[$fieldName])) {
                $item[$fieldName] = floatval($item[$fieldName]);
            }
        }
        return $item;
    }

    protected function defaultOrderLine($mode = '') {
        $defaultItem = array(
            'CompanyNo' => 0, // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'ExternalOrderNo' => '',
            'ExternalOrderPos' => 0,
            'JeevesOrderNo' => 0,
            'OrderPos' => 0,
            'OrderSubPos' => 0,
            'OrderStatus' => 0,
            'WarehouseId' => '',
            'ProductId' => '',
            'ProductDescr' => '',
            'OrderText' => '',
            'OrderQty' => 0.0,
            'OrderRestQty' => 0.0,
            'UnitCode' => '',
            'ShippingDate' => '',
            'Price' => 0.0,
            'NetPrice' => 0.0,
            'RebatePercent' => 0.0,
            'OrderLineTotal' => 0.0,
            'OrderLineTotalInclVAT' => 0.0
        );
        if ($mode !== 'fake') {
            return $defaultItem;
        }

        $defaultItem = array(
            'CompanyNo' => rand(1,2), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'ExternalOrderNo' => '1000004',
            'ExternalOrderPos' => 1,
            'JeevesOrderNo' => 40004,
            'OrderPos' => 10,
            'OrderSubPos' => 10,
            'OrderStatus' => 1,
            'WarehouseId' => 'WAREHOUSE 51',
            'ProductId' => 'SKU4',
            'ProductDescr' => 'T-shirt with blue dolphins',
            'OrderText' => 'Order text',
            'OrderQty' => 2.0,
            'OrderRestQty' => 0.0,
            'UnitCode' => 'UnitCode',
            'ShippingDate' => '2016-08-19',
            'Price' => 100.0,
            'NetPrice' => 80.0,
            'RebatePercent' => 0.0,
            'OrderLineTotal' => 160.0,
            'OrderLineTotalInclVAT' => 200.0
        );
        return $defaultItem;

    }

    /**
     * Return stock information for a given item, if warehouse is omitted then for all warehouses
     * @param array $in
     * @return array
     */
    public function getStockInfo(array $in = array())
    {
        $default = array(
            'method' => 'GetStockInfo',
            'mode' => 'test',
            'CompanyNo' => (int) $this->_helper->getCompanyId(), // 1 = Swedol AB, 2 = Swedol AS (Norge)
            'ProductId' => '',
            'WarehouseId' => ''
        );
        $in = $this->_Default($default, $in);

        $response = $this->call($in);

        $defaultOut = array(
            'GetStockInfoResult' => array()
        );
        $out = $this->_Default($defaultOut, $response['data']);

        $defaultItem = array(
            'CompanyNo' => 0,
            'ProductId' => '',
            'WarehouseId' => '',
            'WarehouseName' => '',
            'AvailableQty' => 0.0,
            'PhysicalQty' => 0.0,
            'OrderedQty' => 0.0,
            'LeadTimeDays' => 0
        );

        if ($response['mode'] === 'fake') {
            $out['GetStockInfoResult'] = array($defaultItem, $defaultItem, $defaultItem);
        }

        foreach ($out['GetStockInfoResult'] as $number => $item) {
            $item = $this->_Default($defaultItem, $item);
            $out['GetStockInfoResult'][$number] = $item;
        }

        $response['data'] = $out;
        return $response;
    }
}