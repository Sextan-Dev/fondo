<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class PasswordResetMailTest extends TestCase
{
    public function test_reset_email_renders_in_spanish_with_the_reset_link(): void
    {
        $user = new User(['name' => 'Ana Pérez', 'email' => 'ana@example.com']);

        $user->sendPasswordResetNotification('fixture-token');

        $messages = app('mail.manager')->mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('ana@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Restablece tu contraseña', $email->getSubject());
        $this->assertStringContainsString('Hola Ana Pérez', $email->getHtmlBody());
        $this->assertStringContainsString(url('password/reset/fixture-token').'?email=ana%40example.com', $email->getHtmlBody());
    }

    // The published vendor layout used Blade's `{{ $x or '' }}`, removed in Laravel 5.7: under the HTTP error
    // handler the undefined $subcopy became an exception and every reset request answered 500.
    public function test_default_notification_layout_renders(): void
    {
        $html = (string) (new MailMessage)->line('Contenido')->action('Abrir', 'https://example.com')->render();

        $this->assertStringContainsString('Contenido', $html);
        $this->assertStringContainsString('button-blue', $html);
    }
}
