<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends \Illuminate\Routing\Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        if (!empty($request->input('find'))) {
            $countries = Country::where('name', $request->input('find'))->get();
        } else {
            $countries = Country::when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso2', 'like', "%$query%")
                        ->orWhere('phonecode', 'like', "%$query%");
                });
            })
                ->limit(20)
                ->get();
        }
        // Format for TomSelect
        $results = $countries->map(function ($country) {
            return [
                'phonecode' => $country->phonecode,
                'value' => $country->name,
                'text' => $country->name,
                'currency' => $country->currency,
                'customProperties' => '<span class="flag flag-xs flag-country-' . strtolower($country->iso2) . '"></span>',
            ];
        });

        return response()->json($results);
    }

    public function getCurrency(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        if (!empty($request->input('find'))) {
            $countries = Country::where('currency', $request->input('find'))->get();
        } else {
            $countries = Country::when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso2', 'like', "%$query%")
                        ->orWhere('phonecode', 'like', "%$query%");
                });
            })
                ->limit(20)
                ->get();
        }
        // Format for TomSelect
        $results = $countries->map(function ($country) {
            return [
                'value' => $country->currency,
                'text' => $country->currency . ' - ' . $country->currency_symbol,
                'currency' => $country->currency,
                'currency_symbol' => $country->currency_symbol,
                'customProperties' => '<span class="flag flag-xs flag-country-' . strtolower($country->iso2) . '"></span>',
            ];
        });

        return response()->json($results);
    }
}
