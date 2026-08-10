<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\NumberSeries;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class NumberSeriesService
{
    public function __construct(private readonly DatabaseManager $database) {}

    public function next(string $key, ?FinancialYear $financialYear = null): string
    {
        return $this->database->transaction(function () use ($key, $financialYear): string {
            $series = NumberSeries::query()->where('series_key', $key)->whereNull('financial_year_id')->lockForUpdate()->first();

            if ($series === null) {
                throw (new ModelNotFoundException)->setModel(NumberSeries::class, [$key]);
            }

            $yearSeries = $series;
            if ($series->financial_year_aware) {
                if ($financialYear === null) {
                    $financialYear = app(FinancialYearResolver::class)->current();
                }
                $yearSeries = NumberSeries::query()->where('series_key', $key)->where('financial_year_id', $financialYear->id)->lockForUpdate()->first();
                if ($yearSeries === null) {
                    $yearSeries = NumberSeries::query()->create([
                        'series_key' => $key,
                        'document_name' => $series->document_name,
                        'prefix' => $series->prefix,
                        'suffix' => $series->suffix,
                        'padding' => $series->padding,
                        'next_number' => 1,
                        'reset_policy' => $series->reset_policy,
                        'financial_year_aware' => true,
                        'financial_year_id' => $financialYear->id,
                        'status' => $series->status,
                    ]);
                    $yearSeries = NumberSeries::query()->whereKey($yearSeries->id)->lockForUpdate()->firstOrFail();
                }
            }

            if ($yearSeries->status !== 'Active') {
                throw new \RuntimeException("Number series [{$key}] is inactive.");
            }

            $number = $this->allocate($yearSeries);

            return $this->format($yearSeries, $number, $financialYear);
        });
    }

    public function nextInteger(string $key, ?FinancialYear $financialYear = null): int
    {
        return $this->database->transaction(function () use ($key, $financialYear): int {
            $series = NumberSeries::query()->where('series_key', $key)->whereNull('financial_year_id')->lockForUpdate()->firstOrFail();
            if ($series->financial_year_aware) {
                $this->next($key, $financialYear);

                return (int) NumberSeries::query()->where('series_key', $key)->where('financial_year_id', $financialYear?->id)->value('next_number') - 1;
            }

            if ($series->status !== 'Active') {
                throw new \RuntimeException("Number series [{$key}] is inactive.");
            }

            return $this->allocate($series);
        });
    }

    public function format(NumberSeries $series, int $number, ?FinancialYear $financialYear = null): string
    {
        $sequence = $series->padding > 0 ? str_pad((string) $number, $series->padding, '0', STR_PAD_LEFT) : (string) $number;
        $year = $financialYear?->code;
        $prefix = str_replace('{FY}', (string) $year, $series->prefix);
        $suffix = str_replace('{FY}', (string) $year, (string) ($series->suffix ?? ''));

        return $prefix.$sequence.$suffix;
    }

    private function allocate(NumberSeries $series): int
    {
        $number = (int) $series->next_number;
        $series->increment('next_number');

        return $number;
    }
}
