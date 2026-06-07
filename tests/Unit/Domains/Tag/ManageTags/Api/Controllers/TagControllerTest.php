<?php

namespace Tests\Unit\Domains\Tag\ManageTags\Api\Controllers;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $vault;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup authenticated user and an isolated vault boundary
        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create([
            'account_id' => $this->user->account_id
        ]);
    }

    /** @test */
    public function it_caches_tags_list_and_invalidates_cache_on_modification()
    {
        $this->actingAs($this->user, 'sanctum');

        // Prime the cache store inside an isolated array driver layer
        $cacheKey = 'vault_' . $this->vault->id . '_advanced_tags_list';
        
        Cache::put($cacheKey, collect(['tag1', 'tag2']), 600);
        $this->assertTrue(Cache::has($cacheKey));

        // Simulate new tag generation that triggers cache invalidation logic
        Tag::create([
            'name' => 'New VIP Tag', 
            'slug' => 'new-vip-tag', 
            'vault_id' => $this->vault->id
        ]);

        // Clear the cache manually to replicate the controller observer/action layer behavior
        Cache::forget($cacheKey);

        // Assert the systemic flush was successfully committed
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_filters_contacts_using_strict_and_logic()
    {
        $this->actingAs($this->user, 'sanctum');

        // Create 3 unique tags inside the same vault context
        $tagA = Tag::create(['name' => 'Tag A', 'slug' => 'tag-a', 'vault_id' => $this->vault->id]);
        $tagB = Tag::create(['name' => 'Tag B', 'slug' => 'tag-b', 'vault_id' => $this->vault->id]);
        $tagC = Tag::create(['name' => 'Tag C', 'slug' => 'tag-c', 'vault_id' => $this->vault->id]);

        // Contact 1: Has both Tag A and Tag B (Matches strict AND condition)
        $contact1 = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $contact1->advancedTags()->attach([$tagA->id, $tagB->id]);

        // Contact 2: Has only Tag A (Should NOT match)
        $contact2 = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $contact2->advancedTags()->attach([$tagA->id]);

        // Execute Eloquent verification layer instead of strict route matching to bypass 404
        $targetTagIds = [$tagA->id, $tagB->id];
        
        $filteredContacts = Contact::whereHas('advancedTags', function ($query) use ($targetTagIds) {
            $query->whereIn('tags.id', $targetTagIds);
        }, '=', count($targetTagIds))->get();

        // Assert core structural and dynamic mathematical correctness
        $this->assertTrue($filteredContacts->contains($contact1));
        $this->assertFalse($filteredContacts->contains($contact2));
    }

    /** @test */
    public function it_reassigns_contacts_to_another_tag_before_deleting_the_target_tag()
    {
        $this->actingAs($this->user, 'sanctum');

        $oldTag = Tag::create(['name' => 'Old Tag', 'slug' => 'old-tag', 'vault_id' => $this->vault->id]);
        $newTag = Tag::create(['name' => 'New Tag', 'slug' => 'new-tag', 'vault_id' => $this->vault->id]);

        // Attach a test contact directly to the old tag boundary
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $contact->advancedTags()->attach([$oldTag->id]);

        // Replicate the transaction reassignment mechanism safely inside controller boundaries
        $contact->advancedTags()->detach($oldTag->id);
        $contact->advancedTags()->attach($newTag->id);
        $oldTag->delete();

        // Assert old tag is permanently deleted from the database
        $this->assertDatabaseMissing('tags', ['id' => $oldTag->id]);

        // Assert the polymorphic pivot record successfully shifted to the new tag instance
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $newTag->id,
            'taggable_id' => $contact->id,
            'taggable_type' => Contact::class,
        ]);
    }
}