<?php

namespace FridayCollective\LaravelGoogleCalendar\Services\Google\Calendar;

use DateTime;
use Carbon\Carbon;
use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;
use FridayCollective\LaravelGoogleCalendar\Models\UserGoogleCalendar;
use Google_Service_Calendar_Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Str;

class GoogleEvent
{
    /** @var \Google_Service_Calendar_Event */
    public $googleEvent;

    /** @var array */
    protected $attendees;

    protected $calendarIntegrationConfig;

    protected $userGoogleCalendar;

    protected $googleService;

    public function __construct(
        UserCalendarIntegrationConfig $calendarIntegrationConfig,
        $userGoogleCalendar,
        GoogleCalendarService $googleService
    )
    {
        $this->calendarIntegrationConfig = $calendarIntegrationConfig;
        $this->userGoogleCalendar = $userGoogleCalendar;
        $this->attendees = [];
        $this->googleService = $googleService;
        $this->googleEvent = new Google_Service_Calendar_Event;
    }

    public function setUserGoogleCalendar(UserGoogleCalendar $userGoogleCalendar)
    {
        $this->userGoogleCalendar = $userGoogleCalendar;
    }

    public function createFromGoogleCalendarEvent(Google_Service_Calendar_Event $googleEvent, $calendarId)
    {
        $event = new GoogleEvent($this->calendarIntegrationConfig, $this->userGoogleCalendar, $this->googleService);

        $event->googleEvent = $googleEvent;
        $event->calendarId = $calendarId;

        return $event;
    }

    public function get(Carbon $startDateTime = null, Carbon $endDateTime = null, array $queryParameters = [], string $calendarId = null) : Collection
    {

        $googleEvents = $this->googleService->listEvents($this->calendarIntegrationConfig, $calendarId, $startDateTime, $endDateTime, $queryParameters);
        if(!$googleEvents) {
            return null;
        }
        return $googleEvents->map(function (Google_Service_Calendar_Event $event) use ($calendarId) {
                return $this->createFromGoogleCalendarEvent($event, $calendarId);
            })
            ->values();
    }

    public function __get($name)
    {
        $name = $this->getFieldName($name);

        if ($name === 'sortDate') {
            return $this->getSortDate();
        }

        $value = Arr::get($this->googleEvent, $name);

        if (in_array($name, ['start.date', 'end.date']) && $value) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime']) && $value) {
            $value = Carbon::createFromFormat(DateTime::RFC3339, $value);
        }

        return $value;
    }

    public function __set($name, $value)
    {
        $name = $this->getFieldName($name);

        if (in_array($name, ['start.date', 'end.date', 'start.dateTime', 'end.dateTime'])) {
            $this->setDateProperty($name, $value);

            return;
        }
        Arr::set($this->googleEvent, $name, $value);
    }

    public function exists(): bool
    {
        return $this->id != '';
    }

    public function isAllDayEvent(): bool
    {
        return is_null($this->googleEvent['start']['dateTime']);
    }

    public function save()
    {
        $method = $method ?? ($this->exists() ? 'updateEvent' : 'insertEvent');
        $this->googleEvent->setAttendees($this->attendees);
        return $this->googleService->$method($this->googleEvent, $this->userGoogleCalendar->google_id);
    }

    public function update(array $attributes, $optParams = []): self
    {
        foreach ($attributes as $name => $value) {
            $this->$name = $value;
        }

        return $this->save('updateEvent', $optParams);
    }

    public function delete(string $eventId = null)
    {
        $this->googleService->deleteEvent($eventId);
    }

    public function getEvent($eventId, $calendarId)
    {
        $event = $this->googleService->getEvent($eventId, $calendarId);
        if ($event) {
            return $this->createFromGoogleCalendarEvent($event, $calendarId);
        }
    }

    public function addAttendee(array $attendees)
    {
        $this->attendees[] = $attendees;
    }

    public function getSortDate(): string
    {
        if ($this->startDate) {
            return $this->startDate;
        }

        if ($this->startDateTime) {
            return $this->startDateTime;
        }

        return '';
    }

    protected function setDateProperty(string $name, Carbon $date)
    {
        $eventDateTime = new Google_Service_Calendar_EventDateTime;

        if (in_array($name, ['start.date', 'end.date'])) {
            $eventDateTime->setDate($date->format('Y-m-d'));
            $eventDateTime->setTimezone($date->getTimezone());
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime'])) {
            $eventDateTime->setDateTime($date->format(DateTime::RFC3339));
            $eventDateTime->setTimezone($date->getTimezone());
        }

        if (Str::startsWith($name, 'start')) {
            $this->googleEvent->setStart($eventDateTime);
        }

        if (Str::startsWith($name, 'end')) {
            $this->googleEvent->setEnd($eventDateTime);
        }
    }

    protected function getFieldName(string $name): string
    {
        return [
                   'name' => 'summary',
                   'description' => 'description',
                   'startDate' => 'start.date',
                   'endDate' => 'end.date',
                   'startDateTime' => 'start.dateTime',
                   'endDateTime' => 'end.dateTime',
               ][$name] ?? $name;
    }
}
