<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.sidebar', function ($view) {
            $utilisateur = auth()->user();
            $notificationsNonLues = 0;

            if ($utilisateur) {
                $notificationsNonLues = Notification::query()
                    ->where('user_id', $utilisateur->id)
                    ->whereNull('read_at')
                    ->count();
            }

            $view->with([
                'notificationsNonLues' => $notificationsNonLues,
            ]);
        });
    }
}
