<?php

namespace Xorus;

use Eluceo\iCal\Component\Calendar;
use ICal\ICal;
use Stash\Driver\FileSystem;
use Stash\Pool;

class CalParser
{
    private $source = '';

    public function __construct($sourceURI)
    {
        if (preg_match('/https?:\/\/ical\.campuseiffel\.fr\/newical\/ical-[^-]+-[^-]+.ics/', $sourceURI)) {
            $this->source = $sourceURI;
        } else {
            throw new \Exception('Cette source n\'est pas autorisée');
        }
    }

    private function cleanifyString($string)
    {
        $string = str_replace('&#039;', '\'', $string);
        $string = str_replace('\,', ',', $string);
        $string = str_replace('\\n', PHP_EOL, $string);

        return $string;
    }

    private function downloadCalendar()
    {
        $driver = new FileSystem([]);
        $pool = new Pool($driver);
        $item = $pool->getItem(md5($this->source));
        $data = $item->get();

        if ($item->isMiss()) {
            $item->lock();
            $data = file($this->source);
            $item->expiresAfter(1300);
            $pool->save($item->set($data));
        }

        return $data;
    }

    public function parseCalendar()
    {
        $calendar = new ICal($this->downloadCalendar());
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

            $summary = $this->cleanifyString($summary);
            $location = $this->cleanifyString($location);
            $desc = $this->cleanifyString($event->description);

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