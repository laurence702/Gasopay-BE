<?php

namespace App\Console\Commands;

use ReflectionClass;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdatePostmanCollection extends Command
{
    protected $signature = 'postman:update';
    protected $description = 'Update Postman collection via static analysis of routes/controllers';

    // Define folder mappings for routes
    private $folderMappings = [
        // Auth related endpoints
        'api/register' => 'Auth',
        'api/login' => 'Auth',
        'api/logout' => 'Auth',
        'api/me' => 'Auth',
        'api/register-rider' => 'Auth',
        
        // Admin related endpoints
        'api/create-admin' => 'Admin',
        'api/riders/verification' => 'Admin',
        'api/users/ban' => 'Admin',
        
        // User related endpoints
        'api/users' => 'Users',
        
        // Branch related endpoints
        'api/branches' => 'Branches',
        'api/super-admin/branches' => 'Branches',
        
        // Payment related endpoints
        'api/payment-histories' => 'Payment',
        'api/payment-proofs' => 'Payment',
        'api/payment' => 'Payment',
        
        // Order related endpoints
        'api/orders' => 'Orders',
        'api/branch-admin/orders' => 'Orders',
        
        // User management related endpoints
        'api/super-admin/users' => 'User Management',
        'api/branch-admin/riders' => 'User Management',
        'api/user-profiles' => 'User Management',
        'api/branch-admin/pending-approvals' => 'User Management',
        
        // Product related endpoints
        'api/products' => 'Products',
        'api/branch-admin/products' => 'Products',
        
        // Admin dashboard endpoints
        'api/branch-admin/statistics' => 'Admin Dashboard',
        'api/branch-admin/branch-info' => 'Admin Dashboard',
        'api/branch-admin/activities' => 'Admin Dashboard',
        'api/branch-admin/scan' => 'Admin Dashboard'
    ];

    public function handle()
    {
        $postmanPath = base_path('guide/gasopay.json');
        if (!File::exists($postmanPath)) {
            $this->error("Postman collection not found at: $postmanPath");
            return;
        }

        // Load existing Postman collection
        $postman = json_decode(File::get($postmanPath), true);

        // Get all API routes
        $routes = $this->getApiRoutes();
        
        // Organize routes into folders
        $organizedItems = $this->organizeIntoFolders($routes);

        // Merge updates into Postman collection (preserve manual edits)
        $postman['item'] = $this->mergePostmanFolders($postman['item'], $organizedItems);
        
        // Remove duplicate endpoints that appear at the root level
        $postman['item'] = $this->removeDuplicateRootItems($postman['item']);

        // Save updated collection
        File::put($postmanPath, json_encode($postman, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Postman collection updated successfully!");
    }

    /**
     * Extract all API routes (filtered by 'api' middleware if needed).
     */
    private function getApiRoutes(): array
    {
        $router = app(Router::class);
        $routes = [];

        foreach ($router->getRoutes() as $route) {
            if (in_array('api', $route->middleware())) {
                $routes[] = [
                    'uri' => $route->uri(),
                    'method' => $route->methods()[0] ?? 'GET',
                    'action' => $route->getActionName(),
                    'name' => $route->getName() // Get route name if available
                ];
            }
        }

        return $routes;
    }

    /**
     * Parse controller and method from route action.
     */
    private function getControllerMethod(string $action): ?array
    {
        if (!str_contains($action, '@')) return null;

        [$controller, $method] = explode('@', $action);
        return [
            'controller' => $controller,
            'method' => $method,
        ];
    }

    /**
     * Infer request payload structure from FormRequest or method parameters.
     */
    private function inferRequestStructure(array $controllerMethod): ?array
    {
        $reflection = new ReflectionClass($controllerMethod['controller']);
        if (!$reflection->hasMethod($controllerMethod['method'])) return null;

        $method = $reflection->getMethod($controllerMethod['method']);
        $parameters = $method->getParameters();

        foreach ($parameters as $param) {
            $type = $param->getType();
            if ($type && is_subclass_of($type->getName(), 'Illuminate\\Foundation\\Http\\FormRequest')) {
                $requestClass = $type->getName();
                $rules = (new $requestClass)->rules();
                return array_keys($rules); // Return fields with validation rules
            }
        }

        return null;
    }

    /**
     * Infer response structure from controller method return type or docblock.
     */
    private function inferResponseStructure(array $controllerMethod): ?array
    {
        $reflection = new ReflectionClass($controllerMethod['controller']);
        if (!$reflection->hasMethod($controllerMethod['method'])) return null;

        $method = $reflection->getMethod($controllerMethod['method']);
        $docComment = $method->getDocComment();

        // Parse @return annotation (e.g., "@return JsonResponse")
        if (preg_match('/@return\s+(\S+)/', $docComment, $matches)) {
            return ['type' => $matches[1]]; // Simplified; extend for detailed structure
        }

        return null;
    }

    /**
     * Create a Postman collection item for a route.
     */
    private function createPostmanItem(string $uri, string $method, ?array $request, ?array $response, ?string $customName = null): array
    {
        // Generate a more user-friendly name if not provided
        $name = $customName ?? $this->generateFriendlyName($uri, $method);
        
        // Parse the URL into components for Postman
        $urlParts = explode('/', $uri);
        $host = ['{{base_url}}'];
        $path = $urlParts;
        
        return [
            'name' => $name,
            'request' => [
                'method' => $method,
                'url' => [
                    'raw' => '{{base_url}}/' . $uri,
                    'host' => $host,
                    'path' => $path,
                ],
                'body' => $request ? [
                    'mode' => 'raw', 
                    'raw' => json_encode($request),
                    'options' => [
                        'raw' => [
                            'language' => 'json'
                        ]
                    ]
                ] : null,
            ],
            'response' => $response ? [$response] : [],
        ];
    }
    
    /**
     * Generate a user-friendly name for the endpoint
     */
    private function generateFriendlyName(string $uri, string $method): string
    {
        // Extract the last meaningful part of the URI
        $parts = explode('/', $uri);
        $lastPart = end($parts);
        
        // If it's a parameter (starts with {), use the previous part
        if (Str::startsWith($lastPart, '{')) {
            $lastPart = $parts[count($parts) - 2] ?? $lastPart;
        }
        
        // Convert to title case and add method
        $friendlyName = Str::title(str_replace(['-', '_'], ' ', $lastPart));
        
        return "$friendlyName";
    }

    /**
     * Organize routes into appropriate folders
     */
    private function organizeIntoFolders(array $routes): array
    {
        $folders = [];
        
        // Initialize folders structure
        foreach (array_unique(array_values($this->folderMappings)) as $folder) {
            $folders[$folder] = [
                'name' => $folder,
                'item' => []
            ];
        }
        
        // Organize routes into folders
        foreach ($routes as $route) {
            $controllerMethod = $this->getControllerMethod($route['action']);
            if (!$controllerMethod) continue;
            
            // Infer request/response structure
            $requestStructure = $this->inferRequestStructure($controllerMethod);
            $responseStructure = $this->inferResponseStructure($controllerMethod);
            
            // Determine which folder this route belongs to
            $folderName = $this->determineFolder($route['uri']);
            
            if ($folderName) {
                // Add to appropriate folder
                $folders[$folderName]['item'][] = $this->createPostmanItem(
                    $route['uri'],
                    $route['method'],
                    $requestStructure,
                    $responseStructure
                );
            }
        }
        
        // Remove empty folders
        foreach ($folders as $key => $folder) {
            if (empty($folder['item'])) {
                unset($folders[$key]);
            }
        }
        
        return array_values($folders);
    }
    
    /**
     * Determine which folder a route belongs to based on its URI
     */
    private function determineFolder(string $uri): ?string
    {
        // First try exact match
        if (isset($this->folderMappings[$uri])) {
            return $this->folderMappings[$uri];
        }
        
        // Special cases based on URI patterns
        if (preg_match('/api\/payment-histories\/.*\/mark-cash/', $uri)) {
            return 'Payment';
        }
        
        if (preg_match('/api\/payment-histories\/.*\/proof/', $uri)) {
            return 'Payment';
        }
        
        if (preg_match('/api\/payment-proofs\/.*\/(approve|reject)/', $uri)) {
            return 'Payment';
        }
        
        if (preg_match('/api\/branch-admin\/riders\/.*\/verification/', $uri)) {
            return 'User Management';
        }
        
        if (preg_match('/api\/products\/.*/', $uri)) {
            return 'Products';
        }
        
        if (preg_match('/api\/branch-admin\/products\/.*/', $uri)) {
            return 'Products';
        }
        
        if (preg_match('/api\/branch-admin\/statistics/', $uri)) {
            return 'Admin Dashboard';
        }
        
        if (preg_match('/api\/branch-admin\/branch-info/', $uri)) {
            return 'Admin Dashboard';
        }
        
        if (preg_match('/api\/branch-admin\/activities/', $uri)) {
            return 'Admin Dashboard';
        }
        
        if (preg_match('/api\/branch-admin\/scan/', $uri)) {
            return 'Admin Dashboard';
        }
        
        // Then try pattern matching
        foreach ($this->folderMappings as $pattern => $folder) {
            if (Str::contains($uri, $pattern)) {
                return $folder;
            }
        }
        
        return null;
    }
    
    /**
     * Merge new folders into existing Postman collection, preserving manual edits.
     */
    private function mergePostmanFolders(array $existing, array $newFolders): array
    {
        $result = $existing;
        
        foreach ($newFolders as $newFolder) {
            $folderName = $newFolder['name'];
            $existingFolderKey = $this->findFolderKey($result, $folderName);
            
            if ($existingFolderKey !== null) {
                // Folder exists, merge items
                $existingItems = $result[$existingFolderKey]['item'] ?? [];
                $newItems = $newFolder['item'];
                
                // Keep track of which items we've updated
                $updatedItems = [];
                
                // Update existing items or add new ones
                foreach ($newItems as $newItem) {
                    $existingItemKey = $this->findItemKeyInFolder($existingItems, $newItem['request']['method'], $newItem['request']['url']['raw']);
                    
                    if ($existingItemKey !== null) {
                        // Preserve existing item's name and other manual edits
                        $newItem['name'] = $existingItems[$existingItemKey]['name'];
                        // Preserve existing responses if any
                        if (!empty($existingItems[$existingItemKey]['response'])) {
                            $newItem['response'] = $existingItems[$existingItemKey]['response'];
                        }
                        $existingItems[$existingItemKey] = $newItem;
                        $updatedItems[] = $existingItemKey;
                    } else {
                        $existingItems[] = $newItem;
                    }
                }
                
                $result[$existingFolderKey]['item'] = $existingItems;
            } else {
                // New folder, add it
                $result[] = $newFolder;
            }
        }
        
        return $result;
    }
    
    /**
     * Find a folder in the Postman collection by name.
     */
    private function findFolderKey(array $items, string $folderName): ?int
    {
        foreach ($items as $key => $item) {
            if (isset($item['name']) && $item['name'] === $folderName) {
                return $key;
            }
        }
        return null;
    }
    
    /**
     * Find an item in a folder by method and URL.
     */
    private function findItemKeyInFolder(array $items, string $method, string $url): ?int
    {
        foreach ($items as $key => $item) {
            if (isset($item['request']['method']) && 
                isset($item['request']['url']['raw']) && 
                $item['request']['method'] === $method &&
                $this->urlsMatch($item['request']['url']['raw'], $url)) {
                return $key;
            }
        }
        return null;
    }
    
    /**
     * Check if two URLs match, ignoring base URL variable.
     */
    private function urlsMatch(string $url1, string $url2): bool
    {
        // Remove base_url variable and compare
        $path1 = preg_replace('/\{\{base_url\}\}\//', '', $url1);
        $path2 = preg_replace('/\{\{base_url\}\}\//', '', $url2);
        
        return $path1 === $path2;
    }
    
    /**
     * Remove duplicate items that appear at the root level but are already in folders
     */
    private function removeDuplicateRootItems(array $items): array
    {
        $folderItems = [];
        $rootItems = [];
        $folderUrls = []; // Initialize the array
        
        // First, collect all items in folders
        foreach ($items as $item) {
            if (isset($item['item']) && is_array($item['item'])) {
                // This is a folder
                $folderItems[] = $item;
                
                // Collect all URLs in this folder
                foreach ($item['item'] as $folderItem) {
                    if (isset($folderItem['request']['url']['raw'])) {
                        $url = $folderItem['request']['url']['raw'];
                        $method = $folderItem['request']['method'] ?? 'GET';
                        $folderUrls[$method . '|' . $url] = true;
                    }
                }
            } else {
                // This is a root item
                $rootItems[] = $item;
            }
        }
        
        // Now filter out root items that are duplicates of folder items
        $filteredRootItems = [];
        foreach ($rootItems as $rootItem) {
            if (isset($rootItem['request']['url']['raw'])) {
                $url = $rootItem['request']['url']['raw'];
                $method = $rootItem['request']['method'] ?? 'GET';
                $key = $method . '|' . $url;
                
                // Check if this URL is already in a folder
                if (!isset($folderUrls[$key])) {
                    // Not a duplicate, keep it
                    $filteredRootItems[] = $rootItem;
                }
            } else {
                // No URL, keep it
                $filteredRootItems[] = $rootItem;
            }
        }
        
        // Combine folders and filtered root items
        return array_merge($folderItems, $filteredRootItems);
    }
}
