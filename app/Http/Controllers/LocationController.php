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
 *
 * SUPERVISOR TYPES:
 * - When fetching supervisors for technician assignment (default): returns INTERNAL only
 * - When type=all is passed: returns all supervisors (for admin views)
 * - When type=internal or type=external is passed: returns that specific type
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
                             'id'       => $c->id,
                             'text'     => $c->name . ' (' . $c->postcode . ')',
                             'postcode' => $c->postcode,
                         ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Select2 AJAX: Search supervisors filtered by state_id and/or city_id
     * GET /ajax/supervisors?state_id=1&city_id=5&search=&page=1&type=internal
     *
     * Type filter:
     *   - type=internal  → Only internal supervisors (has team) — DEFAULT for technician assignment
     *   - type=external  → Only external supervisors (no team)
     *   - type=all       → All supervisors regardless of type
     *   - (empty/none)   → Defaults to 'internal' (safe default for technician assignment)
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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filter by supervisor_type
        // Default to 'internal' for technician assignment safety
        $type = $request->get('type', 'internal');

        if ($type === 'all') {
            // No type filter — return all supervisors
        } elseif (in_array($type, ['internal', 'external'])) {
            $query->where('supervisor_type', $type);
        } else {
            // Unknown type — default to internal only
            $query->where('supervisor_type', 'internal');
        }

        $total   = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get(['id', 'name', 'employee_id', 'state_id', 'city_id', 'mileage_rate', 'supervisor_type'])
                         ->map(fn ($u) => [
                             'id'              => $u->id,
                             'text'            => $u->name . ' (' . $u->employee_id . ')' . ($u->supervisor_type === 'external' ? ' [External]' : ''),
                             'state_id'        => $u->state_id,
                             'city_id'         => $u->city_id,
                             'mileage_rate'    => $u->mileage_rate,
                             'supervisor_type' => $u->supervisor_type,
                         ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * AJAX: Get supervisor detail (state, city, mileage_rate, supervisor_type)
     * Used by technician create/edit form to auto-populate inherited fields.
     * GET /ajax/supervisor-detail?id=5
     */
    public function supervisorDetail(Request $request): JsonResponse
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Supervisor ID required'], 422);
        }

        $supervisor = User::with(['state', 'city'])
            ->whereHas('roles', fn ($q) => $q->where('roles.name', 'supervisor'))
            ->find($id);

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Supervisor not found'], 404);
        }

        return response()->json([
            'success'         => true,
            'state_id'        => $supervisor->state_id,
            'state_name'      => $supervisor->state?->name,
            'city_id'         => $supervisor->city_id,
            'city_name'       => $supervisor->city ? $supervisor->city->name . ' (' . $supervisor->city->postcode . ')' : null,
            'mileage_rate'    => $supervisor->mileage_rate,
            'supervisor_type' => $supervisor->supervisor_type,
        ]);
    }
}
