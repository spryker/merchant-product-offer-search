<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\MerchantProductOfferSearch\Communication\Plugin\Publisher;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\EventQueueSendMessageBodyTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig as SharedMerchantProductOfferSearchConfig;
use Spryker\Zed\Merchant\Dependency\MerchantEvents;
use Spryker\Zed\Merchant\MerchantConfig;
use Spryker\Zed\MerchantProductOfferSearch\Business\Event\ProductEventTrigger;
use Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchBusinessFactory;
use Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\Publisher\Product\MerchantSearchPublisherPlugin;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToProductPageSearchFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchDependencyProvider;
use Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepository;
use SprykerTest\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group MerchantProductOfferSearch
 * @group Communication
 * @group Plugin
 * @group Publisher
 * @group MerchantSearchPublisherPluginTest
 * Add your own group annotations below this line
 */
class MerchantSearchPublisherPluginTest extends Unit
{
    protected MerchantProductOfferSearchCommunicationTester $tester;

    /**
     * @var array<string, list<\Generated\Shared\Transfer\EventEntityTransfer>>
     */
    protected array $triggeredEventEntityTransfers = [];

    /**
     * @var list<int>
     */
    protected array $publishedProductConcreteIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->triggeredEventEntityTransfers = [];
        $eventFacadeMock = $this->createMock(MerchantProductOfferSearchToEventFacadeInterface::class);
        $eventFacadeMock->method('triggerBulk')->willReturnCallback(function (string $eventName, array $eventEntityTransfers): void {
            $this->triggeredEventEntityTransfers[$eventName] = array_merge(
                $this->triggeredEventEntityTransfers[$eventName] ?? [],
                $eventEntityTransfers,
            );
        });
        $this->tester->setDependency(
            MerchantProductOfferSearchDependencyProvider::FACADE_EVENT,
            $eventFacadeMock,
            MerchantProductOfferSearchBusinessFactory::class,
        );

