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

            $query = TrackingAdvice::query();

            if (Schema::hasColumn('tracking_advices', 'page_key')) {
                return $query
                    ->where('page_key', $pageKey)
                    ->first();
            }

            if (Schema::hasColumn('tracking_advices', 'detail_url')) {
                return $query
                    ->where('detail_url', 'like', '%' . $pageKey)
                    ->first();
            }

            if (Schema::hasColumn('tracking_advices', 'activity_id')) {
                $activityId = str_contains($pageKey, '-') ? substr($pageKey, strrpos($pageKey, '-') + 1) : $pageKey;

                return $query
                    ->where('activity_id', $activityId)
                    ->first();
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
