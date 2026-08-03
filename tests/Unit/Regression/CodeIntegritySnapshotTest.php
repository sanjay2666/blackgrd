<?php

namespace Tests\Unit\Regression;

use Illuminate\Routing\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class CodeIntegritySnapshotTest extends TestCase
{
    public function test_active_route_target_failures_match_the_task_1_1_snapshot(): void
    {
        $expected = [];

        $this->assertSame($expected, $this->activeRouteTargetFailures());
    }

    public function test_missing_controller_model_imports_match_the_task_1_1_snapshot(): void
    {
        $expected = [
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabColourFastness',
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabRequirement',
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabTest',
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabTestRequest',
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabTestResult',
            'app/Http/Controllers/LabTestController.php -> App\\Models\\LabTestStandard',
            'app/Http/Controllers/WorkProcessRequirementController.php -> App\\Models\\DyeingPlanningItem',
            'app/Http/Controllers/WorkProcessRequirementController.php -> App\\Models\\PackagingOrder',
            'app/Http/Controllers/WorkProcessRequirementController.php -> App\\Models\\PackagingOrderItem',
            'app/Http/Controllers/WorkProcessRequirementController.php -> App\\Models\\PackagingProcessRequirement',
            'app/Http/Controllers/WorkProcessRequirementController.php -> App\\Models\\WorkPrintProcessRequirement',
        ];

        $this->assertSame($expected, $this->missingControllerModelImports());
    }

    public function test_missing_relationship_targets_match_the_task_1_1_snapshot(): void
    {
        $expected = [];

        $this->assertSame($expected, $this->missingRelationshipTargets());
    }

    public function test_missing_literal_controller_views_match_the_task_1_1_snapshot(): void
    {
        $expected = [
            'app/Http/Controllers/LabTestController.php -> html.labrequest.add-lab-test-result',
            'app/Http/Controllers/LabTestController.php -> html.labrequest.check_lab_report',
            'app/Http/Controllers/LabTestController.php -> html.labrequest.print_lab_report',
            'app/Http/Controllers/LabTestController.php -> html.labrequest.show-labrequest',
            'app/Http/Controllers/LabTestController.php -> pdf.lab-test-report',
            'app/Http/Controllers/WorkOrderController.php -> frontend.workorders.show-dyed-workorders',
            'app/Http/Controllers/WorkPurchaseRequirementController.php -> html.workpurchaserequirements.show-work-purchase-requirement',
        ];

        $this->assertSame($expected, $this->missingLiteralControllerViews());
    }

    /** @return list<string> */
    private function activeRouteTargetFailures(): array
    {
        $failures = [];

        /** @var Route $route */
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);
            $verb = $route->methods()[0] ?? 'UNKNOWN';

            if (! class_exists($class)) {
                $failures[] = "{$verb} {$route->uri()} -> {$action} (missing controller)";
            } elseif (! method_exists($class, $method)) {
                $failures[] = "{$verb} {$route->uri()} -> {$action} (missing method)";
            }
        }

        sort($failures);

        return $failures;
    }

    /** @return list<string> */
    private function missingControllerModelImports(): array
    {
        $failures = [];

        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/^use (App\\\\Models\\\\[A-Za-z0-9_]+);/m', $contents, $matches);

            foreach ($matches[1] as $class) {
                if (! class_exists($class)) {
                    $failures[] = $this->relativePath($file).' -> '.$class;
                }
            }
        }

        sort($failures);

        return array_values(array_unique($failures));
    }

    /** @return list<string> */
    private function missingRelationshipTargets(): array
    {
        $failures = [];

        foreach ($this->phpFiles(app_path('Models')) as $file) {
            $contents = file_get_contents($file);
            preg_match_all(
                '/function\s+([A-Za-z0-9_]+)\s*\([^)]*\).*?return\s+\$this->(?:belongsTo|hasOne|hasMany|belongsToMany)\(([A-Za-z0-9_]+)::class/s',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $class = 'App\\Models\\'.$match[2];
                if (! class_exists($class)) {
                    $failures[] = $this->relativePath($file).'::'.$match[1].' -> '.$class;
                }
            }
        }

        sort($failures);

        return array_values(array_unique($failures));
    }

    /** @return list<string> */
    private function missingLiteralControllerViews(): array
    {
        $failures = [];

        foreach ($this->phpFiles(app_path('Http/Controllers')) as $file) {
            $contents = file_get_contents($file);
            preg_match_all("/(?:view|View::make)\\('([^']+)'/", $contents, $matches);

            foreach ($matches[1] as $view) {
                if (! view()->exists($view)) {
                    $failures[] = $this->relativePath($file).' -> '.$view;
                }
            }
        }

        sort($failures);

        return array_values(array_unique($failures));
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function relativePath(string $file): string
    {
        return str_replace('\\', '/', substr($file, strlen(base_path()) + 1));
    }
}
