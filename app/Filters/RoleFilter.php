<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts a route to specific roles. Runs after `auth`, which already
 * guarantees a session exists — this only checks the role matches.
 *
 * Usage in Routes.php: 'filter' => 'auth,role:admin'
 * or 'filter' => 'auth,role:admin,operator'
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedRoles = $arguments ?? [];
        $role = session()->get('role');

        if (! $role || ($allowedRoles && ! in_array($role, $allowedRoles, true))) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/forbidden'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }
}
