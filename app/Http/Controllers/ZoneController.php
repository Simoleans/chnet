<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Zone::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $zones = $query->paginate(10)->withQueryString();

        return Inertia::render('Zones/Index', [
            'data' => $zones->items(),
            'pagination' => [
                'current_page' => $zones->currentPage(),
                'last_page' => $zones->lastPage(),
                'from' => $zones->firstItem(),
                'to' => $zones->lastItem(),
                'total' => $zones->total(),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:zones,name',
        ]);

        Zone::create(['name' => $request->name]);

        return redirect()->route('zones.index')->with('success', 'Zone created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Zone $zone)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Zone $zone)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Zone $zone)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:zones,name,' . $zone->id,
        ]);

        $zone->update(['name' => $request->name]);

        return redirect()->route('zones.index')->with('success', 'Zone updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Zone $zone)
    {
        $zone->delete();

        return redirect()->route('zones.index')->with('success', 'Zone deleted successfully.');
    }
}
