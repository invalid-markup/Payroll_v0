<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();
            $companyId = null;

            // Extract company ID from token abilities (format: company:UUID)
            foreach ($token->abilities as $ability) {
                if (str_starts_with($ability, 'company:')) {
                    $companyId = substr($ability, 8);
                    break;
                }
            }

            if ($companyId && DB::connection()->getDriverName() === 'pgsql') {
                // Set the current company id on the database connection for RLS
                DB::statement("SELECT set_config('app.current_company_id', ?, false)", [$companyId]);
                // Also set tenant id if required by specs (they are often synonymous in this context)
                DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$companyId]);
            }
        }

        return $next($request);
    }
}
