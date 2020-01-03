<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\MerchantProductOfferSearch;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Orm\Zed\ProductPageSearch\Persistence\SpyProductAbstractPageSearchQuery;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Queue\QueueDependencyProvider;
use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\PageDataExpander\ProductMerchantNamePageDataExpanderPlugin;
use Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\PageDataLoader\ProductMerchantNamePageDataLoaderPlugin;
use Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\PageMapExpander\ProductMerchantNameMapExpanderPlugin;
use Spryker\Zed\ProductCategory\Business\ProductCategoryFacadeInterface;
use Spryker\Zed\ProductPageSearch\Dependency\Facade\ProductPageSearchToSearchBridge;
use Spryker\Zed\ProductPageSearch\ProductPageSearchDependencyProvider;
use Spryker\Zed\ProductSearch\Business\ProductSearchFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class MerchantProductOfferSearchCommunicationTester extends Actor
{
    use _generated\MerchantProductOfferSearchCommunicationTesterActions;

    /**
     * @return void
     */
    public function addDependencies(): void
    {
        $this->addRabbitMqDependency();
        $this->addProductPageSearchDependencies();
        $this->mockSearchFacade();
    }

    /**
     * @return \Orm\Zed\ProductPageSearch\Persistence\SpyProductAbstractPageSearchQuery
     */
    public function getProductAbstractPageSearchPropelQuery(): SpyProductAbstractPageSearchQuery
    {
        return SpyProductAbstractPageSearchQuery::create();
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    public function addProductRelatedData(ProductConcreteTransfer $productConcreteTransfer): void
    {
        $productAbstractTransfer = $this->getProductFacade()->findProductAbstractById(
            $productConcreteTransfer->getFkProductAbstract()
        );

        $localizedAttributes = $this->generateLocalizedAttributes();

        $this->addLocalizedAttributesToProductAbstract($productAbstractTransfer, $localizedAttributes);
        $this->addStoreRelationToProductAbstracts($productAbstractTransfer);
        $this->addLocalizedAttributesToProductConcrete($productConcreteTransfer, $localizedAttributes);

        $locale = $this->getLocaleFacade()->getCurrentLocale();
        $categoryTransfer = $this->haveLocalizedCategory(['locale' => $locale]);
        $this->getProductSearchFacade()->activateProductSearch($productConcreteTransfer->getIdProductConcrete(), [$locale]);

        $productIdsToAssign = [$productAbstractTransfer->getIdProductAbstract()];

        $this->addProductToCategoryMappings($categoryTransfer->getIdCategory(), $productIdsToAssign);
    }

    /**
     * @param \Generated\Shared\Transfer\MerchantTransfer $merchantTransfer
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    public function assertProductPageAbstractSearch(MerchantTransfer $merchantTransfer, ProductConcreteTransfer $productConcreteTransfer): void
    {
        $productPageSearchEntity = $this->getProductAbstractPageSearchPropelQuery()
            ->orderByIdProductAbstractPageSearch()
            ->findOneByFkProductAbstract($productConcreteTransfer->getFkProductAbstract());

        $this->assertNotNull($productPageSearchEntity);

        $data = $productPageSearchEntity->getStructuredData();
        $decodedData = json_decode($data, true);

        $this->assertContains($merchantTransfer->getName(), $decodedData['merchant_names']);
    }

    /**
     * @return void
     */
    protected function addRabbitMqDependency(): void
    {
        $this->setDependency(QueueDependencyProvider::QUEUE_ADAPTERS, function (Container $container) {
            return [
                $container->getLocator()->rabbitMq()->client()->createQueueAdapter(),
            ];
        });
    }

    /**
     * @return void
     */
    protected function addProductPageSearchDependencies(): void
    {
        $this->setDependency(
            ProductPageSearchDependencyProvider::PLUGINS_PRODUCT_ABSTRACT_MAP_EXPANDER,
            [
                new ProductMerchantNameMapExpanderPlugin(),
            ]
        );

        $this->setDependency(
            ProductPageSearchDependencyProvider::PLUGIN_PRODUCT_PAGE_DATA_LOADER,
            [
                new ProductMerchantNamePageDataLoaderPlugin(),
            ]
        );

        $this->setDependency(
            ProductPageSearchDependencyProvider::PLUGIN_PRODUCT_PAGE_DATA_EXPANDER,
            [
                MerchantProductOfferSearchConfig::PLUGIN_PRODUCT_MERCHANT_DATA => new ProductMerchantNamePageDataExpanderPlugin(),
            ]
        );
    }

    /**
     * @return void
     */
    protected function mockSearchFacade(): void
    {
        $this->setDependency(ProductPageSearchDependencyProvider::FACADE_SEARCH, Stub::make(
            ProductPageSearchToSearchBridge::class,
            [
                'transformPageMapToDocumentByMapperName' => function () {
                    return [];
                },
            ]
        ));
    }

    /**
     * @param \Generated\Shared\Transfer\ProductAbstractTransfer $productAbstractTransfer
     *
     * @return void
     */
    protected function addStoreRelationToProductAbstracts(ProductAbstractTransfer $productAbstractTransfer): void
    {
        $idStores = $this->getIdStores();

        $productAbstractTransfer->setStoreRelation((new StoreRelationTransfer())->setIdStores($idStores));

        $this->getProductFacade()->saveProductAbstract($productAbstractTransfer);
    }

    /**
     * @return array
     */
    protected function getIdStores(): array
    {
        $storeIds = [];

        foreach ($this->getStoreFacade()->getAllStores() as $storeTransfer) {
            $storeIds[] = $storeTransfer->getIdStore();
        }

        return $storeIds;
    }

    /**
     * @param int $idCategory
     * @param array $productIdsToAssign
     *
     * @return void
     */
    protected function addProductToCategoryMappings(int $idCategory, array $productIdsToAssign): void
    {
        $this->getProductCategoryFacade()->createProductCategoryMappings($idCategory, $productIdsToAssign);
    }

    /**
     * @return \Spryker\Zed\ProductCategory\Business\ProductCategoryFacadeInterface
     */
    protected function getProductCategoryFacade(): ProductCategoryFacadeInterface
    {
        return $this->getLocator()->productCategory()->facade();
    }

    /**
     * @return \Spryker\Zed\Store\Business\StoreFacadeInterface
     */
    protected function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getLocator()->store()->facade();
    }

    /**
     * @return \Spryker\Zed\Locale\Business\LocaleFacadeInterface
     */
    protected function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getLocator()->locale()->facade();
    }

    /**
     * @return \Spryker\Zed\ProductSearch\Business\ProductSearchFacadeInterface
     */
    protected function getProductSearchFacade(): ProductSearchFacadeInterface
    {
        return $this->getLocator()->productSearch()->facade();
    }
}
