<?php

namespace GitFlow\Orchestrator;

use Exception;
use RuntimeException;

class GitHubClient
{
    private string $token;
    private string $baseUrl = "https://api.github.com";
    private int $maxRetries = 3;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function executeRequest(string $endpoint, string $method = "GET", array $data = []): array
    {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            $ch = curl_init($this->baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token " . $this->token,
                "Accept: application/vnd.github.v3+json",
                "User-Agent: GitFlow-Orchestrator"
            ]);

            if ($method === "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new RuntimeException("CURL Error: " . $error);
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true) ?? [];
            }

            if ($httpCode === 429) {
                sleep(pow(2, $attempt));
                $attempt++;
                continue;
            }

            throw new RuntimeException("GitHub API returned error code: " . $httpCode . " Response: " . $response);
        }

        throw new Exception("Max retries exceeded for GitHub API request");
    }
}