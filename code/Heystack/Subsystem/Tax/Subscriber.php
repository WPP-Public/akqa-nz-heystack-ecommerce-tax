<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Tax namespace
 */
namespace Heystack\Subsystem\Tax;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Heystack\Subsystem\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Subsystem\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Subsystem\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Subsystem\Products\ProductHolder\Events as ProductHolderEvents;
use Heystack\Subsystem\Vouchers\Events as VoucherEvents;

use Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface;

use Heystack\Subsystem\Core\Storage\Storage;
use Heystack\Subsystem\Core\Storage\Event as StorageEvent;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;

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
     * @var \Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface
     */
    protected $taxService;

    /**
     * Holds the Storage Service
     * @var \Heystack\Subsystem\Core\Storage\Storage
     */
    protected $storageService;

    /**
     * Creates the ShippingHandler Subscriber object
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface      $eventService
     * @param \Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface           $taxService
     * @param \Heystack\Subsystem\Core\Storage\Storage                         $storageService
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
        return array(
            CurrencyEvents::CHANGED        => array('onUpdateTotal', 0),
            LocaleEvents::CHANGED          => array('onUpdateTotal', 0),
            ProductHolderEvents::UPDATED   => array('onUpdateTotal', 0),
            VoucherEvents::TOTAL_UPDATED   => array('onUpdateTotal', 0),
            Events::TOTAL_UPDATED          => array('onTotalUpdated', 0),
            Backend::IDENTIFIER . '.' . TransactionEvents::STORED      => array('onTransactionStored', 0)
        );
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
     * @param \Heystack\Subsystem\Core\Storage\Event $event
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
