<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class UiConsistencyTest extends TestCase
{
    public function test_web_views_follow_the_shared_palette_and_typography_rules(): void
    {
        $files = $this->viewFiles();
        $violations = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            foreach ([
                'gradient' => '/gradient(?:-|\()/i',
                'warna di luar palette' => '/(?:blue|purple|pink|indigo|violet|fuchsia|cyan|orange|teal|green)-\d/i',
                'font selain Poppins' => '/font-(?:mono|serif)\b/i',
                'teks lebih kecil dari 12px' => '/text-\[(?:[0-9]|1[01])px\]/i',
            ] as $rule => $pattern) {
                if (preg_match($pattern, $contents)) {
                    $violations[] = "{$relative}: {$rule}";
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
        $this->assertStringContainsString("'Poppins'", file_get_contents(resource_path('css/app.css')));
        $this->assertStringContainsString("sans: ['Poppins'", file_get_contents(base_path('tailwind.config.js')));
    }

    public function test_tables_and_application_shell_keep_mobile_content_contained(): void
    {
        foreach ($this->viewFiles() as $file) {
            if (str_contains(basename($file), 'pdf') || ! str_contains(file_get_contents($file), '<table')) {
                continue;
            }

            $this->assertStringContainsString('overflow-x-auto', file_get_contents($file), $file);
        }

        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('h-[100dvh]', $layout);
        $this->assertStringContainsString('min-w-0', $layout);
    }

    /** @return list<string> */
    private function viewFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
