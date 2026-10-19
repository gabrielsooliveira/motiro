<?php

namespace App\Providers;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\Project;
use App\Policies\HackathonPolicy;
use App\Policies\HackathonRegistrationPolicy;
use App\Policies\JudgePolicy;
use App\Policies\OrganizationPolicy;
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
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Hackathon::class, HackathonPolicy::class);
        Gate::policy(HackathonRegistration::class, HackathonRegistrationPolicy::class);
        Gate::policy(Project::class, JudgePolicy::class);
    }
}
