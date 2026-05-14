<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function current()
    {
        $announcements = Announcement::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get();

        return response()->json(['data' => $announcements]);
    }
}