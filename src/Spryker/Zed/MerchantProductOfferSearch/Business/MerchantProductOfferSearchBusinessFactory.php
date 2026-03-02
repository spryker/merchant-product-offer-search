<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\MerchantProductOfferSearch\Business\Event\ProductEventTrigger;
use Spryker\Zed\MerchantProductOfferSearch\Business\Event\ProductEventTriggerInterface;
use Spryker\Zed\MerchantProductOfferSearch\Business\Expander\MerchantProductOfferSearchExpander;
use Spryker\Zed\MerchantProductOfferSearch\Business\Expander\MerchantProductOfferSearchExpanderInterface;
use Spryker\Zed\MerchantProductOfferSearch\Business\Reader\MerchantProductOfferSearchReader;
use Spryker\Zed\MerchantProductOfferSearch\Business\Reader\MerchantProductOfferSearchReaderInterface;
use Spryker\Zed\MerchantProductOfferSearch\Business\Writer\MerchantProductOfferSearchWriter;
use Spryker\Zed\MerchantProductOfferSearch\Business\Writer\MerchantProductOfferSearchWriterInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventBehaviorFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToMerchantProductOfferFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToProductPageSearchFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToStoreFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchDependencyProvider;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepositoryInterface getRepository()
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 */
class MerchantProductOfferSearchBusinessFactory extends AbstractBusinessFactory
{
    public function createMerchantProductOfferSearchWriter(): MerchantProductOfferSearchWriterInterface
    {
        return new MerchantProductOfferSearchWriter(
            $this->getEventBehaviorFacade(),
            $this->getProductPageSearchFacade(),
            $this->getRepository(),
        );
    }

    public function createMerchantProductOfferSearchExpander(): MerchantProductOfferSearchExpanderInterface
    {
        return new MerchantProductOfferSearchExpander(
            $this->getMerchantProductOfferFacade(),
            $this->getStoreFacade(),
        );
    }

    public function createProductEventTrigger(): ProductEventTriggerInterface
    {
        return new ProductEventTrigger(
            $this->getEventBehaviorFacade(),
            $this->getRepository(),
            $this->getEventFacade(),
        );
    }

    public function createMerchantProductOfferSearchReader(): MerchantProductOfferSearchReaderInterface
    {
        return new MerchantProductOfferSearchReader($this->getRepository());
    }

    public function getEventBehaviorFacade(): MerchantProductOfferSearchToEventBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferSearchDependencyProvider::FACADE_EVENT_BEHAVIOR);
    }

    public function getEventFacade(): MerchantProductOfferSearchToEventFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferSearchDependencyProvider::FACADE_EVENT);
    }

    public function getProductPageSearchFacade(): MerchantProductOfferSearchToProductPageSearchFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferSearchDependencyProvider::FACADE_PRODUCT_PAGE_SEARCH);
    }

    public function getMerchantProductOfferFacade(): MerchantProductOfferSearchToMerchantProductOfferFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferSearchDependencyProvider::FACADE_MERCHANT_PRODUCT_OFFER);
    }

    public function getStoreFacade(): MerchantProductOfferSearchToStoreFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferSearchDependencyProvider::FACADE_STORE);
    }
}
