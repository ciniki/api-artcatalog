<?php
//
// Description
// ===========
// This function will update the web settings controlling the split page gallery.
// The artcatalog is searched for web visible items in different types.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the request is for.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_artcatalog_updateWebSettings($ciniki, $tnid) {
    //
    // Get the current settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid,
        'ciniki.web', 'settings', 'page-gallery-artcatalog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];
    
    //
    // Find the distinct types that are visible online
    //
    $strsql = "SELECT DISTINCT type "
        . "FROM ciniki_artcatalog "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (webflags&0x01) = 1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.artcatalog', 'types', 'type');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $types = $rc['types'];

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artcatalog', 'private', 'maps');
    $rc = ciniki_artcatalog_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

//  $maps = array(
//      '1'=>'paintings',
//      '2'=>'photographs',
//      '3'=>'jewelry',
//      '4'=>'sculptures',
//      '5'=>'fibrearts',
//      '6'=>'clothing',
//      '8'=>'pottery',
//      );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    foreach($maps['item']['typepermalinks'] as $type => $name) {
        if( $type == '' || $type == '0' ) {
            continue;
        }
        $field = "page-gallery-artcatalog-$name";
        //
        // Turn on the flag when type exists
        //
        if( isset($types[$type]) ) { //&& (!isset($settings[$field]) || $settings[$field] != 'yes') ) {
            $strsql = "INSERT INTO ciniki_web_settings (tnid, detail_key, detail_value, "
                . "date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $tnid) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $field) . "' "
                . ", 'yes' "
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = 'yes' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.web');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.web', 'ciniki_web_history', $tnid, 
                2, 'ciniki_web_settings', $field, 'detail_value', 'yes');
            $ciniki['syncqueue'][] = array('push'=>'ciniki.web.setting',
                'args'=>array('id'=>$field));
        }
        //
        // Turn off when type doesn't exist
        //
        else { //if( !isset($types[$type]) || (isset($settings[$field]) && $settings[$field] == 'no') ) {
            $strsql = "INSERT INTO ciniki_web_settings (tnid, detail_key, detail_value, "
                . "date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $tnid) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $field) . "' "
                . ", 'no' "
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = 'no' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.web');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.web', 'ciniki_web_history', $tnid, 
                2, 'ciniki_web_settings', $field, 'detail_value', 'no');
            $ciniki['syncqueue'][] = array('push'=>'ciniki.web.setting',
                'args'=>array('id'=>$field));
        }
    }

    return array('stat'=>'ok');
}
?>
