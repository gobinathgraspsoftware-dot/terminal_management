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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filter by supervisor_type (internal/external)
        if ($request->get('type')) {
            $query->where('supervisor_type', $request->get('type'));
        }

        $total   = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get(['id', 'name', 'employee_id', 'state_id', 'city_id', 'mileage_rate'])
                         ->map(fn ($u) => [
                             'id'           => $u->id,
                             'text'         => $u->name . ' (' . $u->employee_id . ')',
                             'state_id'     => $u->state_id,
                             'city_id'      => $u->city_id,
                             'mileage_rate' => $u->mileage_rate,
                         ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * AJAX: Get supervisor detail (state, city, mileage_rate)
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
            'success'      => true,
            'state_id'     => $supervisor->state_id,
            'state_name'   => $supervisor->state?->name,
            'city_id'      => $supervisor->city_id,
            'city_name'    => $supervisor->city ? $supervisor->city->name . ' (' . $supervisor->city->postcode . ')' : null,
            'mileage_rate' => $supervisor->mileage_rate,
        ]);
    }
}
