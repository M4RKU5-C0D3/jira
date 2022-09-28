<?php /** @noinspection PhpUnused */

namespace m4rku5\Jira;

use RuntimeException;

/**
 * @see https://docs.atlassian.com/software/jira/docs/api/REST/8.9.0/#api/2/
 */
class Jira
{
    /** @var string $baseurl */
    protected string $baseurl;
    /** @var string $user */
    protected string $user;
    /** @var string $password */
    protected string $password;

    //region internals
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

    public function GET(string $endpoint, array $parameters = []): array
    {
        if (count($parameters)) {
            $endpoint = $endpoint . '?' . http_build_query($parameters);
        }

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
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);
    }

    //endregion

    public function myself(): array
    {
        return $this->GET('myself');
    }

    public function addWorklog(string $issue, int $timeSpentSeconds, string $comment, int $timestamp = null): array
    {
        return $this->POST('issue/' . $issue . '/worklog', [
            "comment"          => $comment,
            "started"          => strftime('%FT%T.000+0200', $timestamp ?: time()),
            "timeSpentSeconds" => $timeSpentSeconds,
        ]);
    }

    public function getMyWorklogs(): array
    {
        $since = '1664319600000';
        /* @see https://docs.atlassian.com/software/jira/docs/api/REST/8.9.0/#api/2/worklog-getIdsOfWorklogsModifiedSince */
        $worklogs = $this->GET('worklog/updated', ['since' => $since]);// get worklog-ids for day
        $worklogs = array_column($worklogs['values'], 'worklogId');// extract worklog-ids
        $worklogs = $this->POST('worklog/list', ['ids' => $worklogs]);// get actual worklogs
        $mykey = $this->myself()['key'];// get current user key
        // filter out current user worklogs
        return array_filter($worklogs, fn($worklog) => $worklog['author']['key'] == $mykey);
    }
}
