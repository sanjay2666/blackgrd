<?php

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseSafetyServiceProvider;
use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;

return [
    AppServiceProvider::class,
    DatabaseSafetyServiceProvider::class,
    DomPdfServiceProvider::class,
    ExcelServiceProvider::class,
];
