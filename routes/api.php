<?php

use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use App\Domains\Tag\ManageTags\Api\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the bootstrap/app.php file and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// getting token to get authenticated in postman or other API testing tools. This is just for development purposes and should be removed or protected in production.
Route::get('/get-test-token', function() {
    $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
    return $user->createToken('dev-token')->plainTextToken;
});

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // users
    Route::get('user', [UserController::class, 'user']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    // vaults
    Route::apiResource('vaults', VaultController::class);



    // Tags with Contacts
    
    // list all tags in the user account along with their respective usage counts (number of contacts associated with each tag).
    Route::get('tags', [TagController::class, 'index']);
    
    // creating new tags
    Route::post('tags', [TagController::class, 'store']);
    
    // updating a specific tag
    Route::put('tags/{id}', [TagController::class, 'update']);
    
    // deleting a specific tag and optionally reassigning its associated contacts
    Route::delete('tags/{id}', [TagController::class, 'destroy']);


    // CONTACT TAGS ATTACH/DETACH ENDPOINTS
    
    // attaching multiple tags to a contact (accepting an array of tag IDs in the request body)
    Route::post('contacts/{id}/tags', [TagController::class, 'attachTags']);
    
    // detaching a specific tag from a contact (removing the association between the contact and the tag)
    Route::delete('contacts/{id}/tags/{tagId}', [TagController::class, 'detachTag']);


    /**
     * Filter contacts by multiple tags (Strict AND logic).
     * Required by assignment: GET /api/contacts?tags[]=1&tags[]=2
     */
    Route::get('contacts-filter', [TagController::class, 'filterContacts']);
});
