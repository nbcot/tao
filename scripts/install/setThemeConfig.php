<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */


// Platform themes
use oat\nbcot\model\theme\NbcotDefaultTheme;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\theme\ThemeService;
use oat\tao\model\ThemeNotFoundException;
use oat\tao\model\ThemeRegistry;



// Item themes
// unregister old themes
$themesToUnregister = [
    'tao'           // tao default theme
];
foreach ($themesToUnregister as $theme) {
    try{
        ThemeRegistry::getRegistry()->unregisterTheme($theme);
    } catch (ThemeNotFoundException $e){
        \common_Logger::d('theme ' . $theme . ' is not registered, cannot unregister');
    }
}

// register new themes
$itemsThemes = [
    'NbcotItemTheme' => 'NBCOT Item Theme'
];
foreach ($itemsThemes as $themeId => $themeName) {
    // this is useful when running this scripts multiple times
    try{
        ThemeRegistry::getRegistry()->unregisterTheme($themeId);
    } catch (ThemeNotFoundException $e){
        \common_Logger::d('theme ' . $themeId . ' is not registered, cannot unregister');
    }

    ThemeRegistry::getRegistry()->registerTheme(
        $themeId,
        $themeName,
        implode(DIRECTORY_SEPARATOR,
            array('NBCOT', 'views', 'css', 'themes', 'items', $themeId, 'theme.css')),
        array('items')
    );
}

$defaultTheme = current(array_keys($itemsThemes));
ThemeRegistry::getRegistry()->setDefaultTheme('items', $defaultTheme);

// Platform themes
$serviceManager = ServiceManager::getServiceManager();
$themeService = $serviceManager->get(ThemeService::SERVICE_ID);

$themeService->setTheme(new NbcotDefaultTheme());
$serviceManager->register(ThemeService::SERVICE_ID, $themeService);
