<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SchoolYear;

class CheckActiveSchoolYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if we are on the years management page or creating/activating a year
        if ($request->routeIs('admin.years*') || $request->routeIs('admin.dashboard')) {
            return $next($request);
        }

        $activeYear = SchoolYear::where('is_active', true)->first();

        if (!$activeYear && SchoolYear::count() > 0) {
             return redirect()->route('admin.years')->with('error', 'Veuillez activer une année scolaire pour continuer.');
        }

        if (SchoolYear::count() === 0) {
             return redirect()->route('admin.years')->with('error', 'Veuillez créer une année scolaire pour commencer.');
        }
        return $next($request);
    }
}
