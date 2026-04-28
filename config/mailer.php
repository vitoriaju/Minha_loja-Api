<?php
require_once __DIR__ . '/config.php';

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

function mailer_configurado(): bool
{
    return class_exists(PHPMailer::class)
        && SMTP_HOST !== ''
        && SMTP_USER !== ''
        && SMTP_PASS !== ''
        && !str_contains(SMTP_HOST, 'example.com')
        && !str_contains(SMTP_USER, 'example.com')
        && !str_contains(SMTP_USER, 'seuemail')
        && !str_contains(SMTP_PASS, 'sua-senha');
}

function mailer_erro_configuracao(): string
{
    if (!class_exists(PHPMailer::class)) {
        return 'PHPMailer nao encontrado.';
    }

    if (SMTP_HOST === '' || SMTP_USER === '' || SMTP_PASS === '') {
        return 'Preencha SMTP_HOST, SMTP_USER e SMTP_PASS no .env.';
    }

    if (
        str_contains(SMTP_HOST, 'example.com')
        || str_contains(SMTP_USER, 'example.com')
        || str_contains(SMTP_USER, 'seuemail')
        || str_contains(SMTP_PASS, 'sua-senha')
    ) {
        return 'Troque os valores de exemplo do SMTP no .env pelo e-mail real e senha de app.';
    }

    return 'Verifique as configuracoes SMTP no .env.';
}

function enviar_email(string $destinatario, string $assunto, string $mensagemTexto, string $mensagemHtml = ''): bool
{
    if (!mailer_configurado()) {
        error_log('SMTP/PHPMailer nao configurado: ' . mailer_erro_configuracao());
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = (int) SMTP_PORT;

        if (SMTP_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($destinatario);
        $mail->Subject = $assunto;

        if ($mensagemHtml !== '') {
            $mail->isHTML(true);
            $mail->Body = $mensagemHtml;
            $mail->AltBody = $mensagemTexto;
        } else {
            $mail->Body = $mensagemTexto;
        }

        return $mail->send();
    } catch (MailException $e) {
        error_log('Erro ao enviar e-mail: ' . $e->getMessage());
        return false;
    }
}
