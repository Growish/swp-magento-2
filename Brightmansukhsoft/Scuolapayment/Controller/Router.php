<?php namespace Brightmansukhsoft\Scuolapayment\Controller;

class Router implements \Magento\Framework\App\RouterInterface
    {

     /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * Config primary
     *
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * Url
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;


  public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $url,        
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->_eventManager = $eventManager;
        $this->_url = $url;        
        $this->_storeManager = $storeManager;
        $this->_response = $response;
    }

     public function match(\Magento\Framework\App\RequestInterface $request)
       {
        $identifier = trim($request->getPathInfo(), '/');
        $condition = new \Magento\Framework\DataObject(['identifier' => $identifier, 'continue' => true]);
         $identifier = $condition->getIdentifier();
		 $uri_segments = explode('/', $identifier);
		 $uri_segmentsCount=count($uri_segments);
	
        if ($condition->getRedirectUrl()) {
            $this->_response->setRedirect($condition->getRedirectUrl());
            $request->setDispatched(true);
            return $this->actionFactory->create('Magento\Framework\App\Action\Redirect');
        }

        if (!$condition->getContinue()) {
            return null;
        }

        // check your custom condition here if its satisfy they go ahed othrwise set return null
        $satisfy=true;
        if (!$satisfy) {
            return null;
        }
		if (($uri_segmentsCount=='2') &&  (strpos($identifier, 'redirect')!==false)) {
			$uri_segments = explode('/', $identifier);
            $param=$uri_segments[1];
			$request->setModuleName('scuolapay')-> setControllerName('redirect')->setActionName('scuolapayredirect')->setParam('identifier',$param);
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);
		} else if (($uri_segmentsCount=='2') &&  (strpos($identifier, 'response')!==false)) {
			$uri_segments = explode('/', $identifier);
            $param=$uri_segments[1];
			$request->setModuleName('scuolapay')-> setControllerName('scuolaresponse')->setActionName('response')->setParam('identifier',$param);
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);
		} else {
			return false;
		}


        return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        }
    }