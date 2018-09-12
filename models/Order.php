<?php namespace Lovata\OrdersShopaholic\Models;

use Model;
use October\Rain\Argon\Argon;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Encryptable;

use Kharanenka\Scope\UserBelongsTo;

use Lovata\Toolbox\Classes\Helper\UserHelper;
use Lovata\Toolbox\Traits\Helpers\TraitCached;
use Lovata\Toolbox\Traits\Helpers\PriceHelperTrait;
use Lovata\Toolbox\Traits\Models\SetPropertyAttributeTrait;

use Lovata\OrdersShopaholic\Classes\PromoMechanism\OrderPromoMechanismProcessor;

/**
 * Class Order
 * @package Lovata\OrdersShopaholic\Models
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property int                                                                         $id
 * @property string                                                                      $order_number
 * @property string                                                                      $secret_key
 * @property int                                                                         $user_id
 * @property int                                                                         $status_id
 * @property int                                                                         $payment_method_id
 * @property int                                                                         $shipping_type_id
 * @property string                                                                      $shipping_price
 * @property float                                                                       $shipping_price_value
 * @property string                                                                      $total_price
 * @property float                                                                       $total_price_value
 * @property string                                                                      $position_total_price
 * @property float                                                                       $position_total_price_value
 * @property array                                                                       $property
 *
 * @property \October\Rain\Argon\Argon                                                   $created_at
 * @property \October\Rain\Argon\Argon                                                   $updated_at
 *
 * @property string                                                                      $transaction_id
 * @property string                                                                      $payment_token
 * @property array                                                                       $payment_data
 * @property array                                                                       $payment_response
 *
 * @property \October\Rain\Database\Collection|OrderPosition[]                           $order_position
 * @method static \October\Rain\Database\Relations\HasMany|OrderPosition order_position()
 *
 * @property \October\Rain\Database\Collection|OrderPromoMechanism[]                     $order_promo_mechanism
 * @method static \October\Rain\Database\Relations\HasMany|OrderPromoMechanism order_promo_mechanism()
 *
 * @property Status                                                                      $status
 * @method static Status|\October\Rain\Database\Relations\BelongsTo status()
 *
 * @property \Lovata\Buddies\Models\User                                                 $user
 * @method static \Lovata\Buddies\Models\User|\October\Rain\Database\Relations\BelongsTo user()
 *
 * @property ShippingType                                                                $shipping_type
 * @method static ShippingType|\October\Rain\Database\Relations\BelongsTo shipping_type()
 *
 * @property PaymentMethod                                                               $payment_method
 * @method static PaymentMethod|\October\Rain\Database\Relations\BelongsTo payment_method()
 *
 * Coupons for Shopaholic
 * @property \October\Rain\Database\Collection|\Lovata\CouponsShopaholic\Models\Coupon[] $coupon
 * @method static \October\Rain\Database\Relations\BelongsToMany|\Lovata\CouponsShopaholic\Models\Coupon coupon()
 *
 * @method static $this getByNumber(string $sNumber)
 * @method static $this getByStatus(int $iStatusID)
 * @method static $this getByShippingType(int $iShippingTypeID)
 * @method static $this getByPaymentMethod(int $iPaymentMethodID)
 * @method static $this getBySecretKey(string $sNumber)
 * @method static $this getByTransactionID(string $sTransactionID)
 */
class Order extends Model
{
    use UserBelongsTo;
    use TraitCached;
    use Validation;
    use PriceHelperTrait;
    use SetPropertyAttributeTrait;
    use Encryptable;

    public $table = 'lovata_orders_shopaholic_orders';

    public $rules = [];
    public $attributeNames = [];

    public $arPriceField = [
        'total_price',
        'shipping_price',
        'position_total_price',
    ];

    public $jsonable = ['property'];
    public $dates = ['created_at', 'updated_at'];
    public $encryptable = ['payment_data', 'payment_response'];
    public $hidden = ['payment_data', 'payment_response'];

    public $fillable = [
        'user_id',
        'status_id',
        'shipping_type_id',
        'payment_method_id',
        'shipping_price',
        'property',
    ];

    public $cached = [
        'id',
        'secret_key',
        'order_number',
        'user_id',
        'status_id',
        'payment_method_id',
        'shipping_type_id',
        'shipping_price_value',
        'total_price_value',
        'position_total_price_value',
        'property',
        'created_at',
        'updated_at',
    ];

    public $hasMany = [
        'order_position'        => [
            OrderPosition::class,
        ],
        'order_offer'           => [
            OrderPosition::class,
            'condition' => 'item_type = \Lovata\Shopaholic\Models\Offer',
        ],
        'order_promo_mechanism' => [
            OrderPromoMechanism::class
        ],
    ];
    public $belongsToMany = [];

    public $belongsTo = [
        'status'         => [Status::class, 'order' => 'sort_order asc'],
        'payment_method' => [PaymentMethod::class, 'order' => 'sort_order asc'],
        'shipping_type'  => [ShippingType::class, 'order' => 'sort_order asc'],
    ];

