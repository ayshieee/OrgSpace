<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')
         ->assertRedirect('/login');
});

test('authenticated users can view the dashboard', function () {
    // 1. Create a dummy user
    $user = User::factory()->create();

    // 2. Act as that user and hit the dashboard route
    $this->actingAs($user)
         ->get('/dashboard')
         ->assertOk() // Assert we get a 200 OK status
         ->assertInertia(fn (Assert $page) => $page
             // 3. Assert the exact Vue component name is returned
             ->component('Dashboard')
         );
});