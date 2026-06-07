<?php

namespace App\Domains\Tag\ManageTags\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Contact;
use App\Domains\Tag\ManageTags\AdvancedTagManager; // নতুন ম্যানেজার নেমস্পেস
use Illuminate\Http\Request;

class TagController extends Controller
{
    protected $tagManager;

    public function __construct(AdvancedTagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * List all tags in the user account along with their usage counts.
     */
    public function index()
    {
        $tags = $this->tagManager->getAllTagsWithCache();
        return response()->json(['data' => $tags], 200);
    }

    /**
     * Create a new tag.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tag_category' => 'nullable|string|max:255',
            'vault_id' => 'required|string',
        ]);

        $tag = $this->tagManager->createTag($validated);
        return response()->json(['message' => 'Tag created successfully', 'data' => $tag], 201);
    }

    /**
     * Update an existing tag.
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'tag_category' => 'nullable|string|max:255',
        ]);

        $updatedTag = $this->tagManager->updateTag($tag, $validated);
        return response()->json(['message' => 'Tag updated successfully', 'data' => $updatedTag], 200);
    }

    /**
     * Delete a specific tag and optionally reassign its associated contacts.
     */
    public function destroy(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $reassignTagId = $request->input('reassign_tag_id');

        $this->tagManager->deleteTagAndReassign($tag, $reassignTagId);
        return response()->json(['message' => 'Tag processed and deleted successfully'], 200);
    }

    /**
     * Filter contacts by multiple tags (Strict AND logic).
     * Example: GET /api/contacts?tags[]=1&tags[]=2
     */
    public function filterContacts(Request $request)
    {
        // Query parameters to get an array of tag IDs (?tags[]=1&tags[]=2)
        $tagIds = $request->input('tags', []);
        $perPage = $request->input('per_page', 15);

        // Pass the Contact model object to the dynamic filtering method
        $contacts = $this->tagManager->getFilteredModelResults(new Contact(), $tagIds, $perPage);

        return response()->json($contacts, 200);
    }

    /**
     * Attach multiple tags to a specific contact.
     */
    public function attachTags(Request $request, $id)
    {
        $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $contact = Contact::findOrFail($id);
        $this->tagManager->attachTagsToModel($contact, $request->input('tag_ids'));

        return response()->json(['message' => 'Tags attached successfully'], 200);
    }

    /**
     * Detach a specific tag from a contact.
     */
    public function detachTag($id, $tagId)
    {
        $contact = Contact::findOrFail($id);
        $this->tagManager->detachTagFromModel($contact, $tagId);

        return response()->json(['message' => 'Tag detached successfully'], 200);
    }
}