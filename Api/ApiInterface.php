<?php
namespace Lybe\Jeeves\Api;

interface ApiInterface
{

    /**
     * Returns hello world
     *
     * @api
     *
     * @return string Greeting message with hello world.
     */
    public function getHelloWorld();

    /**
     * Portal: Create a new ERP user
     * Returns user ERP id.
     *
     * @api
     *
     * @return string message.
     */
    public function createUser();

    /**
     * Portal: Update ERP user
     * Returns user ERP id.
     *
     * @api
     *
     * @return string message.
     */
    public function updateUser();

    /**
     * Portal: Remove user role from ERP user in a specific company.
     * If RoleId is left out, all roles for the user in the company will be removed
     * @param array $in
     * @api
     * @return array
     */
    public function removeUserRole();

    /**
     * Portal: Get delivery address(es) for a company.
     * @param array $in
     * @return array
     */
    public function getDeliveryAddresses();


    /**
     * Portal: Create a new delivery address for a company. Returns address ERP id.
     * @param array $in
     * @return array
     */
    public function createDeliveryAddress();

    /**
     * Portal: Update a delivery address for a company. Returns address ERP id.
     * Identify the address by MagentoAddressId - Not all addresses have this
     * Identify the address by Jeeves ERPAddressId - Set that in "CustomerNo"
     * @param array $in
     * @return array
     */
    public function updateDeliveryAddress();

    /**
     * Portal: Delete a delivery address from a company. Returns company ERP id.
     * Identify the address by MagentoAddressId - Not all addresses have this
     * Identify the address by Jeeves ERPAddressId - Set that in "CustomerNo"
     * @param array $in
     * @return array
     */
    public function deleteDeliveryAddress();

    /**
     * Return all order lines for a given order
     * @param array $in
     * @return array
     */
    public function getOrderLines();

    /**
     * Return stock information for a given item, if warehouse is omitted then for all warehouses
     * @param array $in
     * @return array
     */
    public function getStockInfo();

    /**
     * Portal: Order price list by mail.
     * @param array $in
     * @return array
     */
    public function orderPricelist();

    /**
     * Return all orders for a given customer
     * @param array $in
     * @return array
     */
    public function getCustomerOrders();

}