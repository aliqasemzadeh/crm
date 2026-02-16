<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Announcement;
use Livewire\Livewire;
use App\Livewire\Panels\Administrator\Announcement\Index;
use App\Livewire\Panels\Administrator\Announcement\Create;
use App\Livewire\Panels\Administrator\Announcement\Edit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'mobile' => '09123456789',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        // $this->admin->givePermissionTo([
        //     'administrator_announcement_index',
        //     'administrator_announcement_create',
        //     'administrator_announcement_edit',
        //     'administrator_announcement_delete'
        // ]);
    }

    public function test_can_view_announcements_index()
    {
        $this->actingAs($this->admin);

        Livewire::test(Index::class)
            ->assertStatus(200);
    }

    public function test_can_create_announcement()
    {
        $this->actingAs($this->admin);

        Livewire::test(Create::class)
            ->set('title', 'Test Announcement')
            ->set('content', '<p>Test Content</p>')
            ->set('color', 'blue')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Test Announcement',
            'color' => 'blue',
        ]);
    }

    public function test_can_edit_announcement()
    {
        $this->actingAs($this->admin);
        $announcement = Announcement::create([
            'title' => 'Old Title',
            'content' => 'Old Content',
            'is_active' => true
        ]);

        Livewire::test(Edit::class)
            ->call('assignData', $announcement->id)
            ->set('title', 'New Title')
            ->call('edit')
            ->assertHasNoErrors();

        $this->assertEquals('New Title', $announcement->fresh()->title);
    }

    public function test_can_delete_announcement()
    {
        $this->actingAs($this->admin);
        $announcement = Announcement::create([
            'title' => 'Delete Me',
            'content' => 'Content',
            'is_active' => true
        ]);

        Livewire::test(Index::class)
            ->call('delete', $announcement->id);

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }
}
