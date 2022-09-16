<?php

namespace App\Traits;

trait Appendable
{
    public function offerCalculator($offer_id)
    {
        $offer = $this->offers()->find($offer_id);
        if ($offer) {
            $amount = 0;
            switch ($offer->operator) {
                case '%':
                    $amount = ($this->price * $offer->amount) / 100;
                    $amount = ($offer->type === 'discount') ? $this->price - $amount : $this->price + $amount;
                    break;
                case '*':
                    $amount = ($this->price * $offer->amount);
                    break;
                case '-':
                    $amount = ($this->price - $offer->amount);
                    break;
                case '+':
                    $amount = ($this->price + $offer->amount);
                    break;
                default:
                    $amount = $this->price;
                    break;
            }

            return $amount > 0 ? $amount : 0.00;
        }

        return $this->price;
    }

    public function packAmount($offer_id)
    {
        $offer = $this->offers()->find($offer_id);
        if ($offer) {
            switch ($offer->operator) {
                case '%':
                    $amount = ($offer->amount * $this->price) / 100;
                    $amount = ($offer->type === 'discount') ? '-'.$amount : '+'.$amount;
                    break;
                case '*':
                    $amount = ($offer->amount * $this->price);
                    $amount = $amount - $this->price;
                    break;
                default:
                    $amount = $offer->amount;
                    break;
            }

            return $amount;
        }

        return 0.00;
    }
}
