<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UtilisateurController extends Controller
{
    private const DOMAINES_AUTORISES = [
        'intedgroup.com',
        'efeledu.com',
        'adgeducation.com',
        'icgeducation.com',
    ];

    public function index(Request $request)
    {
        $recherche = trim((string) $request->query('search'));
        $filtreType = in_array($request->query('type'), ['personnel', 'etudiant'], true)
            ? $request->query('type')
            : '';

        $total = User::count();
        $totalPersonnels = Personnel::count();
        $totalEtudiants = Etudiant::count();
        $enAttenteVerification = User::whereNull('email_verified_at')->count();

        $utilisateurs = User::query()
            ->with(['personnel', 'etudiant'])
            ->when($recherche !== '', function ($query) use ($recherche) {
                $query->where(function ($utilisateurs) use ($recherche) {
                    $utilisateurs
                        ->where('name', 'like', '%'.$recherche.'%')
                        ->orWhere('email', 'like', '%'.$recherche.'%');
                });
            })
            ->when($filtreType === 'personnel', fn ($query) => $query->whereHas('personnel'))
            ->when($filtreType === 'etudiant', fn ($query) => $query->whereHas('etudiant'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('systeme.utilisateurs', compact(
            'utilisateurs',
            'recherche',
            'filtreType',
            'total',
            'totalPersonnels',
            'totalEtudiants',
            'enAttenteVerification'
        ));
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'type_compte' => ['required', Rule::in(['personnel', 'etudiant'])],
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                function (string $attribute, mixed $value, $fail) {
                    $domaine = Str::lower(Str::afterLast((string) $value, '@'));

                    if (! in_array($domaine, self::DOMAINES_AUTORISES, true)) {
                        $fail(__('messages.email_domaine_ecole'));
                    }
                },
            ],
            'password_temporaire' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'service' => [Rule::requiredIf($request->input('type_compte') === 'personnel'), 'nullable', 'string', 'max:100'],
            'poste' => [Rule::requiredIf($request->input('type_compte') === 'personnel'), 'nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'type_contrat' => [
                Rule::requiredIf($request->input('type_compte') === 'personnel'),
                'nullable',
                Rule::in(['cdi', 'cdd', 'alternant_interne']),
            ],
            'type_etudiant' => [
                Rule::requiredIf($request->input('type_compte') === 'etudiant'),
                'nullable',
                Rule::in(['alt_externe', 'alt_interne', 'etud_initial']),
            ],
            'promotion' => [Rule::requiredIf($request->input('type_compte') === 'etudiant'), 'nullable', 'string', 'max:100'],
            'date_fin_formation' => ['nullable', 'date'],
            'etablissement' => [Rule::requiredIf($request->input('type_compte') === 'etudiant'), 'nullable', 'string', 'max:100'],
        ], [
            'password_temporaire.required' => __('messages.mot_de_passe_temporaire_obligatoire'),
            'password_temporaire.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 8]),
            'password_temporaire.confirmed' => __('messages.mot_de_passe_temporaire_confirmation'),
            'password_temporaire.regex' => __('messages.nouveau_mot_de_passe_complexite'),
        ]);

        $utilisateur = DB::transaction(function () use ($donnees) {
            $utilisateur = User::create([
                'name' => trim($donnees['name']),
                'email' => Str::lower(trim($donnees['email'])),
                'password' => $donnees['password_temporaire'],
            ]);

            $utilisateur->forceFill([
                'mot_de_passe_change' => false,
                'email_verified_at' => null,
            ])->save();

            if ($donnees['type_compte'] === 'personnel') {
                Personnel::create([
                    'user_id' => $utilisateur->id,
                    'service' => trim($donnees['service']),
                    'poste' => trim($donnees['poste']),
                    'telephone' => filled($donnees['telephone'] ?? null) ? trim($donnees['telephone']) : null,
                    'type_contrat' => $donnees['type_contrat'],
                    'role' => 'personnel',
                ]);
            } else {
                Etudiant::create([
                    'user_id' => $utilisateur->id,
                    'type' => $donnees['type_etudiant'],
                    'promotion' => trim($donnees['promotion']),
                    'date_fin_formation' => $donnees['date_fin_formation'] ?? null,
                    'etablissement' => trim($donnees['etablissement']),
                ]);
            }

            return $utilisateur;
        });

        try {
            $utilisateur->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('info', __('messages.utilisateur_cree_email_non_envoye'));
        }

        return back()->with('success', __('messages.utilisateur_cree'));
    }

    public function basculerBlocage(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'utilisateur' => __('messages.utilisateur_blocage_soi_interdit'),
            ]);
        }

        $accesBloque = ! $user->acces_bloque;

        $user->forceFill([
            'acces_bloque' => $accesBloque,
        ])->save();

        if ($accesBloque) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            $user->tokens()->delete();
        }

        return back()->with(
            'success',
            $accesBloque
                ? __('messages.utilisateur_bloque')
                : __('messages.utilisateur_debloque')
        );
    }

    public function reinitialiserMotDePasse(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'utilisateur' => __('messages.utilisateur_reinitialisation_soi_interdite'),
            ]);
        }

        $donnees = $request->validateWithBag('reinitialisationMotDePasse', [
            'nouveau_mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'forcer_changement_mot_de_passe' => ['nullable', 'boolean'],
        ], [
            'nouveau_mot_de_passe.required' => __('messages.nouveau_mot_de_passe_obligatoire'),
            'nouveau_mot_de_passe.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 8]),
            'nouveau_mot_de_passe.confirmed' => __('messages.nouveau_mot_de_passe_confirmation'),
            'nouveau_mot_de_passe.regex' => __('messages.nouveau_mot_de_passe_complexite'),
        ]);

        $user->forceFill([
            'password' => $donnees['nouveau_mot_de_passe'],
            'mot_de_passe_change' => ! $request->boolean('forcer_changement_mot_de_passe'),
        ])->save();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->tokens()->delete();

        return back()->with('success', __('messages.utilisateur_mot_de_passe_reinitialise'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'utilisateur' => __('messages.utilisateur_suppression_soi_interdite'),
            ]);
        }

        $user->loadMissing(['personnel', 'etudiant']);

        if ($this->estDernierAdministrateur($user)) {
            return back()->withErrors([
                'utilisateur' => __('messages.utilisateur_dernier_admin_suppression_interdite'),
            ]);
        }

        if ($this->possedeUnHistorique($user)) {
            return back()->withErrors([
                'utilisateur' => __('messages.utilisateur_suppression_historique_interdite'),
            ]);
        }

        DB::transaction(function () use ($user) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            $user->tokens()->delete();

            $user->delete();
        });

        return back()->with('success', __('messages.utilisateur_supprime'));
    }

    private function estDernierAdministrateur(User $user): bool
    {
        if ($user->personnel?->role !== 'admin') {
            return false;
        }

        return ! Personnel::query()
            ->where('role', 'admin')
            ->where('user_id', '!=', $user->id)
            ->exists();
    }

    private function possedeUnHistorique(User $user): bool
    {
        if ($user->personnel?->affectations()->exists()) {
            return true;
        }

        if ($user->etudiant?->emprunts()->exists()) {
            return true;
        }

        return Ticket::query()
            ->where('demandeur_id', $user->id)
            ->orWhere('technicien_id', $user->id)
            ->exists();
    }
}
