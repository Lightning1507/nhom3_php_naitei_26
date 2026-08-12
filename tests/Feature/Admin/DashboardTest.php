<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_admin_dashboard_renders(): void
    {
        $response = $this->get('/admin');

        $response
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Admin Dashboard');
    }
}
