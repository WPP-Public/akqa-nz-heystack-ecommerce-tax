<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Tax namespace
 */
namespace Heystack\Tax;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Heystack\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Products\ProductHolder\Events as ProductHolderEvents;
use Heystack\Vouchers\Events as VoucherEvents;

use Heystack\Tax\Interfaces\TaxHandlerInterface;

use Heystack\Core\Storage\Storage;
use Heystack\Core\Storage\Event as StorageEvent;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;

/**
 * Handles both subscribing to events and acting on those events needed for TaxHandler to work properly
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Tax
 * @see Symfony\Component\EventDispatcher
 */
class Subscriber implements EventSubscriberInterface
{
    /**
     * Holds the Event Dispatcher Service
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;

    /**
     * Holds the TaxHandler Service
     * @var \Heystack\Tax\Interfaces\TaxHandlerInterface
     */
    protected $taxService;

    /**
     * Holds the Storage Service
     * @var \Heystack\Core\Storage\Storage
     */
    protected $storageService;

    /**
     * Creates the ShippingHandler Subscriber object
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventService
     * @param \Heystack\Tax\Interfaces\TaxHandlerInterface      $taxService
     * @param \Heystack\Core\Storage\Storage                    $storageService
     */
    public function __construct(EventDispatcherInterface $eventService, TaxHandlerInterface $taxService,  Storage $storageService)
    {
        $this->eventService = $eventService;
        $this->taxService = $taxService;
        $this->storageService = $storageService;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CurrencyEvents::CHANGED        => ['onUpdateTotal', 0],
            LocaleEvents::CHANGED          => ['onUpdateTotal', 0],
            ProductHolderEvents::UPDATED   => ['onUpdateTotal', 0],
            VoucherEvents::TOTAL_UPDATED   => ['onUpdateTotal', 0],
            Events::TOTAL_UPDATED          => ['onTotalUpdated', 0],
            Backend::IDENTIFIER . '.' . TransactionEvents::STORED      => ['onTransactionStored', 0]
        ];
    }

    /**
     * Called to update the TaxHandler's total
     */
    public function onUpdateTotal()
    {
        $this->taxService->updateTotal();
    }

    /**
     * Called after the TaxHandler's total is updated.
     * Tells the transaction to update its total.
     */
    public function onTotalUpdated()
    {
        $this->eventService->dispatch(TransactionEvents::UPDATE);
    }

    /**
     * Called after the Transaction is stored.
     * Tells the storage service to store all the information held in the TaxHandler
     * @param \Heystack\Core\Storage\Event $event
     */
    public function onTransactionStored(StorageEvent $event)
    {

//        $this->shippingService->setParentReference($event->getParentReference());
//
//        $this->storageService->process($this->shippingService);
//
//        $this->eventService->dispatch(Events::STORED);
    }

}
