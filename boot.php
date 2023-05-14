<?php

use AndiLeni\Statistics\rex_dashboard_browser;
use AndiLeni\Statistics\rex_dashboard_browsertype;
use AndiLeni\Statistics\rex_dashboard_hour;
use AndiLeni\Statistics\rex_dashboard_os;
use AndiLeni\Statistics\rex_dashboard_views_total;
use AndiLeni\Statistics\stats_media_request;
use AndiLeni\Statistics\stats_visit;
use AndiLeni\Statistics\stats_weekday_dashboard;
use AndiLeni\Statistics\rex_effect_stats_mm;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Vectorface\Whip\Whip;



// dashboard addon integration
if (rex::isBackend() && rex_addon::get('dashboard')->isAvailable()) {
    rex_dashboard::addItem(
        rex_dashboard_views_total::factory('stats_views_total', 'Statistik | Seitenaufrufe')
    );
    rex_dashboard::addItem(
        rex_dashboard_browser::factory('stats_browser', 'Statistik | Browser')->setDonut()
    );
    rex_dashboard::addItem(
        rex_dashboard_browsertype::factory('stats_browsertype', 'Statistik | Gerätetypen')->setDonut()
    );
    rex_dashboard::addItem(
        rex_dashboard_os::factory('stats_os', 'Statistik | Betriebssysteme')->setDonut()
    );
    rex_dashboard::addItem(
        rex_dashboard_hour::factory('stats_hour', 'Statistik | Seitenaufrufe: Uhrzeiten')
    );
    rex_dashboard::addItem(
        stats_weekday_dashboard::factory('stats_weekday', 'Statistik | Seitenaufrufe: Wochentage')
    );
}


if (rex::isBackend()) {
    $addon = rex_addon::get('statistics');


    // permissions
    rex_perm::register('statistics[]', null);
    rex_perm::register('statistics[settings]', null, rex_perm::OPTIONS);


    rex_view::addCssFile($addon->getAssetsUrl('datatables.min.css'));
    rex_view::addCssFile($addon->getAssetsUrl('statistics.css'));

    rex_view::addJsFile($addon->getAssetsUrl('echarts.min.js'));
    rex_view::addJsFile($addon->getAssetsUrl('dark.js'));
    rex_view::addJsFile($addon->getAssetsUrl('shine.js'));
    rex_view::addJsFile($addon->getAssetsUrl('datatables.min.js'));

    rex_view::addJsFile($addon->getAssetsUrl('statistics.js'));

    $pagination_scroll = $addon->getConfig('statistics_scroll_pagination');
    if ($pagination_scroll == 'panel') {
        rex_view::addJsFile($addon->getAssetsUrl('statistics_scroll_container.js'));
    } elseif ($pagination_scroll == 'table') {
        rex_view::addJsFile($addon->getAssetsUrl('statistics_scroll_table.js'));
    }
}


// set variable to check in EP whether the visit is coming from a logged-in user or not
if (rex::isFrontend()) {
    $addon = rex_addon::get('statistics');
    $ignore_backend_loggedin = $addon->getConfig('statistics_ignore_backend_loggedin');

    if ($ignore_backend_loggedin) {
        $statistics_has_backend_login = rex_backend_login::hasSession();
    } else {
        $statistics_has_backend_login = false;
    }
} else {
    $statistics_has_backend_login = true;
}



// NOTICE: EP 'RESPONSE_SHUTDOWN' is not called on madia request
// do actions after content is delivered
rex_extension::register('RESPONSE_SHUTDOWN', function () use ($statistics_has_backend_login) {

    if (rex::isFrontend()) {

        $addon = rex_addon::get('statistics');
        $log_all = $addon->getConfig('statistics_log_all');
        $ignore_backend_loggedin = $addon->getConfig('statistics_ignore_backend_loggedin');


        // return when visit is coming from a logged-in user
        if ($ignore_backend_loggedin && $statistics_has_backend_login) {
            return;
        }


        $response_code = rex_response::getStatus();


        // check responsecode and if non-200 requests should be logged
        if ($response_code == '200 OK' || $log_all) {


            // get ip from visitor, set to 0.0.0.0 when ip can not be determined
            $whip = new Whip();
            $clientAddress = $whip->getValidIpAddress();
            $clientAddress = $clientAddress ? $clientAddress : '0.0.0.0';

            // domain
            try {
                $domain = rex::getRequest()->getHost();
            } catch (SuspiciousOperationException $e) {
                $domain = 'undefined';
            }

            // page url
            $url = $domain . rex::getRequest()->getRequestUri();

            // optionally ignore url parameters
            if ($addon->getConfig('statistics_ignore_url_params')) {
                $url = stats_visit::remove_url_parameters($url);
            }

            // user agent
            $userAgent = rex_server('HTTP_USER_AGENT', 'string', '');

            $visit = new stats_visit($clientAddress, $url, $userAgent, $domain);


            // Track only frontend requests if page url should not be ignored
            // ignore requests with empty user agent
            if (!rex::isBackend() && $userAgent != '' && !$visit->ignore_visit()) {

                // visit is not a media request, hence either bot or human visitor

                // parse useragent
                $visit->parse_ua();

                if ($visit->is_bot()) {

                    // visitor is a bot
                    $visit->save_bot();
                } else {

                    if ($visit->save_visit()) {

                        // visitor is human
                        // check hash with save_visit, if true then save visit

                        // check if referer exists, if yes safe it
                        $referer = rex_server('HTTP_REFERER', 'string', '');
                        if ($referer != '') {
                            $referer = urldecode($referer);

                            if (!str_starts_with($referer, rex::getServer())) {
                                $visit->save_referer($referer);
                            }
                        }


                        // check if unique visitor
                        if ($visit->save_visitor()) {

                            // save visitor
                            $visit->persist_visitor();
                        }


                        $visit->persist();
                    }
                }
            }
        }
    }
});


// media
if (rex::isBackend()) {

    if (rex_addon::get('media_manager')->isAvailable()) {
        rex_media_manager::addEffect(rex_effect_stats_mm::class);
    }
} else {

    rex_extension::register('MEDIA_MANAGER_AFTER_SEND', function () {
        $addon = rex_addon::get('statistics');

        if ($addon->getConfig('statistics_media_log_all') == true) {

            $url = rex_server('REQUEST_URI', 'string', '');

            $media_request = new stats_media_request($url);

            if ($media_request->is_media()) {

                $media_request->save_media();
            }
        }
    });
}
