<?php

namespace Heystack\Tax;

use Heystack\Core\State\State;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Purchasable\PurchasableHolder\Events as PurchasableHolderEvents;
use Heystack\Tax\Interfaces\TaxHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
    use HasEventServiceTrait;
    use HasStateServiceTrait;

    /**
     * Holds the TaxHandler Service
     * @var \Heystack\Tax\Interfaces\TaxHandlerInterface
     */
    protected $taxService;
    
    protected $currencyChanging;

    /**
     * Creates the ShippingHandler Subscriber object
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventService
     * @param \Heystack\Tax\Interfaces\TaxHandlerInterface $taxService
     * @param \Heystack\Core\State\State $stateService
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        TaxHandlerInterface $taxService,
        State $stateService
    )
    {
        $this->eventService = $eventService;
        $this->taxService = $taxService;
        $this->stateService = $stateService;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // Low priority because its value derives from other totals
            CurrencyEvents::CHANGED                                          => ['onCurrencyChanged', -10],
            LocaleEvents::CHANGED                                            => ['onUpdateTotal', -10],
            PurchasableHolderEvents::PURCHASABLE_ADDED                       => ['onUpdateTotal', -10],
            PurchasableHolderEvents::PURCHASABLE_REMOVED                     => ['onUpdateTotal', -10],
            PurchasableHolderEvents::PURCHASABLE_CHANGED                     => ['onUpdateTotal', -10],
            sprintf('%s.%s', Backend::IDENTIFIER, TransactionEvents::STORED) => ['onTransactionStored', 0]
        ];
    }

    /**
     * Called to update the TaxHandler's total
     */
    public function onUpdateTotal()
    {
        $this->taxService->updateTotal();
    }
    
    public function onCurrencyChanged()
    {
        $this->currencyChanging = true;
        $this->taxService->updateTotal();
        $this->currencyChanging = true;
    }

    /**
     * Called after the TaxHandler's total is updated.
     * Tells the transaction to update its total.
     */
    public function onTotalUpdated()
    {
        if (!$this->currencyChanging) {
            $this->eventService->dispatch(TransactionEvents::UPDATE);
        }
    }
    
    public function onTransactionStored()
    {
        $this->stateService->removeByKey(TaxHandler::IDENTIFIER);
    }
}
