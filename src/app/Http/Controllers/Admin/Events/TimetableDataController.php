<?php

namespace App\Http\Controllers\Admin\Events;

use Illuminate\Support\Facades\Session;

use App\Models\Event;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TimetableDataController extends Controller
{
    /**
     * Store Timetable Data to Database
     * @param  Event          $event
     * @param  EventTimetable $timetable
     * @param  Request        $request
     * @return Redirect
     */
    public function store(Event $event, EventTimetable $timetable, Request $request)
    {
        $rules = [
            'name'          => 'required',
            'start_time'    => 'required'
        ];
        $messages = [
            'name.required'         => 'Name is required',
            'start_time.required'   => 'Start Time is required',
        ];
        $this->validate($request, $rules, $messages);

        $data                       = new EventTimetableData();
        $data->event_timetable_id   = $timetable->id;
        $data->start_time           = $request->start_time;
        $data->name                 = $request->name;
        $data->desc                 = $request->desc;
        
        if (!$data->save()) {
            Session::flash('alert-danger', 'Cannot save Timetable Slot!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully saved Timetable Slot!');
        return Redirect::back();
    }

    /**
     * Update Timetable Data
     * @param  Event              $event
     * @param  EventTimetable     $timetable
     * @param  EventTimetableData $data
     * @param  Request            $request
     * @return Redirect
     */
    public function update(Event $event, EventTimetable $timetable, EventTimetableData $data, Request $request)
    {
        if ($data->event_timetable_id != $timetable->id) {
            abort(404);
        }
        $rules = [
            'name'          => 'filled',
            'start_time'    => 'filled'
        ];
        $messages = [
            'name.filled'       => 'Name cannot be empty',
            'start_time.filled' => 'Start Time cannot be empty',
        ];
        $this->validate($request, $rules, $messages);

        $data->start_time   = $request->start_time;
        $data->name         = $request->name;
        $data->desc         = $request->desc;

        if (!$data->save()) {
            Session::flash('alert-danger', 'Cannot update Timetable Slot!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully updated Timetable Slot!');
        return Redirect::back();
    }

    /**
     * Delete Timetable Slot
     * @param  Event              $event
     * @param  EventTimetable     $timetable
     * @param  EventTimetableData $data
     * @return Redirect
     */
    public function destroy(Event $event, EventTimetable $timetable, EventTimetableData $data)
    {
        if ($data->event_timetable_id != $timetable->id) {
            abort(404);
        }
        if (!$data->delete()) {
            Session::flash('alert-danger', 'Cannot delete Timetable Slot!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully deleted Timetable Slot!');
        return Redirect::back();
    }

    /**
     * Save the whole slot grid in one go: rows with an id are updated (or
     * removed when ticked), rows without one are added, and blank new rows
     * are skipped.
     * @param  Event          $event
     * @param  EventTimetable $timetable
     * @param  Request        $request
     * @return Redirect
     */
    public function saveAll(Event $event, EventTimetable $timetable, Request $request)
    {
        if ($timetable->event_id != $event->id) {
            abort(404);
        }

        $rows = [];
        foreach ((array) $request->input('slots', []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            $isNew = empty($row['id']);
            if ($isNew && ($row['name'] ?? '') === '' && ($row['desc'] ?? '') === '') {
                continue;
            }
            if ($isNew && !empty($row['remove'])) {
                continue;
            }
            $rows[$index] = $row;
        }

        $validator = Validator::make(['slots' => $rows], [
            'slots.*.id'     => 'nullable|integer',
            'slots.*.remove' => 'nullable|boolean',
            'slots.*.name'   => 'exclude_if:slots.*.remove,1|required|string|max:255',
            'slots.*.desc'   => 'exclude_if:slots.*.remove,1|nullable|string|max:255',
            'slots.*.day'    => 'exclude_if:slots.*.remove,1|required|date_format:Y-m-d',
            'slots.*.time'   => 'exclude_if:slots.*.remove,1|required|date_format:H:i',
        ], [
            'slots.*.name.required'    => 'Row :position needs a name',
            'slots.*.name.max'         => 'Row :position has a name over 255 characters',
            'slots.*.desc.max'         => 'Row :position has a description over 255 characters',
            'slots.*.day.required'     => 'Row :position needs a day',
            'slots.*.day.date_format'  => 'Row :position has a day that is not a date',
            'slots.*.time.required'    => 'Row :position needs a time',
            'slots.*.time.date_format' => 'Row :position needs a time like 20:30',
        ]);
        $validator->after(function ($validator) use ($rows, $timetable) {
            $ids = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $known = EventTimetableData::where('event_timetable_id', $timetable->id)
                ->whereIn('id', $ids)
                ->pluck('id');
            if ($ids->diff($known)->isNotEmpty()) {
                $validator->errors()->add('slots', 'Some slots no longer exist. Reload the page and try again.');
            }
        });
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $existing = EventTimetableData::where('event_timetable_id', $timetable->id)->get()->keyBy('id');
        $added = $updated = $removed = 0;

        DB::transaction(function () use ($rows, $existing, $timetable, &$added, &$updated, &$removed) {
            foreach ($rows as $row) {
                $slot = empty($row['id']) ? null : $existing->get((int) $row['id']);
                if ($slot && !empty($row['remove'])) {
                    $slot->delete();
                    $removed++;
                    continue;
                }
                if (!$slot) {
                    $slot = new EventTimetableData();
                    $slot->event_timetable_id = $timetable->id;
                }
                $slot->start_time = $row['day'] . ' ' . $row['time'] . ':00';
                $slot->name       = $row['name'];
                $slot->desc       = ($row['desc'] ?? '') === '' ? null : $row['desc'];
                if (!$slot->exists) {
                    $slot->save();
                    $added++;
                } elseif ($slot->isDirty()) {
                    $slot->save();
                    $updated++;
                }
            }
        });

        $parts = array_filter([
            $added ? $added . ' added' : null,
            $updated ? $updated . ' updated' : null,
            $removed ? $removed . ' removed' : null,
        ]);
        Session::flash(
            'alert-success',
            $parts ? 'Saved timetable: ' . implode(', ', $parts) . '.' : 'No changes to save.'
        );
        return Redirect::back();
    }
}
