<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Contract;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'initials' => $request->user()->initials,
                    'avatar_url' => $request->user()->avatar_url,
                    'avatar_color' => $request->user()->avatar_color,
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'ziggy' => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            'notifications' => function () use ($request) {
                $user = $request->user();
                if (!$user) return [];

                if ($user->hasRole('Head')) {
                    return Contract::whereIn('status', [
                            'pending_creation',
                            'pending_update',
                            'pending_deletion'
                        ])
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(fn ($contract) => [
                            'id' => $contract->id,
                            'title' => $contract->title,
                            'status' => $contract->status,
                            'url' => route('contracts.show', $contract->id),
                        ]);
                }

                if ($user->hasRole('Officer')) {
                    return Contract::where('created_by', $user->id)
                        ->whereIn('status', ['rejected', 'pending_update'])
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(fn ($contract) => [
                            'id' => $contract->id,
                            'title' => $contract->title,
                            'status' => $contract->status,
                            'url' => route('contracts.show', $contract->id),
                        ]);
                }

                return [];
            },
        ];
    }
}
