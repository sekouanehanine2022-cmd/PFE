<?php

namespace Tests\Feature;

use App\Mail\RelanceEmpruntMail;
use App\Models\{Affectation, Emprunt, Etudiant, Materiel, Notification, PcPortable, Personnel, User};
use App\Services\RelanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RelanceAutomatiqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 08:00:00', 'Europe/Paris'));
        config(['mail.default' => 'smtp']);
        Mail::fake();
    }

    public function test_trois_paliers_sans_doublons_et_lecture_independante(): void
    {
        foreach ([14, 7, 1] as $palier) {
            $this->operations($palier);
        }
        $this->artisan('relances:envoyer')->assertSuccessful();
        Notification::query()->update(['read_at' => now()]);
        $this->artisan('relances:envoyer')->assertSuccessful();
        Mail::assertSentCount(6);
        $this->assertDatabaseCount('notifications', 6);
        $this->assertSame(6, Notification::whereNotNull('email_envoye_at')->count());
        $this->assertSame(6, Notification::whereNotNull('read_at')->count());
        Mail::assertSent(RelanceEmpruntMail::class, fn ($mail) => $mail->joursRestants === 1 && str_contains($mail->render(), 'TEST-'));
    }

    public function test_mode_array_cree_les_notifications_sans_marquer_un_envoi_reel(): void
    {
        config(['mail.default' => 'array']);
        $this->operations(7);
        app(RelanceService::class)->envoyer();
        Mail::assertNothingSent();
        $this->assertSame(2, Notification::whereNull('email_envoye_at')->count());
        config(['mail.default' => 'smtp']);
        app(RelanceService::class)->envoyer();
        Mail::assertSentCount(2);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_prolongation_et_retour(): void
    {
        [$affectation, $emprunt] = $this->operations(7);
        app(RelanceService::class)->envoyer();
        $affectation->update(['date_fin' => today('Europe/Paris')->addDays(14)]);
        $emprunt->update(['date_fin_prevue' => today('Europe/Paris')->addDays(14)]);
        app(RelanceService::class)->envoyer();
        $this->assertDatabaseCount('notifications', 4);
        $affectation->update(['statut' => 'rendu']);
        $emprunt->update(['date_retour' => today()]);
        $this->travel(7)->days();
        app(RelanceService::class)->envoyer();
        Mail::assertSentCount(4);
    }

    public function test_autres_dates_sans_fin_et_non_pc_ignores(): void
    {
        [$affectation, $emprunt] = $this->operations(7);
        $affectation->update(['date_fin' => null]);
        $emprunt->pcPortable->delete();
        $this->operations(8);
        app(RelanceService::class)->envoyer();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_echec_email_conserve_notification_et_permet_retry(): void
    {
        $this->operations(1);
        $fake = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->twice()->andThrow(new \RuntimeException('SMTP indisponible'));
        $this->assertSame(2, app(RelanceService::class)->envoyer()['erreurs']);
        $this->assertSame(2, Notification::whereNull('email_envoye_at')->count());
        Mail::swap($fake);
        app(RelanceService::class)->envoyer();
        $this->assertDatabaseCount('notifications', 2);
        Mail::assertSentCount(2);
    }

    public function test_notification_ouvre_mon_materiel_pour_le_destinataire(): void
    {
        [, $emprunt] = $this->operations(1);
        app(RelanceService::class)->envoyer();
        $notification = Notification::where('emprunt_id', $emprunt->id)->firstOrFail();
        $this->actingAs($emprunt->etudiant->user)->patch(route('notifications.lire', $notification))->assertRedirect(route('mon-materiel.index'));
    }

    private function operations(int $palier): array
    {
        $personnel = User::factory()->create(['mot_de_passe_change' => true]);
        Personnel::create(['user_id' => $personnel->id, 'type_contrat' => 'cdd', 'role' => 'personnel']);
        $etudiant = User::factory()->create(['mot_de_passe_change' => true]);
        Etudiant::create(['user_id' => $etudiant->id, 'type' => 'alt_externe']);
        $materiel = Materiel::create(['type_materiel' => 'pc_portable', 'nom' => 'Dell Latitude', 'marque' => 'Dell', 'etat' => 'disponible']);
        PcPortable::create(['materiel_id' => $materiel->id, 'numero_serie' => 'TEST-'.$materiel->id, 'cpu' => 'Intel i5', 'ram' => '16 Go', 'stockage' => '512 Go', 'os' => 'Windows 11']);
        $date = today('Europe/Paris')->addDays($palier);
        return [
            Affectation::create(['personnel_id' => $personnel->id, 'materiel_id' => $materiel->id, 'date_debut' => today(), 'date_fin' => $date, 'statut' => 'active']),
            Emprunt::create(['etudiant_id' => $etudiant->id, 'materiel_id' => $materiel->id, 'date_debut' => today(), 'date_fin_prevue' => $date, 'statut' => 'en_cours']),
        ];
    }
}
