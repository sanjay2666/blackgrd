<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SafeAuthRedirect
{
    public function intended(Request $request, string $fallback): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->isSafe($request, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to($fallback);
    }

    private function isSafe(Request $request, string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        if (! isset($parts['host'])) {
            return str_starts_with($url, '/') && ! str_starts_with($url, '//');
        }

        return isset($parts['scheme'])
            && hash_equals($request->getHost(), (string) ($parts['host'] ?? ''))
            && hash_equals($request->getScheme(), (string) $parts['scheme']);
    }
}
