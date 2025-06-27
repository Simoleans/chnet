<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Zone;
use App\Models\Plan;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::active()->where('id','!=',Auth::user()->id)->with(['zone', 'plan']);

        // Filtro de búsqueda
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('id_number', 'like', '%' . $searchTerm . '%');
            });
        }

        $users = $query->orderBy('id','desc')->paginate(10)->appends($request->query());

        $zones = Zone::all();
        $plans = Plan::all();

        return Inertia::render('User/Index', [
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
            ],
            'zones' => $zones,
            'plans' => $plans,
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'zone_id' => 'nullable|exists:zones,id',
            'code' => 'required|string|max:50|unique:users',
            'nationality' => 'required|string|in:V,E',
            'id_number' => 'required|string|max:20',
            'plan_id' => 'nullable|exists:plans,id',
            'password' => 'required|string|min:8',
            'status' => 'required|boolean',
            'role' => 'required|integer|in:0,1',
        ]);

        // Concatenar nacionalidad con número de cédula
        $fullIdNumber = $request->nationality . '-' . $request->id_number;

        // Validar que la cédula completa sea única
        $existingUser = User::where('id_number', $fullIdNumber)->first();
        if ($existingUser) {
            return back()->withErrors(['id_number' => 'Esta cédula ya está registrada.']);
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'zone_id' => $request->zone_id,
            'code' => $request->code,
            'id_number' => $fullIdNumber,
            'plan_id' => $request->plan_id,
            'password' => bcrypt($request->password),
            'status' => $request->status,
            'role' => $request->role,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'zone_id' => 'nullable|exists:zones,id',
            'code' => 'required|string|max:50|unique:users,code,' . $id,
            'nationality' => 'required|string|in:V,E',
            'id_number' => 'required|string|max:20',
            'plan_id' => 'nullable|exists:plans,id',
            'password' => 'nullable|string|min:8',
            'status' => 'required|boolean',
            'role' => 'required|integer|in:0,1',
        ]);

        // Concatenar nacionalidad con número de cédula
        $fullIdNumber = $request->nationality . '-' . $request->id_number;

        // Validar que la cédula completa sea única (excluyendo el usuario actual)
        $existingUser = User::where('id_number', $fullIdNumber)->where('id', '!=', $id)->first();
        if ($existingUser) {
            return back()->withErrors(['id_number' => 'Esta cédula ya está registrada.']);
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'zone_id' => $request->zone_id,
            'code' => $request->code,
            'id_number' => $fullIdNumber,
            'plan_id' => $request->plan_id,
            'status' => $request->status,
            'role' => $request->role,
        ];

        // Solo actualizar la contraseña si se proporciona
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $user->update($updateData);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Busca un usuario por código y retorna información de deuda
     */
    public function searchByCode(string $code)
    {
        try {
            $userData = User::searchByCode($code);

            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ningún usuario con ese código'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            \Log::error('Error buscando usuario por código: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
}
