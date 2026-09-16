<?php

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')
         ->assertRedirect('/login');
});

test('users with no organization are redirected to get started', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
         ->get('/dashboard')
         ->assertRedirect(route('get-started.show'));
});

test('authenticated users with an active organization see the organizations hub', function () {
    $user = User::factory()->create();

    $organization = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'active',
    ]);

    OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
         ->get('/dashboard')
         ->assertOk()
         ->assertInertia(fn (Assert $page) => $page
             ->component('Organizations/Hub')
         );
});

test('a member can view their organization\'s own dashboard', function () {
    $user = User::factory()->create();

    $organization = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'active',
    ]);

    OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
         ->get("/organizations/{$organization->id}/dashboard")
         ->assertOk()
         ->assertInertia(fn (Assert $page) => $page
             ->component('Dashboard')
         );
});

test('a non-member is forbidden from viewing another organization\'s dashboard', function () {
    $user = User::factory()->create();

    $organization = Organization::create([
        'name' => 'Other Org',
        'slug' => 'other-org',
        'status' => 'active',
    ]);

    $this->actingAs($user)
         ->get("/organizations/{$organization->id}/dashboard")
         ->assertForbidden();
});