<?php

namespace App\Support;

/**
 * Filtre les provenances techniques de collecte dans un texte Markdown
 * avant affichage, tout en conservant la traçabilité en base.
 *
 * Règles appliquées côté présentation uniquement :
 * - supprimer les métadonnées de pack (source_unique, niveau, date) ;
 * - supprimer les patterns "fils Gmail", "thread ID", labels techniques ;
 * - remplacer les chemins locaux / IDs par une formulation sobre ;
 * - conserver les références documentaires intellectuellement utiles
 *   (numéro de jugement, date, auteur/émetteur pertinent).
 */
class DocumentProvenanceCleaner
{
    /**
     * Nettoie les provenances techniques d'un texte Markdown avant rendu HTML.
     */
    public static function cleanForDisplay(string $markdown): string
    {
        // 1. Supprimer les lignes de métadonnées du pack en tête de document
        //    (source_unique, niveau, date) qu'elles soient sur une seule ligne
        //    ou réparties sur plusieurs lignes.
        $markdown = preg_replace(
            '/^\s*source_unique:\s*`.+?`\s*\([^)]+\)\s*$/miu',
            '',
            $markdown
        );
        $markdown = preg_replace('/^\s*niveau:\s*\S+\s*$/miu', '', $markdown);
        $markdown = preg_replace('/^\s*date:\s*\d{4}-\d{2}-\d{2}\s*$/miu', '', $markdown);

        // 2. Supprimer les fragments "fils Gmail, transfert par X" ou similaires.
        $markdown = preg_replace(
            '/\s*\(?\s*fils\s+Gmail\s*,?\s*transfert\s+par\s+[^)\n]+\s*\)?/iu',
            '',
            $markdown
        );

        // 3. Supprimer les mentions de thread ID / label Gmail / ID message.
        //    Capture les formes : (thread `id`), thread `id`, (thread id).
        $markdown = preg_replace(
            '/\s*\(?\s*thread\s+[`\']?[a-f0-9]{10,}[`\']?\s*\)?/iu',
            '',
            $markdown
        );
        $markdown = preg_replace('/\s*\(?\s*label\s+Gmail\s*[^)\n]*\)?/iu', '', $markdown);

        // 4. Remplacer "Gmail" isolé ou précédé de "Fil" par "Courriel".
        $markdown = preg_replace(
            '/\bFil\s+Gmail\s+(\S.*?)(\d{2}\/\d{2}\/\d{4})/iu',
            'Courriel de $1 ($2)',
            $markdown
        );
        $markdown = preg_replace('/\(\s*Gmail\s*\)/iu', '(Courriel)', $markdown);
        $markdown = preg_replace('/\bGmail\b/iu', 'Courriel', $markdown);

        // 4-bis. Nettoyer les mentions "thread" résiduelles (y compris parenthèses vides).
        $markdown = preg_replace('/\s*\(?\s*thread\s*\)?/iu', ' ', $markdown);

        // 5. Supprimer ou simplifier les chemins locaux / Drive / dossiers Hermès/CIVITAS
        //    lorsqu'ils servent à la collecte.
        $markdown = preg_replace(
            '/\(?PDF\s+local\s*,?\s*canonisé\s+dans\s+CIVITAS\)?/iu',
            '',
            $markdown
        );
        $markdown = preg_replace(
            '/\(10\s+séances\s+pertinentes\s*;?\s*corpus\s+transversal\s+CIVITAS\s*,?\s*référence\s+uniquement\)/iu',
            '',
            $markdown
        );
        $markdown = preg_replace(
            '/corpus\s+transversal\s+CIVITAS\s*,\s*référence\s+uniquement/iu',
            '',
            $markdown
        );
        $markdown = preg_replace('/\bselon\s+l\'export\s+CIVITAS\b/iu', '', $markdown);

        // Chemins absolus Unix (/home/… /tmp/…).
        $markdown = preg_replace(
            '/\/?(?:home|tmp|Users)[\/][a-zA-Z0-9_\-.\/]+/iu',
            '',
            $markdown
        );

        // 6. Supprimer les références Google Drive / Hermès / CIVITAS de collecte
        //    (mais pas les références au corpus public comme "Séraphothèque").
        $markdown = preg_replace(
            '/\(?Google\s+Drive\s*\/\s*[^)\n]+\)?/iu',
            '',
            $markdown
        );
        $markdown = preg_replace(
            '/\b[dD]ossier\s+Herm[eè]\s*\/\s*CIVITAS\s+[^\n]*/iu',
            'Document de travail',
            $markdown
        );

        // 7. Nettoyer les parenthèses vides ou contenant uniquement ponctuation résiduelle.
        $markdown = preg_replace('/\s*\(\s*[;:,]*\s*\)\s*/u', ' ', $markdown);

        // 8. Dédoublonner les ponctuations résiduelles (ex: "  ." => ".").
        $markdown = preg_replace('/\s+\./u', '.', $markdown);
        $markdown = preg_replace('/\.{2,}/u', '.', $markdown);

        // 9. Nettoyer les espaces et lignes vides multiples.
        $markdown = preg_replace('/\n{3,}/u', "\n\n", $markdown);
        $markdown = preg_replace('/[ \t]+/u', ' ', $markdown);
        $markdown = trim($markdown);

        return $markdown;
    }
}
