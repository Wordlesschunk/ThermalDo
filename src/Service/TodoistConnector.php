<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TodoistConnector
{
    private const string TASKS_URL =
        'https://api.todoist.com/api/v1/tasks/filter';

    public function __construct(
        private HttpClientInterface $httpClient,

        #[Autowire('%env(TODOIST_API_TOKEN)%')]
        private string $apiToken,
    ) {
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     due: string|null,
     *     due_time: string|null,
     *     priority: int,
     *     labels: array<int, string>,
     *     url: string|null
     * }>
     */
    public function getTodaysTasks(): array
    {
        return $this->getTasksByFilter('today');
    }

    public function getTodaysTasksAsTitles(): array
    {
        $tasks = $this->getTasksByFilter('today');
        $formattedTasks = [];

        foreach ($tasks as $key => $task) {
            $formattedTasks[] = $task['title'];
        }

        return $formattedTasks;
    }

    /**
     * Includes tasks due today and overdue tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTodaysAndOverdueTasks(): array
    {
        return $this->getTasksByFilter('today | overdue');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTasksByFilter(string $filter): array
    {
        $tasks = [];
        $cursor = null;

        do {
            $query = [
                'query' => $filter,
                'limit' => 200,
            ];

            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            $response = $this->httpClient->request(
                'GET',
                self::TASKS_URL,
                [
                    'headers' => [
                        'Authorization' => sprintf(
                            'Bearer %s',
                            $this->apiToken
                        ),
                    ],
                    'query' => $query,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException(sprintf(
                    'Todoist returned HTTP %d: %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                ));
            }

            $data = $response->toArray(false);

            foreach ($data['results'] ?? [] as $task) {
                $dueDate = $task['due']['date'] ?? null;

                $tasks[] = [
                    'id' => (string) $task['id'],
                    'title' => (string) ($task['content'] ?? ''),
                    'description' => (string) ($task['description'] ?? ''),
                    'due' => $this->extractDate($dueDate),
                    'due_time' => $this->extractTime($dueDate),
                    'priority' => (int) ($task['priority'] ?? 1),
                    'labels' => $task['labels'] ?? [],
                    'url' => $task['url'] ?? null,
                ];
            }

            $cursor = $data['next_cursor'] ?? null;
        } while ($cursor !== null);

        return $tasks;
    }

    private function extractDate(?string $dueDate): ?string
    {
        if ($dueDate === null) {
            return null;
        }

        return substr($dueDate, 0, 10);
    }

    private function extractTime(?string $dueDate): ?string
    {
        if (
            $dueDate === null
            || !str_contains($dueDate, 'T')
        ) {
            return null;
        }

        return substr($dueDate, 11, 5);
    }
}
