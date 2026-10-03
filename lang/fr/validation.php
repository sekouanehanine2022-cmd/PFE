<?php

return [
    'accepted' => 'Le champ :attribute doit etre accepte.',
    'after_or_equal' => 'Le champ :attribute doit etre posterieur ou egal a :date.',
    'before_or_equal' => 'Le champ :attribute doit etre une date anterieure ou egale a aujourd hui.',
    'boolean' => 'Le champ :attribute doit etre vrai ou faux.',
    'date' => 'Le champ :attribute doit etre une date valide.',
    'email' => 'Le champ :attribute doit etre une adresse email valide.',
    'integer' => 'Le champ :attribute doit etre un nombre entier.',
    'in' => 'La valeur selectionnee pour :attribute est invalide.',
    'max' => [
        'numeric' => 'Le champ :attribute ne doit pas etre superieur a :max.',
        'file' => 'Le fichier :attribute ne doit pas depasser :max kilo-octets.',
        'string' => 'Le champ :attribute ne doit pas depasser :max caracteres.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max elements.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit etre au moins :min.',
        'file' => 'Le fichier :attribute doit faire au moins :min kilo-octets.',
        'string' => 'Le champ :attribute doit contenir au moins :min caracteres.',
        'array' => 'Le champ :attribute doit contenir au moins :min elements.',
    ],
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire lorsque :other vaut :value.',
    'same' => 'Les champs :attribute et :other doivent correspondre.',
    'string' => 'Le champ :attribute doit etre une chaine de caracteres.',
    'unique' => 'Cette valeur est deja utilisee pour :attribute.',

    'custom' => [
        'date_fin' => [
            'after_or_equal' => 'La date de fin prevue doit etre posterieure ou egale a la date de debut.',
        ],
        'reference' => [
            'regex' => 'La reference doit contenir uniquement des lettres, chiffres, tirets ou underscores.',
            'unique' => 'Cette reference existe deja.',
        ],
        'numero_serie' => [
            'regex' => 'Le numero de serie doit contenir uniquement des lettres, chiffres, espaces, points, tirets ou underscores.',
            'unique' => 'Ce numero de serie existe deja.',
        ],
        'adresse_mac' => [
            'regex' => 'L adresse MAC doit respecter le format 00:1A:2B:3C:4D:5E.',
        ],
        'cpu' => [
            'regex' => 'Le processeur contient des caracteres non autorises.',
        ],
        'ram_autre' => [
            'required_if' => 'Veuillez renseigner la RAM personnalisee.',
            'regex' => 'La RAM doit respecter un format comme 16 Go DDR5.',
        ],
        'stockage_autre' => [
            'required_if' => 'Veuillez renseigner le stockage personnalise.',
            'regex' => 'Le stockage doit respecter un format comme 512 Go SSD ou 1 To NVMe.',
        ],
        'os_autre' => [
            'required_if' => 'Veuillez renseigner le systeme d exploitation personnalise.',
            'regex' => 'Le systeme d exploitation contient des caracteres non autorises.',
        ],
        'taille' => [
            'regex' => 'La taille doit etre un nombre, par exemple 15.6.',
        ],
        'taille_autre' => [
            'required_if' => 'Veuillez renseigner la taille personnalisee.',
            'regex' => 'La taille doit etre un nombre, par exemple 27.',
        ],
        'resolution_autre' => [
            'required_if' => 'Veuillez renseigner la resolution personnalisee.',
            'regex' => 'La resolution doit respecter un format comme 1920x1080.',
        ],
        'dalle_autre' => [
            'required_if' => 'Veuillez renseigner le type de dalle personnalise.',
            'regex' => 'Le type de dalle contient des caracteres non autorises.',
        ],
        'taux_rafraichissement' => [
            'regex' => 'Le taux de rafraichissement doit etre un nombre, par exemple 60.',
        ],
        'connexion_autre' => [
            'required_if' => 'Veuillez renseigner la connexion personnalisee.',
            'regex' => 'La connexion contient des caracteres non autorises.',
        ],
        'disposition_autre' => [
            'required_if' => 'Veuillez renseigner la disposition personnalisee.',
            'regex' => 'La disposition contient des caracteres non autorises.',
        ],
        'type_impression_autre' => [
            'required_if' => 'Veuillez renseigner le type d impression personnalise.',
            'regex' => 'Le type d impression contient des caracteres non autorises.',
        ],
        'vitesse' => [
            'regex' => 'La vitesse doit respecter un format comme 38 ppm.',
        ],
        'type_cable_autre' => [
            'required_if' => 'Veuillez renseigner le type de cable personnalise.',
            'regex' => 'Le type de cable contient des caracteres non autorises.',
        ],
        'longueur' => [
            'regex' => 'La longueur doit respecter un format comme 1.5m ou 50cm.',
        ],
        'emplacement' => [
            'regex' => 'L emplacement contient des caracteres non autorises.',
        ],
    ],

    'attributes' => [
        'reference' => 'reference',
        'nom' => 'nom / modele',
        'marque' => 'marque',
        'numero_serie' => 'numero de serie',
        'adresse_mac' => 'adresse MAC',
        'cpu' => 'processeur',
        'ram' => 'RAM',
        'ram_autre' => 'RAM personnalisee',
        'stockage' => 'stockage',
        'stockage_autre' => 'stockage personnalise',
        'os' => 'systeme d exploitation',
        'os_autre' => 'systeme d exploitation personnalise',
        'taille' => 'taille',
        'taille_autre' => 'taille personnalisee',
        'ecran' => 'taille ecran',
        'resolution' => 'resolution',
        'resolution_autre' => 'resolution personnalisee',
        'dalle' => 'dalle',
        'dalle_autre' => 'dalle personnalisee',
        'taux_rafraichissement' => 'taux de rafraichissement',
        'sous_type' => 'type de peripherique',
        'connexion' => 'connexion',
        'connexion_autre' => 'connexion personnalisee',
        'disposition' => 'disposition',
        'disposition_autre' => 'disposition personnalisee',
        'retro_eclairage' => 'retro-eclairage',
        'type_impression' => 'type d impression',
        'type_impression_autre' => 'type d impression personnalise',
        'couleur' => 'couleur',
        'vitesse' => 'vitesse',
        'type_cable' => 'type de cable',
        'type_cable_autre' => 'type de cable personnalise',
        'longueur' => 'longueur',
        'quantite' => 'quantite',
        'seuil_alerte' => 'seuil d alerte',
        'etat' => 'etat',
        'emplacement' => 'emplacement',
        'date_achat' => 'date d achat',
        'date_fin' => 'date de fin prevue',
        'a_qui_id' => 'beneficiaire',
    ],
];
