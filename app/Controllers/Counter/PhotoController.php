<?php

namespace App\Controllers\Counter;

use App\Controllers\BaseController;
use CodeIgniter\Files\File;

/**
 * Serves uploaded member photos from writable/uploads/photos/ (outside the
 * public webroot) under a controlled route, rather than exposing that
 * directory directly. Public, no auth — the same photo is shown on the
 * public QR verification page as on the (authenticated) counter screen.
 */
class PhotoController extends BaseController
{
    public function show(string $filename)
    {
        // basename() strips any path traversal (../, absolute paths) —
        // this must only ever resolve to a file directly inside the
        // photos directory.
        $safeName = basename($filename);
        $path = WRITEPATH . 'uploads/photos/' . $safeName;

        if ($safeName === '' || ! is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        $file = new File($path);

        return $this->response
            ->setContentType($file->getMimeType())
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody(file_get_contents($path));
    }
}
