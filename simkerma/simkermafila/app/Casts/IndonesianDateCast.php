<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class IndonesianDateCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if (empty($value) || $value === '-' || $value === '0000-00-00') {
            return null;
        }

        try {
            $dateStr = strtr(strtolower($value), [
                'januari' => 'january',
                'februari' => 'february',
                'maret' => 'march',
                'mei' => 'may',
                'juni' => 'june',
                'juli' => 'july',
                'agustus' => 'august',
                'oktober' => 'october',
                'desember' => 'december'
            ]);
            
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }
        
        return $value;
    }
}
