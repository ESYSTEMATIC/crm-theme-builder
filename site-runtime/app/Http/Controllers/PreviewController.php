<?php

namespace App\Http\Controllers;

use App\Services\HostSiteResolver;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;

class PreviewController extends Controller
{
    public function __construct(
        private HostSiteResolver $resolver
    ) {}

    public function __invoke(Request $request): RedirectResponse|Response
    {
        $token = $request->query('token');
        if (!$token) {
            return new Response('Missing preview token', 400);
        }

        $host = $request->getHost();
        $siteData = $this->resolver->resolve($host);

        if (!$siteData) {
            return new Response('Site not found', 404);
        }

        // Validate the token belongs to this site and is not expired
        $draftVersion = $this->resolver->validatePreviewToken($siteData['id'], $token);
        if ($draftVersion === null) {
            return new Response('Invalid or expired preview token', 403);
        }

        // Set preview cookie and redirect to root
        // Cookie: HttpOnly, Secure (in production), SameSite=Lax, 60 min TTL
        $cookie = Cookie::make(
            name: 'preview_session',
            value: $token,
            minutes: 60,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );

        return redirect('/')
            ->withCookie($cookie);
    }
}
