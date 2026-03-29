<?php

namespace App\Services;

class RegistrationGeoService
{
    public function isWithinTagoSurigaoDelSurBounds(float $latitude, float $longitude): bool
    {
        $bounds = config('registration_geo.tago_bounds');

        return $latitude >= $bounds['min_lat']
            && $latitude <= $bounds['max_lat']
            && $longitude >= $bounds['min_lng']
            && $longitude <= $bounds['max_lng'];
    }
}
