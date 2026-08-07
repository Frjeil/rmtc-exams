<?php

namespace App\Services;

use App\Exceptions\HolidayServiceUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NagerHolidayService
{
    /**
     * Verifica se la data è un giorno festivo in Italia.
     */
    public function isHoliday(string $date): bool
    {
        return $this->holidaysForYear($date)->contains(
            fn (array $holiday): bool => $holiday['date'] === $date,
        );
    }

    /**
     * Festivi dell'anno della data, memorizzati in cache.
     *
     * Nota: in cache viene salvato un array (mai oggetti): alcuni store
     * (es. database) serializzano il valore e oggetti come Collection
     * possono diventare __PHP_Incomplete_Class al ripristino.
     */
    public function holidaysForYear(string $date): Collection
    {
        $year = CarbonImmutable::parse($date)->year;

        $holidays = Cache::remember(
            "nager_holidays_{$year}",
            now()->diffInSeconds(now()->endOfYear()->addDay()),
            fn (): array => $this->fetch($year),
        );

        return collect($holidays);
    }

    /**
     * Chiama Nager.Date. In caso di timeout, errore HTTP o risposta
     * malformata lancia HolidayServiceUnavailableException (fail-closed).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetch(int $year): array
    {
        $url = sprintf(
            '%s/PublicHolidays/%d/%s',
            config('exams.nager.base_url'),
            $year,
            config('exams.nager.country_code'),
        );

        try {
            $response = Http::timeout(config('exams.nager.timeout'))
                ->acceptJson()
                ->get($url);

            $response->throw();

            $data = $response->json();

            if (! is_array($data)) {
                throw new HolidayServiceUnavailableException(
                    'Il servizio dei giorni festivi ha restituito una risposta non valida.',
                );
            }

            return $data;
        } catch (ConnectionException|RequestException $e) {
            throw new HolidayServiceUnavailableException(
                'Il servizio dei giorni festivi non è disponibile ('.$e->getMessage().'). Riprova più tardi.',
                $e,
            );
        }
    }
}
