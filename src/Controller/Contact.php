<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Kursova\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function count;
use function React\Async\await;

final readonly class Contact
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        $submitted = $request->getMethod() === 'POST';
        $valid = false;
        $errors = [];

        if ($submitted) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $name = $body['name'] ?? 'name';
                $email = $body['email'] ?? 'email';
                $text = $body['text'] ?? 'text';

                $nameErrors = [];
                $emailErrors = [];
                $textErrors = [];

                if ($name === '') {
                    $nameErrors[] = 'Името е задължително';
                } elseif (strlen($name) > 255) {
                    $nameErrors[] = 'Името е прекалено дълго';
                }

                if ($email === '') {
                    $emailErrors[] = 'Имейлът е задължителен';
                } elseif (strlen($email) > 255) {
                    $emailErrors[] = 'Имейлът е прекалено дълъг';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErrors[] = 'Имейлът е невалиден';
                }

                if ($text === '') {
                    $textErrors[] = 'Запитването е задължително';
                } elseif (strlen($text) > 65535) {
                    $textErrors[] = 'Запитването е прекалено дълго';
                }

                $errors['name'] = $nameErrors;
                $errors['email'] = $emailErrors;
                $errors['text'] = $textErrors;

                $valid = count($nameErrors) === 0 && count($emailErrors) === 0 && count($textErrors) === 0;

                if ($valid) {
                    $connection = $context->getConnection();
                    await(
                        $connection->query('INSERT INTO enquiries (name, email, text) VALUES(?, ?, ?)', [
                            $name,
                            $email,
                            $text
                        ])
                    );
                }
            }
        }

        return
            $context->respond("contact", [
                'submitted' => $submitted,
                'valid' => $valid,
                'errors' => $errors,
            ]);
    }
}