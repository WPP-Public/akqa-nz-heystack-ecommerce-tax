<?php

namespace Heystack\Subsystem\Tax;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Core\State\StateableInterface;
use Heystack\Subsystem\Core\ServiceStore;

use Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface;
use Heystack\Subsystem\Tax\Traits\TaxConfigTrait;

use Heystack\Subsystem\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;
use Heystack\Subsystem\Ecommerce\Locale\Interfaces\LocaleServiceInterface;
use Heystack\Subsystem\Ecommerce\Services as EcommerceServices;
use Heystack\Subsystem\Products\Services as ProductServices;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Heystack\Subsystem\Core\Storage\StorableInterface;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\Traits\ParentReferenceTrait;

class TaxHandler implements TaxHandlerInterface, StateableInterface, \Serializable, StorableInterface
{
    use TransactionModifierStateTrait;
    use TransactionModifierSerializeTrait;
    use TaxConfigTrait;
    use ParentReferenceTrait;

    const IDENTIFIER = 'taxhandler';
    const TOTAL_KEY = 'total';
    const CONFIG_KEY = 'config';

    protected $data = array();

    protected $stateService;
    protected $eventService;
    protected $localeService;

    public function __construct(State $stateService, EventDispatcherInterface $eventService, LocaleServiceInterface $localeService)
    {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->localeService = $localeService;
    }

    /**
     * Returns a unique identifier
     * @return \Heystack\Subsystem\Core\Identifier\Identifier
     */
    public function getIdentifier()
    {
        return new Identifier(self::IDENTIFIER);
    }

    /**
     * Returns the total value of the TransactionModifier for use in the Transaction
     */
    public function getTotal()
    {
        return isset($this->data[self::TOTAL_KEY]) ? $this->data[self::TOTAL_KEY] : 0;
    }

    public function updateTotal()
    {
        $transaction = ServiceStore::getService(EcommerceServices::TRANSACTION);
        $total = 0;
        $countryCode = strtoupper($this->localeService->getActiveCountry()->getCountryCode());

        if (isset($this->data[self::CONFIG_KEY][$countryCode])) {

            $rate = isset($this->data[self::CONFIG_KEY][$countryCode]['Rate']) ? $this->data[self::CONFIG_KEY][$countryCode]['Rate'] : 0;

            $productHolder = ServiceStore::getService(ProductServices::PRODUCTHOLDER);

            $taxable = $transaction->getTotalWithExclusions(array($this->getIdentifier()->getFull())) - $productHolder->getTaxExemptTotal();

            $total = ($taxable / ($rate + 1)) * $rate;

        }

        $this->data[self::TOTAL_KEY] = $total;

        $this->saveState();

        $this->eventService->dispatch(Events::TOTAL_UPDATED);
    }

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     */
    public function getType()
    {
        $countryCode = strtoupper($this->localeService->getActiveCountry()->getCountryCode());

        if (isset($this->data[self::CONFIG_KEY][$countryCode])) {

            $countryConfig = $this->data[self::CONFIG_KEY][$countryCode];

            if (isset($countryConfig['Type']) && $countryConfig['Type'] == self::$exclusiveTaxType) {

                return TransactionModifierTypes::CHARGEABLE;

            }

        }

        return TransactionModifierTypes::NEUTRAL;
    }

    public function getStorableData()
    {

       return array(
           'id' => 'Tax',
           'parent' => true,
           'flat' => array(
               'Total' => $this->getTotal()
           )
       );

    }

    public function getStorableIdentifier()
    {

        return self::IDENTIFIER;

    }

    /**
     * Get the name of the schema this system relates to
     * @return string
     */
    public function getSchemaName()
    {

        return 'Tax';

    }

    public function getStorableBackendIdentifiers()
    {
        return array(
            Backend::IDENTIFIER
        );
    }
}
