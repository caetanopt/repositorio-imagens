<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sends transactional email via the Amazon SES v2 API, signed with
 * AWS Signature Version 4. No AWS SDK dependency — SES v2 SendEmail is a
 * single signed HTTPS POST, and pulling in aws-sdk-php just for this would
 * add a large dependency for one call.
 */
class MailerService
{
    private string $region;
    private string $accessKeyId;
    private string $secretAccessKey;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->region          = (string) env('AWS_SES_REGION', 'eu-west-1');
        $this->accessKeyId     = (string) env('AWS_ACCESS_KEY_ID', '');
        $this->secretAccessKey = (string) env('AWS_SECRET_ACCESS_KEY', '');
        $this->fromAddress     = (string) env('MAIL_FROM_ADDRESS', '');
        $this->fromName        = (string) env('MAIL_FROM_NAME', env('APP_NAME', 'Repositório Digital'));
    }

    /**
     * @return bool true if SES accepted the message for sending
     */
    public function send(string $toAddress, string $subject, string $html, string $text): bool
    {
        if ($this->accessKeyId === '' || $this->secretAccessKey === '' || $this->fromAddress === '') {
            error_log('MailerService: AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / MAIL_FROM_ADDRESS não configurados.');
            return false;
        }

        $host     = "email.{$this->region}.amazonaws.com";
        $path     = '/v2/email/outbound-emails';
        $endpoint = "https://{$host}{$path}";

        $from = $this->fromName !== '' ? "{$this->fromName} <{$this->fromAddress}>" : $this->fromAddress;

        $payload = json_encode([
            'FromEmailAddress' => $from,
            'Destination'      => ['ToAddresses' => [$toAddress]],
            'Content'          => [
                'Simple' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body'    => [
                        'Html' => ['Data' => $html, 'Charset' => 'UTF-8'],
                        'Text' => ['Data' => $text, 'Charset' => 'UTF-8'],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $payloadHash = hash('sha256', $payload);
        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-amz-date:{$amzDate}\n";
        $signedHeaders    = 'content-type;host;x-amz-date';

        $canonicalRequest = implode("\n", [
            'POST',
            $path,
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$this->region}/ses/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretAccessKey, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 'ses', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, "
            . "SignedHeaders={$signedHeaders}, Signature={$signature}";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "X-Amz-Date: {$amzDate}",
                "Authorization: {$authorization}",
            ],
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status >= 300) {
            error_log("MailerService: falha ao enviar email via SES (status {$status}): " . ($curlErr ?: $response));
            return false;
        }

        return true;
    }
}
