<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Facades\Filament;

class AdminLogin extends BaseLogin
{
  protected function getEmailFormComponent(): Component
  {
    $panelId = Filament::getCurrentPanel()?->getId();
    $email = match ($panelId) {
      'super-admin' => 'admin@example.com',
      'admin' => 'admin@example.com',
      default => 'admin@example.com',
    };

    return parent::getEmailFormComponent()->default($email);
  }

  protected function getPasswordFormComponent(): Component
  {
    $panelId = Filament::getCurrentPanel()?->getId();
    $password = match ($panelId) {
      'super-admin' => 'password',
      'admin' => 'password',
      default => 'password',
    };

    return parent::getPasswordFormComponent()->default($password);
  }
}
