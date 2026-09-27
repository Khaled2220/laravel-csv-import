<?php

namespace App\Providers;

use App\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('view-import',function (User $user , Import $import){
            return $import->user_id === $user->id;
        });
        Gate::define('cancel-import',function (User $user , Import $import){
            return $import->user_id === $user->id && in_array($import->status,[
                'pending',
                'processing',] ,true); 
        });
        Gate::define('retry-import',function (User $user , Import $import){
            return $import->user_id === $user->id && $import->status === 'failed';        
        });
        Gate::define('view-import-history',function(User $user ){
            return true ;
        });
    }
}
