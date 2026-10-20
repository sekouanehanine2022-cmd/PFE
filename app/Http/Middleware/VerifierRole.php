<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    // Controle le role requis avant de laisser executer la route protegee.
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

    // Determine le role du compte depuis son profil specialise.
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

    // Uniformise la liste des roles autorises avant leur comparaison.
    private function normaliserRoles(array $roles): array
    {
        return array_map(fn ($role) => $this->normaliserRole($role), $roles);
    }

    // Uniformise un role pour eviter les differences de casse et d ecriture.
    private function normaliserRole(string $role): string
    {
        return $role === 'technicien' ? 'admin' : $role;
    }
}
