<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/fondo-files-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/uploads', 0700, true);
        $this->app->usePublicPath($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_business_attachment_formats_remain_allowed(): void
    {
        foreach (['pdf', 'xlsx', 'docx', 'xml', 'xls', 'msg', 'jpg', 'jpeg', 'png', 'doc', 'zip', 'rar', 'eml', 'tiff', 'jfif', 'txt', 'pptx', 'json'] as $extension) {
            $this->assertSame('document.'.$extension, safeUploadName('document.'.$extension));
        }
    }

    public function test_executable_and_traversal_uploads_are_rejected(): void
    {
        foreach (['document.php', 'document.PHP7', 'document.phtml', '.user.ini', '../document.pdf', 'folder\\document.pdf', 'document.html', 'document.svg'] as $name) {
            try {
                safeUploadName($name);
                $this->fail('Unsafe filename accepted');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('file', $error->errors());
            }
        }
    }

    public function test_file_resolution_cannot_escape_uploads(): void
    {
        file_put_contents($this->directory.'/index.php', 'fixture');
        file_put_contents($this->directory.'/uploads/receipt.pdf', 'fixture');
        symlink($this->directory.'/index.php', $this->directory.'/uploads/link.pdf');
        $this->assertNull(publicUploadPath('uploads/../index.php'));
        $this->assertNull(publicUploadPath('uploads/link.pdf'));
        $this->assertSame($this->directory.'/uploads/receipt.pdf', publicUploadPath('/uploads/receipt.pdf'));
    }

    public function test_duplicate_attachment_names_preserve_both_files(): void
    {
        $first = UploadedFile::fake()->createWithContent('receipt.pdf', 'first attachment');
        $second = UploadedFile::fake()->createWithContent('receipt.pdf', 'second attachment');
        $directory = $this->directory.'/uploads';
        $firstName = storeUploadedFile($first, $directory, 'receipt.pdf');
        $secondName = storeUploadedFile($second, $directory, 'receipt.pdf');
        $this->assertSame('receipt.pdf', $firstName);
        $this->assertNotSame($firstName, $secondName);
        $this->assertSame('first attachment', file_get_contents($directory.'/'.$firstName));
        $this->assertSame('second attachment', file_get_contents($directory.'/'.$secondName));
    }

    public function test_multiple_renamed_attachments_keep_distinct_contents(): void
    {
        $request = Request::create('/', 'POST', [], [], ['files' => [
            UploadedFile::fake()->createWithContent('one.pdf', 'one'),
            UploadedFile::fake()->createWithContent('two.pdf', 'two'),
        ]]);
        $names = storageFile($request, 'files', 'operation', '/uploads/', true);
        $this->assertCount(2, $names);
        $this->assertNotSame($names[0]['file'], $names[1]['file']);
        $this->assertSame('one', file_get_contents($this->directory.'/uploads/'.$names[0]['file']));
        $this->assertSame('two', file_get_contents($this->directory.'/uploads/'.$names[1]['file']));
    }

    public function test_profile_image_resize_remains_compatible(): void
    {
        $request = Request::create('/', 'POST', [], [], ['img' => UploadedFile::fake()->image('photo.jpg', 140, 100)]);
        $name = storageImage($request, 'img', 'migration-photo', '/uploads/', true, 70, 70);
        $size = getimagesize($this->directory.'/uploads/'.$name);
        $this->assertSame(70, $size[0]);
        $this->assertSame(70, $size[1]);
    }
}
