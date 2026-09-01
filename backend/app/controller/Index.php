<?php

namespace app\controller;

use app\BaseController;

class Index extends BaseController
{
    public function index()
    {
        return json([
            'code'       => 'OK',
            'message'    => 'success',
            'data'       => ['status' => 'up', 'time' => date('c')],
            'request_id' => strtoupper(bin2hex(random_bytes(8))),
        ]);
    }
}
