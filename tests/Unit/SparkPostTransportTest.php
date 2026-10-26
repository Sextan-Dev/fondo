<?php

namespace Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Envelope;
use Tests\TestCase;
use Vemcogroup\SparkPostDriver\Transport\SparkPostTransport;

class SparkPostTransportTest extends TestCase
{
    public function test_notifications_preserve_recipients_content_and_attachments_without_network(): void
    {
        $environment = \Illuminate\Support\Env::getRepository();
        $previousEndpoint = $environment->get('SPARKPOST_ENDPOINT');
        $environment->set('SPARKPOST_ENDPOINT', 'https://api.eu.sparkpost.com/api/v1');
        try {
            $services = require config_path('services.php');
        } finally {
            $previousEndpoint === null
                ? $environment->clear('SPARKPOST_ENDPOINT')
                : $environment->set('SPARKPOST_ENDPOINT', $previousEndpoint);
        }
        $this->assertSame('https://api.eu.sparkpost.com/api/v1', $services['sparkpost']['options']['endpoint']);
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"results":{"id":"migration-fixture"}}'),
        ]));
        $handler->push(Middleware::history($history));
        config([
            'mail.driver' => 'sparkpost',
            'services.sparkpost' => ['secret' => 'fixture-api-key', 'options' => $services['sparkpost']['options'], 'guzzle' => ['handler' => $handler]],
        ]);
        $manager = app('mail.manager');
        $manager->purge();
        $transport = $manager->mailer()->getSymfonyTransport();
        $this->assertInstanceOf(SparkPostTransport::class, $transport);
        $file = tempnam(sys_get_temp_dir(), 'migration-mail-');
        file_put_contents($file, 'Pièce jointe — é ñ € — 001234');
        try {
            $message = (new Email)->from('sender@example.test')->to('recipient@example.test')
                ->bcc('archive@example.test')->replyTo('reply@example.test')
                ->subject('Notification — é ñ € — 001234')->text('Texte — é ñ € — 001234')
                ->html('<p>Contenu — é ñ € — 001234</p>')
                ->attachFromPath($file, 'recette.txt', 'text/plain');
            $transport->send($message, Envelope::create($message));
            $this->assertCount(1, $history);
            $request = $history[0]['request'];
            $this->assertSame('https://api.eu.sparkpost.com/api/v1/transmissions', (string) $request->getUri());
            $body = json_decode((string) $request->getBody(), true);
            $this->assertSame('Notification — é ñ € — 001234', $body['content']['subject']);
            $this->assertSame('<p>Contenu — é ñ € — 001234</p>', $body['content']['html']);
            $this->assertSame('Texte — é ñ € — 001234', $body['content']['text']);
            $this->assertCount(2, $body['recipients']);
            $this->assertSame(['recipient@example.test', 'archive@example.test'], array_column(array_column($body['recipients'], 'address'), 'email'));
            $this->assertStringContainsString('reply@example.test', $body['content']['reply_to']);
            $this->assertSame('text/plain', $body['content']['attachments'][0]['type']);
            $this->assertSame('Pièce jointe — é ñ € — 001234', base64_decode($body['content']['attachments'][0]['data']));
        } finally {
            unlink($file);
        }
    }
}
