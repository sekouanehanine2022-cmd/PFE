<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->loadMissing(['personnel', 'etudiant']);

        $roleUtilisateur = $this->roleUtilisateur($user);
        $rolesAutorises = $this->normaliserRoles($roles);

        if (! in_array($roleUtilisateur, $rolesAutorises, true)) {
            abort(403, 'Acces non autorise.');
        }

        return $next($request);
    }

    private function roleUtilisateur($user): string
    {
        if ($user->personnel) {
            return $this->normaliserRole($user->personnel->role);
        }

        if ($user->etudiant) {
            return 'etudiant';
        }

        return 'utilisateur';
    }

    private function normaliserRoles(array $roles): array
    {
        return array_map(fn ($role) => $this->normaliserRole($role), $roles);
    }

    private function normaliserRole(string $role): string
    {
        return $role === 'technicien' ? 'admin' : $role;
    }
}
