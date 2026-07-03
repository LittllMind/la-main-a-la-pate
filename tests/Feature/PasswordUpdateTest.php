<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldPassword123!')]);

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'oldPassword123!',
            'password' => 'newPassword123!',
            'password_confirmation' => 'newPassword123!',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('newPassword123!', $user->password));
    }

    public function test_user_cannot_update_password_without_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'newPassword123!',
            'password_confirmation' => 'newPassword123!',
        ])->assertSessionHasErrors(['current_password'], null, 'updatePassword');

        $user->refresh();
        $this->assertFalse(Hash::check('newPassword123!', $user->password));
    }
}
