<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class JWT extends BaseConfig
{
    public $secret;
    public $ttl    = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->secret = env('jwt.secret');
        $this->ttl    = env('jwt.ttl', 3600);
    }
}
