<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppInstall extends Model {
  protected $table = 'app_installs';

  protected $fillable = [
    'nonce',
    'access_token',
  ];
}