    /** @var OrderPromoMechanismProcessor */
    protected $obMechanismProcessor;

    /**
     * Order constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $sUserModelClass = UserHelper::instance()->getUserModel();
        if (!empty($sUserModelClass)) {
            $this->belongsTo['user'] = [$sUserModelClass];
        }

        parent::__construct($attributes);
    }

    /**
     * Get orders by number
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByNumber($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('order_number', $sData);
        }

        return $obQuery;
    }

    /**
     * Get orders by status
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByStatus($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('status_id', $sData);
        }

        return $obQuery;
    }

    /**
     * Get orders by shipping type
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByShippingType($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('shipping_type_id', $sData);
        }

        return $obQuery;
    }

    /**
     * Get orders by payment method
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByPaymentMethod($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('payment_method_id', $sData);
        }

        return $obQuery;
    }

    /**
     * Get orders by secret_key field
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetBySecretKey($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('secret_key', $sData);
        }

        return $obQuery;
    }

    /**
     * Get order by transaction ID
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByTransactionID($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('transaction_id', $sData);
        }

        return $obQuery;
    }

    /**
     * Get order by payment token
     * @param Order  $obQuery
     * @param string $sData
     * @return Order
     */
    public function scopeGetByPaymentToken($obQuery, $sData)
    {
        if (!empty($sData)) {
            $obQuery->where('payment_token', $sData);
        }

        return $obQuery;
    }

    /**
     * Get position total price data
     * @return \Lovata\OrdersShopaholic\Classes\PromoMechanism\PriceContainer
     */
    public function getPositionTotalPriceData()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getPositionTotalPrice();

        return $obPriceData;
    }

    /**
     * Get shipping price data
     * @return \Lovata\OrdersShopaholic\Classes\PromoMechanism\PriceContainer
     */
    public function getShippingPriceData()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getShippingPrice();

        return $obPriceData;
    }

    /**
     * Get total price data
     * @return \Lovata\OrdersShopaholic\Classes\PromoMechanism\PriceContainer
     */
    public function getTotalPriceData()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getTotalPrice();

        return $obPriceData;
    }

    /**
     * Get position count
     * @return int
     */
    public function getQuantityAttribute()
    {
        return $this->order_position->count();
    }

    /**
     * Get shipping price value from model
     * @return float
     */
    public function getShippingPriceValue()
    {
        $fPrice = $this->getAttributeFromArray('shipping_price');

        return $fPrice;
    }

    /**
     * Before save model method
     */
    public function beforeSave()
    {
        //Generate new order number
        $this->generateOrderNumber();
    }

    /**
     * After fetch event handler
     */
    public function afterFetch()
    {
        $this->initPromoMechanismProcessor();
    }

    /**
     * Generate secret key
     * @return string
     */
    public function generateSecretKey()
    {
        return md5($this->order_number.(string) microtime(true));
    }

    /**
     * Generate new order number
     */
    protected function generateOrderNumber()
    {
        // if there is no saved order number create the new one
        if (!empty($this->order_number)) {
            return;
        }

        $obDate = Argon::today()->startOfDay();
        $bAvailableNumber = false;
        $iTodayOrdersCount = $this->where('created_at', '>=', $obDate->toDateTimeString())->count() + 1;

        do {
            while (strlen($iTodayOrdersCount) < 4) {
                $iTodayOrdersCount = '0'.$iTodayOrdersCount;
            }

            $this->order_number = Argon::today()->format('ymd').'-'.$iTodayOrdersCount;
            if (empty($this->getByNumber($this->order_number)->first())) {
                $bAvailableNumber = true;
            } else {
                $iTodayOrdersCount++;
            }
        } while (!$bAvailableNumber);

        $this->secret_key = $this->generateSecretKey();
    }

    /**
     * Create object of OrderPromoMechanismProcessor class for Order
     */
    protected function initPromoMechanismProcessor()
    {
        if (!empty($this->obMechanismProcessor) && $this->obMechanismProcessor instanceof OrderPromoMechanismProcessor) {
            return;
        }

        $this->obMechanismProcessor = OrderPromoMechanismProcessor::get($this);
    }

    /**
     * Get position total price value
     * @return float
     */
    protected function getPositionTotalPriceValueAttribute()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getPositionTotalPrice();

        return $obPriceData->price_value;
    }

    /**
     * Get shipping price value
     * @return float
     */
    protected function getShippingPriceValueAttribute()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getShippingPrice();

        return $obPriceData->price_value;
    }

    /**
     * Get total price value
     * @return float
     */
    protected function getTotalPriceValueAttribute()
    {
        $this->initPromoMechanismProcessor();

        $obPriceData = $this->obMechanismProcessor->getTotalPrice();

        return $obPriceData->price_value;
    }
}