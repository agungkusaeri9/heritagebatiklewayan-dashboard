<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function provinces(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Province::query();
        if ($search) {
            $query->where('province_name', 'like', "%{$search}%");
        }

        return Inertia::render('locations/Provinces', [
            'provinces' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function cities(Request $request, Province $province)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = $province->cities();

        if ($search) {
            $query->where('city_name', 'like', "%{$search}%");
        }

        return Inertia::render('locations/Cities', [
            'province' => $province,
            'cities' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function subdistricts(Request $request, City $city)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = $city->subdistricts();

        if ($search) {
            $query->where('subdistrict_name', 'like', "%{$search}%");
        }

        return Inertia::render('locations/Subdistricts', [
            'city' => $city,
            'subdistricts' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function getCities(Request $request)
    {
        return response()->json(City::where('province_id', $request->province_id)->get());
    }

    public function getSubdistricts(Request $request)
    {
        return response()->json(Subdistrict::where('city_id', $request->city_id)->get());
    }
}
