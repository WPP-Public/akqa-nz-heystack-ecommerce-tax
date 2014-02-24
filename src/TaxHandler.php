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

use Heystack\Subsystem\Core\Exception\ConfigurationException;
use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\Interfaces\HasDataInterface;
use Heystack\Subsystem\Core\Interfaces\HasStateServiceInterface;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Core\State\StateableInterface;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\StorableInterface;
use Heystack\Subsystem\Core\Storage\Traits\ParentReferenceTrait;
use Heystack\Subsystem\Ecommerce\Locale\Interfaces\CountryInterface;
use Heystack\Subsystem\Ecommerce\Locale\Interfaces\LocaleServiceInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Transaction\Interfaces\TransactionInterface;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Heystack\Subsystem\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface;
use Heystack\Subsystem\Tax\Traits\TaxConfigTrait;
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
class TaxHandler implements TaxHandlerInterface, StateableInterface, \Serializable, StorableInterface, HasStateServiceInterface, HasDataInterface
{
    use TransactionModifierStateTrait;
    use TransactionModifierSerializeTrait;
    use ParentReferenceTrait;

    const IDENTIFIER = 'taxhandler';
    const TOTAL_KEY = 'total';
    const CONFIG_KEY = 'config';

    /**
     * Constants used in the config array passed in through setter injection. Used in the yml config.
     */
    const RATE_CONFIG_KEY = 'Rate';
    const TYPE_CONFIG_KEY = 'Type';
    const INCLUSIVE_TAX_TYPE = 'Inclusive';
    const EXCLUSIVE_TAX_TYPE = 'Exclusive';

    protected $stateService;
    protected $eventService;
    protected $localeService;
    protected $transaction;
    protected $purchasableHolder;

    public function __construct(
        State $stateService,
        EventDispatcherInterface $eventService,
        LocaleServiceInterface $localeService,
        TransactionInterface $transaction,
        PurchasableHolderInterface $purchasableHolder
    ) {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->localeService = $localeService;
        $this->transaction = $transaction;
        $this->purchasableHolder = $purchasableHolder;
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

    /**
     * Updates the total tax due
     */
    public function updateTotal()
    {
        $total = 0;
        $countryCode = strtoupper($this->localeService->getActiveCountry()->getCountryCode());

        if (isset($this->data[self::CONFIG_KEY][$countryCode])) {

            $rate = isset($this->data[self::CONFIG_KEY][$countryCode][self::RATE_CONFIG_KEY])
                ? $this->data[self::CONFIG_KEY][$countryCode][self::RATE_CONFIG_KEY]
                : 0;

            $taxable = $this->transaction->getTotalWithExclusions(
                [$this->getIdentifier()->getFull()]
            ) - $this->purchasableHolder->getTaxExemptTotal();

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
     *
     * @param array $config
     * @throws ConfigurationException
     */
    public function setConfig(array $config)
    {
        foreach ($config as $key => $value) {

            $key = strtoupper($key);

            $country = $this->localeService->getCountry(new Identifier($key));

            if ($country instanceof CountryInterface) {

                if (is_array($value) && isset($value[self::RATE_CONFIG_KEY]) && isset($value[self::TYPE_CONFIG_KEY])) {

                    if (in_array(
                        $value[self::TYPE_CONFIG_KEY],
                        [self::INCLUSIVE_TAX_TYPE, self::EXCLUSIVE_TAX_TYPE]
                    )
                    ) {

                        if (is_numeric($value[self::RATE_CONFIG_KEY])) {

                            $this->data[self::CONFIG_KEY][$key] = $value;

                        } else {
                            throw new ConfigurationException(
                                'Tax configuration for ' . $key . ' should have a numeric `Rate`'
                            );
                        }

                    } else {
                        throw new ConfigurationException(
                            'Tax configuration for ' . $key . ' should have a `Type` of `Exclusive` or `Inclusive`'
                        );
                    }

                } else {
                    throw new ConfigurationException(
                        'Tax configuration for ' . $key . ' should have an array with `Rate` & `Type` keys'
                    );
                }

            } else {
                throw new ConfigurationException(
                    'Tax configuration for ' . $key . ' does not match any configured country in the Locale Service'
                );
            }

        }
    }

    /**
     * Retrieves the configuration array
     * @return array
     */
    public function getConfig()
    {
        return isset($this->data[self::CONFIG_KEY]) ? $this->data[self::CONFIG_KEY] : null;
    }

    public function getStorableData()
    {

        return [
            'id' => 'Tax',
            'parent' => true,
            'flat' => [
                'Total' => $this->getTotal()
            ]
        ];

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
        return [
            Backend::IDENTIFIER
        ];
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param State $stateService
     * @return mixed|void
     */
    public function setStateService(State $stateService)
    {
        $this->stateService = $stateService;
    }

    public function getStateService()
    {
        return $this->stateService;
    }
}
