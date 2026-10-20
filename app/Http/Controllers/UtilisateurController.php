<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use App\Rules\SansNomPrenom;
use App\Services\UtilisateurService;
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

    // Recupere la liste et les compteurs des Utilisateur pour afficher la page.
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

    // Verifie le formulaire puis enregistre la creation (Utilisateur).
    public function store(Request $request, UtilisateurService $utilisateurService)
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
                new SansNomPrenom(is_string($request->input('name')) ? $request->input('name') : ''),
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
                Rule::in(['alt_externe', 'etud_initial']),
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

        $utilisateur = $utilisateurService->creer($donnees);

        try {
            $utilisateur->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('info', __('messages.utilisateur_cree_email_non_envoye'));
        }

        return back()->with('success', __('messages.utilisateur_cree'));
    }

    // Bloque ou debloque le compte ; un blocage revoque ses sessions et jetons mobiles.
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

        // Bloquer le compte invalide aussi ses connexions existantes sur web et mobile.
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

    // Valide le nouveau mot de passe choisi par l administrateur et revoque les acces existants.
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
                new SansNomPrenom($user->name),
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

    // Verifie si la suppression est autorisee, puis supprime l'element (Utilisateur).
    public function destroy(Request $request, User $user, UtilisateurService $utilisateurService)
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

        $utilisateurService->supprimer($user);

        return back()->with('success', __('messages.utilisateur_supprime'));
    }

    // Empeche de supprimer le dernier compte pouvant administrer l application.
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

    // Detecte les operations qui imposent de conserver le compte et de preferer son blocage.
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
