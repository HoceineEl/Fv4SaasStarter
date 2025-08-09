<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\FontProviders\LocalFontProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

class SuperAdminPanelProvider extends PanelProvider
{
  public function panel(Panel $panel): Panel
  {
    return $panel
      ->id('super-admin')
      ->path('super-admin')
      ->login(AdminLogin::class)
      ->colors([
        'primary' => Color::Blue,
      ])
      ->font('Cairo', url: asset('css/app.css'), provider: LocalFontProvider::class)
      ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
      ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
      ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\\Filament\\SuperAdmin\\Widgets')
      ->pages([
        Dashboard::class,
      ])
      ->userMenuItems([
        \Filament\Actions\Action::make('stop-impersonating')
          ->label(__('app.stop_impersonating'))
          ->visible(fn() => app('impersonate')->isImpersonating())
          ->url(fn () => route('impersonate.leave'))
          ->postToUrl(),
      ])
      ->widgets([
        AccountWidget::class,
        FilamentInfoWidget::class,
      ])
      ->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        DispatchServingFilamentEvent::class,
      ])
      ->authMiddleware([
        Authenticate::class,
      ])
      ->plugins([
        FilamentShieldPlugin::make(),
      ])
      ->viteTheme('resources/css/filament/super-admin/theme.css');
  }
}
