<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'organizations' => $request->user()
                ->organizations()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'size:2'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $organization = Organization::create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(6)),
            'country' => $validated['country'] ?? 'SN',
            'currency' => $validated['currency'] ?? 'XOF',
            'status' => 'active',
        ]);

        $organization->users()->attach($request->user()->id, [
            'role' => 'owner',
        ]);

        return response()->json([
            'message' => 'Organisation créée avec succès.',
            'organization' => $organization,
        ], 201);
    }

    public function show(Request $request, Organization $organization)
    {
        abort_unless(
            $request->user()->organizations()
                ->whereKey($organization->id)
                ->exists(),
            403
        );

        return response()->json([
            'organization' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        abort_unless(
            $request->user()->organizations()
                ->whereKey($organization->id)
                ->wherePivot('role', 'owner')
                ->exists(),
            403
        );

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'size:2'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $organization->update($validated);

        return response()->json([
            'message' => 'Organisation mise à jour avec succès.',
            'organization' => $organization->fresh(),
        ]);
    }

    public function destroy(Request $request, Organization $organization)
    {
        abort_unless(
            $request->user()->organizations()
                ->whereKey($organization->id)
                ->wherePivot('role', 'owner')
                ->exists(),
            403
        );

        $organization->delete();

        return response()->noContent();
    }
}