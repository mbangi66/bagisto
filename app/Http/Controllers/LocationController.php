<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\City;
use App\Models\Block;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Return states for a given country ID.
     */
    public function getStates($countryId)
    {
        // Fetch states where country_id matches the given countryId.
        $states = State::where('country_id', $countryId)
                       ->orderBy('state_name_ar')
                       ->get(['id', 'state_name', 'state_name_ar']);

        return response()->json($states);
    }

    /**
     * Return cities for a given state name.
     */
    public function getCities($stateName)
    {
        // Find the state record based on the state name.
        $state = State::where('state_name', $stateName)->first();

        if (!$state) {
            return response()->json([]);
        }

        // Fetch cities for the found state.
        $cities = City::where('state_id', $state->id)
                      ->orderBy('city_name_ar')
                      ->get(['id', 'city_name', 'city_name_ar', 'state_id']);

        return response()->json($cities);
    }

    /**
     * Return blocks for a given city name.
     */
    public function getBlocks($cityName)
    {
        // Find the city record based on the city name.
        $city = City::where('city_name', $cityName)->first();

        if (!$city) {
            return response()->json([]);
        }

        // Fetch blocks for the found city.
        $blocks = Block::where('city_id', $city->id)
                       ->orderBy('name_ar')
                       ->get(['id', 'name_en', 'name_ar', 'city_id']);

        return response()->json($blocks);
    }
}
