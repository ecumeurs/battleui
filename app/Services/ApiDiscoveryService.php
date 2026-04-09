<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class ApiDiscoveryService
{
    /**
     * Get all API endpoints documented in ATD atoms.
     *
     * @return array
     */
    public function getEndpoints(): array
    {
        $docsPath = base_path('../docs');
        $endpoints = [];

        if (!File::isDirectory($docsPath)) {
            return [];
        }

        $files = File::files($docsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($file->getPathname());
            $atom = $this->parseAtom($content);

            if ($atom && isset($atom['frontmatter']['type']) && $atom['frontmatter']['type'] === 'API') {
                $endpoints[] = $this->extractApiDetails($atom);
            }
        }

        return $endpoints;
    }

    /**
     * Parse an ATD atom file into frontmatter and sections.
     *
     * @param string $content
     * @return array|null
     */
    private function parseAtom(string $content): ?array
    {
        $sections = preg_split('/^---$/m', $content, 3);
        
        if (count($sections) < 3) {
            return null;
        }

        try {
            $frontmatter = Yaml::parse($sections[1]);
        } catch (\Exception $e) {
            return null;
        }

        return [
            'frontmatter' => $frontmatter,
            'body' => $sections[2]
        ];
    }

    /**
     * Extract structured API details from the atom body.
     *
     * @param array $atom
     * @return array
     */
    private function extractApiDetails(array $atom): array
    {
        $body = $atom['body'];
        $details = [
            'id' => $atom['frontmatter']['id'] ?? 'unknown',
            'name' => $atom['frontmatter']['human_name'] ?? 'Unknown API',
            'endpoints' => []
        ];

        // Match "## THE RULE / LOGIC" section
        if (preg_match('/## THE RULE \/ LOGIC(.*?)(?=##|$)/s', $body, $matches)) {
            $logicSection = $matches[1];
            
            // Try to find multiple endpoints (like in api_matchmaking) or a single one
            $endpointBlocks = preg_split('/- \*\*Endpoint \d+:/', $logicSection);
            
            if (count($endpointBlocks) > 1) {
                // Remove the first empty block
                array_shift($endpointBlocks);
                foreach ($endpointBlocks as $block) {
                    $details['endpoints'][] = $this->parseEndpointBlock($block);
                }
            } else {
                // Single endpoint
                $details['endpoints'][] = $this->parseEndpointBlock($logicSection);
            }
        }

        return $details;
    }

    /**
     * Parse a block of text containing endpoint details.
     */
    private function parseEndpointBlock(string $block): array
    {
        $info = [
            'uri' => 'N/A',
            'verb' => 'GET',
            'intent' => 'N/A',
            'input' => [],
            'output' => []
        ];

        // URI
        if (preg_match('/- \*\*URI:\*\* `(.*?)`|URI: `(.*?)`/', $block, $m)) {
            $info['uri'] = $m[1] ?: $m[2];
        }

        // Verb
        if (preg_match('/- \*\*Verb:\*\* `(.*?)`|Verb: `(.*?)`/', $block, $m)) {
            $info['verb'] = $m[1] ?: $m[2];
        }

        // Intent
        if (preg_match('/- \*\*Intent:\*\* (.*)|Intent: (.*)/', $block, $m)) {
            $info['intent'] = trim($m[1] ?: $m[2]);
        }

        // Input parameters (naive list parsing)
        if (preg_match('/\*\*Fully Detailed Input:\*\*\n(.*?)(?=\*\*|$)/s', $block, $m)) {
            $paramsSection = $m[1];
            preg_match_all('/^\s*-\s+`(.*?)`:\s+(.*)/m', $paramsSection, $pMatches, PREG_SET_ORDER);
            foreach ($pMatches as $p) {
                $info['input'][] = [
                    'name' => $p[1],
                    'description' => trim($p[2])
                ];
            }
        }

        // Output (naive capture)
        if (preg_match('/\*\*Fully Detailed Output:\*\*\n(.*?)(?=\*\*|$)/s', $block, $m)) {
            $info['output'] = trim($m[1]);
        }

        return $info;
    }
}
