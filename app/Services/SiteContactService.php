<?php

namespace App\Services;

use App\Models\SiteContact;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * SiteContactService
 *
 * Handles all business logic for Site Contact operations
 */
class SiteContactService
{
    /**
     * Get all contacts for a site
     */
    public function getSiteContacts(Site $site)
    {
        return $site->contacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('contact_name')
            ->get();
    }

    /**
     * Create a new site contact
     */
    public function createContact(Site $site, array $data): SiteContact
    {
        DB::beginTransaction();
        try {
            // If this contact is marked as primary, unset other primary contacts
            if (isset($data['is_primary']) && $data['is_primary']) {
                $site->contacts()->update(['is_primary' => false]);
            }
            
            $data['site_id'] = $site->id;
            $contact = SiteContact::create($data);
            
            DB::commit();
            return $contact;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update existing site contact
     */
    public function updateContact(SiteContact $contact, array $data): SiteContact
    {
        DB::beginTransaction();
        try {
            // If this contact is being set as primary, unset other primary contacts
            if (isset($data['is_primary']) && $data['is_primary']) {
                SiteContact::where('site_id', $contact->site_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }
            
            $contact->update($data);
            
            DB::commit();
            return $contact;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete site contact
     */
    public function deleteContact(SiteContact $contact): bool
    {
        DB::beginTransaction();
        try {
            $isPrimary = $contact->is_primary;
            $siteId = $contact->site_id;
            
            $contact->delete();
            
            // If deleted contact was primary, make the first remaining contact primary
            if ($isPrimary) {
                $firstContact = SiteContact::where('site_id', $siteId)->first();
                if ($firstContact) {
                    $firstContact->update(['is_primary' => true]);
                }
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Set contact as primary
     */
    public function setPrimaryContact(SiteContact $contact): bool
    {
        DB::beginTransaction();
        try {
            // Unset all primary contacts for this site
            SiteContact::where('site_id', $contact->site_id)
                ->update(['is_primary' => false]);
            
            // Set this contact as primary
            $contact->update(['is_primary' => true]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get primary contact for a site
     */
    public function getPrimaryContact(Site $site): ?SiteContact
    {
        return $site->contacts()
            ->where('is_primary', true)
            ->first();
    }
}
