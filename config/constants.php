<?php
/**
 * Shared labels and option lists — Home Services Dispatch System
 */
declare(strict_types=1);

function service_options(): array
{
    return [
        'electrician' => 'Electrician',
        'ac_repair'     => 'AC Repair',
        'plumber'       => 'Plumber',
    ];
}

/** Area keys stored in DB; labels for UI (Mumbai hyperlocal). */
function area_options(): array
{
    return [
        'vikhroli'   => 'Vikhroli',
        'ghatkopar'  => 'Ghatkopar',
        'powai'      => 'Powai',
        'bhandup'    => 'Bhandup',
        'mulund'     => 'Mulund',
        'kanjurmarg' => 'Kanjurmarg',
    ];
}

function priority_options(): array
{
    return [
        'normal' => 'Normal',
        'urgent' => 'Urgent',
    ];
}

function label_service(string $key): string
{
    return service_options()[$key] ?? $key;
}

function label_area(string $key): string
{
    return area_options()[$key] ?? $key;
}
