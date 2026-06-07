<?php

namespace App\Domains\Tag\ManageTags;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvancedTagManager
{
    /**
     * all tag and uses count with 10 minutes Redis cache
     */
    public function getAllTagsWithCache(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('advanced_tags_list', 600, function () {
            return Tag::withCount('advancedContacts')->get();
        });
    }

    /**
     * creating new gloabl tag
     */
    public function createTag(array $data): Tag
    {
        $data['slug'] = Str::slug($data['name']);
        $tag = Tag::create($data);

        Cache::forget('advanced_tags_list');
        return $tag;
    }

    /**
     * update a existing tag
     */
    public function updateTag(Tag $tag, array $data): Tag
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);
        Cache::forget('advanced_tags_list');
        return $tag;
    }

    /**
     * tag delete and polymorphic reassignment
     */
    public function deleteTagAndReassign(Tag $tag, ?int $reassignTagId): void
    {
        DB::transaction(function () use ($tag, $reassignTagId) {
            if ($reassignTagId && $reassignTagId != $tag->id) {
                $taggables = $tag->advancedContacts()->get();

                if ($taggables->isNotEmpty()) {
                    $newTag = Tag::findOrFail($reassignTagId);
                    foreach ($taggables as $pivotRow) {
                        $newTag->advancedContacts()->syncWithoutDetaching([$pivotRow->id]);
                    }
                }
            }

            // clear old pivot table records before deleting
            DB::table('taggables')->where('tag_id', $tag->id)->delete();
            $tag->delete();
        });

        Cache::forget('advanced_tags_list');
    }

    /**
     * sync multiple tags to any model
     */
    public function attachTagsToModel(Model $model, array $tagIds): void
    {
        $model->advancedTags()->syncWithoutDetaching($tagIds);
        Cache::forget('advanced_tags_list');
    }

    /**
     * detach single tag from any model
     */
    public function detachTagFromModel(Model $model, int $tagId): void
    {
        $model->advancedTags()->detach($tagId);
        Cache::forget('advanced_tags_list');
    }

    /**
     * Dynnamic AND filtering logic (Single SQL)
     */
    public function getFilteredModelResults(Model $modelInstance, array $tagIds, int $perPage = 15)
    {
        $query = $modelInstance->newQuery();

        if (!empty($tagIds)) {
            $query->whereHas('advancedTags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            }, '=', count($tagIds)); 
        }

        $sortField = request()->input('sort', 'id');
        $query->orderBy($sortField);

        return $query->with('advancedTags')->paginate($perPage);
    }
}