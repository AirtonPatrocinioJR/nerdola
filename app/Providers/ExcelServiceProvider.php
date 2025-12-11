<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Classes\PHPExcel;
use Maatwebsite\Excel\Classes\FormatIdentifier;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Parsers\ViewParser;
use Maatwebsite\Excel\Readers\Html;
use Config;
use PHPExcel_Settings;
use PHPExcel_Shared_Font;

class ExcelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the format identifier
        $this->app->singleton('excel.identifier', function ($app) {
            return new FormatIdentifier($app['files']);
        });

        // Bind the PHPExcel class
        $this->app->singleton('phpexcel', function ($app) {
            // Set locale
            $locale = Config::get('app.locale', 'en_us');
            PHPExcel_Settings::setLocale($locale);

            // Set the caching settings
            new \Maatwebsite\Excel\Classes\Cache();

            // Init phpExcel
            return new PHPExcel();
        });

        // Bind the laravel excel reader
        $this->app->singleton('excel.reader', function ($app) {
            return new LaravelExcelReader($app['files'], $app['excel.identifier']);
        });

        // Bind the html reader class
        $this->app->singleton('excel.readers.html', function ($app) {
            return new Html();
        });

        // Bind the view parser
        $this->app->singleton('excel.parsers.view', function ($app) {
            return new ViewParser($app['excel.readers.html']);
        });

        // Bind the excel writer
        $this->app->singleton('excel.writer', function ($app) {
            return new LaravelExcelWriter($app->make('Illuminate\Http\Response'), $app['files'], $app['excel.identifier']);
        });

        // Bind the Excel class and inject its dependencies
        $this->app->singleton('excel', function ($app) {
            return new Excel($app['phpexcel'], $app['excel.reader'], $app['excel.writer']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set the autosizing settings
        $method = Config::get('excel::export.autosize-method', PHPExcel_Shared_Font::AUTOSIZE_METHOD_APPROX);
        PHPExcel_Shared_Font::setAutoSizeMethod($method);
    }
}

