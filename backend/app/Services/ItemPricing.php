<?php

namespace App\Services;

use App\Models\Classification;
use App\Models\Condition;
use App\Models\Consignor;
use App\Models\Discount;
use Money\Money;

class ItemPricing
{
    public function calculatePrice(int $price, $classificationId=null, $conditionId=null, $discountId=null, $discountAmount=null, $discountAmountType=null, $quantityOrdered=null) {
        $price = Money::USD($price);

        $conditionDiscount = is_null($conditionId) ? 0 : Condition::withTrashed()->find($conditionId)->discount;
        $classificationDiscount = is_null($classificationId) ? 0 : Classification::withTrashed()->find($classificationId)->discount;
        $discountDiscount = is_null($discountId) ? 0 : Discount::find($discountId)->discount;
        $discount = round($conditionDiscount + $classificationDiscount + $discountDiscount, 4);

        if ($discount != 0)
        {
            $price = $price->multiply(1-$discount);
        } 
        else if ($discountAmount)
        {
            if ($discountAmountType === "total" && $quantityOrdered > 1)
            {
                $discountAmount = intval($discountAmount / $quantityOrdered);
            }
            
            $price = $price->subtract(Money::USD($discountAmount));
        }

        return (int) $price->getAmount();
    }

    public function calculateConsignmentFee(Money $price, int $consignorId)
    {
        $consignor = Consignor::withTrashed()->find($consignorId);
        $feePercentage = $consignor->consignment_fee_percentage;

        if (!$feePercentage)
        {
            return [
                'consignment_fee' => null,
                'consignment_fee_percentage' => null
            ];
        }

        return [
            'consignment_fee' => (int) $price->multiply($feePercentage)->getAmount(),
            'consignment_fee_percentage' => $feePercentage
        ];
    }
}