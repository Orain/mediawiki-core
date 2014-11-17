<?php
/**
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
* http://www.gnu.org/copyleft/gpl.html
*
* @file
* @ingroup Maintenance
* @ingroup Orain
* @author Southparkfan
* @version 1.0
*/

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
    $IP = __DIR__ . '/../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class CheckWikiActivity extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->addOption( 'days', 'Max amount of inactivity in days allowed to not be marked as an inactive wiki. Default is 30.', false, true );
        $this->addOption( 'display-active', 'Outputs a message if the wiki meets the activity requirements. By default set to false.', false, false );
        $this->addOption( 'timezone', 'Timezone, default is UTC. Example of allowed values are "UTC", "Europe/Amsterdam", etc.', false, true );
        $this->mDescription = 'Checks the amount of RecentChanges entries on wikis to determine inactivity';
    }

    public function execute() {
        global $wgDBname;

        if ( $this->hasOption( 'days' ) ) {
            $inactivitydays = $this->getOption( 'days' );
        } else {
            $inactivitydays = 30;
        }

        if ( $this->hasOption( 'timezone' ) ) {
            $timezone = $this->getOption( 'timezone' );
        } else {
            $timezone = 'UTC';
        }

        # Generates a timestamp in yearmonthdayhourminutesecond format of the current time

        date_default_timezone_set( $timezone );
        $date = date( "YmdHis", strtotime( "-$inactivitydays days" ) );

        $dbw = wfGetDB( DB_MASTER );

        $res = $dbw->select(
            'recentchanges',
            'rc_timestamp',
            'rc_timestamp >= ' . $dbw->addQuotes( $date ),
            __METHOD__,
            array(
                'ORDER BY' => 'rc_timestamp DESC',
                'LIMIT' => 1,
            )
        );

        if ( $res->numRows() > 0 ) {
            if ( $this->hasOption( 'display-active' ) ) {
                foreach ( $res as $row ) {
                        $this->output( "The wiki \"$wgDBname\" does NOT meet the inactivity requirements, date of last RecentChanges log entry: " . wfTimestamp( TS_DB, $row->rc_timestamp ) . "\n" );
                }
            }
        } else {
                $res = $dbw->select(
                        'recentchanges',
                        'rc_timestamp',
                        'rc_timestamp < ' . $dbw->addQuotes( $date ),
                        __METHOD__,
                        array(
                                'ORDER BY' => 'rc_timestamp DESC',
                                'LIMIT' => 1,
                        )
                );

                foreach ( $res as $row ) {
                        $this->output( "The wiki \"$wgDBname\" DOES meet the inactivity requirements, date of last RecentChanges log entry: " . wfTimeStamp( TS_DB, $row->rc_timestamp ) . "\n" );
                }
        }

    }
}

$maintClass = 'CheckWikiActivity';
require_once RUN_MAINTENANCE_IF_MAIN;
