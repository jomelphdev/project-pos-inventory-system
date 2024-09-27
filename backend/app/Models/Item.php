<?php

namespace App\Models;

use App\Casts\LengthCast;
use App\Casts\MassCast;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpUnitConversion\Unit\Mass\Ounce;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['images'];
    
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'created_by',
        'organization_id',
        'classification_id',
        'condition_id',
        'manifest_item_id',
        'consignor_id',
        'title',
        'description',
        'price',
        'original_price',
        'cost',
        'sku',
        'upc',
        'asin',
        'mpn',
        'merchant_name',
        'merchant_price',
        'length',
        'width',
        'depth',
        'weight',
        'brand',
        'color',
        'ean',
        'elid',
        'condition_description',
        'mongo_id',
        'manifest_quantity_expected',
        'consignment_fee'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'organization_id' => 'integer',
        'classification_id' => 'integer',
        'condition_id' => 'integer',
        'manifest_item_id' => 'integer',
        'consignor_id' => 'integer',
        'price' => MoneyCast::class,
        'original_price' => MoneyCast::class,
        'cost' => MoneyCast::class,
        'length' => LengthCast::class,
        'width' => LengthCast::class,
        'depth' => LengthCast::class,
        'weight' => MassCast::class,
        'is_consignment' => 'boolean',
        'consignment_fee' => 'integer',
    ];

    // RELATIONSHIPS

    public function itemImages()
    {
        return $this->hasMany(\App\Models\ItemImage::class);
    }

    public function quantities()
    {
        return $this->hasMany(\App\Models\Quantity::class);
    }

    public function currentQuantities()
    {
        return $this->hasMany(\App\Models\CurrentQuantity::class);
    }

    public function posOrderItems()
    {
        return $this->hasMany(\App\Models\PosOrderItem::class);
    }

    public function posReturnItems()
    {
        return $this->hasMany(\App\Models\PosReturnItem::class);
    }

    public function itemSpecificDiscounts()
    {
        return $this->hasMany(\App\Models\ItemSpecificDiscount::class)->withTrashed()->orderBy('deleted_at', 'ASC');
    }

    public function itemHistory()
    {
        return $this->hasMany(\App\Models\ItemHistory::class);
    }

    public function onlineOrderItems()
    {
        return $this->belongsToMany(\App\Models\OnlineOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->withTrashed();
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function classification()
    {
        return $this->belongsTo(\App\Models\Classification::class)->withTrashed();
    }

    public function condition()
    {
        return $this->belongsTo(\App\Models\Condition::class)->withTrashed();
    }

    public function manifest()
    {
        return $this->belongsTo(\App\Models\Manifest::class);
    }

    public function consignor()
    {
        return $this->belongsTo(\App\Models\Consignor::class);
    }

    // METHODS

    public function getQuantitiesForStore($storeId)
    {
        return $this->quantities()
            ->select('id', 'created_by', 'quantity_received', 'message', 'is_transfer', 'created_at')
            ->where('store_id', $storeId);
    }

    public function getQuantityReceievedForStore($storeId)
    {
        return $this->quantities()->where('store_id', $storeId)->sum('quantity_received');
    }

    public function getQuantityOrderedForStore($storeId)
    {
      return $this->posOrderItems()->whereHas('posOrder', function (Builder $query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->sum('quantity_ordered');
    }

    public function getQuantityReturnedForStore($storeId)
    {
        return $this->posReturnItems()->whereHas('posReturn', function (Builder $query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->where('action', 1)
            ->sum('quantity_returned');
    }

    public function getQuantityDiscardedForStore($storeId)
    {
        return $this->posReturnItems()->whereHas('posReturn', function (Builder $query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->where('action', 0)
            ->sum('quantity_returned');
    }

    public function getQuantitySoldForStore($storeId)
    {
        return $this->getQuantityOrderedForStore($storeId) - $this->getQuantityReturnedForStore($storeId) - $this->getQuantityDiscardedForStore($storeId);
    }

    public function getQuantityForStore($storeId)
    {
        return $this->getQuantityReceievedForStore($storeId) - $this->getQuantitySoldForStore($storeId) - $this->getQuantityDiscardedForStore($storeId);
    }

    public function orderItemsForStore($storeId)
    {
        return $this->posOrderItems()
            ->select(['id', 'pos_order_id', 'quantity_ordered', 'created_at'])
            ->with(['posOrder' => function($q) {
                return $q->select('id', 'created_by')->without('posOrderItems');
            }])
            ->whereHas('posOrder', function($q) use ($storeId) {
                return $q->where('store_id', $storeId);
            });
    }

    public function returnItemsForStore($storeId)
    {
        return $this->posReturnItems()
            ->select(['id', 'pos_return_id', 'quantity_returned', 'action', 'created_at'])
            ->with(['posReturn' => function($q) {
                return $q->select('id', 'created_by')->without('posReturnItems');
            }])
            ->whereHas('posReturn', function($q) use ($storeId) {
                return $q->where('store_id', $storeId);
            });
    }

    public function storeIdsWithQty()
    {
        return $this->quantities()->select('store_id')->groupBy('store_id')->pluck('store_id')->toArray();
    }

    // ATTRIBUTES

    public function getStoreQuantitiesAttribute()
    {
        $storeQuantities = collect([]);
        foreach ($this->storeIdsWithQty() as $id) 
        {
            $storeQuantities->push([
                'store_id' => $id,
                'quantity' => (int) $this->getQuantityForStore($id),
                'quantity_received' => (int) $this->getQuantityReceievedForStore($id),
                'quantity_sold' => (int) $this->getQuantitySoldForStore($id),
                'quantity_returned' => (int) $this->getQuantityReturnedForStore($id),
                'quantity_discarded' => (int) $this->getQuantityDiscardedForStore($id),
            ]);
        }
        
        return $storeQuantities;
    }

    public function getQuantityLogAttribute()
    {
        $storeQuantities = collect([]);
        foreach ($this->storeIdsWithQty() as $id) 
        {
            $quantities = $this->getQuantitiesForStore($id);
            
            $storeQuantities->push([
                'store_id' => $id,
                'quantities' => $quantities->get(),
                'orders' => $this->orderItemsForStore($id)->get(),
                'returns' => $this->returnItemsForStore($id)->get()
            ]);
        }
        
        return $storeQuantities;
    }

    public function getImagesAttribute()
    {
        return $this->itemImages()->get()->pluck('image_url');
    }

    // QUERY SCOPES

    public function scopeInventoryForStores(Builder $query, array $stores, int $orgId, bool $withEmptyQuantities=false, int $lastSeenId=null, int $limit=null)
    {
        $storesStr = implode(',', $stores);

        $query = $query->select(
                'id',
                'title', 
                'description',
                'sku', 
                'upc', 
                'original_price',
                'price', 
                'cost',
                'length',
                'width',
                'depth',
                'weight'
            )
            ->addSelect([
                'classification_name' => \App\Models\Classification::withTrashed()->select('name')->whereColumn('id', 'items.classification_id')->limit(1),
                'condition_name' => \App\Models\Condition::withTrashed()->select('name')->whereColumn('id', 'items.condition_id')->limit(1)
            ])
            ->selectRaw("
                COALESCE((
                    SELECT quantity
                    FROM current_quantities as cq
                    WHERE items.id=cq.item_id AND cq.store_id IN (?) AND cq.quantity > 0
                ), 0) as quantity
            ", [$storesStr])
            ->where('organization_id', $orgId);
        
        if (!$withEmptyQuantities)
        {
            $query->whereHas('currentQuantities', function (Builder $q) use ($stores) {
                $q->whereIn('store_id', $stores)->where('quantity', '>', 0);
            });
        }
        if ($lastSeenId)
        {
            $query->where('id', '>', $lastSeenId);
        }
        if ($limit)
        {
            $query->limit($limit);
        }

        return $query;
    }

    public function scopeBasicRegexQuery(Builder $q, $orgId, $query, $limit=30, $lastSeenId=null)
    {
        $titleQuery = $query;

        if (!preg_match('/(\d+)/', $titleQuery))
        {
            $titleQuery = "%{$query}%";
        }
        
        $q = $q->where('organization_id', $orgId)
            ->where(function($q) use ($query, $titleQuery) {
                return $q
                    ->orWhere('sku', 'LIKE', $query)
                    ->orWhere('upc', 'LIKE', $query)
                    ->orWhere('title', 'LIKE', $titleQuery);
            });

        if ($lastSeenId)
        {
            $q->where('id', '<', $lastSeenId);
        }
            
            
        return $q
            ->with('createdBy:id,username')
            ->select('id', 'title', 'upc', 'price', 'original_price', 'condition_id', 'classification_id', 'organization_id', 'created_at', 'created_by')
            ->orderBy('created_at', 'DESC')
            ->limit($limit);
    }
}
