<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['form', 'url', 'app'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        $this->session = service('session');
    }

    /** Currently logged-in user's session data, or null if not signed in. */
    protected function currentUser(): ?array
    {
        if (! $this->session->get('user_id')) {
            return null;
        }

        return [
            'id' => $this->session->get('user_id'),
            'username' => $this->session->get('username'),
            'role' => $this->session->get('role'),
            'name' => $this->session->get('name'),
            'assigned_counter' => $this->session->get('assigned_counter'),
        ];
    }
}
