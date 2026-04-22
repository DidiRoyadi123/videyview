<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminCrudActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrudActivityController extends Controller
{
    public function undo(AdminCrudActivity $crudActivity)
    {
        if ($crudActivity->undone_at) {
            return back()->with('error', 'Aksi ini sudah dibatalkan sebelumnya.');
        }

        try {
            DB::transaction(function () use ($crudActivity) {
                $crudActivity->subject_type::withoutCrudLogging(function() use ($crudActivity) {
                    $modelClass = $crudActivity->subject_type;
                    $id = $crudActivity->subject_id;

                    if ($crudActivity->event === 'created') {
                        // Undo Create: Delete the inserted model
                        $model = $modelClass::find($id);
                        if ($model) {
                            $model->delete();
                        }
                    } elseif ($crudActivity->event === 'updated') {
                        // Undo Update: Revert to before_state
                        $model = $modelClass::find($id);
                        if ($model && $crudActivity->state_before) {
                            // Turn off timestamps if restoring an exact old state might be required, 
                            // but usually updating to past state is just an update.
                            $model->update($crudActivity->state_before);
                        }
                    } elseif ($crudActivity->event === 'deleted') {
                        // Undo Delete: Insert back the model with exact data
                        if ($crudActivity->state_before) {
                            DB::table((new $modelClass)->getTable())->insert($crudActivity->state_before);
                        }
                    }
                });

                $crudActivity->update(['undone_at' => now()]);
            });

            return back()->with('success', 'Aksi berhasil dibatalkan (Undo).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan aksi: ' . $e->getMessage());
        }
    }

    public function redo(AdminCrudActivity $crudActivity)
    {
        if (!$crudActivity->undone_at) {
            return back()->with('error', 'Aksi ini belum dibatalkan, tidak bisa di-redo.');
        }

        try {
            DB::transaction(function () use ($crudActivity) {
                $crudActivity->subject_type::withoutCrudLogging(function() use ($crudActivity) {
                    $modelClass = $crudActivity->subject_type;
                    $id = $crudActivity->subject_id;

                    if ($crudActivity->event === 'created') {
                        // Redo Create: Insert back
                        if ($crudActivity->state_after) {
                            DB::table((new $modelClass)->getTable())->insert($crudActivity->state_after);
                        }
                    } elseif ($crudActivity->event === 'updated') {
                        // Redo Update: Apply after_state
                        $model = $modelClass::find($id);
                        if ($model && $crudActivity->state_after) {
                            $model->update($crudActivity->state_after);
                        }
                    } elseif ($crudActivity->event === 'deleted') {
                        // Redo Delete: Delete it again
                        $model = $modelClass::find($id);
                        if ($model) {
                            $model->delete();
                        }
                    }
                });

                $crudActivity->update(['undone_at' => null]);
            });

            return back()->with('success', 'Aksi berhasil dipulihkan (Redo).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memulihkan aksi: ' . $e->getMessage());
        }
    }
}
