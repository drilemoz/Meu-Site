<?php
declare(strict_types=1);
require_once __DIR__.'/bus.php';

/** Resumo público das próximas partidas programadas, sem estimar horários nem usar GPS. */
function bus_home_summary(PDO $db, string $siteCity, int $limit=3): array {
    $origin = bus_site_origin_city($siteCity);
    $limit = max(1, min(5, $limit));
    try {
        $routes = bus_routes_from_city($db, $origin);
        if (!$routes) return ['origin'=>$origin, 'status'=>'empty', 'trips'=>[]];
        $trips = bus_upcoming_departures($routes, bus_departures_for_routes($db, $routes), null, $limit);
        return ['origin'=>$origin, 'status'=>$trips ? 'ok' : 'no_departures', 'trips'=>$trips];
    } catch (Throwable $e) {
        // A home de notícias não deve cair se o banco do módulo de ônibus estiver indisponível.
        return ['origin'=>$origin, 'status'=>'unavailable', 'trips'=>[]];
    }
}
