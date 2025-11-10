<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\License;
use App\Models\LicenseCheckLog;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\Campaign;
use BackedEnum;

class PlatformFeatures extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-star';

    protected string $view = 'filament.pages.platform-features';

    protected static ?string $navigationLabel = 'Platform Features';

    protected static ?string $title = 'Platform Features & Capabilities';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function getViewData(): array
    {
        return [
            'contractFeatures' => $this->getContractFeatures(),
            'licenseFeatures' => $this->getLicenseFeatures(),
            'leadFeatures' => $this->getLeadFeatures(),
            'userFeatures' => $this->getUserFeatures(),
            'systemFeatures' => $this->getSystemFeatures(),
            'securityFeatures' => $this->getSecurityFeatures(),
            'integrations' => $this->getIntegrations(),
            'statistics' => $this->getPlatformStatistics(),
            'technicalStack' => $this->getTechnicalStack(),
        ];
    }

    protected function getContractFeatures(): array
    {
        return [
            'core' => [
                'Contract Creation' => [
                    'description' => 'Create contracts from templates with variable replacement',
                    'enabled' => true,
                    'routes' => ['api/contracts', 'admin/contracts/create'],
                ],
                'AI Contract Generation' => [
                    'description' => 'Generate contracts using GPT-4 based on natural language descriptions',
                    'enabled' => true,
                    'routes' => ['api/contracts/ai/generate'],
                ],
                'Digital Signatures' => [
                    'description' => 'Multi-party signing with drawn, typed, or uploaded signatures',
                    'enabled' => true,
                    'routes' => ['api/contracts/signatures'],
                ],
                'Email Notifications' => [
                    'description' => 'Automated emails for signature requests, signatures, and completion',
                    'enabled' => true,
                    'notifications' => ['ContractSignatureRequested', 'ContractSigned', 'ContractFullySigned'],
                ],
                'PDF Generation' => [
                    'description' => 'Generate PDF documents with embedded signatures',
                    'enabled' => true,
                    'library' => 'DomPDF',
                ],
                'Version Control' => [
                    'description' => 'Track all contract changes with version history',
                    'enabled' => true,
                    'model' => 'ContractVersion',
                ],
                'Template Management' => [
                    'description' => 'Create reusable templates with variables and categories',
                    'enabled' => true,
                    'count' => ContractTemplate::count(),
                ],
                'Approval Workflows' => [
                    'description' => 'Multi-level approval system for contracts',
                    'enabled' => true,
                    'model' => 'ContractApproval',
                ],
                'Contract Analytics' => [
                    'description' => 'Track contract value, status, and performance metrics',
                    'enabled' => true,
                    'routes' => ['api/contracts/statistics'],
                ],
            ],
            'statistics' => [
                'total_contracts' => Contract::count(),
                'active_contracts' => Contract::where('status', 'signed')->count(),
                'pending_signatures' => Contract::where('status', 'pending_signature')->count(),
                'templates' => ContractTemplate::count(),
                'total_signatures' => ContractSignature::count(),
            ],
        ];
    }

    protected function getLicenseFeatures(): array
    {
        return [
            'core' => [
                'License Management' => [
                    'description' => 'Create and manage software licenses for products',
                    'enabled' => true,
                    'types' => ['Trial', 'Monthly', 'Yearly', 'Lifetime'],
                ],
                'Domain Validation' => [
                    'description' => 'Verify licenses against authorized domains with 11 normalization rules',
                    'enabled' => true,
                    'edge_cases' => 11,
                ],
                'Public License Checker' => [
                    'description' => 'Public-facing page for license verification',
                    'enabled' => true,
                    'routes' => ['/license-checker', 'api/licenses/check'],
                ],
                'Rate Limiting' => [
                    'description' => 'Multi-layer rate limiting and brute force protection',
                    'enabled' => true,
                    'limits' => ['100 per hour per IP', '1000 per day per license'],
                ],
                'Grace Period' => [
                    'description' => '7-day grace period for expired licenses',
                    'enabled' => true,
                    'days' => 7,
                ],
                'License Transfers' => [
                    'description' => 'Transfer licenses between domains with limits',
                    'enabled' => true,
                    'default_max' => 3,
                ],
                'Automated Notifications' => [
                    'description' => 'Email notifications at 30, 7, 1 days before expiration',
                    'enabled' => true,
                    'schedule' => 'Daily at 09:30',
                ],
                'Audit Logging' => [
                    'description' => 'Complete audit trail of all license checks',
                    'enabled' => true,
                    'retention' => '90 days',
                ],
                'Reactivation Modes' => [
                    'description' => 'Multiple reactivation strategies',
                    'enabled' => true,
                    'modes' => ['Full', 'Extend', 'Resume'],
                ],
            ],
            'statistics' => [
                'total_licenses' => License::count(),
                'active_licenses' => License::where('status', 'active')->count(),
                'total_checks' => LicenseCheckLog::count(),
                'expired_licenses' => License::where('status', 'expired')->count(),
            ],
        ];
    }

    protected function getLeadFeatures(): array
    {
        return [
            'core' => [
                'Multi-Platform Discovery' => [
                    'description' => 'Discover leads from Reddit, X (Twitter), and more',
                    'enabled' => true,
                    'platforms' => ['Reddit', 'X/Twitter', 'LinkedIn'],
                ],
                'AI Reply Generation' => [
                    'description' => 'Generate personalized replies using GPT-4',
                    'enabled' => true,
                    'model' => 'GPT-4',
                ],
                'Lead Messaging' => [
                    'description' => 'Send and track messages to leads',
                    'enabled' => true,
                    'channels' => ['Comment', 'DM'],
                ],
                'Response Tracking' => [
                    'description' => 'Automated detection of lead responses',
                    'enabled' => true,
                    'platforms' => ['X DM (implemented)', 'Manual marking'],
                ],
                'Campaign Management' => [
                    'description' => 'Organize leads into campaigns with automation',
                    'enabled' => true,
                    'model' => 'Campaign',
                ],
                'Follow-up Automation' => [
                    'description' => 'Scheduled follow-ups with AI or templates',
                    'enabled' => true,
                    'modes' => ['AI', 'Template', 'Manual'],
                ],
                'Engagement Scoring' => [
                    'description' => 'Score leads based on engagement metrics',
                    'enabled' => true,
                    'factors' => ['Response rate', 'Time to response', 'Message count'],
                ],
                'SMTP Configuration' => [
                    'description' => 'Custom SMTP for email campaigns',
                    'enabled' => true,
                    'resource' => 'SmtpConfigResource',
                ],
            ],
            'statistics' => [
                'total_leads' => DB::table('evenleads_leads')->count(),
                'contacted_leads' => DB::table('evenleads_leads')->where('status', 'contacted')->count(),
                'campaigns' => DB::table('evenleads_campaigns')->count(),
            ],
        ];
    }

    protected function getUserFeatures(): array
    {
        return [
            'core' => [
                'User Authentication' => [
                    'description' => 'Secure authentication with Laravel Sanctum',
                    'enabled' => true,
                    'methods' => ['Email/Password', 'OAuth'],
                ],
                'Role-Based Access Control' => [
                    'description' => 'Granular permissions and role management',
                    'enabled' => true,
                    'roles' => ['Admin', 'User', 'Custom'],
                ],
                'Organization Management' => [
                    'description' => 'Multi-tenancy with organization structure',
                    'enabled' => true,
                    'features' => ['Team management', 'Resource isolation'],
                ],
                'Subscription & Billing' => [
                    'description' => 'Stripe-powered subscription management',
                    'enabled' => true,
                    'provider' => 'Stripe',
                ],
                'Plan Limits Enforcement' => [
                    'description' => 'Enforce feature limits based on subscription plans',
                    'enabled' => true,
                    'tracked' => ['Contracts', 'AI generations', 'Leads'],
                ],
                'Data Deletion Requests' => [
                    'description' => 'GDPR-compliant user data deletion',
                    'enabled' => true,
                    'compliance' => 'GDPR',
                ],
                'User Deletion Protection' => [
                    'description' => 'Prevent deletion of users with active resources',
                    'enabled' => true,
                    'checks' => ['Active licenses', 'Subscriptions', 'Contracts'],
                ],
            ],
            'statistics' => [
                'total_users' => User::count(),
                'active_subscriptions' => DB::table('subscriptions')->where('ends_at', '>', now())->count(),
                'admin_users' => User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->count(),
            ],
        ];
    }

    protected function getSystemFeatures(): array
    {
        return [
            'admin_panel' => [
                'Filament Admin' => [
                    'description' => 'Modern admin panel with 26 resources',
                    'enabled' => true,
                    'resources' => 26,
                    'framework' => 'Filament v3',
                ],
                'Dashboard Widgets' => [
                    'description' => 'Real-time statistics and metrics',
                    'enabled' => true,
                    'widgets' => ['Contract stats', 'License stats', 'Lead stats'],
                ],
                'Bulk Operations' => [
                    'description' => 'Perform actions on multiple records',
                    'enabled' => true,
                    'actions' => ['Export', 'Delete', 'Update status'],
                ],
            ],
            'content' => [
                'Page Management' => [
                    'description' => 'Create and manage website pages',
                    'enabled' => true,
                ],
                'Blog System' => [
                    'description' => 'Full-featured blog with posts and categories',
                    'enabled' => true,
                ],
                'FAQ Management' => [
                    'description' => 'Manage frequently asked questions',
                    'enabled' => true,
                ],
                'Testimonials' => [
                    'description' => 'Customer testimonials and reviews',
                    'enabled' => true,
                ],
                'Changelog' => [
                    'description' => 'Track platform updates and changes',
                    'enabled' => true,
                ],
                'Feedback System' => [
                    'description' => 'Collect and manage user feedback',
                    'enabled' => true,
                ],
            ],
            'automation' => [
                'Scheduled Tasks' => [
                    'description' => 'Automated cron jobs with monitoring',
                    'enabled' => true,
                    'jobs' => [
                        'Check expiring licenses (daily)',
                        'Cleanup license logs (weekly)',
                        'Archive expired contracts',
                        'Check lead responses',
                    ],
                ],
                'Queue System' => [
                    'description' => 'Background job processing',
                    'enabled' => true,
                    'driver' => 'Database/Redis',
                ],
                'Email Notifications' => [
                    'description' => 'Automated email notifications',
                    'enabled' => true,
                    'types' => 10,
                ],
            ],
        ];
    }

    protected function getSecurityFeatures(): array
    {
        return [
            'authentication' => [
                'Laravel Sanctum' => 'API token authentication',
                'Session Management' => 'Secure session handling',
                'Password Hashing' => 'Bcrypt with configurable rounds',
                'Two-Factor Auth' => 'Optional 2FA support',
            ],
            'authorization' => [
                'Policy-Based' => 'ContractPolicy, LicensePolicy',
                'Role-Based Access Control' => 'Granular permissions',
                'Admin Gate' => 'Admin-only features',
            ],
            'protection' => [
                'CSRF Protection' => 'Built-in CSRF tokens',
                'XSS Prevention' => 'Input sanitization',
                'SQL Injection Protection' => 'Eloquent ORM',
                'Rate Limiting' => 'Multi-layer rate limits',
                'Brute Force Protection' => 'IP blocking after 20 failures',
                'Input Validation' => 'Comprehensive validation rules',
            ],
            'audit' => [
                'License Check Logs' => '90-day retention',
                'Contract Version History' => 'Complete audit trail',
                'Job Run Monitoring' => 'Automated failure alerts',
                'Activity Logging' => 'Laravel logs',
            ],
        ];
    }

    protected function getIntegrations(): array
    {
        return [
            'payment' => [
                'Stripe' => [
                    'type' => 'Payment Processing',
                    'features' => ['Subscriptions', 'Webhooks', 'Invoices'],
                    'enabled' => true,
                ],
            ],
            'ai' => [
                'OpenAI GPT-4' => [
                    'type' => 'AI Generation',
                    'features' => ['Contract generation', 'Reply generation', 'Content analysis'],
                    'enabled' => true,
                ],
            ],
            'social' => [
                'Reddit API' => [
                    'type' => 'Lead Discovery',
                    'features' => ['Post discovery', 'Comment posting'],
                    'enabled' => true,
                ],
                'X (Twitter) API' => [
                    'type' => 'Lead Discovery',
                    'features' => ['Tweet discovery', 'DM detection', 'Reply posting'],
                    'enabled' => true,
                ],
            ],
            'pdf' => [
                'DomPDF' => [
                    'type' => 'PDF Generation',
                    'features' => ['Contract PDFs', 'Signature embedding'],
                    'enabled' => true,
                ],
            ],
            'email' => [
                'SMTP' => [
                    'type' => 'Email Delivery',
                    'features' => ['Notifications', 'Campaigns', 'Custom SMTP'],
                    'enabled' => true,
                ],
            ],
        ];
    }

    protected function getPlatformStatistics(): array
    {
        return [
            'codebase' => [
                'PHP Files' => 318,
                'Lines of Code' => '36,799+',
                'Models' => 33,
                'Controllers' => 50,
                'Migrations' => 103,
                'Filament Resources' => 26,
                'Policies' => 2,
                'Notifications' => 10,
            ],
            'api' => [
                'Total Routes' => 267,
                'API Routes' => 100,
                'Admin Routes' => 96,
                'Web Routes' => 68,
            ],
            'database' => [
                'Tables' => 103,
                'Total Records' => $this->getTotalRecords(),
            ],
            'development' => [
                'Laravel Version' => '12.33.0',
                'PHP Version' => '8.4.13',
                'Filament Version' => '3.x',
                'Recent Commits' => 29,
            ],
        ];
    }

    protected function getTechnicalStack(): array
    {
        return [
            'backend' => [
                'Framework' => 'Laravel 12.33.0',
                'Language' => 'PHP 8.4.13',
                'Database' => 'MySQL 8.0+ / SQLite',
                'ORM' => 'Eloquent',
                'Queue' => 'Laravel Queue (Database/Redis)',
                'Cache' => 'Redis/File',
            ],
            'frontend' => [
                'Admin Panel' => 'Filament 3.x',
                'UI Components' => 'Livewire 3',
                'Styling' => 'Tailwind CSS',
                'JavaScript' => 'Alpine.js',
            ],
            'infrastructure' => [
                'Authentication' => 'Laravel Sanctum',
                'Payments' => 'Stripe',
                'PDF' => 'DomPDF',
                'AI' => 'OpenAI GPT-4',
                'Social APIs' => 'Reddit, X/Twitter',
            ],
            'development' => [
                'Testing' => 'PHPUnit',
                'Version Control' => 'Git',
                'Dependency Manager' => 'Composer 2.8.12',
                'Environment' => 'Production-ready',
            ],
        ];
    }

    protected function getTotalRecords(): int
    {
        return Contract::count() +
               License::count() +
               User::count() +
               DB::table('evenleads_leads')->count() +
               ContractTemplate::count();
    }
}
