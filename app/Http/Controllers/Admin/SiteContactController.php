<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Site\StoreSiteContactRequest;
use App\Http\Requests\Admin\Site\UpdateSiteContactRequest;
use App\Models\Site;
use App\Models\SiteContact;
use App\Services\SiteContactService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Admin SiteContactController
 *
 * Handles site contact management for Admin users
 *
 * @package App\Http\Controllers\Admin
 */
class SiteContactController extends Controller implements HasMiddleware
{
    protected SiteContactService $contactService;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(SiteContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Get all contacts for a site (AJAX).
     */
    public function index(Site $site): JsonResponse
    {
        $contacts = $this->contactService->getSiteContacts($site);

        return response()->json([
            'success' => true,
            'contacts' => $contacts
        ]);
    }

    /**
     * Store a new site contact.
     */
    public function store(StoreSiteContactRequest $request, Site $site): JsonResponse
    {
        try {
            $contact = $this->contactService->createContact($site, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Contact added successfully.',
                'contact' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific contact.
     */
    public function show(Site $site, SiteContact $contact): JsonResponse
    {
        // Verify contact belongs to site
        if ($contact->site_id !== $site->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found for this site.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'contact' => $contact
        ]);
    }

    /**
     * Update a site contact.
     */
    public function update(UpdateSiteContactRequest $request, Site $site, SiteContact $contact): JsonResponse
    {
        // Verify contact belongs to site
        if ($contact->site_id !== $site->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found for this site.'
            ], 404);
        }

        try {
            $contact = $this->contactService->updateContact($contact, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully.',
                'contact' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a site contact.
     */
    public function destroy(Site $site, SiteContact $contact): JsonResponse
    {
        // Verify contact belongs to site
        if ($contact->site_id !== $site->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found for this site.'
            ], 404);
        }

        try {
            $this->contactService->deleteContact($contact);

            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a contact as primary.
     */
    public function setPrimary(Site $site, SiteContact $contact): JsonResponse
    {
        // Verify contact belongs to site
        if ($contact->site_id !== $site->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found for this site.'
            ], 404);
        }

        try {
            $this->contactService->setPrimaryContact($contact);

            return response()->json([
                'success' => true,
                'message' => 'Primary contact updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary contact: ' . $e->getMessage()
            ], 500);
        }
    }
}
