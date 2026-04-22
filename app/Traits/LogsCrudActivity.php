<?php

namespace App\Traits;

use App\Models\AdminCrudActivity;
use Illuminate\Support\Facades\Auth;

trait LogsCrudActivity
{
    public static $disableCrudLogging = false;

    protected static function bootLogsCrudActivity()
    {
        static::created(function ($model) {
            if (self::$disableCrudLogging) return;
            self::logCrudEvent('created', $model);
        });

        static::updated(function ($model) {
            if (self::$disableCrudLogging) return;
            if ($model->wasChanged()) {
                self::logCrudEvent('updated', $model);
            }
        });

        static::deleted(function ($model) {
            if (self::$disableCrudLogging) return;
            self::logCrudEvent('deleted', $model);
        });
    }

    public static function withoutCrudLogging(callable $callback)
    {
        $previous = self::$disableCrudLogging;
        self::$disableCrudLogging = true;
        try {
            return $callback();
        } finally {
            self::$disableCrudLogging = $previous;
        }
    }

    protected static function logCrudEvent($event, $model)
    {
        $userId = Auth::id();
        
        $subjectId = $model->getKey();
        if (!$subjectId) return;

        $stateBefore = null;
        $stateAfter = null;

        if ($event === 'created') {
            $stateAfter = $model->toArray();
        } elseif ($event === 'updated') {
            $changes = $model->getChanges();
            $original = $model->getOriginal();
            
            $stateBefore = [];
            $stateAfter = [];
            
            foreach ($changes as $key => $value) {
                if ($key === 'updated_at') continue;
                $stateBefore[$key] = array_key_exists($key, $original) ? $original[$key] : null;
                $stateAfter[$key] = $value;
            }
            
            if (empty($stateBefore) && empty($stateAfter)) return;
        } elseif ($event === 'deleted') {
            $stateBefore = $model->toArray();
        }

        AdminCrudActivity::create([
            'user_id' => $userId,
            'subject_type' => get_class($model),
            'subject_id' => $subjectId,
            'event' => $event,
            'state_before' => $stateBefore,
            'state_after' => $stateAfter,
        ]);
    }
}
