<?php

namespace m4rku5\Jira;

use RuntimeException;

/**
 * @see https://docs.atlassian.com/jira-software/REST/latest/
 */
class Jira
{
    /** @var string $baseurl */
    protected string $baseurl;
    /** @var string $user */
    protected string $user;
    /** @var string $password */
    protected string $password;

    public function __construct(string $baseurl, string $user, string $password)
    {
        $this->baseurl = $baseurl;
        $this->user = $user;
        $this->password = $password;
    }

    private function _curl(string $url, array $options = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array_replace_recursive([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_USERPWD        => $this->user . ":" . $this->password,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json; charset=utf-8",
            ],
        ], $options));
        $exec = curl_exec($ch);
        curl_close($ch);

        return json_decode($exec, true);
    }

    public function GET(string $endpoint): array
    {
        return $this->_curl($this->baseurl . '/' . $endpoint, [CURLOPT_HTTPGET => true]);
    }

    public function PUT()
    {
        throw new RuntimeException('not yet implemented');
    }

    public function POST(string $endpoint, array $data): array
    {
        return $this->_curl($this->baseurl . '/' . $endpoint, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $data,
        ]);
    }

    public function myself()
    {
        return $this->GET('myself');
    }
}
