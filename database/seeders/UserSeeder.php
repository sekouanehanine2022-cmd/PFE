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
        $collaborateurs = [
            ['name' => 'Rafik Selmi',            'email' => 'r.selmi@intedgroup.com',       'service' => 'Direction',      'poste' => 'Responsable campus Paris'],
            ['name' => 'Kamal Yazli',             'email' => 'k.yazli@intedgroup.com',       'service' => 'Commercial',     'poste' => 'Directeur commercial'],
            ['name' => 'Celine Saib',             'email' => 'c.saib@intedgroup.com',        'service' => 'Commercial',     'poste' => 'Responsable commerciale'],
            ['name' => 'Liticia Bouslimani',      'email' => 'l.bouslimani@intedgroup.com',  'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Noumia Guezoui',          'email' => 'n.guezoui@intedgroup.com',     'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Rawda El Hosary',         'email' => 'r.elhosary@intedgroup.com',    'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Laeticia Adour',          'email' => 'l.adour@intedgroup.com',       'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Sandra Ouamdi',           'email' => 's.ouamdi@intedgroup.com',      'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Feriel Khodja',           'email' => 'f.khodja@intedgroup.com',      'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Sofia Talbi',             'email' => 's.talbi@intedgroup.com',       'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Celena Hammadou',         'email' => 'c.hammadou@intedgroup.com',    'service' => 'Commercial',     'poste' => 'Conseillère en formation IT'],
            ['name' => 'Menil Mohammedi',         'email' => 'm.mohammedi@intedgroup.com',   'service' => 'Commercial',     'poste' => 'Conseiller en formation'],
            ['name' => 'Lydia Lagab',             'email' => 'l.lagab@intedgroup.com',       'service' => 'Administration', 'poste' => 'Coordinatrice commerciale et administrative'],
            ['name' => 'Roumaissa Djaroun',       'email' => 'r.djaroun@intedgroup.com',     'service' => 'Administration', 'poste' => 'Chargée de gestion des contrats alternance'],
            ['name' => 'Melinda Hammani',         'email' => 'm.hammani@intedgroup.com',     'service' => 'Commercial',     'poste' => 'Chargée marketing'],
            ['name' => 'Hanane Didaoui',          'email' => 'h.didaoui@intedgroup.com',     'service' => 'Administration', 'poste' => 'Responsable administrative'],
            ['name' => 'Amira Lyna Haddar',       'email' => 'a.haddar@intedgroup.com',      'service' => 'Administration', 'poste' => 'Conseillère administrative'],
            ['name' => 'Sara Hafhouf',            'email' => 's.hafhouf@intedgroup.com',     'service' => 'Pédagogie',      'poste' => 'Coordinatrice pédagogique'],
            ['name' => 'Manel Ben Fradj',         'email' => 'm.benfradj@intedgroup.com',    'service' => 'Pédagogie',      'poste' => 'Assistante pédagogique'],
            ['name' => 'Soulaimy Ahamed',         'email' => 's.ahamed@intedgroup.com',      'service' => 'Support IT',     'poste' => 'Chargé support informatique'],
            ['name' => 'Ahmed Hamoudi',           'email' => 'a.hamoudi@intedgroup.com',     'service' => 'Support IT',     'poste' => 'Chargé support informatique'],
            ['name' => 'Nadine Kheddouci',        'email' => 'n.kheddouci@intedgroup.com',   'service' => 'Administration', 'poste' => 'Chargée d\'admission'],
            ['name' => 'Lisa Hamour',             'email' => 'l.hamour@intedgroup.com',      'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Liz Gars',                'email' => 'l.gars@intedgroup.com',        'service' => 'Pédagogie',      'poste' => 'Formatrice'],
            ['name' => 'Edna Ktorza',             'email' => 'e.ktorza@intedgroup.com',      'service' => 'Pédagogie',      'poste' => 'Formatrice'],
            ['name' => 'Lina Arkoub',             'email' => 'l.arkoub@intedgroup.com',      'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Hanine Sekouane',         'email' => 'h.sekouane@intedgroup.com',    'service' => 'Support IT',     'poste' => 'Chargé support informatique'],
            ['name' => 'Chanez braik',           'email' => 'c.braik@intedgroup.com',      'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Ouerdia Maked',           'email' => 'o.maked@intedgroup.com',       'service' => 'Pédagogie',      'poste' => 'Assistante pédagogique'],
            ['name' => 'Chanez Laribi',           'email' => 'c.laribi@intedgroup.com',      'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Anais Benchegra',         'email' => 'a.benchegra@intedgroup.com',   'service' => 'Administration', 'poste' => 'Chargée d\'accueil et relation client'],
            ['name' => 'Sid Ali Akhemoum',        'email' => 's.akhemoum@intedgroup.com',    'service' => 'Support IT',     'poste' => 'Chargé support informatique'],
            ['name' => 'Sandrine Benichou',       'email' => 's.benichou@intedgroup.com',    'service' => 'Pédagogie',      'poste' => 'Formatrice'],
            ['name' => 'Farid Mefidene',          'email' => 'f.mefidene@intedgroup.com',    'service' => 'Pédagogie',      'poste' => 'Formateur'],
            ['name' => 'Anis Ben Ammar',          'email' => 'a.benammar@intedgroup.com',    'service' => 'Pédagogie',      'poste' => 'Formateur'],
            ['name' => 'Ines Laiche',             'email' => 'i.laiche@intedgroup.com',      'service' => 'Pédagogie',      'poste' => 'Assistante pédagogique'],
            ['name' => 'Jaafer El Metri',         'email' => 'j.elmetri@intedgroup.com',     'service' => 'Support IT',     'poste' => 'Responsable support'],
            ['name' => 'Kahina Ziane',            'email' => 'k.ziane@intedgroup.com',       'service' => 'Administration', 'poste' => 'Conseillère administrative'],
            ['name' => 'Lina Hadj Ahmed',         'email' => 'l.hadjahmed@intedgroup.com',   'service' => 'Commercial',     'poste' => 'Conseillère en formation'],
            ['name' => 'Nejiba Mganem',           'email' => 'n.mganem@intedgroup.com',      'service' => 'Direction',      'poste' => 'Directrice qualité'],
            ['name' => 'Yassine Kalboussi',       'email' => 'y.kalboussi@intedgroup.com',   'service' => 'Administration', 'poste' => 'Assistant comptable'],
            ['name' => 'Imene Ben Hassen',        'email' => 'i.benhassen@intedgroup.com',   'service' => 'Direction',      'poste' => 'Directrice administrative et financière'],
            ['name' => 'Lamis Sassi',             'email' => 'l.sassi@intedgroup.com',       'service' => 'Administration', 'poste' => 'Responsable des Ressources Humaines'],
        ];

        foreach ($collaborateurs as $collab) {
            $user = User::updateOrCreate(
                ['email' => $collab['email']],
                ['name' => $collab['name'], 'password' => Hash::make('password')]
            );
            Personnel::updateOrCreate(
                ['user_id' => $user->id],
                ['service' => $collab['service'], 'poste' => $collab['poste'], 'role' => 'technicien']
            );
        }

        // ---- ÉTUDIANTS ----
        $etudiants = [
            ['name' => 'Lydia Abdou',               'email' => 'l.abdou@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Nabila Aidli',              'email' => 'n.aidli@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Katia Rakene',              'email' => 'k.rakene@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Ilham Smah',                'email' => 'i.smah@adgeducation.com',               'promotion' => 'TP MEM 2024'],
            ['name' => 'Houria Nouali',             'email' => 'h.nouali@adgeducation.com',             'promotion' => 'TP NTC 2024'],
            ['name' => 'Taous Ait Abdelaziz',       'email' => 't.aitabdelaziz@adgeducation.com',       'promotion' => 'TP REM 2024'],
            ['name' => 'Yassine Akli',              'email' => 'y.akli@adgeducation.com',               'promotion' => 'TP CV 2024'],
            ['name' => 'Rania Meddaouer',           'email' => 'r.meddaouer@adgeducation.com',          'promotion' => 'TP MEM 2024'],
            ['name' => 'Liza Amari',                'email' => 'l.amari@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Lycia Bektache',            'email' => 'l.bektache@adgeducation.com',           'promotion' => 'TP REM 2024'],
            ['name' => 'Liticia Ferhat',            'email' => 'l.ferhat@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Lounes Bouassel',           'email' => 'l.bouassel@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Nina Kadi',                 'email' => 'n.kadi@adgeducation.com',               'promotion' => 'TP NTC 2024'],
            ['name' => 'Ali Khaldoun',              'email' => 'a.khaldoun@adgeducation.com',           'promotion' => 'TP REM 2024'],
            ['name' => 'Syphax Khicha',             'email' => 's.khicha@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Nesrine Chibane',           'email' => 'n.chibane@adgeducation.com',            'promotion' => 'TP MEM 2024'],
            ['name' => 'Mohamed Elarbi Kheyar',     'email' => 'm.kheyar@adgeducation.com',             'promotion' => 'TP NTC 2024'],
            ['name' => 'Kassiwisdom Konou',         'email' => 'k.konou@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Massinissa Temine',         'email' => 'm.temine@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Anais Boudjema',            'email' => 'a.boudjema@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Silya Ali',                 'email' => 's.ali@adgeducation.com',                'promotion' => 'TP NTC 2024'],
            ['name' => 'Lina Ahmed',                'email' => 'l.ahmed@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Rafik Arab',                'email' => 'r.arab@adgeducation.com',               'promotion' => 'TP CV 2024'],
            ['name' => 'Lynda Hassaini',            'email' => 'l.hassaini@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Kenza Djelouah',            'email' => 'k.djelouah@adgeducation.com',           'promotion' => 'TP NTC 2024'],
            ['name' => 'Dahbia Sadaoui',            'email' => 'd.sadaoui@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Christevie Nganga',         'email' => 'c.nganga@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Hadjar Riah',               'email' => 'h.riah@adgeducation.com',               'promotion' => 'TP MEM 2024'],
            ['name' => 'Aldjia Abbar',              'email' => 'a.abbar@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Mohamed Amine Boutaout',    'email' => 'm.boutaout@adgeducation.com',           'promotion' => 'TP REM 2024'],
            ['name' => 'Nacima Cherat',             'email' => 'n.cherat@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Zahia Sahnoun',             'email' => 'z.sahnoun@adgeducation.com',            'promotion' => 'TP MEM 2024'],
            ['name' => 'Massinissa Boukhzzar',      'email' => 'm.boukhzzar@adgeducation.com',          'promotion' => 'TP NTC 2024'],
            ['name' => 'Youssra El Bissouri',       'email' => 'y.elbissouri@adgeducation.com',         'promotion' => 'TP REM 2024'],
            ['name' => 'Alicia Ouandjeli',          'email' => 'a.ouandjeli@adgeducation.com',          'promotion' => 'TP CV 2024'],
            ['name' => 'Lillia Derriche',           'email' => 'l.derriche@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Tighedine Sonia',           'email' => 't.sonia@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Anissa Belkessam',          'email' => 'a.belkessam@adgeducation.com',          'promotion' => 'TP REM 2024'],
            ['name' => 'Lyticia Belambri',          'email' => 'l.belambri@adgeducation.com',           'promotion' => 'TP CV 2024'],
            ['name' => 'Miassa Mehidi',             'email' => 'm.mehidi@adgeducation.com',             'promotion' => 'TP MEM 2024'],
            ['name' => 'Feriel Sahli',              'email' => 'f.sahli@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Amin Khanfouci',            'email' => 'a.khanfouci@adgeducation.com',          'promotion' => 'TP REM 2024'],
            ['name' => 'Dihia Amari',               'email' => 'd.amari@adgeducation.com',              'promotion' => 'TP CV 2024'],
            ['name' => 'Assia Zehnati',             'email' => 'a.zehnati@adgeducation.com',            'promotion' => 'TP MEM 2024'],
            ['name' => 'Ange Vally',                'email' => 'a.vally@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Sakina Si-Mohammed',        'email' => 's.simohammed@adgeducation.com',         'promotion' => 'TP REM 2024'],
            ['name' => 'Sabrina Larbiouene',        'email' => 's.larbiouene@adgeducation.com',         'promotion' => 'TP CV 2024'],
            ['name' => 'Mounia Benyahia',           'email' => 'm.benyahia@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Ghiles Idris',              'email' => 'g.idris@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Fatma Haciane',             'email' => 'f.haciane@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Chirine Chaaban',           'email' => 'c.chaaban@adgeducation.com',            'promotion' => 'TP CV 2024'],
            ['name' => 'Amel Ouazar',               'email' => 'a.ouazar@adgeducation.com',             'promotion' => 'TP MEM 2024'],
            ['name' => 'Nadira Makhlouf',           'email' => 'n.makhlouf@adgeducation.com',           'promotion' => 'TP NTC 2024'],
            ['name' => 'Djamel-Eddine Saadi',       'email' => 'd.saadi@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Anayis Aouiche',            'email' => 'a.aouiche@adgeducation.com',            'promotion' => 'TP CV 2024'],
            ['name' => 'Hafid Chaouch',             'email' => 'h.chaouch@adgeducation.com',            'promotion' => 'TP MEM 2024'],
            ['name' => 'Safia Diab',                'email' => 's.diab@adgeducation.com',               'promotion' => 'TP NTC 2024'],
            ['name' => 'Amadou Raissa Seyni',       'email' => 'a.seyni@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Youva Si Mohamed',          'email' => 'y.simohamed@adgeducation.com',          'promotion' => 'TP CV 2024'],
            ['name' => 'Thileli Ouaad',             'email' => 't.ouaad@adgeducation.com',              'promotion' => 'TP MEM 2024'],
            ['name' => 'Aghiles Chikhi',            'email' => 'a.chikhi@adgeducation.com',             'promotion' => 'TP NTC 2024'],
            ['name' => 'Yasmine Demdoum',           'email' => 'y.demdoum@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Saifeddine Dahech',         'email' => 's.dahech@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Nafissatou Nadiaye',        'email' => 'n.nadiaye@adgeducation.com',            'promotion' => 'TP MEM 2024'],
            ['name' => 'Amour Sumbumayd',           'email' => 'a.sumbumayd@adgeducation.com',          'promotion' => 'TP NTC 2024'],
            ['name' => 'Rabah Moussaoui',           'email' => 'r.moussaoui@adgeducation.com',          'promotion' => 'TP REM 2024'],
            ['name' => 'Thiziri Talbi',             'email' => 't.talbi@adgeducation.com',              'promotion' => 'TP CV 2024'],
            ['name' => 'Bintou Sidibe',             'email' => 'b.sidibe@adgeducation.com',             'promotion' => 'TP MEM 2024'],
            ['name' => 'Chanez Aliane',             'email' => 'c.aliane@adgeducation.com',             'promotion' => 'TP NTC 2024'],
            ['name' => 'Salima Mouzaia',            'email' => 's.mouzaia@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Katia Seddiki',             'email' => 'k.seddiki@adgeducation.com',            'promotion' => 'TP CV 2024'],
            ['name' => 'Cylia Aberbour',            'email' => 'c.aberbour@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Rafik Benchikh',            'email' => 'r.benchikh@adgeducation.com',           'promotion' => 'TP NTC 2024'],
            ['name' => 'Thileli Zerraki',           'email' => 't.zerraki@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'El Mounir Kaoutar',         'email' => 'e.kaoutar@adgeducation.com',            'promotion' => 'TP CV 2024'],
            ['name' => 'Nadjir Boubouzal',          'email' => 'n.boubouzal@adgeducation.com',          'promotion' => 'TP MEM 2024'],
            ['name' => 'Bekabong Muzembe',          'email' => 'b.muzembe@adgeducation.com',            'promotion' => 'TP NTC 2024'],
            ['name' => 'Lisa Sefiane',              'email' => 'l.sefiane@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Hanane Chikhi',             'email' => 'h.chikhi@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Lyna Ighmouracene',         'email' => 'l.ighmouracene@adgeducation.com',       'promotion' => 'TP MEM 2024'],
            ['name' => 'Yassine Ram',               'email' => 'y.ram@adgeducation.com',                'promotion' => 'TP NTC 2024'],
            ['name' => 'Ferroudja Amara',           'email' => 'f.amara@adgeducation.com',              'promotion' => 'TP REM 2024'],
            ['name' => 'Imane Abchiche',            'email' => 'i.abchiche@adgeducation.com',           'promotion' => 'TP CV 2024'],
            ['name' => 'Lyes Ouzaiche',             'email' => 'l.ouzaiche@adgeducation.com',           'promotion' => 'TP MEM 2024'],
            ['name' => 'Tassadit Ammadou',          'email' => 't.ammadou@adgeducation.com',            'promotion' => 'TP NTC 2024'],
            ['name' => 'Yasmine Kheloufi',          'email' => 'y.kheloufi@adgeducation.com',           'promotion' => 'TP REM 2024'],
            ['name' => 'Sabrina Hamitouche',        'email' => 's.hamitouche@adgeducation.com',         'promotion' => 'TP CV 2024'],
            ['name' => 'Mehdi Alami',               'email' => 'm.alami@adgeducation.com',              'promotion' => 'TP MEM 2024'],
            ['name' => 'Melissa Mezrag',            'email' => 'm.mezrag@adgeducation.com',             'promotion' => 'TP NTC 2024'],
            ['name' => 'Amayas Doumane',            'email' => 'a.doumane@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Ameni Tarhouni',            'email' => 'a.tarhouni@adgeducation.com',           'promotion' => 'TP CV 2024'],
            ['name' => 'Fatah Haret',               'email' => 'f.haret@adgeducation.com',              'promotion' => 'TP MEM 2024'],
            ['name' => 'Melissa Azibi',             'email' => 'm.azibi@adgeducation.com',              'promotion' => 'TP NTC 2024'],
            ['name' => 'Frank Leonel Dieudo',       'email' => 'f.dieudo@adgeducation.com',             'promotion' => 'TP REM 2024'],
            ['name' => 'Idir Abdelouhab',           'email' => 'i.abdelouhab@adgeducation.com',         'promotion' => 'TP CV 2024'],
            ['name' => 'Amar Mane',                 'email' => 'a.mane@adgeducation.com',               'promotion' => 'TP MEM 2024'],
            ['name' => 'Amara Nait Chalal',         'email' => 'a.naitchalal@adgeducation.com',         'promotion' => 'TP NTC 2024'],
            ['name' => 'Armand Mampuya',            'email' => 'a.mampuya@adgeducation.com',            'promotion' => 'TP REM 2024'],
            ['name' => 'Amine Otmane',              'email' => 'a.otmane@adgeducation.com',             'promotion' => 'TP CV 2024'],
            ['name' => 'Ait Mammar Dassine',        'email' => 'a.aitmammardassine@adgeducation.com',   'promotion' => 'TP MEM 2024'],
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