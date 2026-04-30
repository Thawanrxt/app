<?php

namespace App\Http\Controllers;

use App\Models\TrackingAdvice;
use Illuminate\Support\Facades\Schema;
use Throwable;

abstract class Controller
{
    protected function resolveTrackingAdvice(string $pageKey): ?TrackingAdvice
    {
        try {
            if (! Schema::hasTable('tracking_advices')) {
                return null;
            }

            return TrackingAdvice::query()
                ->where('page_key', $pageKey)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }
}
