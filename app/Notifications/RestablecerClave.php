<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;

class RestablecerClave extends ResetPassword implements ShouldQueue {}
