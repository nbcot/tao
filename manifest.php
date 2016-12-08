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
 * Copyright (c) 2016 (original work) NBCOT;
 *
 *
 */

use oat\nbcot\scripts\install\EnableInlineFeedback;
use oat\nbcot\scripts\install\SetNewTestRunner;
use oat\nbcot\scripts\install\SetNewTestRunnerConfig;
use oat\nbcot\scripts\install\RegisterRequiredProperties;
use oat\nbcot\scripts\update\Updater;

return array(
    'name' => 'NBCOT',
    'label' => 'NBCOT',
    'description' => '',
    'license' => 'GPL-2.0',
    'version' => '0.3.1',
    'author' => 'NBCOT',
    'requires' => array(
        'tao' => '>=7.18.4'
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#NBCOTManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#NBCOTManager', array('ext'=>'NBCOT')),
    ),
    'install' => array(
        'php' => array(
            __DIR__ . '/scripts/install/setThemeConfig.php',
            SetNewTestRunner::class,
            SetNewTestRunnerConfig::class,
            EnableInlineFeedback::class,
            RegisterRequiredProperties::class
        )
    ),
    'uninstall' => array(
    ),
    'update' => Updater::class,
    'routes' => array(
        '/NBCOT' => 'oat\\nbcot\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => dirname(__FILE__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'NBCOT/',

        #BASE WWW required by JS
        'BASE_WWW' => ROOT_URL.'NBCOT/views/'
    ),
    'extra' => array(
        'structures' => dirname(__FILE__).DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    )
);