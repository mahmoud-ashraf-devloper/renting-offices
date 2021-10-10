<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;

class OfficeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return OfficeResource::collection(
            Office::query()
                ->where('approval_status', Office::APPROVAL_APPROVED)
                ->where('hidden', false)
                ->when(request('host'),
                        fn(Builder $builder)
                            => $builder->where('user_id', request('host')))
                ->when(request('user'),
                        fn(Builder $builder)
                            => $builder->whereRelation('reservations', 'user_id', '=', request('user')))
                ->when(
                request('lat') && request('lng')
                    ,fn(Builder $builder)
                        => $builder->nearestTo(request('lat'), request('lng'))
//                    ,fn(Builder $builder)
//                        => $builder->orderBy('id', 'DESC')
                )
                ->with(['user','images','tags'])
                ->withCount(['reservations' =>
                        fn(Builder $builder) => $builder->where('status', Reservation::STATUS_ACTIVE)
                ])
                ->paginate(20)
        );
    }
}
