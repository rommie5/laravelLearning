<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'Officer', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'name'     => 'Test User',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Officer');

        $this->otherUser = User::factory()->create([
            'name'     => 'Other User',
            'password' => bcrypt('password'),
        ]);
        $this->otherUser->assignRole('Officer');
    }

    /** Create a fake database notification for a user. */
    private function createNotification(User $user, array $data = []): object
    {
        $id = \Illuminate\Support\Str::uuid()->toString();

        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'id'              => $id,
            'type'            => 'App\Notifications\UserActionNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => json_encode(array_merge([
                'type'       => 'system',
                'subtype'    => 'test',
                'title'      => 'Test Notification',
                'message'    => 'This is a test.',
                'icon'       => 'bell',
                'color'      => 'gray',
                'priority'   => 'low',
                'email'      => false,
                'action_url' => '/dashboard',
            ], $data)),
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['id' => $id];
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /notifications/unread-count
    // ─────────────────────────────────────────────────────────────────────

    public function test_unread_count_returns_correct_integer(): void
    {
        $this->createNotification($this->user);
        $this->createNotification($this->user);
        // Third one is already read
        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'id'              => \Illuminate\Support\Str::uuid()->toString(),
            'type'            => 'App\Notifications\UserActionNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'data'            => json_encode(['title' => 'Read notif']),
            'read_at'    => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/notifications/unread-count');

        $response->assertOk()
            ->assertJsonFragment(['success' => true, 'count' => 2]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /notifications/{id}/read
    // ─────────────────────────────────────────────────────────────────────

    public function test_mark_notification_as_read(): void
    {
        $notif = $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->postJson("/notifications/{$notif->id}/read");

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notif->id,
        ]);

        // Verify read_at is now set
        $this->assertNotNull(
            \Illuminate\Support\Facades\DB::table('notifications')
                ->where('id', $notif->id)
                ->value('read_at')
        );
    }

    public function test_mark_read_returns_404_for_nonexistent_notification(): void
    {
        $this->actingAs($this->user)
            ->postJson('/notifications/nonexistent-uuid/read')
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /notifications/read-all
    // ─────────────────────────────────────────────────────────────────────

    public function test_mark_all_as_read(): void
    {
        $this->createNotification($this->user);
        $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->postJson('/notifications/read-all');

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        // All user's notifications should now be read
        $unread = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unread);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE /notifications/{id}
    // ─────────────────────────────────────────────────────────────────────

    public function test_delete_notification(): void
    {
        $notif = $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->deleteJson("/notifications/{$notif->id}");

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    public function test_delete_returns_404_for_nonexistent_notification(): void
    {
        $this->actingAs($this->user)
            ->deleteJson('/notifications/nonexistent-uuid')
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Cross-user security: user cannot read another user's notification
    // ─────────────────────────────────────────────────────────────────────

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $notif = $this->createNotification($this->otherUser);

        // Acting as $this->user trying to mark $otherUser's notification
        $response = $this->actingAs($this->user)
            ->postJson("/notifications/{$notif->id}/read");

        $response->assertStatus(404); // scoped query returns nothing → 404 (not 403 to avoid enumeration)
    }

    public function test_cannot_delete_another_users_notification(): void
    {
        $notif = $this->createNotification($this->otherUser);

        $response = $this->actingAs($this->user)
            ->deleteJson("/notifications/{$notif->id}");

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /notifications/data (dropdown API)
    // ─────────────────────────────────────────────────────────────────────

    public function test_data_endpoint_returns_correct_shape(): void
    {
        $this->createNotification($this->user, ['title' => 'Contract Approved', 'color' => 'green']);

        $response = $this->actingAs($this->user)
            ->getJson('/notifications/data?limit=5');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'count',
                'notifications' => [['id', 'title', 'message', 'color', 'icon', 'url', 'read', 'created_at']],
            ]);
    }
}
