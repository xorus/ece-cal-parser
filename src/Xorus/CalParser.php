<?php

namespace Xorus;

use Eluceo\iCal\Component\Calendar;
use ICal\ICal;

class CalParser
{
    private $source = '';

    public function __construct($sourceURI) {
        if (preg_match('/https?:\/\/ical\.campuseiffel\.fr\/newical\/ical-[^-]+-[^-]+.ics/', $sourceURI)) {
            $this->source = $sourceURI;
        } else {
            throw new \Exception('Cette source n\'est pas autorisée');
        }
    }

    public function parseCalendar()
    {
        $calendar = new ICal($this->source);
        $newCalendar = new Calendar('Planning Cours');

        /** @var \ICal\EventObject $event */
        foreach ($calendar->events() as $event) {
            $summary = $event->summary;
            if (preg_match('/[A-Z]{3}-[A-Z]{3}\d{3} - ([^-]+) - /', $summary, $matches)) {
                $summary = $matches[1];
                $summary = preg_replace('/\(s?i? ?app\)/i', '', $summary);
            }

            $location = $event->location;
            if (preg_match('/^[^-]* ?-? ?E\d - ([^-]+) -/', $location, $matches)) {
                $location = $matches[1];
            }

            $event->summary = $summary;
            $event->location = $location;
            $desc = str_replace("\\n", PHP_EOL, $event->description);

            $vEvent = new \Eluceo\iCal\Component\Event();
            $vEvent->setDtStart(new \DateTime($event->dtstart))
                ->setDtEnd(new \DateTime($event->dtend))
                ->setSummary($summary)
                ->setLocation($location)
                ->setDescription($desc);

            $newCalendar->addComponent($vEvent);
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');
        echo $newCalendar->render();
    }
}