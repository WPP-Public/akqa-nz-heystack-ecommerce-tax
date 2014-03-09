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

use Heystack\Core\Exception\ConfigurationException;
use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Interfaces\HasStateServiceInterface;
use Heystack\Core\State\State;
use Heystack\Core\State\StateableInterface;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Core\Storage\StorableInterface;
use Heystack\Core\Storage\Traits\ParentReferenceTrait;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Currency\Traits\HasCurrencyServiceTrait;
use Heystack\Ecommerce\Locale\Interfaces\CountryInterface;
use Heystack\Ecommerce\Locale\Interfaces\LocaleServiceInterface;
use Heystack\Ecommerce\Locale\Traits\HasLocaleServiceTrait;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Ecommerce\Transaction\Interfaces\HasTransactionInterface;
use Heystack\Ecommerce\Transaction\Traits\HasTransactionTrait;
use Heystack\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;
use Heystack\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Heystack\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use Heystack\Tax\Interfaces\TaxExemptPurchasableInterface;
use Heystack\Tax\Interfaces\TaxHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tax Handler
 *
 * Calculates the tax total for the transaction
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Tax
 */
class TaxHandler
    implements
        TaxHandlerInterface,
        StateableInterface,
        \Serializable,
        StorableInterface,
        HasStateServiceInterface,
        HasTransactionInterface
{
    use TransactionModifierSerializeTrait;
    use ParentReferenceTrait;
    use HasStateServiceTrait;
    use HasEventServiceTrait;
    use HasTransactionTrait;
    use HasPurchasableHolderTrait;
    use HasLocaleServiceTrait;
    use HasCurrencyServiceTrait;

    /**
     * The identifier used for storage etc
     */
    const IDENTIFIER = 'Tax';
    /**
     * Constants used in the config array passed in through setter injection. Used in the yml config.
     */
    const RATE_CONFIG_KEY = 'Rate';
    /**
     * Key for Inclusive or exclusive type setting
     */
    const TYPE_CONFIG_KEY = 'Type';
    /**
     * Inclusive tax (not chargeable)
     */
    const INCLUSIVE_TAX_TYPE = 'Inclusive';
    /**
     * Exclusive tax (is chargeable)
     */
    const EXCLUSIVE_TAX_TYPE = 'Exclusive';
    /**
     * @var \SebastianBergmann\Money\Money
     */
    protected $total;
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param State $stateService
     * @param EventDispatcherInterface $eventService
     * @param LocaleServiceInterface $localeService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param CurrencyServiceInterface $currencyService
     */
    public function __construct(
        State $stateService,
        EventDispatcherInterface $eventService,
        LocaleServiceInterface $localeService,
        PurchasableHolderInterface $purchasableHolder,
        CurrencyServiceInterface $currencyService
    ) {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->localeService = $localeService;
        $this->purchasableHolder = $purchasableHolder;
        $this->currencyService = $currencyService;
        $this->total = $this->currencyService->getZeroMoney();
    }

    /**
     * Returns a unique identifier
     * @return \Heystack\Core\Identifier\Identifier
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
        return $this->total;
    }

    /**
     * Updates the total tax due
     */
    public function updateTotal()
    {
        $total = $this->currencyService->getZeroMoney();
        
        $countryCode = strtoupper($this->localeService->getActiveCountry()->getCountryCode());

        if (isset($this->config[$countryCode])) {
            
            if (isset($this->config[$countryCode][self::RATE_CONFIG_KEY])) {
                $rate = $this->config[$countryCode][self::RATE_CONFIG_KEY];
            } else {
                $rate = 0;
            }

            $taxable = $this->transaction->getTotalWithExclusions(
                [$this->getIdentifier()->getFull()]
            );
            
            // TODO: Migrate to ratios
            $total = $taxable->subtract($this->getTaxExemptTotal())->multiply($rate / ($rate + 1));
        }

        $this->total = $total;

        $this->saveState();

        $this->eventService->dispatch(Events::TOTAL_UPDATED);
    }

    /**
     * Get tax exemptions from purchasables if they exist
     * @return \SebastianBergmann\Money\Money
     */
    protected function getTaxExemptTotal()
    {
        $total = $this->currencyService->getZeroMoney();

        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {
            if ($purchasable instanceof TaxExemptPurchasableInterface && $purchasable->isTaxExempt()) {
                $total = $total->add($purchasable->getTotal());
            }
        }

        return $total;
    }

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     * @return string
     */
    public function getType()
    {
        $countryCode = strtoupper($this->localeService->getActiveCountry()->getCountryCode());

        if (isset($this->config[$countryCode])) {

            $countryConfig = $this->config[$countryCode];

            if (isset($countryConfig[self::TYPE_CONFIG_KEY])
                && $countryConfig[self::TYPE_CONFIG_KEY] == self::EXCLUSIVE_TAX_TYPE
            ) {

                return TransactionModifierTypes::CHARGEABLE;

            }

        }

        return TransactionModifierTypes::NEUTRAL;
    }

    /**
     * Sets an array of config parameters onto the data array.
     * Checks to see if the configuration array is well formed.
     * @param array $config
     * @return void
     * @throws \Heystack\Core\Exception\ConfigurationException
     */
    public function setConfig(array $config)
    {
        foreach ($config as $key => $value) {

            $key = strtoupper($key);

            $country = $this->localeService->getCountry(new Identifier($key));
            
            if (!$country instanceof CountryInterface) {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' does not match any configured country in the Locale Service'
                );
            }
            
            if (!is_array($value)) {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' should be an array'
                );
            }
            
            if (!isset($value[self::RATE_CONFIG_KEY]) || !isset($value[self::TYPE_CONFIG_KEY])) {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' should have an array with `Rate` & `Type` keys'
                );
            }
            
            if (!in_array($value[self::TYPE_CONFIG_KEY], [self::INCLUSIVE_TAX_TYPE, self::EXCLUSIVE_TAX_TYPE])) {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' should have a `Type` of `Exclusive` or `Inclusive`'
                );
            }

            if (!is_numeric($value[self::RATE_CONFIG_KEY])) {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' should have a numeric `Rate`'
                );
            }

            $this->config[$key] = $value;
        }
    }

    /**
     * Retrieves the configuration array
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getStorableData()
    {
        $total = $this->getTotal();
        return [
            'id' => self::IDENTIFIER,
            'parent' => true,
            'flat' => [
                'Total' => $total->getAmount() / $total->getCurrency()->getSubUnit()
            ]
        ];
    }

    /**
     * @return string
     */
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

    /**
     * @return array
     */
    public function getStorableBackendIdentifiers()
    {
        return [
            Backend::IDENTIFIER
        ];
    }
    
    /**
     * Saves the data array on the State service
     */
    public function saveState()
    {
        $this->stateService->setByKey(self::IDENTIFIER, $this->getData());
    }
    /**
     * Uses the State service to restore the data array
     */
    public function restoreState()
    {
        if ($data = $this->stateService->getByKey(self::IDENTIFIER)) {
            $this->setData($data);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [$this->total, $this->config];
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        if (is_array($data)) {
            list($this->total, $this->config) = $data;
        }
    }
}
