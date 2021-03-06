<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.artcatalog module and 
// add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artcatalog_dbIntegrityCheck(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artcatalog', 'private', 'checkAccess');
    $rc = ciniki_artcatalog_checkAccess($ciniki, $args['tnid'], 'ciniki.artcatalog.dbIntegrityCheck', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefFix');

    if( $args['fix'] == 'yes' ) {
        //
        // Load objects file
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artcatalog', 'private', 'objects');
        $rc = ciniki_artcatalog_objects($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $objects = $rc['objects'];

        //
        // Check any references for the objects
        //
        foreach($objects as $o => $obj) {
            $rc = ciniki_core_objectRefFix($ciniki, $args['tnid'], 'ciniki.artcatalog.'.$o, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Update the history for ciniki_artcatalog
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.artcatalog', $args['tnid'],
            'ciniki_artcatalog', 'ciniki_artcatalog_history', 
            array('uuid', 'name', 'permalink', 'type', 'flags', 'webflags', 'image_id', 
                'catalog_number', 'category', 'year', 'media', 'size', 'framed_size', 'price', 
                'location', 'awards', 'notes', 'description', 'inspiration', 'user_id'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the history for ciniki_artcatalog_tags
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.artcatalog', $args['tnid'],
            'ciniki_artcatalog_tags', 'ciniki_artcatalog_history', 
            array('uuid', 'artcatalog_id', 'tag_type', 'tag_name'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the history for ciniki_artcatalog_tracking
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.artcatalog', $args['tnid'],
            'ciniki_artcatalog_tracking', 'ciniki_artcatalog_history', 
            array('uuid', 'artcatalog_id', 'name', 'external_number', 'start_date', 'end_date', 'notes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check for items missing a UUID
        //
        $strsql = "UPDATE ciniki_artcatalog_history SET uuid = UUID() WHERE uuid = ''";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.artcatalog');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Remove any entries with blank table_key, they are useless 
        // we don't know what they were attached to
        //
        $strsql = "DELETE FROM ciniki_artcatalog_history WHERE table_key = ''";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.artcatalog');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
