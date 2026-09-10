<?php

namespace Tests\Unit;

use App\Support\DocumentProvenanceCleaner;
use PHPUnit\Framework\TestCase;

class DocumentProvenanceCleanerTest extends TestCase
{
    public function test_removes_pack_metadata_lines(): void
    {
        $input = "# Titre\nsource_unique: `EXPORT-LMALP.md` (CIVITAS)\nniveau: INSTRUCTION\ndate: 2026-09-09\n\nContenu.";
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringNotContainsString('source_unique', $clean);
        $this->assertStringNotContainsString('niveau:', $clean);
        $this->assertStringNotContainsString('date:', $clean);
        $this->assertStringContainsString('Contenu.', $clean);
    }

    public function test_removes_gmail_thread_id(): void
    {
        $input = 'Positions du maire (fils Gmail, transfert par Patrice Denjean) (thread `1a07ac892386db3a`).';
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringNotContainsString('fils Gmail', $clean);
        $this->assertStringNotContainsString('thread', $clean);
        $this->assertStringNotContainsString('1a07ac892386db3a', $clean);
        $this->assertStringNotContainsString('transfert par Patrice Denjean', $clean);
    }

    public function test_replaces_gmail_with_courriel(): void
    {
        $input = 'Analyse reçue via Gmail.';
        $this->assertStringContainsString(
            'Courriel',
            DocumentProvenanceCleaner::cleanForDisplay($input)
        );
    }

    public function test_removes_local_path_and_civitas_collection_note(): void
    {
        $input = 'Jugement TA (PDF local, canonisé dans CIVITAS).';
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringNotContainsString('PDF local', $clean);
        $this->assertStringNotContainsString('CIVITAS', $clean);
        $this->assertStringContainsString('Jugement TA', $clean);
    }

    public function test_removes_unix_paths(): void
    {
        $input = 'Voir /home/aur-lien/Obsidian-Vault/source.pdf et /tmp/extract.md';
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringNotContainsString('/home/', $clean);
        $this->assertStringNotContainsString('/tmp/', $clean);
        $this->assertStringNotContainsString('Obsidian-Vault', $clean);
    }

    public function test_preserves_useful_document_references(): void
    {
        $input = '- **Jugement TA n°2202355** (17/09/2024). CR-CM 2021-10-12.';
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringContainsString('Jugement TA n°2202355', $clean);
        $this->assertStringContainsString('CR-CM 2021-10-12', $clean);
    }

    public function test_removes_google_drive_path(): void
    {
        $input = 'Document stocké sur Google Drive / LMALP/HERMES ÉCHANGES/fichier.pdf.';
        $clean = DocumentProvenanceCleaner::cleanForDisplay($input);

        $this->assertStringNotContainsString('Google Drive', $clean);
        $this->assertStringNotContainsString('HERMES ÉCHANGES', $clean);
    }
}
