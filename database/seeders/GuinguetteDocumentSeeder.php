<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Services\DocumentStorageService;
use Illuminate\Database\Seeder;
use App\Models\RepresentationType;
use App\Models\VisibilityLevel;

class GuinguetteDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'guinguette-urbanisme')->firstOrFail();
        $subject->loadMissing('subCategory');

        if (
            $subject->category_id === null
            || $subject->sub_category_id === null
            || $subject->subCategory?->category_id !== $subject->category_id
        ) {
            throw new \RuntimeException(
                "Taxonomie invalide pour guinguette-urbanisme : category_id={$subject->category_id}, sub_category_id={$subject->sub_category_id}."
            );
        }

        $storage = app(DocumentStorageService::class);

        // Représentation primaire : jugement PDF
        $docs = [
            [
                'title' => 'Jugement du Tribunal administratif de Nîmes, affaire n°2202355',
                'document_date' => '2024-09-17',
                'document_type' => 'decree',
                'author' => 'Tribunal administratif de Nîmes',
                'recipient' => 'Partie publique (requérante Mme Frank, défendeurs Préfet de Lozère, Commune du Rozier)',
                'source_reference' => 'manual:guinguette:ta-2202355-2024-09-17',
                'representation_type' => RepresentationType::Original,
                'redacted' => false,
                'establishes' => 'Chronologie : courrier Mme Frank réceptionné 27/05/2022 ; refus implicite 27/07/2022 ; requête TA 01/08/2022 ; PV n°386/2022 par gendarmerie ; courrier procureur 02/02/2024 (classement sans suite). Erreur matérielle « maire de Saint Quentin la Poterie ». Rejet indemnitaire (préjudice non démontré). Non-lieu sur annulation/injonction (PV dressé).',
                'limitations' => 'Le tribunal ne tranche pas la faute au fond. Ne déclare pas que le PV a été dressé à la demande du maire. Ne constate pas la conformité des installations. Les pièces produites par les parties ne sont pas incluses dans le PDF jugement.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/01-sujets/guinguette-urbanisme/sources/2024-09-17-TA-Nimes-2202355-jugement.pdf',
                'source_sha256' => 'cd54b90d7b61774f4be32136af164ecb485d7dee64b5ec40c3fae857b23c4315',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 27 septembre 2019 — Voirie Domaine Valgalier',
                'document_date' => '2019-09-27',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2019-09-27',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Mention d’une voirie entre Domaine Valgalier et le bureau.',
                'limitations' => 'Lien avec la Guinguette non explicite dans le CR. Aucun acte d’urbanisme n’est mentionné.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2019/2019-09-27.md',
                'source_sha256' => 'bc0300fae3f0efb16997c49a8ec0284542452e647554efd413db04201245ef7c',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 10 février 2020 — Personnel saisonnier',
                'document_date' => '2020-02-10',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2020-02-10',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Proposition d’embauche d’Estelle Frank comme agente saisonnière au camping municipal (contrat 6 mois).',
                'limitations' => 'Le contexte est strictement personnel/employeur. Aucun lien avec l’urbanisme n’est établi par le document.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2020/2020-02-10.md',
                'source_sha256' => '06ae6edbfafe7f880662ba2d18167138a38f883d074be94399d872177684ecff',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 7 juin 2021 — Container Valgalier',
                'document_date' => '2021-06-07',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2021-06-07',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Un container est installé chez Walter Valgalier. Les services d’urbanisme de la CCMGC l’indiquent comme « tolérable tant que provisoire (3 mois) », en attendant la révision du PLUi.',
                'limitations' => 'Il n’est pas établi qu’une décision d’urbanisme a été prise. Le statut « tolérable » est présenté comme une indication informelle de services. Aucune trace de PV d’infraction à ce stade.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2021/2021-06-07.md',
                'source_sha256' => 'ca2d2a258efae03d66698fae44d6f09bab84b841f0eaf83a8864f16c4f4de5f3',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 12 octobre 2021 — La Guinguette, « matériel déplaçable »',
                'document_date' => '2021-10-12',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2021-10-12',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Un container et un chalet en bois servant à « La Guinguette » sont présents. Le maire les qualifie de « matériel déplaçable ». Il évoque une demande d’extension « en dur » freinée par le zonage PLUi, et une stratégie potentielle (extension du bâti actuel puis changement de destination).',
                'limitations' => 'Qualification « matériel déplaçable » = assertion dans le PV, pas une qualification technique d’un service d’urbanisme. Aucune autorisation n’est mentionnée. Contradiction interne avec les DP/PC ultérieurs (démarches pour du « matériel déplaçable » ?).',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2021/2021-10-12.md',
                'source_sha256' => '346f3a1834000acacef3b7ded37b44f3327cb3bc806e15c199197f3b69ad202d',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 2 juin 2022 — Litige urbanisme Guinguette',
                'document_date' => '2022-06-02',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2022-06-02',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Deux déclarations préalables déposées les 10 et 17 mars 2022. Un permis de construire provisoire déposé le 10 mai 2022, en instruction. Un courrier de précontentieux en recommandé avec accusé de réception est envoyé par Me Enard-Bazire (Mme Frank), demandant la mise en demeure du maire de dresser un PV d’infraction et réclamant 30 000 EUR. Le CM approuve à l’unanimité la sollicitation d’un avocat spécialisé. Avis favorable à l’unanimité pour le transfert de licence IV vers « La Guinguette — La Baraquita ».',
                'limitations' => 'Dates exactes d’envoi et teneur exacte du courrier de précontentieux non précisées dans le CR. Décisions sur les DP et le PC non mentionnées. Aucune trace du PV n°386/2022 dans ce CR (alors que chronologiquement il aurait déjà pu être rédigé). Le transfert de licence IV est voté mais le dispositif n’est pas présent.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2022/2022-06-02.md',
                'source_sha256' => '3751d44961b5335d606f418a2df3ec55ff8a10567e989acced3eeeeccf885474',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 28 juin 2022 — Contentieux Guinguette, échange de superficies',
                'document_date' => '2022-06-28',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2022-06-28',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Mme Van de Put demande si le retrait de délégation est lié au contentieux Frank/Valgalier ; le maire évite de répondre directement. Contact pris avec Me Jean-Marc Maillot ; attente de ses conclusions. Mention d’un « échange de superficies » pour les WC PMR « comme convenu verbalement entre les parties ».',
                'limitations' => 'Le lien entre retrait de délégation et contentieux n’est ni établi ni infirmé. L’échange de superficies est qualifié de « verbal » : aucun acte écrit n’est identifié. La relation avec les parcels n°165/415/513 n’est pas établie.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2022/2022-06-28.md',
                'source_sha256' => 'ead10c720144e0bd17d87dd507f4a5aabe4930c45fcd6c1340bd41d876f8ba7c',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 29 mars 2023 — Extension Guinguette, fouilles archéologiques',
                'document_date' => '2023-03-29',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2023-03-29',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => 'Un permis de construire pour extension est « en cours d’instruction ». La DRAC impose des fouilles archéologiques préventives. La révision du PLUi est « presque achevée ». Des installations sur remorques sont prévues pour la saison. Le maire signale que le procureur n’a pas souhaité poursuivre Mme Frank ni M. Valgalier suite aux dépositions faites à la gendarmerie de Meyrueis « l’année précédente ».',
                'limitations' => 'Statut final du PC 2023 inconnu. Pas de trace de décision ABF/DRAC. Le classement sans suite est rapporté mais le PV, la décision du procureur et les motifs ne sont pas accessibles. L’assertion du maire « une même affaire ne peut pas être jugée deux fois » est juridiquement imprécise.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2023/2023-03-29.md',
                'source_sha256' => 'b50acfbc248f1aaa902ccec18edd407ddd9a857bc34d3516d9813e11e4ee2f84',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 21 octobre 2025 — Nouveau permis Guinguette',
                'document_date' => '2025-10-21',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2025-10-21',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => '« Mr Valgalier, propriétaire de ‘La Guinguette’ va déposer un nouveau permis, rdv est pris avec les Bâtiments de France le 17 novembre prochain, sur site. »',
                'limitations' => 'Teneur exacte du nouveau permis inconnue. Avis ABF, décision, date d’inscription, date de délibération (si applicable) inconnus. Seule l’évocation en séance est établie.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2025/2025-10-21.md',
                'source_sha256' => '7b21e9f0cc7d35cb026071b8f28b7b6856d63370929d37fbe0ae0d423cacc371',
            ],
            [
                'title' => 'Procès-verbal du conseil municipal du Rozier, 11 février 2026 — Extension La Guinguette, orientations budgétaires',
                'document_date' => '2026-02-11',
                'document_type' => 'report',
                'author' => 'Secrétariat de mairie du Rozier',
                'recipient' => 'Conseil municipal du Rozier',
                'source_reference' => 'manual:guinguette:cm-2026-02-11',
                'representation_type' => RepresentationType::Transcription,
                'redacted' => false,
                'establishes' => '« L’enfouissement d’une partie des lignes EDF (Route de Florac) pour permettre à Mr Valgalier la construction de son extension de la Guinguette » est inscrit dans les orientations budgétaires 2026.',
                'limitations' => 'Lien de causalité budgétaire ⟷ autorisation non établi. Une inscription budgétaire n’est pas un permis de construire. Aucune décision d’urbanisme n’est documentée.',
                'source_path' => '/home/aur-lien/Obsidian-Vault-Majordome/CIVITAS/90-corpus-transversaux/cr-cm/2026/2026-02-11.md',
                'source_sha256' => 'bf83139dfe352ae24d9b318218bf008de4dc480df1aca6e6ae60b4c6318665ee',
            ],
        ];

        $position = 0;
        foreach ($docs as $meta) {
            $sourcePath = $meta['source_path'];
            if (! file_exists($sourcePath) || ! is_readable($sourcePath)) {
                throw new \RuntimeException("Fichier source illisible : {$sourcePath}");
            }

            $currentSha256 = hash_file('sha256', $sourcePath);
            if ($currentSha256 !== $meta['source_sha256']) {
                throw new \RuntimeException("SHA-256 mismatch pour {$sourcePath}. Attendu {$meta['source_sha256']}, obtenu {$currentSha256}");
            }

            $path = $storage->storeEncrypted($subject->id, $sourcePath, basename($sourcePath));

            SubjectDocument::create([
                'subject_id' => $subject->id,
                'filename' => basename($sourcePath),
                'stored_filename' => basename($path),
                'path' => $path,
                'disk' => 'documents',
                'mime_type' => mime_content_type($sourcePath) ?: 'application/octet-stream',
                'size' => filesize($sourcePath),
                'title' => $meta['title'],
                'document_date' => $meta['document_date'],
                'document_type' => $meta['document_type'],
                'author' => $meta['author'],
                'recipient' => $meta['recipient'] ?? null,
                'source_reference' => $meta['source_reference'],
                'representation_type' => $meta['representation_type'],
                'redacted' => $meta['redacted'],
                'visibility' => VisibilityLevel::Working->value,
                'establishes' => $meta['establishes'],
                'limitations' => $meta['limitations'],
                'position' => ++$position,
                'source_sha256' => $currentSha256,
            ]);
        }
    }
}
