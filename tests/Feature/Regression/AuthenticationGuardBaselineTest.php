<?php

namespace Tests\Feature\Regression;

use App\Models\User;
use Tests\TestCase;

class AuthenticationGuardBaselineTest extends TestCase
{
    public function test_guest_is_redirected_to_the_user_login_from_user_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/sale-orders')->assertRedirect(route('login'));
        $this->get('/show-workorders')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_the_admin_login_from_admin_routes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/admin/companies')->assertRedirect(route('admin.login'));
    }

    public function test_user_and_admin_login_pages_are_separate_and_renderable(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('frontend.auth.login');
        $this->get('/admin/login')->assertOk()->assertViewIs('admin.auth.login');
    }

    public function test_admin_guard_does_not_authenticate_the_user_dashboard(): void
    {
        $admin = $this->transientUser('Admin', 900001);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/dashboard')->assertOk();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_user_guard_does_not_authenticate_the_admin_dashboard(): void
    {
        $user = $this->transientUser('User', 900002);

        $this->actingAs($user, 'web');

        $this->get('/dashboard')->assertOk();
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_direct_encrypted_id_routes_do_not_leak_to_guests(): void
    {
        $this->get('/sale-orders/not-an-encrypted-id/edit')->assertRedirect(route('login'));
        $this->get('/print-workorder-gatepass/not-an-encrypted-id')->assertRedirect(route('login'));
        $this->get('/admin/companies/not-an-encrypted-id/edit')->assertRedirect(route('admin.login'));
    }

    private function transientUser(string $type, int $id): User
    {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'user_type' => $type,
            'name' => "Regression {$type}",
            'email' => strtolower($type)."-regression@example.test",
            'status' => 'Active',
        ]);
        $user->exists = true;

        return $user;
    }
}
