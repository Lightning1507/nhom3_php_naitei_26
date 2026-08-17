<?php

namespace Tests\Feature;

use Tests\TestCase;

class CitizenSpaTest extends TestCase
{
    public function test_citizen_root_renders_the_react_mount_point(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app')
            ->assertSee('id="citizen-app"', false);
    }

    public function test_citizen_client_side_route_renders_the_same_spa(): void
    {
        $response = $this->get('/applications');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app');
    }

    public function test_citizen_auth_routes_render_the_same_spa(): void
    {
        foreach (['/login', '/register'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertViewIs('citizen.app');
        }
    }
}
