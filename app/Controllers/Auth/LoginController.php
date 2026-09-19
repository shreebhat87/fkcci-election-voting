<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class LoginController extends BaseController
{
    public function index()
    {
        if ($this->session->get('user_id')) {
            return redirect()->to($this->homeFor($this->session->get('role')));
        }

        return view('auth/login');
    }

    public function attempt()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Enter a username and password.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $users = model(UserModel::class);
        $user = $users->findActiveByUsername($username);

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Incorrect username or password.');
        }

        $this->session->regenerate();
        $this->session->set([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name'],
            'assigned_counter' => $user['assigned_counter'],
            'assigned_exit_desk' => $user['assigned_exit_desk'],
        ]);
        $users->touchLastLogin($user['id']);

        $redirect = $this->session->get('redirect_after_login');
        $this->session->remove('redirect_after_login');

        return redirect()->to($redirect ?: $this->homeFor($user['role']));
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to('/login')->with('message', 'Signed out.');
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => '/admin',
            'exit_operator' => '/exit',
            default => '/counter',
        };
    }
}
