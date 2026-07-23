<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stockage privé et chiffré des documents de sujets.
 *
 * - Les fichiers ne sont jamais accessibles via un symlink public.
 * - Le téléchargement obligatoirement passe par SubjectDocumentController::download,
 *   protégé par Gate::authorize('view', $subject).
 * - Le chiffrement utilise OpenSSL AES-256-GCM (PHP core). La clé est dérivée de APP_KEY.
 */
class DocumentStorageService
{
    private const ENCRYPTED_EXTENSION = '.enc';
    private const CIPHER = 'aes-256-gcm';

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

        $binaryKey = base64_decode(Str::after($appKey, 'base64:'), true);
        if ($binaryKey === false || strlen($binaryKey) < 32) {
            $binaryKey = $appKey;
        }

        return hash_hkdf('sha256', $binaryKey, 32, 'lmalp-document-storage');
    }

    /**
     * Stocke un fichier brut en le chiffrant.
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

        $iv = random_bytes(12); // GCM recommande 96 bits
        $tag = '';
        $key = self::deriveKey();
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) {
            throw new RuntimeException('Chiffrement OpenSSL échoué.');
        }

        $storedBase = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '-' . Str::random(8);
        $storedName = $storedBase . self::ENCRYPTED_EXTENSION;

        $dir = $this->directoryFor($subjectId, $storedName);
        $relativePath = rtrim($dir, '/') . '/' . $storedName;

        Storage::disk($this->diskName)->put($relativePath, $iv . $cipher . $tag);

        return $relativePath;
    }

    /**
     * Déchiffre un fichier et écrit le contenu clair dans un chemin temporaire.
     */
    public function decryptToFile(string $relativePath, string $destinationPath): void
    {
        $plain = $this->decrypt($relativePath);
        file_put_contents($destinationPath, $plain);
    }

    /**
     * Déchiffre le contenu et le retourne en mémoire.
     */
    public function decrypt(string $relativePath): string
    {
        $encrypted = Storage::disk($this->diskName)->get($relativePath);

        if ($encrypted === null) {
            throw new RuntimeException("Fichier introuvable dans le stockage privé : {$relativePath}");
        }

        $iv = substr($encrypted, 0, 12);
        $tag = substr($encrypted, -16);
        $cipher = substr($encrypted, 12, -16);

        $key = self::deriveKey();
        $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plain === false) {
            throw new RuntimeException("Échec du déchiffrement (clé invalide ou fichier corrompu) : {$relativePath}");
        }

        return $plain;
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
