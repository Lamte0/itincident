<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtrer par rôle
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Recherche
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name')->paginate(15));
    }

    /**
     * Liste des maintenanciers
     */
    public function maintenanciers()
    {
        $maintenanciers = User::where('role', 'MAINTENANCIER')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($maintenanciers);
    }

    /**
     * Créer un utilisateur (Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:UTILISATEUR,MAINTENANCIER,CHEF_SERVICE,ADMIN'],
            'service' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'matricule' => ['nullable', 'string', 'max:50', 'unique:users'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'service' => $request->service,
            'telephone' => $request->telephone,
            'matricule' => $request->matricule,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
        ], 201);
    }

    /**
     * Afficher un utilisateur
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Mettre à jour un utilisateur (Admin)
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'in:UTILISATEUR,MAINTENANCIER,CHEF_SERVICE,ADMIN'],
            'service' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'matricule' => ['nullable', 'string', 'max:50', 'unique:users,matricule,' . $user->id],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($request->only([
            'name', 'email', 'role', 'service', 'telephone', 'matricule', 'is_active'
        ]));

        return response()->json([
            'message' => 'Utilisateur mis à jour',
            'user' => $user,
        ]);
    }

    /**
     * Changer le mot de passe d'un utilisateur (Admin)
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé',
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 422);
        }

        // Vérifier s'il a des incidents
        if ($user->incidentsCrees()->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur a des incidents associés. Désactivez-le plutôt.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé',
        ]);
    }
}
