<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ProductAlert\Controller\Unsubscribe;

use Magento\ProductAlert\Controller\Unsubscribe as UnsubscribeController;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Price extends UnsubscribeController
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
        parent::__construct($context, $customerSession);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$productId) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        try {
            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->productRepository->getById($productId);
            if (!$product->isVisibleInCatalog()) {
                throw new NoSuchEntityException();
            }
            /** @var \Magento\ProductAlert\Model\Price $model */
            $model = $this->_objectManager->create('Magento\ProductAlert\Model\Price')
                ->setCustomerId($this->customerSession->getCustomerId())
                ->setProductId($product->getId())
                ->setWebsiteId(
                    $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
                        ->getStore()
                        ->getWebsiteId()
                )
                ->loadByParam();
            if ($model->getId()) {
                $model->delete();
            }

            $this->messageManager->addSuccess(__('You deleted the alert subscription.'));
        } catch (NoSuchEntityException $noEntityException) {
            $this->messageManager->addError(__('We can\'t find the product.'));
            $resultRedirect->setPath('customer/account/');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Unable to update the alert subscription.'));
        }
        $resultRedirect->setUrl($product->getProductUrl());
        return $resultRedirect;
    }
}
