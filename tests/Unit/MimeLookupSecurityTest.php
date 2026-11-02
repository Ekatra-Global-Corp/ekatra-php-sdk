<?php

namespace Ekatra\Product\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\Transformers\SyncProductTransform;

/**
 * The HTTP MIME lookup must never reach internal addresses (SSRF).
 * Uses IP literals only, so no network access is needed.
 */
class MimeLookupSecurityTest extends TestCase
{
    private function invoke(string $method, string $url)
    {
        $transformer = new SyncProductTransform();
        $reflection = new \ReflectionMethod($transformer, $method);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }
        return $reflection->invoke($transformer, $url);
    }

    public function testInternalAndNonHttpUrlsAreRejected()
    {
        $blocked = [
            'http://127.0.0.1/image',
            'http://169.254.169.254/latest/meta-data/',
            'http://10.0.0.5/image',
            'http://172.16.0.1/image',
            'http://192.168.1.1/image',
            'http://0.0.0.0/image',
            'http://[::1]/image',
            'http://[fd00::1]/image',
            'file:///etc/passwd',
            'gopher://127.0.0.1:6379/_INFO',
            'ftp://example.com/image',
            'not a url',
        ];

        foreach ($blocked as $url) {
            $this->assertNull($this->invoke('resolvePublicHttpTarget', $url), "Should reject: {$url}");
            $this->assertNull($this->invoke('getMimeTypeFromHttp', $url), "Should not fetch: {$url}");
        }
    }

    public function testPublicIpTargetsArePinned()
    {
        $this->assertSame(
            ['host' => '93.184.216.34', 'port' => 443, 'ip' => '93.184.216.34'],
            $this->invoke('resolvePublicHttpTarget', 'https://93.184.216.34/image')
        );

        $this->assertSame(
            ['host' => '2606:4700::1111', 'port' => 8080, 'ip' => '[2606:4700::1111]'],
            $this->invoke('resolvePublicHttpTarget', 'http://[2606:4700::1111]:8080/image')
        );
    }
}
