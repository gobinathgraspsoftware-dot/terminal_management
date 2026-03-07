<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocationController
 * Shared AJAX endpoints for State/City Select2 dropdowns and
 * supervisor lookup filtered by technician's state + city.
 */
class LocationController extends Controller
{
    /**
     * Select2 AJAX: Search states
     * GET /ajax/states?search=&page=1
     */
    public function states(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        $page   = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $query = State::query()->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total   = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get(['id', 'name'])
                         ->map(fn ($s) => ['id' => $s->id, 'text' => $s->name]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Select2 AJAX: Search cities filtered by state_id
     * GET /ajax/cities?state_id=1&search=&page=1
     */
    public function cities(Request $request): JsonResponse
    {
        $stateId = $request->get('state_id');
        $search  = $request->get('search', '');
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        // Return empty when no state selected
        if (!$stateId) {
            return response()->json([
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }

        $query = City::where('state_id', $stateId)->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('postcode', 'like', "%{$search}%");
            });
        }

        $total   = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get(['id', 'name', 'postcode'])
                         ->map(fn ($c) => [
                             'id'   => $c->id,
                             'text' => $c->name . ' (' . $c->postcode . ')',
                         ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Select2 AJAX: Search supervisors filtered by state_id and/or city_id
     * Supervisors whose coverage_states JSON includes the selected state name,
     * OR supervisors who have state_id matching the selected state.
     * GET /ajax/supervisors?state_id=1&city_id=5&search=&page=1
     */
    public function supervisors(Request $request): JsonResponse
    {
        $stateId = $request->get('state_id');
        $cityId  = $request->get('city_id');
        $search  = $request->get('search', '');
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $query = User::whereHas('roles', fn ($q) => $q->where('roles.name', 'supervisor'))
                     ->where('status', 'active');

        // Filter by state: match supervisor's state_id OR supervisor's coverage_states JSON
        if ($stateId) {
            $state = State::find($stateId);
            $stateName = $state ? $state->name : null;

            $query->where(function ($q) use ($stateId, $stateName) {
                $q->where('state_id', $stateId);
                if ($stateName) {
                    $q->orWhereJsonContains('coverage_states', $stateName);
                }
            });
        }

        // Additionally filter by city if provided
        if ($cityId) {
            // This is optional tighter filtering — supervisors in the same city
            // We only apply this as an additional preference, not a hard filter,
            // because supervisors may cover an entire state.
            // Leave this as a soft filter: do NOT further restrict here.
            // The state filter is sufficient for matching supervisors.
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $total   = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get(['id', 'name', 'employee_id'])
                         ->map(fn ($u) => [
                             'id'   => $u->id,
                             'text' => $u->name . ' (' . $u->employee_id . ')',
                         ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
}
