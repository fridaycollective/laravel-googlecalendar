<?php

namespace FridayCollective\LaravelGoogleCalendar\Http\Controllers;

use FridayCollective\LaravelGoogleCalendar\Handlers\CalendarListHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class UserGoogleCalendarController
 * @package FridayCollective\LaravelGoogleCalendar\Http\Controllers
 */
class UserGoogleCalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return auth()->user()->googleCalendars;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = auth()->user();

        if ($user->calendarIntegrationConfig) {
            CalendarListHandler::syncCalendarList($user->calendarIntegrationConfig);
        }
        return response()->json(['message' => 'Success'], 200);
    }
}