        $this->publishedProductConcreteIds = [];
        $productPageSearchFacadeMock = $this->createMock(MerchantProductOfferSearchToProductPageSearchFacadeInterface::class);
        $productPageSearchFacadeMock->method('publishProductConcretes')->willReturnCallback(function (array $productConcreteIds): void {
            $this->publishedProductConcreteIds = array_merge($this->publishedProductConcreteIds, $productConcreteIds);
        });
        $this->tester->setDependency(
            MerchantProductOfferSearchDependencyProvider::FACADE_PRODUCT_PAGE_SEARCH,
            $productPageSearchFacadeMock,
            MerchantProductOfferSearchBusinessFactory::class,
        );
    }

    /**
     * @dataProvider getRangeSizeAndReadBatchSizeDataProvider
     */
    public function testGivenMerchantEventWithoutModifiedColumnsWhenRangeEventsArePublishedThenEveryProductOfTheActiveOffersIsRepublished(
        int $rangeSize,
        int $readBatchSize
    ): void {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $otherMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $firstProductConcreteTransfer = $this->tester->haveProduct();
        $productConcreteTransfers = [
            $firstProductConcreteTransfer,
            $this->tester->haveProductConcrete([
                ProductConcreteTransfer::FK_PRODUCT_ABSTRACT => $firstProductConcreteTransfer->getFkProductAbstractOrFail(),
            ]),
            $this->tester->haveProduct(),
            $this->tester->haveProduct(),
        ];
        $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[0]);
        $this->haveProductOffer($otherMerchantTransfer, $this->tester->haveProduct());
        $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[1]);
        $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[2]);
        $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[3]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct(), false);
        $eventEntityTransfers = [(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())];

        // Act
        $this->createProductEventTrigger($rangeSize, $readBatchSize)->triggerMerchantProducts($eventEntityTransfers);
        $rangeEventEntityTransfers = $this->triggeredEventEntityTransfers[SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH] ?? [];
        $this->triggeredEventEntityTransfers = [];
        (new MerchantSearchPublisherPlugin())->handleBulk($rangeEventEntityTransfers, SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH);

        // Assert
        $this->assertSame($this->getProductConcreteIds($productConcreteTransfers), $this->getSortedUniqueIds($this->publishedProductConcreteIds));
        $this->assertSame($this->getProductAbstractIds($productConcreteTransfers), $this->getTriggeredProductAbstractIds());
    }

    /**
     * @return array<string, array<int>>
     */
    public function getRangeSizeAndReadBatchSizeDataProvider(): array
    {
        return [
            'range size 2, read batch size 3' => [2, 3],
            'range size 2, read batch size 1' => [2, 1],
            'range size 0, read batch size 0' => [0, 0],
            'default range size and read batch size' => [
                (new MerchantProductOfferSearchConfig())->getMerchantProductOfferRangeSize(),
                (new MerchantProductOfferSearchConfig())->getMerchantProductOfferRangeReadBatchSize(),
            ],
        ];
    }

    public function testGivenRangeEventsOfOneMerchantInOneQueueChunkWhenEventQueueConsumerProcessesThemThenEveryRangeIsRepublished(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $productConcreteTransfers = [$this->tester->haveProduct(), $this->tester->haveProduct()];
        foreach ($productConcreteTransfers as $productConcreteTransfer) {
            $this->haveProductOffer($merchantTransfer, $productConcreteTransfer);
        }
        $this->createProductEventTrigger(1, 1)->triggerMerchantProducts([(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())]);
        $queueReceiveMessageTransfers = $this->createQueueReceiveMessageTransfers(
            $this->triggeredEventEntityTransfers[SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH],
        );
        $this->triggeredEventEntityTransfers = [];

        // Act
        $this->tester->getLocator()->event()->facade()->processEnqueuedMessages($queueReceiveMessageTransfers);

        // Assert
        $this->assertSame($this->getProductConcreteIds($productConcreteTransfers), $this->getSortedUniqueIds($this->publishedProductConcreteIds));
        $this->assertSame($this->getProductAbstractIds($productConcreteTransfers), $this->getTriggeredProductAbstractIds());
    }

    public function testGivenRangeEventWhenHandlingThenOnlyProductsOfTheMerchantOffersInsideTheRangeAreRepublished(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $otherMerchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $productConcreteTransfers = [$this->tester->haveProduct(), $this->tester->haveProduct()];
        $firstProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[0]);
        $this->haveProductOffer($otherMerchantTransfer, $this->tester->haveProduct());
        $secondProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[1]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct());
        $rangeEventEntityTransfers = [
            (new EventEntityTransfer())->setAdditionalValues([
                SharedMerchantProductOfferSearchConfig::RANGE_MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
                SharedMerchantProductOfferSearchConfig::RANGE_FROM_ID_PRODUCT_OFFER => $firstProductOfferTransfer->getIdProductOfferOrFail(),
                SharedMerchantProductOfferSearchConfig::RANGE_TO_ID_PRODUCT_OFFER => $secondProductOfferTransfer->getIdProductOfferOrFail(),
            ]),
        ];

        // Act
        (new MerchantSearchPublisherPlugin())->handleBulk($rangeEventEntityTransfers, SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH);

        // Assert
        $this->assertSame($this->getProductConcreteIds($productConcreteTransfers), $this->getSortedUniqueIds($this->publishedProductConcreteIds));
        $this->assertSame([SharedMerchantProductOfferSearchConfig::PRODUCT_ABSTRACT_SEARCH_PUBLISH], array_keys($this->triggeredEventEntityTransfers));
        $this->assertSame($this->getProductAbstractIds($productConcreteTransfers), $this->getTriggeredProductAbstractIds());
    }

    public function testGivenTwoRangeEventsOfOneMerchantWhenHandlingThenTheProductOfTheOfferBetweenTheRangesIsNotRepublished(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $productConcreteTransfers = [$this->tester->haveProduct(), $this->tester->haveProduct()];
        $firstProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[0]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct());
        $secondProductOfferTransfer = $this->haveProductOffer($merchantTransfer, $productConcreteTransfers[1]);
        $rangeEventEntityTransfers = [
            $this->createRangeEventEntityTransfer($merchantTransfer, $firstProductOfferTransfer, $firstProductOfferTransfer),
            $this->createRangeEventEntityTransfer($merchantTransfer, $secondProductOfferTransfer, $secondProductOfferTransfer),
        ];

        // Act
        (new MerchantSearchPublisherPlugin())->handleBulk($rangeEventEntityTransfers, SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH);

        // Assert
        $this->assertSame($this->getProductConcreteIds($productConcreteTransfers), $this->getSortedUniqueIds($this->publishedProductConcreteIds));
        $this->assertSame($this->getProductAbstractIds($productConcreteTransfers), $this->getTriggeredProductAbstractIds());
    }

    public function testGivenMerchantWithOnlyInactiveOffersWhenHandlingMerchantEventThenNoRangeEventIsTriggered(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct(), false);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct(), false);

        // Act
        (new MerchantSearchPublisherPlugin())->handleBulk(
            [(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())],
            MerchantEvents::MERCHANT_PUBLISH,
        );

        // Assert
        $this->assertSame([], $this->triggeredEventEntityTransfers);
    }

    /**
     * @dataProvider getProductAbstractSearchRelevantMerchantColumnsDataProvider
     */
    public function testGivenMerchantEventWithRelevantModifiedColumnWhenHandlingThenRangeEventsAreTriggered(string $modifiedColumn): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct());

        // Act
        (new MerchantSearchPublisherPlugin())->handleBulk(
            [(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())->setModifiedColumns([$modifiedColumn])],
            MerchantEvents::MERCHANT_PUBLISH,
        );

        // Assert
        $this->assertSame([SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH], array_keys($this->triggeredEventEntityTransfers));
        foreach ($this->triggeredEventEntityTransfers[SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH] as $rangeEventEntityTransfer) {
            $this->assertSame(
                $merchantTransfer->getMerchantReferenceOrFail(),
                $rangeEventEntityTransfer->getAdditionalValues()[SharedMerchantProductOfferSearchConfig::RANGE_MERCHANT_REFERENCE],
            );
        }
    }

    /**
     * @return array<string, array<string>>
     */
    public function getProductAbstractSearchRelevantMerchantColumnsDataProvider(): array
    {
        $dataSets = [];
        foreach ((new MerchantProductOfferSearchConfig())->getProductAbstractSearchRelevantMerchantColumns() as $modifiedColumn) {
            $dataSets[$modifiedColumn] = [$modifiedColumn];
        }

        return $dataSets;
    }

    /**
     * @dataProvider getIrrelevantMerchantModifiedColumnsDataProvider
     *
     * @param list<string> $modifiedColumns
     */
    public function testGivenMerchantEventWithIrrelevantModifiedColumnsWhenHandlingThenNothingIsRepublished(array $modifiedColumns): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);
        $this->haveProductOffer($merchantTransfer, $this->tester->haveProduct());

        // Act
        (new MerchantSearchPublisherPlugin())->handleBulk(
            [(new EventEntityTransfer())->setId($merchantTransfer->getIdMerchantOrFail())->setModifiedColumns($modifiedColumns)],
            MerchantEvents::ENTITY_SPY_MERCHANT_UPDATE,
        );

        // Assert
        $this->assertSame([], $this->triggeredEventEntityTransfers);
        $this->assertSame([], $this->publishedProductConcreteIds);
    }

    /**
     * @return array<string, array<list<string>>>
     */
    public function getIrrelevantMerchantModifiedColumnsDataProvider(): array
    {
        return [
            'merchant related data marker' => [[MerchantConfig::MODIFIED_COLUMN_MERCHANT_RELATED_DATA]],
            'merchant email' => [[SpyMerchantTableMap::COL_EMAIL]],
        ];
    }

    protected function createProductEventTrigger(int $rangeSize, int $readBatchSize): ProductEventTrigger
    {
        $merchantProductOfferSearchBusinessFactory = new MerchantProductOfferSearchBusinessFactory();

        return new ProductEventTrigger(
            $merchantProductOfferSearchBusinessFactory->getEventBehaviorFacade(),
            new MerchantProductOfferSearchRepository(),
            $merchantProductOfferSearchBusinessFactory->getEventFacade(),
            $this->createMerchantProductOfferSearchConfig($rangeSize, $readBatchSize),
        );
    }

    protected function createMerchantProductOfferSearchConfig(int $rangeSize, int $readBatchSize): MerchantProductOfferSearchConfig
    {
        return new class ($rangeSize, $readBatchSize) extends MerchantProductOfferSearchConfig {
            public function __construct(protected int $rangeSize, protected int $readBatchSize)
            {
            }

            public function getMerchantProductOfferRangeSize(): int
            {
                return $this->rangeSize;
            }

            public function getMerchantProductOfferRangeReadBatchSize(): int
            {
                return $this->readBatchSize;
            }
        };
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     *
     * @return list<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function createQueueReceiveMessageTransfers(array $rangeEventEntityTransfers): array
    {
        $queueReceiveMessageTransfers = [];
        foreach ($rangeEventEntityTransfers as $rangeEventEntityTransfer) {
            $messageBody = [
                EventQueueSendMessageBodyTransfer::LISTENER_CLASS_NAME => MerchantSearchPublisherPlugin::class,
                EventQueueSendMessageBodyTransfer::TRANSFER_CLASS_NAME => EventEntityTransfer::class,
                EventQueueSendMessageBodyTransfer::TRANSFER_DATA => $rangeEventEntityTransfer->toArray(),
                EventQueueSendMessageBodyTransfer::EVENT_NAME => SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH,
            ];

            $queueReceiveMessageTransfers[] = (new QueueReceiveMessageTransfer())
                ->setQueueMessage((new QueueSendMessageTransfer())->setBody((string)json_encode($messageBody)));
        }

        return $queueReceiveMessageTransfers;
    }

    protected function createRangeEventEntityTransfer(
        MerchantTransfer $merchantTransfer,
        ProductOfferTransfer $fromProductOfferTransfer,
        ProductOfferTransfer $toProductOfferTransfer
    ): EventEntityTransfer {
        return (new EventEntityTransfer())->setAdditionalValues([
            SharedMerchantProductOfferSearchConfig::RANGE_MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
            SharedMerchantProductOfferSearchConfig::RANGE_FROM_ID_PRODUCT_OFFER => $fromProductOfferTransfer->getIdProductOfferOrFail(),
            SharedMerchantProductOfferSearchConfig::RANGE_TO_ID_PRODUCT_OFFER => $toProductOfferTransfer->getIdProductOfferOrFail(),
        ]);
    }

    protected function haveProductOffer(
        MerchantTransfer $merchantTransfer,
        ProductConcreteTransfer $productConcreteTransfer,
        bool $isActive = true
    ): ProductOfferTransfer {
        return $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
            ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSkuOrFail(),
            ProductOfferTransfer::IS_ACTIVE => $isActive,
        ]);
    }

    /**
     * @return list<int>
     */
    protected function getTriggeredProductAbstractIds(): array
    {
        $productAbstractIds = [];
        foreach ($this->triggeredEventEntityTransfers[SharedMerchantProductOfferSearchConfig::PRODUCT_ABSTRACT_SEARCH_PUBLISH] ?? [] as $eventEntityTransfer) {
            $productAbstractIds[] = $eventEntityTransfer->getIdOrFail();
        }

        return $this->getSortedUniqueIds($productAbstractIds);
    }

    /**
     * @param list<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return list<int>
     */
    protected function getProductConcreteIds(array $productConcreteTransfers): array
    {
        return $this->getSortedUniqueIds(array_map(
            fn (ProductConcreteTransfer $productConcreteTransfer): int => $productConcreteTransfer->getIdProductConcreteOrFail(),
            $productConcreteTransfers,
        ));
    }

    /**
     * @param list<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return list<int>
     */
    protected function getProductAbstractIds(array $productConcreteTransfers): array
    {
        return $this->getSortedUniqueIds(array_map(
            fn (ProductConcreteTransfer $productConcreteTransfer): int => $productConcreteTransfer->getFkProductAbstractOrFail(),
            $productConcreteTransfers,
        ));
    }

    /**
     * @param array<int> $ids
     *
     * @return list<int>
     */
    protected function getSortedUniqueIds(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
