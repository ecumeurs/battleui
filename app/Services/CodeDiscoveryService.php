<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use ReflectionClass;
use ReflectionMethod;

class CodeDiscoveryService
{
    /**
     * Get all API endpoints documented and discovered from code.
     */
    public function getEndpoints(): array
    {
        $routes = Route::getRoutes();
        $endpoints = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            
            // Only include v1 API routes
            if (!str_starts_with($uri, 'api/v1/')) {
                continue;
            }

            // Skip the help endpoint itself
            if (str_contains($uri, '/help')) {
                continue;
            }

            $endpoints[] = $this->introspectRoute($route);
        }

        return $endpoints;
    }

    /**
     * Introspect a single route to extract technical and semantic details.
     */
    private function introspectRoute($route): array
    {
        $action = $route->getAction('controller');
        if (!$action || !str_contains($action, '@')) {
            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'error' => 'No controller found'
            ];
        }

        [$controllerClass, $methodName] = explode('@', $action);

        $details = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'auth' => $this->detectAuth($route),
            'intent' => 'Pending documentation...',
            'parameters' => [],
            'output' => null,
            'tag' => null,
            'atd_link' => null,
        ];

        try {
            $classReflection = new ReflectionClass($controllerClass);
            $methodReflection = new ReflectionMethod($controllerClass, $methodName);
            
            $methodDoc = $methodReflection->getDocComment() ?: '';
            $classDoc = $classReflection->getDocComment() ?: '';

            // 1. Extract ATD Link & Intent (Method first, then class)
            if (preg_match('/@spec-link \[\[(.*?)\]\]/', $methodDoc, $matches)) {
                $atomId = $matches[1];
                $details['atd_link'] = $atomId;
                $details['intent'] = $this->fetchIntentFromAtom($atomId) ?: $details['intent'];
            } elseif (preg_match('/@spec-link \[\[(.*?)\]\]/', $classDoc, $matches)) {
                $atomId = $matches[1];
                $details['atd_link'] = $atomId;
                $details['intent'] = $this->fetchIntentFromAtom($atomId) ?: $details['intent'];
            }

            // 2. Extract Input Parameters from FormRequest
            $details['parameters'] = $this->extractParameters($methodReflection);

            // 3. Extract Output DTO Link
            if (preg_match('/@api-output \[\[(.*?)\]\]/', $methodDoc, $matches)) {
                $details['output'] = $matches[1];
            }

            // 4. Extract Custom Grouping Tag (Method override class)
            if (preg_match('/@api-tag (.*?)\n/', $methodDoc, $matches)) {
                $details['tag'] = trim($matches[1]);
            } elseif (preg_match('/@api-tag (.*?)\n/', $classDoc, $matches)) {
                $details['tag'] = trim($matches[1]);
            }

        } catch (\Exception $e) {
            $details['error'] = $e->getMessage();
        }

        return $details;
    }

    /**
     * Detect if the route requires authentication.
     */
    private function detectAuth($route): array
    {
        $middleware = $route->gatherMiddleware();
        return [
            'required' => in_array('auth:sanctum', $middleware),
            'admin_only' => in_array('admin', $middleware),
        ];
    }

    /**
     * Extract parameters by looking at the FormRequest type-hint in the method.
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                if (is_subclass_of($className, 'Illuminate\Foundation\Http\FormRequest')) {
                    $formRequest = new $className();
                    if (method_exists($formRequest, 'rules')) {
                        $rules = $formRequest->rules();
                        foreach ($rules as $field => $fieldRules) {
                            $params[] = $this->parseLaravelRule($field, $fieldRules);
                        }
                    }
                }
            }
        }
        return $params;
    }

    /**
     * Parse Laravel validation rules into a human-readable format.
     */
    private function parseLaravelRule(string $field, mixed $rules): array
    {
        $ruleString = is_array($rules) ? implode('|', array_filter($rules, fn($r) => is_string($r))) : (string) $rules;
        
        return [
            'name' => $field,
            'type' => $this->inferTypeFromRules($ruleString),
            'required' => str_contains($ruleString, 'required') && !str_contains($ruleString, 'sometimes'),
            'constraints' => $ruleString,
        ];
    }

    private function inferTypeFromRules(string $rules): string
    {
        if (str_contains($rules, 'integer') || str_contains($rules, 'numeric')) return 'integer';
        if (str_contains($rules, 'boolean')) return 'boolean';
        if (str_contains($rules, 'array')) return 'array';
        if (str_contains($rules, 'uuid')) return 'uuid';
        return 'string';
    }

    /**
     * Fetch the intent from the ATD Atom file.
     */
    private function fetchIntentFromAtom(string $id): ?string
    {
        $docsPath = base_path('../docs');
        $files = File::files($docsPath);

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            if (str_contains($content, "id: $id")) {
                if (preg_match('/## INTENT\n(.*?)(?=\n##|$)/s', $content, $matches)) {
                    return trim($matches[1]);
                }
            }
        }
        return null;
    }

    /**
     * Parse the DTO atom and return a structured registry.
     */
    public function getDtoRegistry(): array
    {
        $dtoPath = base_path('../docs/battleui_api_dtos.atom.md');
        if (!File::exists($dtoPath)) return [];

        $content = File::get($dtoPath);
        $dtos = [];

        // Split by DTO header: - **DTO_NAME**
        $sections = preg_split('/\n\-\s+\*\*/', $content);
        
        // Skip first section before any DTO
        array_shift($sections);

        foreach ($sections as $section) {
            // Section starts with "DTO_NAME** body"
            if (preg_match('/^(.*?)\*\*(.*?)(?=\n\-\s+\*\*|##|$)/s', $section, $matches)) {
                $name = trim($matches[1]);
                $body = $matches[2];
                $props = [];

                // Match properties: - `field`: `type` or similar
                $lines = explode("\n", $body);
                foreach ($lines as $line) {
                    if (preg_match('/\s+\-\s+`?(.*?)`?:\s+`?(.*?)`?(?:\n|$)/', $line, $propMatches)) {
                        $props[] = [
                            'name' => trim($propMatches[1], '` '),
                            'type' => trim($propMatches[2], '` ')
                        ];
                    }
                }

                $dtos[$name] = [
                    'id' => $name,
                    'properties' => $props
                ];
            }
        }

        return $dtos;
    }

    /**
     * Get WebSocket documentation registry.
     */
    public function getWebsockets(): array
    {
        return [
            'server' => [
                'host' => config('reverb.apps.0.host', '127.0.0.1'),
                'port' => 8080,
                'protocol' => 'Pusher v7',
            ],
            'handshake' => '1. Connect -> 2. Receive socket_id -> 3. POST /broadcasting/auth -> 4. Send pusher:subscribe',
            'channels' => [
                [
                    'pattern' => 'private-user.{ws_channel_key}',
                    'description' => 'System-wide notifications for a specific user (e.g. Match Found).',
                    'when' => 'Subscribe immediately after authentication.',
                    'events' => [
                        [
                            'name' => 'match.found',
                            'description' => 'Triggered when the matchmaking engine pairs you with an opponent.',
                            'payload' => ['match_id' => 'uuid', 'channel_key' => 'uuid']
                        ]
                    ]
                ],
                [
                    'pattern' => 'private-arena.{match_id}',
                    'description' => 'Real-time tactical board updates for an active match.',
                    'when' => 'Subscribe after receiving the match_id from a match.found event or polling.',
                    'events' => [
                        [
                            'name' => 'board.updated',
                            'description' => 'Full tactical state update whenever an entity acts.',
                            'payload' => ['match_id' => 'uuid', 'data' => 'BoardState']
                        ]
                    ]
                ]
            ]
        ];
    }
}
