<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contrat public de la route canonique /sujets/{slug} :
 * si public_status = published, tous les visiteurs (Guest, standard, Citizen,
 * Admin, Moderator, Owner, Collaborator) reçoivent public_body.
 * Pas de mutation des bodies. Pas de fuite Citizen/Working.
 */
class SubjectPublicRouteAudienceTest extends TestCase
{
    use RefreshDatabase;

    private string $workingMarker;
    private string $publicBody;
    private string $citizenBody;
    private string $workingBody;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->workingMarker = 'Gel de PUBLIC-V1';
        $this->publicBody = "# V7 Public Body\n\nDossier documentaire — mis à jour le 24 août 2026.\n";
        $this->citizenBody = "# V6 Citizen Body\n\n{$this->workingMarker}\n";
        $this->workingBody = "# V6 Working Body\n\n{$this->workingMarker}\n";
    }

    private function seedTaxonomy(): array
    {
        $category = Category::factory()->create(['name' => 'Vie du village', 'slug' => 'vie-du-village']);
        $seraphotheque = SubCategory::factory()->create([
            'id' => 14,
            'category_id' => $category->id,
            'name' => 'Séraphothèque',
            'slug' => 'seraphotheque',
        ]);
        $other = SubCategory::factory()->create([
            'category_id' => $category->id,
            'name' => 'Autre',
            'slug' => 'autre',
        ]);

        return [$category, $seraphotheque, $other];
    }

    private function createSeraphothequeV7Subject(User $owner, bool $publish = true): Subject
    {
        [, $seraphotheque] = $this->seedTaxonomy();

        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $seraphotheque->category_id,
            'sub_category_id' => $seraphotheque->id,
            'theme' => 'Séraphothèque',
            'title' => 'La Séraphothèque — situation 2026',
            'slug' => 'seraphotheque-situation-2026',
            'body' => $this->workingBody,
            'citizen_body' => $this->citizenBody,
            'public_body' => $this->publicBody,
            'citizen_status' => 'draft',
            'public_status' => $publish ? 'published' : 'draft',
            'public_is_listed' => false,
        ]);

        $this->addDocuments($subject);

        return $subject->fresh();
    }

    private function addDocuments(Subject $subject): void
    {
        SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'title' => 'Public document',
            'source_reference' => 'ref:public',
            'visibility' => VisibilityLevel::Public,
        ]);
        SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'title' => 'Citizen document',
            'source_reference' => 'ref:citizen',
            'visibility' => VisibilityLevel::Citizen,
        ]);
        SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'title' => 'Working document',
            'source_reference' => 'ref:working',
            'visibility' => VisibilityLevel::Working,
        ]);
    }

    private function normalizeForComparison(string $html): string
    {
        // Normalize dynamic CSRF tokens (meta tag + form inputs) so identical logical pages compare equal.
        $html = preg_replace('/<meta name="csrf-token" content="[^"]+">/s', '<meta name="csrf-token" content="CSRF">', $html);
        $html = preg_replace('/name="_token" value="[^"]+"/s', 'name="_token" value="CSRF"', $html);

        return $html;
    }

    private function assertPublicV7Response(Subject $subject, User $user = null, string $label = 'guest'): void
    {
        $before = $this->snapshotBodies($subject);

        $response = $user === null
            ? $this->get(route('subjects.show', $subject->slug))
            : $this->actingAs($user)->get(route('subjects.show', $subject->slug));

        $response->assertOk("{$label} should see public route");

        $html = $response->baseResponse->getContent();
        $this->assertStringContainsString('V7 Public Body', $html, "{$label} sees public body");
        $this->assertStringContainsString('24 août 2026', $html, "{$label} sees V7 date marker");
        $this->assertStringNotContainsString($this->workingMarker, $html, "{$label} must not see working/citizen marker");

        $after = $this->snapshotBodies($subject);
        $this->assertSame($before, $after, "{$label} request must not mutate any body");
    }

    private function snapshotBodies(Subject $subject): array
    {
        $s = $subject->fresh();

        return [
            'body_sha' => hash('sha256', $s->body ?? ''),
            'citizen_body_sha' => hash('sha256', $s->citizen_body ?? ''),
            'public_body_sha' => hash('sha256', $s->public_body ?? ''),
        ];
    }

    /** @test */
    public function guest_sees_public_body_v7_on_seraphotheque(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);

        $this->assertPublicV7Response($subject);
    }

    /** @test */
    public function authenticated_standard_sees_public_body_v7_not_404(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $standard = User::factory()->create(['role' => 'citoyen']);

        $this->assertPublicV7Response($subject, $standard, 'authenticated standard');
    }

    /** @test */
    public function citizen_sees_public_body_v7_on_public_route(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $citizen = User::factory()->create(['role' => 'citoyen']);

        $this->assertPublicV7Response($subject, $citizen, 'citizen');
    }

    /** @test */
    public function admin_sees_public_body_v7_on_public_route(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertPublicV7Response($subject, $admin, 'admin');
    }

    /** @test */
    public function moderator_sees_public_body_v7_on_public_route(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->assertPublicV7Response($subject, $moderator, 'moderator');
    }

    /** @test */
    public function owner_sees_public_body_v7_on_public_route(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);

        $this->assertPublicV7Response($subject, $owner, 'owner');
    }

    /** @test */
    public function collaborator_sees_public_body_v7_on_public_route(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $collab = User::factory()->create(['role' => 'citoyen']);
        $subject->collaborators()->attach($collab->id);

        $this->assertPublicV7Response($subject, $collab, 'collaborator');
    }

    /** @test */
    public function guest_then_admin_then_guest_again_keeps_public_representation(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        // Guest
        $guest = $this->get(route('subjects.show', $subject->slug));
        $guest->assertOk();
        $guestHtml = $this->normalizeForComparison($guest->baseResponse->getContent());
        $this->assertStringContainsString('V7 Public Body', $guestHtml);

        // Admin
        $this->actingAs($admin)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('V7 Public Body');

        // Logout and guest again
        auth()->logout();
        $this->flushSession();

        $guestAgain = $this->get(route('subjects.show', $subject->slug));
        $guestAgain->assertOk();
        $guestAgainHtml = $this->normalizeForComparison($guestAgain->baseResponse->getContent());
        $this->assertStringContainsString('V7 Public Body', $guestAgainHtml);
        $this->assertSame($guestHtml, $guestAgainHtml, 'Guest sees identical representation after admin logout');
    }

    /** @test */
    public function public_route_does_not_leak_citizen_or_working_documents(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('subjects.show', $subject->slug));
        $html = $response->baseResponse->getContent();

        $this->assertStringContainsString('Public document', $html);
        $this->assertStringNotContainsString('Citizen document', $html, 'Citizen document must not leak on public route');
        $this->assertStringNotContainsString('Working document', $html, 'Working document must not leak on public route');
    }

    /** @test */
    public function citizen_preview_route_still_serves_citizen_body(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);

        $this->actingAs($owner)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('V6 Citizen Body')
            ->assertSee($this->workingMarker);
    }

    /** @test */
    public function public_preview_route_still_serves_public_body(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);

        $this->actingAs($owner)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertOk()
            ->assertSee('V7 Public Body')
            ->assertDontSee($this->workingMarker);
    }

    /** @test */
    public function owner_edit_route_still_reaches_working_body(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSeraphothequeV7Subject($owner);

        $this->actingAs($owner)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->assertSee('V6 Working Body')
            ->assertSee($this->workingMarker);
    }

    /** @test */
    public function normal_public_subject_serves_public_body_to_all_audiences(): void
    {
        [$category, , $otherSub] = $this->seedTaxonomy();
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'sub_category_id' => $otherSub->id,
            'theme' => 'Autre',
            'body' => $this->workingBody,
            'citizen_body' => $this->citizenBody,
            'public_body' => $this->publicBody,
            'citizen_status' => 'draft',
            'public_status' => 'published',
        ]);

        $this->get(route('subjects.show', $subject->slug))->assertOk()->assertSee('V7 Public Body')->assertDontSee($this->workingMarker);
        $this->actingAs($owner)->get(route('subjects.show', $subject->slug))->assertOk()->assertSee('V7 Public Body')->assertDontSee($this->workingMarker);
    }

    /** @test */
    public function public_unlisted_subject_serves_public_body(): void
    {
        [$category, , $otherSub] = $this->seedTaxonomy();
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'sub_category_id' => $otherSub->id,
            'theme' => 'Autre',
            'body' => $this->workingBody,
            'public_body' => $this->publicBody,
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $this->get(route('subjects.show', $subject->slug))->assertOk()->assertSee('V7 Public Body');
    }

    /** @test */
    public function citizen_only_subject_keeps_citizen_for_authenticated_and_404_for_guest(): void
    {
        [$category, , $otherSub] = $this->seedTaxonomy();
        $owner = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'sub_category_id' => $otherSub->id,
            'theme' => 'Autre',
            'body' => $this->workingBody,
            'citizen_body' => $this->citizenBody,
            'public_body' => null,
            'citizen_status' => 'published',
            'public_status' => 'draft',
        ]);

        $this->get(route('subjects.show', $subject->slug))->assertNotFound();

        $this->actingAs($citizen)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('V6 Citizen Body')
            ->assertSee($this->workingMarker);
    }

    /** @test */
    public function working_only_subject_is_visible_to_owner_not_guest(): void
    {
        [$category, , $otherSub] = $this->seedTaxonomy();
        $owner = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'sub_category_id' => $otherSub->id,
            'theme' => 'Autre',
            'body' => $this->workingBody,
            'citizen_body' => null,
            'public_body' => null,
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'status' => 'draft',
        ]);

        $this->get(route('subjects.show', $subject->slug))->assertNotFound();

        $this->actingAs($owner)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('V6 Working Body')
            ->assertSee($this->workingMarker);
    }
}
