<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_artcatalog_hooks_uiSettings($ciniki, $business_id, $args) {

    $settings = array();

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_artcatalog_settings', 'business_id', 
        $business_id, 'ciniki.artcatalog', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    return array('stat'=>'ok', 'settings'=>$settings);  
}
?>
