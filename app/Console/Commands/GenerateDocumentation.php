<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use Carbon\Carbon;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate {--format=markdown : Output format (markdown, html, pdf)} {--output=docs : Output directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive system documentation';

    protected $outputDir;
    protected $format;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->outputDir = $this->option('output');
        $this->format = $this->option('format');

        $this->info('Generating system documentation...');

        // Create output directory
        if (!File::exists($this->outputDir)) {
            File::makeDirectory($this->outputDir, 0755, true);
        }

        // Generate documentation sections
        $this->generateOverview();
        $this->generateApiDocumentation();
        $this->generateDatabaseDocumentation();
        $this->generateModelsDocumentation();
        $this->generateControllersDocumentation();
        $this->generateRoutesDocumentation();
        $this->generateConfigurationDocumentation();
        $this->generateDeploymentDocumentation();
        $this->generateMaintenanceDocumentation();
        $this->generateIndex();

        $this->info('Documentation generated successfully!');
        $this->info("Location: {$this->outputDir}");
        $this->info("Format: {$this->format}");

        return 0;
    }

    /**
     * Generate system overview
     */
    protected function generateOverview()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownOverview() : $this->generateHtmlOverview();
        $this->writeFile('overview', $content);
    }

    /**
     * Generate API documentation
     */
    protected function generateApiDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownApi() : $this->generateHtmlApi();
        $this->writeFile('api', $content);
    }

    /**
     * Generate database documentation
     */
    protected function generateDatabaseDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownDatabase() : $this->generateHtmlDatabase();
        $this->writeFile('database', $content);
    }

    /**
     * Generate models documentation
     */
    protected function generateModelsDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownModels() : $this->generateHtmlModels();
        $this->writeFile('models', $content);
    }

    /**
     * Generate controllers documentation
     */
    protected function generateControllersDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownControllers() : $this->generateHtmlControllers();
        $this->writeFile('controllers', $content);
    }

    /**
     * Generate routes documentation
     */
    protected function generateRoutesDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownRoutes() : $this->generateHtmlRoutes();
        $this->writeFile('routes', $content);
    }

    /**
     * Generate configuration documentation
     */
    protected function generateConfigurationDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownConfiguration() : $this->generateHtmlConfiguration();
        $this->writeFile('configuration', $content);
    }

    /**
     * Generate deployment documentation
     */
    protected function generateDeploymentDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownDeployment() : $this->generateHtmlDeployment();
        $this->writeFile('deployment', $content);
    }

    /**
     * Generate maintenance documentation
     */
    protected function generateMaintenanceDocumentation()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownMaintenance() : $this->generateHtmlMaintenance();
        $this->writeFile('maintenance', $content);
    }

    /**
     * Generate index file
     */
    protected function generateIndex()
    {
        $content = $this->format === 'markdown' ? $this->generateMarkdownIndex() : $this->generateHtmlIndex();
        $this->writeFile('index', $content);
    }

    /**
     * Generate Markdown overview
     */
    protected function generateMarkdownOverview()
    {
        $content = "# Academy Next Top Model - System Documentation\n\n";
        $content .= "## Overview\n\n";
        $content .= "Academy Next Top Model is a comprehensive management system for modeling agencies, ";
        $content .= "designed to streamline talent management, contract administration, and client interactions.\n\n";
        
        $content .= "## Key Features\n\n";
        $content .= "- **Talent Management**: Complete profile management for models\n";
        $content .= "- **Contract Management**: Digital contract creation and management\n";
        $content .= "- **Payment Processing**: Integrated payment gateway for transactions\n";
        $content .= "- **Multi-language Support**: English and Indonesian language support\n";
        $content .= "- **Mobile Responsive**: Optimized for all device types\n";
        $content .= "- **Security**: 2FA, CSRF protection, and data encryption\n";
        $content .= "- **Analytics**: Comprehensive visitor and usage analytics\n";
        $content .= "- **WhatsApp Integration**: Direct communication with clients\n\n";

        $content .= "## Technology Stack\n\n";
        $content .= "- **Backend**: Laravel 10.x\n";
        $content .= "- **Frontend**: Blade templates, Tailwind CSS, JavaScript\n";
        $content .= "- **Database**: MySQL 8.0\n";
        $content .= "- **Authentication**: Laravel Sanctum, 2FA\n";
        $content .= "- **File Storage**: Local + Cloud (AWS S3)\n";
        $content .= "- **Email**: SMTP\n";
        $content .= "- **SMS**: OTP verification system\n";
        $content .= "- **Payment**: Payment gateway integration\n\n";

        $content .= "## System Architecture\n\n";
        $content .= "The system follows a modular architecture with clear separation of concerns:\n\n";
        $content .= "```\nFrontend (Blade/Tailwind)\n    |\n    v\nControllers (HTTP Layer)\n    |\n    v\nServices (Business Logic)\n    |\n    v\nModels (Data Layer)\n    |\n    v\nDatabase (MySQL)\n```\n\n";

        $content .= "## Documentation Sections\n\n";
        $content .= "- [API Documentation](api.md) - REST API endpoints and usage\n";
        $content .= "- [Database Schema](database.md) - Database tables and relationships\n";
        $content .= "- [Models](models.md) - Eloquent models and relationships\n";
        $content .= "- [Controllers](controllers.md) - HTTP controllers and methods\n";
        $content .= "- [Routes](routes.md) - Route definitions and middleware\n";
        $content .= "- [Configuration](configuration.md) - Environment and app configuration\n";
        $content .= "- [Deployment](deployment.md) - Deployment instructions and requirements\n";
        $content .= "- [Maintenance](maintenance.md) - Maintenance procedures and troubleshooting\n\n";

        $content .= "## Getting Started\n\n";
        $content .= "For detailed setup instructions, see the [Deployment Guide](deployment.md).\n\n";

        $content .= "---\n";
        $content .= "*Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "*\n";

        return $content;
    }

    /**
     * Generate Markdown API documentation
     */
    protected function generateMarkdownApi()
    {
        $content = "# API Documentation\n\n";
        $content .= "## Overview\n\n";
        $content .= "This document describes the REST API endpoints available in the Academy Next Top Model system.\n\n";

        $content .= "## Authentication\n\n";
        $content .= "Most API endpoints require authentication. Use Laravel Sanctum tokens or session-based authentication.\n\n";
        $content .= "```http\n";
        $content .= "Authorization: Bearer {token}\n";
        $content .= "```\n\n";

        $content .= "## Base URL\n\n";
        $content .= "```\n";
        $content .= "http://localhost:8000/api\n";
        $content .= "```\n\n";

        $content .= "## Endpoints\n\n";

        // Get all routes and document them
        $routes = Route::getRoutes();
        
        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $methods = implode(', ', $route->methods());
                $uri = $route->uri();
                $action = $route->getAction('uses');
                
                $content .= "### {$methods} {$uri}\n\n";
                
                if ($action && is_string($action)) {
                    $content .= "**Controller:** `{$action}`\n\n";
                }
                
                // Try to get method documentation from controller
                if ($action && str_contains($action, '@')) {
                    list($controller, $method) = explode('@', $action);
                    $content .= $this->getMethodDocumentation($controller, $method);
                }
                
                $content .= "---\n\n";
            }
        }

        $content .= "## Response Format\n\n";
        $content .= "All API responses follow a consistent format:\n\n";
        $content .= "```json\n";
        $content .= "{\n";
        $content .= "  \"success\": true,\n";
        $content .= "  \"data\": {},\n";
        $content .= "  \"message\": \"Operation completed successfully\",\n";
        $content .= "  \"errors\": []\n";
        $content .= "}\n";
        $content .= "```\n\n";

        $content .= "## Error Handling\n\n";
        $content .= "Errors are returned with appropriate HTTP status codes:\n\n";
        $content .= "- `400 Bad Request` - Validation errors\n";
        $content .= "- `401 Unauthorized` - Authentication required\n";
        $content .= "- `403 Forbidden` - Insufficient permissions\n";
        $content .= "- `404 Not Found` - Resource not found\n";
        $content .= "- `422 Unprocessable Entity` - Validation failed\n";
        $content .= "- `500 Internal Server Error` - Server error\n\n";

        return $content;
    }

    /**
     * Generate Markdown database documentation
     */
    protected function generateMarkdownDatabase()
    {
        $content = "# Database Documentation\n\n";
        $content .= "## Overview\n\n";
        $content .= "The Academy Next Top Model system uses MySQL as its primary database.\n\n";

        $content .= "## Tables\n\n";

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $content .= $this->generateTableDocumentation($tableName);
        }

        $content .= "## Relationships\n\n";
        $content .= "### Entity Relationship Diagram\n\n";
        $content .= "```\n";
        $content .= "Users (1) -----> (N) Talents\n";
        $content .= "Users (1) -----> (N) Contracts\n";
        $content .= "Talents (1) -----> (N) Contracts\n";
        $content .= "Contracts (1) -----> (N) ContractPayments\n";
        $content .= "Contracts (1) -----> (N) ContractAmendments\n";
        $content .= "```\n\n";

        return $content;
    }

    /**
     * Generate documentation for a specific table
     */
    protected function generateTableDocumentation($tableName)
    {
        $content = "### {$tableName}\n\n";
        
        $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
        
        $content .= "| Column | Type | Null | Key | Default | Extra |\n";
        $content .= "|--------|------|------|-----|---------|-------|\n";
        
        foreach ($columns as $column) {
            $content .= "| {$column->Field} | {$column->Type} | {$column->Null} | {$column->Key} | {$column->Default} | {$column->Extra} |\n";
        }
        
        $content .= "\n";

        // Add table description if available
        $description = $this->getTableDescription($tableName);
        if ($description) {
            $content .= "**Description:** {$description}\n\n";
        }

        return $content;
    }

    /**
     * Get table description
     */
    protected function getTableDescription($tableName)
    {
        $descriptions = [
            'users' => 'System users including models, agencies, and administrators',
            'talents' => 'Model profiles and information',
            'contracts' => 'Digital contracts between models and agencies',
            'contract_payments' => 'Payment records for contracts',
            'contract_amendments' => 'Contract modifications and amendments',
            'analytics_visits' => 'Website visit tracking data',
            'analytics_daily' => 'Daily aggregated analytics',
            'backup_records' => 'Backup operation logs',
            'migrations' => 'Database migration history',
            'password_resets' => 'Password reset tokens',
            'personal_access_tokens' => 'API authentication tokens',
        ];

        return $descriptions[$tableName] ?? null;
    }

    /**
     * Get method documentation from controller
     */
    protected function getMethodDocumentation($controller, $method)
    {
        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);
            $docComment = $methodReflection->getDocComment();
            
            if ($docComment) {
                // Parse PHPDoc
                $lines = explode("\n", $docComment);
                $documentation = "";
                
                foreach ($lines as $line) {
                    $line = trim($line, " \t/*");
                    if (str_starts_with($line, '@')) {
                        continue;
                    }
                    if (!empty($line) && !str_starts_with($line, $method)) {
                        $documentation .= $line . "\n";
                    }
                }
                
                if (!empty($documentation)) {
                    return "**Description:** " . trim($documentation) . "\n\n";
                }
            }
        } catch (\Exception $e) {
            // Method doesn't exist or reflection failed
        }
        
        return "";
    }

    /**
     * Write content to file
     */
    protected function writeFile($filename, $content)
    {
        $extension = $this->format === 'markdown' ? '.md' : '.html';
        $filepath = $this->outputDir . '/' . $filename . $extension;
        
        File::put($filepath, $content);
        
        $this->line("Generated: {$filepath}");
    }

    /**
     * Generate Markdown index
     */
    protected function generateMarkdownIndex()
    {
        $content = "# Academy Next Top Model - Documentation Index\n\n";
        $content .= "## Complete Documentation\n\n";
        $content .= "- [System Overview](overview.md)\n";
        $content .= "- [API Documentation](api.md)\n";
        $content .= "- [Database Schema](database.md)\n";
        $content .= "- [Models Documentation](models.md)\n";
        $content .= "- [Controllers Documentation](controllers.md)\n";
        $content .= "- [Routes Documentation](routes.md)\n";
        $content .= "- [Configuration Guide](configuration.md)\n";
        $content .= "- [Deployment Guide](deployment.md)\n";
        $content .= "- [Maintenance Guide](maintenance.md)\n\n";

        $content .= "## Quick Links\n\n";
        $content .= "- [Getting Started](deployment.md#getting-started)\n";
        $content .= "- [API Authentication](api.md#authentication)\n";
        $content .= "- [Database Setup](database.md#setup)\n";
        $content .= "- [Security Configuration](configuration.md#security)\n";
        $content .= "- [Troubleshooting](maintenance.md#troubleshooting)\n\n";

        $content .= "---\n";
        $content .= "*Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "*\n";

        return $content;
    }

    // Placeholder methods for HTML generation
    protected function generateHtmlOverview() { return "<h1>Overview</h1>"; }
    protected function generateHtmlApi() { return "<h1>API Documentation</h1>"; }
    protected function generateHtmlDatabase() { return "<h1>Database Documentation</h1>"; }
    protected function generateHtmlModels() { return "<h1>Models Documentation</h1>"; }
    protected function generateHtmlControllers() { return "<h1>Controllers Documentation</h1>"; }
    protected function generateHtmlRoutes() { return "<h1>Routes Documentation</h1>"; }
    protected function generateHtmlConfiguration() { return "<h1>Configuration Documentation</h1>"; }
    protected function generateHtmlDeployment() { return "<h1>Deployment Documentation</h1>"; }
    protected function generateHtmlMaintenance() { return "<h1>Maintenance Documentation</h1>"; }
    protected function generateHtmlIndex() { return "<h1>Documentation Index</h1>"; }
    protected function generateMarkdownModels() { return "# Models Documentation\n\n"; }
    protected function generateMarkdownControllers() { return "# Controllers Documentation\n\n"; }
    protected function generateMarkdownRoutes() { return "# Routes Documentation\n\n"; }
    protected function generateMarkdownConfiguration() { return "# Configuration Documentation\n\n"; }
    protected function generateMarkdownDeployment() { return "# Deployment Documentation\n\n"; }
    protected function generateMarkdownMaintenance() { return "# Maintenance Documentation\n\n"; }
}
