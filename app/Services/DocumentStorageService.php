<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stockage privé et chiffré des documents de sujets.
 *
 * - Les fichiers ne sont jamais accessibles via un symlink public.
 * - Le téléchargement obligatoire passe par SubjectDocumentController::download,
 *   protégé par Gate::authorize('view', $subject).
 * - Le chiffrement utilise libsodium (crypto_secretbox). La clé est dérivée de APP_KEY.
 */
class DocumentStorageService
{
    // Prefixe pour distinguer les fichiers chiffrés
    private const ENCRYPTED_EXTENSION = '.enc';

    private string $diskName;

    public function __construct(string $diskName = 'documents')
    {
        $this->diskName = $diskName;
    }

    /**
     * Dérive une clé de chiffrement stable à partir de APP_KEY.
     */
    public static function deriveKey(): string
    {
        $appKey = config('app.key');

        if (! $appKey || strlen($appKey) < 32) {
            throw new RuntimeException('APP_KEY manquant ou trop court pour le chiffrement des documents.');
        }

        return hash_hkdf('sha256', $appKey, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, 'lmalp-document-storage');
    }

    /**
     * Stocke un fichier brut en le chiffrant.
     *
     * @param int $subjectId ID du sujet propriétaire.
     * @param string $sourcePath Chemin absolu du fichier source à chiffrer.
     * @param string $originalFilename Nom d'origine, utilisé seulement pour la racine du nom stocké.
     * @return string Chemin relatif dans le disk (ex: "a1/42/{uuid}.enc").
     */
    public function storeEncrypted(int $subjectId, string $sourcePath, string $originalFilename): string
    {
        if (! file_exists($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException("Fichier source illisible : {$sourcePath}");
        }

        $plain = file_get_contents($sourcePath);
        if ($plain === false) {
            throw new RuntimeException("Impossible de lire : {$sourcePath}");
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, self::deriveKey());
        sodium_memzero($plain);

        $storedBase = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '-' . Str::random(8);
        $storedName = $storedBase . self::ENCRYPTED_EXTENSION;

        $dir = $this->directoryFor($subjectId, $storedName);
        $relativePath = rtrim($dir, '/') . '/' . $storedName;

        Storage::disk($this->diskName)->put($relativePath, $nonce . $cipher);
        sodium_memzero($cipher);

        return $relativePath;
    }

    /**
     * Déchiffre un fichier et écrit le contenu clair dans un chemin temporaire.
     */
    public function decryptToFile(string $relativePath, string $destinationPath): void
    {
        $encrypted = Storage::disk($this->diskName)->get($relativePath);

        if ($encrypted === null) {
            throw new RuntimeException("Fichier introuvable dans le stockage privé : {$relativePath}");
        }

        $nonce = substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::deriveKey());
        sodium_memzero($cipher);

        if ($plain === false) {
            throw new RuntimeException("Échec du déchiffrement (clé invalide ou fichier corrompu) : {$relativePath}");
        }

        file_put_contents($destinationPath, $plain);
        sodium_memzero($plain);
    }

    /**
     * Déchiffre le contenu et le retourne en mémoire (attention à la taille).
     */
    public function decrypt(string $relativePath): string
    {
        $encrypted = Storage::disk($this->diskName)->get($relativePath);

        if ($encrypted === null) {
            throw new RuntimeException("Fichier introuvable dans le stockage privé : {$relativePath}");
        }

        $nonce = substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::deriveKey());
        sodium_memzero($cipher);

        if ($plain === false) {
            throw new RuntimeException("Échec du déchiffrement : {$relativePath}");
        }

        return $plain;
    }

    /**
     * Retourne un stream lisible du contenu déchiffré.
     */
    public function decryptStream(string $relativePath)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lmalp_doc_');
        $this->decryptToFile($relativePath, $tmp);

        $handle = fopen($tmp, 'rb');
        if (! $handle) {
            throw new RuntimeException('Impossible douvrir le stream temporaire.');
        }

        // Supprime le fichier temporaire à la fermeture du handle
        stream_set_blocking($handle, false);

        return $handle;
    }

    /**
     * Supprime un fichier du stockage privé.
     */
    public function delete(string $relativePath): bool
    {
        return Storage::disk($this->diskName)->delete($relativePath);
    }

    /**
     * Vérifie la présence d'un fichier.
     */
    public function exists(string $relativePath): bool
    {
        return Storage::disk($this->diskName)->exists($relativePath);
    }

    /**
     * Calcule le répertoire de stockage selon sujet + hash du nom.
     */
    private function directoryFor(int $subjectId, string $storedName): string
    {
        $hash = substr(sha1($storedName), 0, 2);
        return "{$hash}/{$subjectId}";
    }
}
