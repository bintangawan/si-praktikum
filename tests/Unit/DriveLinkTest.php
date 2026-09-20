<?php

namespace Tests\Unit;

use App\Services\DriveLink;
use PHPUnit\Framework\TestCase;

class DriveLinkTest extends TestCase
{
    public function test_only_supported_drive_file_links_produce_preview_urls(): void
    {
        $this->assertSame('https://drive.google.com/file/d/abc_123/preview?resourcekey=key-1', DriveLink::preview('https://drive.google.com/file/d/abc_123/view?usp=sharing&resourcekey=key-1'));
        $this->assertSame('https://drive.google.com/file/d/abc/preview', DriveLink::preview('https://drive.google.com/open?id=abc'));
        foreach (['http://drive.google.com/file/d/abc/view', 'https://evil.test/file/d/abc/view', 'https://drive.google.com.evil.test/file/d/abc/view', 'https://drive.google.com/drive/folders/abc', 'javascript:alert(1)', 'https://user@drive.google.com/file/d/abc/view', 'https://drive.google.com/open?id[]=abc'] as $url) {
            $this->assertNull(DriveLink::preview($url), $url);
        }
    }
}
