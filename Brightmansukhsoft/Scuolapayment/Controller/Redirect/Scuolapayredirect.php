<?php
/**
 * Copyright � 2020 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Brightmansukhsoft\Scuolapayment\Controller\Redirect;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
class Scuolapayredirect extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $_checkoutSession;
    protected $orderRepository;
    protected $customerSession;
    private $timezone;
    protected $_encryptor;
    protected $_resource;
    protected $helper;
    protected $_coreRegistry = null;
    public function __construct(Context $context, PageFactory $pageFactory, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Brightmansukhsoft\Scuolapayment\Helper\Data $helperData, \Magento\Framework\App\ResourceConnection $resource, \Magento\Framework\Encryption\EncryptorInterface $encryptor, \Magento\Store\Model\StoreManagerInterface $storemanager, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Registry $registry, \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone)
    {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helperData;
        $this->_resource = $resource;
        $this->storemanager = $storemanager;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->_encryptor = $encryptor;
        $this->_coreRegistry = $registry;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this
            ->pageFactory
            ->create();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $orderId = $this
            ->_checkoutSession
            ->getLastRealOrder()
            ->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()
            ->getCurrentCurrencyCode();
        if ($currency == 'EUR')
        {
            if ($orderId)
            {
                $orderNo = $this
                    ->_checkoutSession
                    ->getLastRealOrder()
                    ->getIncrementId();
                $order = $this
                    ->_objectManager
                    ->create('Magento\Sales\Model\Order')
                    ->load($orderId);
                $total = $order->getGrandTotal();
                $total_amt = $total + 0;
                $total_amt = number_format($total_amt, 2, '.', '');
                $total_amt = $total_amt * 100;
                $total_amt = (string)$total_amt;

                $helper = $this->helper;
                $environment = $helper->getConfig('payment/scuolapayment/environment');
                if ($environment == 'Production')
                {
                    $redirectUrl = 'https://webpayments-api.scuolapay.it';
                    $webhook = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/production_webhook');
                    $secretEncrypted = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_secret');
                    $secret = $this
                        ->_encryptor
                        ->decrypt($secretEncrypted);
                    $business = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/production_bussiness');

                }
                else
                {
                    $redirectUrl = 'https://webpayments-api-dev.scuolapay.it';
                    $webhook = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_webhook');
                    $secretEncrypted = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_secret');
                    $secret = $this
                        ->_encryptor
                        ->decrypt($secretEncrypted);
                    $business = $helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_bussiness');
                }
                $responseUrl = $this
                    ->storemanager
                    ->getStore()
                    ->getBaseUrl() . 'scuolapay/response';
                if ($webhook)
                {
                    $orderData = array(
                        'secret' => $secret,
                        'business' => $business,
                        'redirectUrl' => $redirectUrl,
                        'amount' => $total_amt,
                        'orderId' => $orderNo,
                        'responseURL' => $responseUrl,
                        'webhookURL' => $webhook
                    );
                }
                else
                {
                    $orderData = array(
                        'secret' => $secret,
                        'business' => $business,
                        'redirectUrl' => $redirectUrl,
                        'amount' => $total_amt,
                        'orderId' => $orderNo,
                        'responseURL' => $responseUrl,
                        'webhookURL' => ''
                    );
                }
                $this
                    ->_coreRegistry
                    ->register('orderdata', $orderData);
                $page = $this
                    ->pageFactory
                    ->create();
                return $page;
            }
            else
            {
                echo 'Order id is not found.';
            }
        }
        else
        {
            echo "Only EUR currency accepted";
        }

    }
}

