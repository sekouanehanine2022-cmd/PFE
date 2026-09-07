<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Personnel;
use App\Models\Etudiant;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ---- COLLABORATEURS ----
        // Seuls ces 2 comptes ont le rôle admin (équipe informatique) ;
        // tous les autres collaborateurs seront role "personnel".
        $emailsAdmin = ['h.sekouane@efeledu.com', 'a.hamoudi@intedgroup.com'];

        $collaborateurs = [
            ['name' => 'Rafik Selmi', 'email' => 'r.selmi@intedgroup.com', 'service' => 'Administration', 'poste' => 'Responsable campus paris'],
            ['name' => 'Kamal Yazli', 'email' => 'k.yazli@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Directeur commercial'],
            ['name' => 'Celine Saib', 'email' => 'c.saib@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Responsable commerciale'],
            ['name' => 'Liticia Bouslimani', 'email' => 'l.bouslimani@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Noumia Guezoui', 'email' => 'n.guezoui@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Rawda El Hosary', 'email' => 'r.elhosary@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Laeticia Adour', 'email' => 'l.adour@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Sandra Ouamdi', 'email' => 's.ouamdi@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Feriel Khodja', 'email' => 'f.khodja@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Sofia Talbi', 'email' => 's.talbi@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Celena Hammadou', 'email' => 'c.hammadou@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation IT'],
            ['name' => 'Menil Mohammedi', 'email' => 'm.mohammedi@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Conseiller  en formation'],
            ['name' => 'Lydia Lagab', 'email' => 'l.lagab@intedgroup.com', 'service' => 'Commercial', 'poste' => 'Coordinatrice commerciale et administrative'],
            ['name' => 'Roumaissa Djaroun', 'email' => 'r.djaroun@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chargée de gestion des contrats d\'alternance/ Référente OPCO'],
            ['name' => 'Melinda Hammani', 'email' => 'm.hammani@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chargée marketing'],
            ['name' => 'Hanane Didaoui', 'email' => 'h.didaoui@intedgroup.com', 'service' => 'Administration', 'poste' => 'Responsable administrative'],
            ['name' => 'Amira Lyna Haddar', 'email' => 'a.haddar@intedgroup.com', 'service' => 'Administration', 'poste' => 'Conseillere administrative'],
            ['name' => 'Sara Hafhouf', 'email' => 's.hafhouf@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Coordinatrice pedagogique'],
            ['name' => 'Manel Ben Fradj', 'email' => 'm.benfradj@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Assistante pedagogique'],
            ['name' => 'Soulaimy Ahamed Ibouroi', 'email' => 's.ahamedibouroi@intedgroup.com', 'service' => 'Support IT', 'poste' => 'Chargé support informatique'],
            ['name' => 'Ahmed Hamoudi', 'email' => 'a.hamoudi@intedgroup.com', 'service' => 'Support IT', 'poste' => 'Chargé support informatique'],
            ['name' => 'Nadine Kheddouci', 'email' => 'n.kheddouci@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chagée d\'admission'],
            ['name' => 'Lisa Hamour', 'email' => 'l.hamour@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Liz Gars', 'email' => 'l.gars@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Formatrice'],
            ['name' => 'Edna Ktorza', 'email' => 'e.ktorza@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Formatrice'],
            ['name' => 'Lina Arkoub', 'email' => 'l.arkoub@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Hanine Sekouane', 'email' => 'h.sekouane@efeledu.com', 'service' => 'Support IT', 'poste' => 'Chargé support informatique'],
            ['name' => 'Ouerdia Maked', 'email' => 'o.maked@efeledu.com', 'service' => 'Pédagogie', 'poste' => 'Assistante pedagogique'],
            ['name' => 'Chanez Laribi', 'email' => 'c.laribi@efeledu.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Chanez BRAIK', 'email' => 'c.braik@efeledu.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Anais Benchegra', 'email' => 'a.benchegra@intedgroup.com', 'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Sid Ali Akhemoum', 'email' => 's.akhemoum@intedgroup.com', 'service' => 'Support IT', 'poste' => 'Chargé support informatique'],
            ['name' => 'Sandrine Benichou', 'email' => 's.benichou@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Formatrice'],
            ['name' => 'Farid Mefidene', 'email' => 'f.mefidene@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Formateur'],
            ['name' => 'Anis Ben Ammar', 'email' => 'a.benammar@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Formateur'],
            ['name' => 'Ines Laiche', 'email' => 'i.laiche@intedgroup.com', 'service' => 'Pédagogie', 'poste' => 'Assistante pedagogique'],
            ['name' => 'Jaafer El Metri', 'email' => 'j.elmetri@intedgroup.com', 'service' => 'Support IT', 'poste' => 'Responsable support'],
            ['name' => 'Kahina Ziane', 'email' => 'k.ziane@efeledu.com', 'service' => 'Administration', 'poste' => 'Conseillere administrative'],
            ['name' => 'Lina Hadj Ahmed', 'email' => 'l.hadjahmed@efeledu.com', 'service' => 'Commercial', 'poste' => 'Conseillere en formation'],
            ['name' => 'Nejiba Mganem', 'email' => 'n.mganem@intedgroup.com', 'service' => 'Direction', 'poste' => 'Directrice qualité'],
            ['name' => 'Yassine Kalboussi', 'email' => 'y.kalboussi@intedgroup.com', 'service' => 'Administration', 'poste' => 'Assistant comptable'],
            ['name' => 'Imene Ben Hassen', 'email' => 'i.benhassen@intedgroup.com', 'service' => 'Direction', 'poste' => 'Directrice administrative et financière'],
            ['name' => 'Lamis Sassi', 'email' => 'l.sassi@intedgroup.com', 'service' => 'Administration', 'poste' => 'Responsable des Ressources Humaines'],
        ];

        foreach ($collaborateurs as $collab) {
            $user = User::updateOrCreate(
                ['email' => $collab['email']],
                ['name' => $collab['name'], 'password' => Hash::make('password')]
            );

            $role = in_array($collab['email'], $emailsAdmin) ? 'admin' : 'personnel';

            Personnel::updateOrCreate(
                ['user_id' => $user->id],
                ['service' => $collab['service'], 'poste' => $collab['poste'], 'role' => $role]
            );
        }

        // ---- ÉTUDIANTS ----
        $etudiants = [
            ['name' => 'Lydia Abdou', 'email' => 'l.abdou@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Nabila Aidli', 'email' => 'n.aidli@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Katia Rakene', 'email' => 'k.rakene@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Ilham Smah', 'email' => 'i.smah@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Houria Nouali', 'email' => 'h.nouali@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Taous Ait Abdelaziz', 'email' => 't.aitabdelaziz@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Yassine Akli', 'email' => 'y.akli@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Rania Meddaouer', 'email' => 'r.meddaouer@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Liza Amari', 'email' => 'l.amari@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Lycia Bektache', 'email' => 'l.bektache@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Liticia Ferhat', 'email' => 'l.ferhat@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Lounes Bouassel', 'email' => 'l.bouassel@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Nina Kadi', 'email' => 'n.kadi@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Ali Khaldoun', 'email' => 'a.khaldoun@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Syphax Khicha', 'email' => 's.khicha@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Nesrine Chibane', 'email' => 'n.chibane@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Mohamed Elarbi Kheyar', 'email' => 'm.kheyar@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Kassiwisdom Konou', 'email' => 'k.konou@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Massinissa Temine', 'email' => 'm.temine@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Anais Boudjema', 'email' => 'a.boudjema@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Silya Ali', 'email' => 's.ali@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Behalil Lina Ahmed', 'email' => 'l.ahmed@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Rafik Arab', 'email' => 'r.arab@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Lynda Hassaini', 'email' => 'l.hassaini@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Kenza Djelouah', 'email' => 'k.djelouah@adgeducation.com', 'promotion' => 'DWWM'],
            ['name' => 'Dahbia Sadaoui', 'email' => 'd.sadaoui@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Christevie Nganga', 'email' => 'c.nganga@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Hadjar Riah', 'email' => 'h.riah@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Aldjia Abbar', 'email' => 'a.abbar@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Mohamed Amine Boutaout', 'email' => 'm.boutaout@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Nacima Cherat', 'email' => 'n.cherat@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Zahia Sahnoun', 'email' => 'z.sahnoun@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Massinissa Boukhzzar', 'email' => 'm.boukhzzar@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Youssra El Bissouri', 'email' => 'y.elbissouri@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Alicia Ouandjeli', 'email' => 'a.ouandjeli@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Lillia Derriche', 'email' => 'l.derriche@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Tighedine Sonia', 'email' => 't.sonia@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Anissa Belkessam', 'email' => 'a.belkessam@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Lyticia Belambri', 'email' => 'l.belambri@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Miassa Mehidi', 'email' => 'm.mehidi@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Feriel Sahli', 'email' => 'f.sahli@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Amin Khanfouci', 'email' => 'a.khanfouci@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Dihia Amari', 'email' => 'd.amari@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Assia Zehnati', 'email' => 'a.zehnati@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Ange Vally', 'email' => 'a.vally@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Sakina Si-Mohammed', 'email' => 's.simohammed@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Sabrina Larbiouene', 'email' => 's.larbiouene@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Mounia Benyahia', 'email' => 'm.benyahia@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Ghiles Idris', 'email' => 'g.idris@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Fatma Haciane', 'email' => 'f.haciane@adgeducation.com', 'promotion' => 'CDA'],
            ['name' => 'Chirine Chaaban', 'email' => 'c.chaaban@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Amel Ouazar', 'email' => 'a.ouazar@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Nadira Makhlouf', 'email' => 'n.makhlouf@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Djamel-Eddine Saadi', 'email' => 'd.saadi@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Anayis Aouiche', 'email' => 'a.aouiche@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Hafid-Chaouch Anais', 'email' => 'h.chaouch@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Diab Safia', 'email' => 's.diab@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Amadou Raissa Seyni', 'email' => 'a.seyni@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Youva Si Mohamed', 'email' => 'y.simohamed@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Thileli Ouaad', 'email' => 't.ouaad@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Aghiles Chikhi', 'email' => 'a.chikhi@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Yasmine Demdoum', 'email' => 'y.demdoum@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Saifeddine Dahech', 'email' => 's.dahech@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Nafissatou Nadiaye', 'email' => 'n.nadiaye@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Amour Sumbumayd', 'email' => 'a.sumbumayd@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Rabah Moussaoui', 'email' => 'r.moussaoui@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Thiziri Thileli Talbi', 'email' => 't.talbi@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Bintou Sidibe', 'email' => 'b.sidibe@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Chanez Aliane', 'email' => 'c.aliane@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Salima Mouzaia', 'email' => 's.mouzaia@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Katia Seddiki', 'email' => 'k.seddiki@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Cylia Aberbour', 'email' => 'c.aberbour@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Rafik Benchikh', 'email' => 'r.benchikh@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Thileli Zerraki', 'email' => 't.zerraki@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'El Mounir Kaoutar', 'email' => 'e.kaoutar@adgeducation.com', 'promotion' => 'REM'],
            ['name' => 'Nadjir Boubouzal', 'email' => 'n.boubouzal@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Bekabong Muzembe', 'email' => 'b.muzembe@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Lisa Sefiane', 'email' => 'l.sefiane@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Hanane Chikhi', 'email' => 'h.chikhi@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Lyna Ighmouracene', 'email' => 'l.ighmouracene@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Yassine Ram', 'email' => 'y.ram@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Ferroudja Amara', 'email' => 'f.amara@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Imane Abchiche Chatha', 'email' => 'i.abchiche@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Lyes Ouzaiche', 'email' => 'l.ouzaiche@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Tassadit Ammadou', 'email' => 't.ammadou@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Yasmine Kheloufi', 'email' => 'y.kheloufi@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Sabrina Hamitouche', 'email' => 's.hamitouche@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Mehdi Alami', 'email' => 'm.alami@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Melissa Mezrag', 'email' => 'm.mezrag@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Amayas Doumane', 'email' => 'a.doumane@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Ameni Tarhouni', 'email' => 'a.tarhouni@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Fatah Haret', 'email' => 'f.haret@icgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Melissa Azibi', 'email' => 'm.azibi@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Frank Leonel Dieudo Njionwou', 'email' => 'f.dieudo@icgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Idir Abdelouhab', 'email' => 'i.abdelouhab@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Amar Mane', 'email' => 'a.mane@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Amara Nait Chalal', 'email' => 'a.naitchalal@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Armand Mampuya', 'email' => 'a.mampuya@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Amine Otmane', 'email' => 'a.otmane@adgeducation.com', 'promotion' => 'NTC'],
            ['name' => 'Ait Amer Dassine', 'email' => 'a.aitmammardassine@adgeducation.com', 'promotion' => 'NTC'],
        ];

        foreach ($etudiants as $etud) {
            $user = User::updateOrCreate(
                ['email' => $etud['email']],
                ['name' => $etud['name'], 'password' => Hash::make('password')]
            );
            Etudiant::updateOrCreate(
                ['user_id' => $user->id],
                ['type' => 'alt_externe', 'promotion' => $etud['promotion']]
            );
        }
    }
}
