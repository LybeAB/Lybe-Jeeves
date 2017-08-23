<?php
namespace Lybe\Jeeves\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
class Data extends AbstractHelper
{
    public $_storeManager;

    /**
     * Logging instance
     * @var \Lybe\jeeves\Logger\Logger
     */
    protected $_logger;
    protected $_scopeConfig;

    public function __construct(
        \Lybe\Jeeves\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem\DirectoryList $_directorylist
    )
    {
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
    }

    public function getApiMode($storeId = null)
    {
        $path = 'api/mode';
        return $this->getConfig($path, $storeId);
    }

    public function getApiTestEnabled($storeId = null)
    {
        $path = 'test/test_action';
        return $this->getConfig($path, $storeId);
    }

    protected function getApiPublic($storeId = null)
    {
        $path = 'api/public';
        return $this->getConfig($path, $storeId);
    }

    public function getAdminId($storeId = null)
    {
        $path = 'api_usage/admin_id';
        return $this->getConfig($path, $storeId);
    }

    public function getWsdlUrl($storeId = null)
    {
        $paths = array(
            'test' => array(
                'internal' => 'test_wsdl_url',
                'external' => 'test_public_wsdl_url'
            ),
            'live' => array(
                'internal' => 'live_wsdl_url',
                'external' => 'live_wsdl_url'
            )
        );

        $mode = $this->getApiMode($storeId);
        $public = $this->getApiPublic($storeId);

        $path = '';
        if (isset($paths[$mode][$public])) {
            $path = $paths[$mode][$public];
        }

        $url = '';
        if (empty($path) === false) {
            $path = 'api/' . $path;
            $url = $this->getConfig($path, $storeId);
        }else{
            // test mode off with Test internal
            $path = $paths['test']['internal'];
            $path = 'api/' . $path;
            $url = $this->getConfig($path, $storeId);
        }

        return $url;
    }

    public function getCompanyId($storeId = null)
    {
        $paths = array(
            'test' => 'test_company_id',
            'live' => 'live_company_id'
        );

        $mode = $this->getApiMode($storeId);

        $companyId = 0;
        if (isset($paths[$mode])) {
            $path = 'api/' . $paths[$mode];
            $companyId = (int) $this->getConfig($path, $storeId);
        }else{
            // test mode off with Test internal
            $path = $paths['test'];
            $path = 'api/' . $path;
            $companyId = (int) $this->getConfig($path, $storeId);
        }
        return $companyId;
    }

    protected function getConfig($path = '', $storeId = null) {
        $path = 'swedol_jeeves/' . $path;

        if($storeId === null) $storeId = $this->_storeManager->getStore()->getId();
        $storeId = (int) $storeId;

        $response = '';
        if (empty($path)  === false) {
            $response = $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
       }

        return $response;
    }
}